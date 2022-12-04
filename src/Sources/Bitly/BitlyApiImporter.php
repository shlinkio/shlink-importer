<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Bitly;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Util\DateHelper;
use Throwable;

use function Functional\filter;
use function Functional\map;
use function is_array;
use function ltrim;
use function parse_url;
use function sprintf;
use function str_starts_with;

class BitlyApiImporter implements ImporterStrategyInterface
{
    public function __construct(private readonly RestApiConsumerInterface $apiConsumer)
    {
    }

    /**
     * @throws ImportException
     */
    public function import(ImportParams $importParams): ImportResult
    {
        $params = BitlyApiParams::fromImportParams($importParams);
        return ImportResult::withShortUrls($this->importShortUrls($params));
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     * @throws ImportException
     */
    private function importShortUrls(BitlyApiParams $params): iterable
    {
        $progressTracker = BitlyApiProgressTracker::initFromParams($params);
        $initialGroup = $progressTracker->initialGroup();
        $initialGroupFound = $initialGroup === null;

        try {
            ['groups' => $groups] = $this->callToBitlyApi('/groups', $params, $progressTracker);

            foreach ($groups as ['guid' => $groupId]) {
                // Skip groups until the initial one is found
                $initialGroupFound = $initialGroupFound || $groupId === $initialGroup;
                if (! $initialGroupFound) {
                    continue;
                }

                yield from $this->loadUrlsForGroup($groupId, $params, $progressTracker);
            }
        } catch (ImportException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    /**
     * @return ImportedShlinkUrl[]
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function loadUrlsForGroup(
        string $groupId,
        BitlyApiParams $params,
        BitlyApiProgressTracker $progressTracker,
    ): iterable {
        $pagination = [];
        $archived = $params->ignoreArchived ? 'off' : 'both';
        $createdBefore = $groupId === $progressTracker->initialGroup() ? $progressTracker->createdBefore() : '';

        do {
            $url = $pagination['next'] ?? sprintf(
                '/groups/%s/bitlinks?archived=%s&created_before=%s',
                $groupId,
                $archived,
                $createdBefore,
            );
            ['links' => $links, 'pagination' => $pagination] = $this->callToBitlyApi($url, $params, $progressTracker);
            $progressTracker->updateLastProcessedGroup($groupId);

            $filteredLinks = filter(
                $links,
                static fn (array $link): bool => isset($link['long_url']) && ! empty($link['long_url']),
            );

            yield from map($filteredLinks, function (array $link) use ($params, $progressTracker): ImportedShlinkUrl {
                $hasCreatedDate = isset($link['created_at']);
                if ($hasCreatedDate) {
                    $progressTracker->updateLastProcessedUrlDate($link['created_at']);
                }

                $longUrl = $link['long_url'];
                $date = $hasCreatedDate && $params->keepCreationDate
                    ? DateHelper::dateFromAtom($link['created_at'])
                    : clone $progressTracker->startDate();
                $parsedLink = $this->parseLink($link['link'] ?? '');
                $host = $parsedLink['host'] ?? null;
                $domain = $host !== 'bit.ly' && $params->importCustomDomains ? $host : null;
                $shortCode = ltrim($parsedLink['path'] ?? '', '/');
                $tags = $params->importTags ? $link['tags'] ?? [] : [];
                $title = $link['title'] ?? null;

                return new ImportedShlinkUrl(ImportSource::BITLY, $longUrl, $tags, $date, $domain, $shortCode, $title);
            });
        } while (! empty($pagination['next']));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function callToBitlyApi(
        string $url,
        BitlyApiParams $params,
        BitlyApiProgressTracker $progressTracker,
    ): array {
        $url = str_starts_with($url, 'http') ? $url : sprintf('https://api-ssl.bitly.com/v4%s', $url);

        try {
            return $this->apiConsumer->callApi($url, ['Authorization' => sprintf('Bearer %s', $params->accessToken)]);
        } catch (InvalidRequestException $e) {
            throw BitlyApiException::fromInvalidRequest(
                $e,
                $progressTracker->generateContinueToken() ?? $params->continueToken,
            );
        }
    }

    private function parseLink(string $link): array
    {
        $parsedUrl = parse_url($link);
        return is_array($parsedUrl) ? $parsedUrl : [];
    }
}
