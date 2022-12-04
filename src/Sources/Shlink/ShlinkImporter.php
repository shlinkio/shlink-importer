<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use DateTimeImmutable;
use Generator;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Throwable;

use function array_reverse;
use function ceil;
use function Functional\map;
use function http_build_query;
use function sprintf;

class ShlinkImporter implements ImporterStrategyInterface
{
    private const SHORT_URLS_PER_PAGE = 50;
    private const VISITS_PER_PAGE = 300;

    private DateTimeImmutable $importStartTime;

    public function __construct(
        private readonly RestApiConsumerInterface $apiConsumer,
        private readonly ShlinkMapperInterface $mapper,
    ) {
    }

    /**
     * @throws ImportException
     */
    public function import(ImportParams $importParams): ImportResult
    {
        $this->importStartTime = new DateTimeImmutable();
        $params = ShlinkParams::fromImportParams($importParams);

        return ImportResult::withShortUrlsAndOrphanVisits(
            $this->importShortUrls($params),
            $this->importOrphanVisits($params),
        );
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     * @throws ImportException
     */
    private function importShortUrls(ShlinkParams $params): iterable
    {
        try {
            yield from $this->loadUrls($params);
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    private function loadUrls(ShlinkParams $params, int $page = 1): Generator
    {
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => self::SHORT_URLS_PER_PAGE]);
        $url = sprintf('%s/rest/v2/short-urls?%s', $params->baseUrl, $queryString);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey, 'Accept' => 'application/json'],
        );

        yield from $this->mapUrls($parsedBody['shortUrls']['data'] ?? [], $params);

        if ($this->shouldContinue($parsedBody['shortUrls']['pagination'] ?? [])) {
            yield from $this->loadUrls($params, $page + 1);
        }
    }

    private function shouldContinue(array $pagination): bool
    {
        $currentPage = $pagination['currentPage'];
        $pagesCount = $pagination['pagesCount'];

        return $currentPage < $pagesCount;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     * @return ImportedShlinkUrl[]
     */
    private function mapUrls(array $urls, ShlinkParams $params): array
    {
        return map($urls, function (array $url) use ($params): ImportedShlinkUrl {
            // Shlink returns visits ordered from newer to older. To keep stats working once imported, we need to
            // reverse them.
            // In order to do that, we calculate the amount of pages we will get, and start from last to first.
            // Then, each page's result set gets reversed individually.
            $visitsCount = $url['visitsCount'];
            $expectedPages = (int) ceil($visitsCount / self::VISITS_PER_PAGE);

            return $this->mapper->mapShortUrl(
                $url,
                $params->importVisits && $expectedPages > 0
                    ? $this->loadVisits($url['shortCode'], $url['domain'] ?? null, $params, $expectedPages)
                    : [],
                $this->importStartTime,
            );
        });
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    private function loadVisits(string $shortCode, ?string $domain, ShlinkParams $params, int $page): Generator
    {
        $queryString = http_build_query(
            ['page' => $page, 'itemsPerPage' => self::VISITS_PER_PAGE, 'domain' => $domain],
        );
        $url = sprintf('%s/rest/v2/short-urls/%s/visits?%s', $params->baseUrl, $shortCode, $queryString);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey, 'Accept' => 'application/json'],
        );

        yield from array_reverse(map(
            $parsedBody['visits']['data'] ?? [],
            fn (array $visit) => $this->mapper->mapVisit($visit, $this->importStartTime),
        ));

        if ($page > 1) {
            yield from $this->loadVisits($shortCode, $domain, $params, $page - 1);
        }
    }

    /**
     * @return iterable<ImportedShlinkOrphanVisit>
     * @throws ImportException
     */
    private function importOrphanVisits(ShlinkParams $params): iterable
    {
        if (! $params->importOrphanVisits) {
            return [];
        }

        try {
            // Shlink returns visits ordered from newer to older. To keep stats working once imported, we need to
            // reverse them.
            // In order to do that, we calculate the amount of pages we will get, and start from last to first.
            // Then, each page's result set gets reversed individually.
            $visitsCount = $this->getOrphanVisitsCount($params);
            $expectedPages = (int) ceil($visitsCount / self::VISITS_PER_PAGE);

            yield from $this->loadOrphanVisits($params, $expectedPages);
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    private function getOrphanVisitsCount(ShlinkParams $params): int
    {
        $url = sprintf('%s/rest/v2/visits', $params->baseUrl);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey, 'Accept' => 'application/json'],
        );

        return (int) ($parsedBody['visits']['orphanVisitsCount'] ?? 0);
    }

    private function loadOrphanVisits(ShlinkParams $params, int $page): iterable
    {
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => self::VISITS_PER_PAGE]);
        $url = sprintf('%s/rest/v2/visits/orphan?%s', $params->baseUrl, $queryString);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey, 'Accept' => 'application/json'],
        );

        yield from array_reverse(map(
            $parsedBody['visits']['data'] ?? [],
            fn (array $visit) => $this->mapper->mapOrphanVisit($visit, $this->importStartTime),
        ));

        if ($page > 1) {
            yield from $this->loadOrphanVisits($params, $page - 1);
        }
    }
}
