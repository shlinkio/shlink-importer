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
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\Importer\Model\ImportedShortUrlMeta;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Util\DateHelpersTrait;
use Throwable;

use function Functional\map;
use function http_build_query;
use function sprintf;

class ShlinkApiImporter implements ImporterStrategyInterface
{
    use DateHelpersTrait;

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
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => 50]);
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
     * @throws \Shlinkio\Shlink\Importer\Http\InvalidRequestException
     */
    private function mapUrls(array $urls, ShlinkApiParams $params): array
    {
        return map($urls, function (array $url) use ($params): ImportedShlinkUrl {
            $shortCode = $url['shortCode'];
            $domain = $url['domain'] ?? null;
            $meta = new ImportedShortUrlMeta(
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
                $this->loadVisits($shortCode, $domain, $params),
                $url['visitsCount'] ?? null,
                $meta,
            );
        });
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws \Shlinkio\Shlink\Importer\Http\InvalidRequestException
     */
    private function loadVisits(string $shortCode, ?string $domain, ShlinkApiParams $params, int $page = 1): Generator
    {
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => 1000, 'domain' => $domain]);
        $url = sprintf('%s/rest/v2/short-urls/%s/visits?%s', $params->baseUrl(), $shortCode, $queryString);
        $parsedBody = $this->apiConsumer->callApi(
            $url,
            ['X-Api-Key' => $params->apiKey(), 'Accept' => 'application/json'],
        );

        yield from $this->mapVisits($parsedBody['visits']['data'] ?? []);

        if ($this->shouldContinue($parsedBody['visits']['pagination'] ?? [])) {
            yield from $this->loadVisits($shortCode, $domain, $params, $page + 1);
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
