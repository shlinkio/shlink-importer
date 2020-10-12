<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Importer\Strategy;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ShlinkUrl;
use Throwable;

use function Functional\filter;
use function Functional\map;
use function json_decode;
use function ltrim;
use function parse_url;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class BitlyApiV4Importer implements ImporterStrategyInterface
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;

    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return ShlinkUrl[]
     * @throws ImportException
     */
    public function import(array $params): iterable
    {
        ['access_token' => $accessToken] = $params;

        try {
            $groupsResp = $this->httpClient->sendRequest(
                $this->createBitlyRequest('https://api-ssl.bitly.com/v4/groups', $accessToken),
            );
            ['groups' => $groups] = $this->respToJson($groupsResp);

            foreach ($groups as ['guid' => $groupId]) {
                yield from $this->loadUrlsForGroup($groupId, $accessToken);
            }
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    /**
     * @return ShlinkUrl[]
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function loadUrlsForGroup(string $groupId, string $accessToken): iterable
    {
        $pagination = [];

        do {
            $url = $pagination['next'] ?? sprintf('https://api-ssl.bitly.com/v4/groups/%s/bitlinks', $groupId);
            $linksResp = $this->httpClient->sendRequest($this->createBitlyRequest($url, $accessToken));
            ['links' => $links, 'pagination' => $pagination] = $this->respToJson($linksResp);

            $filteredLinks = filter($links, static function (array $link): bool {
                $hasLongUrl = isset($link['long_url']) && ! empty($link['long_url']);
                $isArchived = $link['archived'] ?? false;

                return $hasLongUrl && ! $isArchived;
            });

            yield from map($filteredLinks, static function (array $link) {
                $date = isset($link['created_at'])
                    ? DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $link['created_at'])
                    : new DateTimeImmutable();
                $parsedLink = parse_url($link['link'] ?? '');
                $host = $parsedLink['host'] ?? null;
                $normalizedHost = $host !== 'bit.ly' ? $host : null;
                $shortCode = ltrim($parsedLink['path'] ?? '', '/');

                return new ShlinkUrl($link['long_url'], $link['tags'] ?? [], $date, $normalizedHost, $shortCode);
            });
        } while (! empty($pagination['next']));
    }

    private function createBitlyRequest(string $url, string $accessToken): RequestInterface
    {
        return $this->requestFactory->createRequest('GET', $url)->withHeader(
            'Authorization',
            sprintf('Bearer %s', $accessToken),
        );
    }

    /**
     * @throws JsonException
     */
    private function respToJson(ResponseInterface $resp): array
    {
        return json_decode((string) $resp->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
