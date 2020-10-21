<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Shlinkio\Shlink\Importer\Exception\BitlyApiV4Exception;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ShlinkUrl;
use Shlinkio\Shlink\Importer\Params\BitlyApiV4Params;
use Throwable;

use function base64_encode;
use function Functional\filter;
use function Functional\map;
use function json_decode;
use function ltrim;
use function parse_url;
use function sprintf;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

class BitlyApiV4Importer implements ImporterStrategyInterface
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;

    private ?string $lastProcessedUrlDate = null;
    private ?string $lastProcessedGroup = null;

    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return ShlinkUrl[]
     * @throws ImportException
     */
    public function import(array $rawParams): iterable
    {
        $params = BitlyApiV4Params::fromRawParams($rawParams);
        $startDate = new DateTimeImmutable();

        try {
            ['groups' => $groups] = $this->callToBitlyApi('/groups', $params);

            foreach ($groups as ['guid' => $groupId]) {
                yield from $this->loadUrlsForGroup($groupId, $params, $startDate);
            }
        } catch (ImportException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    /**
     * @return ShlinkUrl[]
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function loadUrlsForGroup(string $groupId, BitlyApiV4Params $params, DateTimeInterface $startDate): iterable
    {
        $pagination = [];
        $archived = $params->ignoreArchived() ? 'off' : 'both';

        do {
            $url = $pagination['next'] ?? sprintf('/groups/%s/bitlinks?archived=%s', $groupId, $archived);
            ['links' => $links, 'pagination' => $pagination] = $this->callToBitlyApi($url, $params);
            $this->lastProcessedGroup = $groupId;

            $filteredLinks = filter(
                $links,
                static fn (array $link): bool => isset($link['long_url']) && ! empty($link['long_url']),
            );

            yield from map($filteredLinks, function (array $link) use ($params, $startDate): ShlinkUrl {
                $this->lastProcessedUrlDate = $link['created_at'] ?? $this->lastProcessedUrlDate;
                $date = isset($link['created_at']) && $params->keepCreationDate()
                    ? $this->dateFromAtom($link['created_at'])
                    : clone $startDate;
                $parsedLink = parse_url($link['link'] ?? '');
                $host = $parsedLink['host'] ?? null;
                $domain = $host !== 'bit.ly' && $params->importCustomDomains() ? $host : null;
                $shortCode = ltrim($parsedLink['path'] ?? '', '/');
                $tags = $params->importTags() ? $link['tags'] ?? [] : [];

                return new ShlinkUrl($link['long_url'], $tags, $date, $domain, $shortCode);
            });
        } while (! empty($pagination['next']));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function callToBitlyApi(string $url, BitlyApiV4Params $params): array
    {
        $url = str_starts_with($url, 'http') ? $url : sprintf('https://api-ssl.bitly.com/v4%s', $url);
        $request = $this->requestFactory->createRequest('GET', $url)->withHeader(
            'Authorization',
            sprintf('Bearer %s', $params->accessToken()),
        );
        $resp = $this->httpClient->sendRequest($request);
        $body = (string) $resp->getBody();
        $statusCode = $resp->getStatusCode();

        if ($statusCode >= 400) {
            throw BitlyApiV4Exception::fromInvalidRequest(
                $url,
                $statusCode,
                $body,
                $this->generateContinueToken() ?? $params->continueToken(),
            );
        }

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private function generateContinueToken(): ?string
    {
        if ($this->lastProcessedGroup === null || $this->lastProcessedUrlDate === null) {
            return $this->lastProcessedGroup;
        }

        // Generate the timestamp corresponding to 1 second before the last processed URL
        $createdBefore = $this->dateFromAtom($this->lastProcessedUrlDate)->sub(new DateInterval('PT1S'))->format('U');

        return base64_encode(sprintf('%s__%s', $this->lastProcessedGroup, $createdBefore));
    }

    private function dateFromAtom(string $atomDate): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $atomDate);
    }
}
