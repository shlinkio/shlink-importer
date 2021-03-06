<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

use DateTimeImmutable;
use Generator;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrlMeta;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Util\DateHelpersTrait;
use Throwable;

use function array_reverse;
use function ceil;
use function Functional\map;
use function http_build_query;
use function sprintf;

class ShlinkApiImporter implements ImporterStrategyInterface
{
    use DateHelpersTrait;

    private const SHORT_URLS_PER_PAGE = 50;
    private const VISITS_PER_PAGE = 300;

    private DateTimeImmutable $importStartTime;
    private RestApiConsumerInterface $apiConsumer;

    public function __construct(RestApiConsumerInterface $apiConsumer)
    {
        $this->apiConsumer = $apiConsumer;
    }

    /**
     * @return ImportedShlinkUrl[]
     * @throws ImportException
     */
    public function import(array $rawParams): iterable
    {
        $this->importStartTime = new DateTimeImmutable();

        try {
            yield from $this->loadUrls(ShlinkApiParams::fromRawParams($rawParams));
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    private function loadUrls(ShlinkApiParams $params, int $page = 1): Generator
    {
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => self::SHORT_URLS_PER_PAGE]);
        $url = sprintf('%s/rest/v2/short-urls?%s', $params->baseUrl(), $queryString);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey(), 'Accept' => 'application/json'],
        );

        yield from $this->mapUrls($parsedBody['shortUrls']['data'] ?? [], $params);

        if ($this->shouldContinue($parsedBody['shortUrls']['pagination'] ?? [])) {
            yield from $this->loadUrls($params, $page + 1);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    private function mapUrls(array $urls, ShlinkApiParams $params): array
    {
        return map($urls, function (array $url) use ($params): ImportedShlinkUrl {
            $shortCode = $url['shortCode'];
            $domain = $url['domain'] ?? null;
            $visitsCount = $url['visitsCount'];

            // Shlink returns visits ordered from newer to older. To keep stats working once imported, we need to
            // reverse them.
            // In order to do that, we calculate the amount of pages we will get, and start from last to first.
            // Then, each page's result set gets reversed individually.
            $expectedPages = (int) ceil($visitsCount / self::VISITS_PER_PAGE);

            $meta = new ImportedShlinkUrlMeta(
                $this->nullableDateFromAtom($url['meta']['validSince'] ?? null),
                $this->nullableDateFromAtom($url['meta']['validUntil'] ?? null),
                $url['meta']['maxVisits'] ?? null,
            );

            return new ImportedShlinkUrl(
                ImportSources::SHLINK,
                $url['longUrl'] ?? '',
                $url['tags'] ?? [],
                $this->nullableDateFromAtom($url['dateCreated'] ?? null) ?? $this->importStartTime,
                $domain,
                $shortCode,
                $url['title'] ?? null,
                $params->importVisits() ? $this->loadVisits($shortCode, $domain, $params, $expectedPages) : [],
                $visitsCount,
                $meta,
            );
        });
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    private function loadVisits(string $shortCode, ?string $domain, ShlinkApiParams $params, int $page): Generator
    {
        $queryString = http_build_query(
            ['page' => $page, 'itemsPerPage' => self::VISITS_PER_PAGE, 'domain' => $domain],
        );
        $url = sprintf('%s/rest/v2/short-urls/%s/visits?%s', $params->baseUrl(), $shortCode, $queryString);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey(), 'Accept' => 'application/json'],
        );

        yield from array_reverse($this->mapVisits($parsedBody['visits']['data'] ?? []));

        if ($page > 1) {
            yield from $this->loadVisits($shortCode, $domain, $params, $page - 1);
        }
    }

    private function mapVisits(array $visits): array
    {
        return map($visits, function (array $visit): ImportedShlinkVisit {
            $location = ! isset($visit['visitLocation']) ? null : new ImportedShlinkVisitLocation(
                $visit['visitLocation']['countryCode'] ?? '',
                $visit['visitLocation']['countryName'] ?? '',
                $visit['visitLocation']['regionName'] ?? '',
                $visit['visitLocation']['cityName'] ?? '',
                $visit['visitLocation']['timezone'] ?? '',
                $visit['visitLocation']['latitude'] ?? 0.0,
                $visit['visitLocation']['longitude'] ?? 0.0,
            );

            return new ImportedShlinkVisit(
                $visit['referer'] ?? '',
                $visit['userAgent'] ?? '',
                $this->nullableDateFromAtom($visit['date'] ?? null) ?? $this->importStartTime,
                $location,
            );
        });
    }

    private function shouldContinue(array $pagination): bool
    {
        $currentPage = $pagination['currentPage'];
        $pagesCount = $pagination['pagesCount'];

        return $currentPage < $pagesCount;
    }
}
