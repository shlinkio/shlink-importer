<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

use DateTimeImmutable;
use Generator;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Strategy\AbstractApiImporterStrategy;
use Throwable;

use function Functional\map;
use function http_build_query;
use function sprintf;

class ShlinkApiImporter extends AbstractApiImporterStrategy
{
    private DateTimeImmutable $importStartTime;

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
     */
    private function loadUrls(ShlinkApiParams $params, int $page = 1): Generator
    {
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => 50]);
        $url = sprintf('%s/rest/v2/short-urls?%s', $params->baseUrl(), $queryString);
        $parsedBody = $this->callApi($url, ['X-Api-Key' => $params->apiKey(), 'Accept' => 'application/json']);

        yield from $this->mapUrls($parsedBody['shortUrls']['data'] ?? [], $params);

        if ($this->shouldContinue($parsedBody['shortUrls']['pagination'] ?? [])) {
            yield from $this->loadUrls($params, $page + 1);
        }
    }

    private function mapUrls(array $urls, ShlinkApiParams $params): array
    {
        return map($urls, function (array $url) use ($params): ImportedShlinkUrl {
            $shortCode = $url['shortCode'];
            $domain = $url['domain'] ?? null;

            return new ImportedShlinkUrl(
                ImportSources::SHLINK,
                $url['longUrl'] ?? '',
                $url['tags'] ?? [],
                isset($url['dateCreated'])
                    ? DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $url['dateCreated'])
                    : $this->importStartTime,
                $domain,
                $shortCode,
                $url['title'] ?? null,
                $this->loadVisits($shortCode, $domain, $params),
                $url['visitsCount'] ?? null,
            );
        });
    }

    private function loadVisits(string $shortCode, ?string $domain, ShlinkApiParams $params, int $page = 1): Generator
    {
        $queryString = http_build_query(['page' => $page, 'itemsPerPage' => 1000, 'domain' => $domain]);
        $url = sprintf('%s/rest/v2/short-urls/%s/visits?%s', $params->baseUrl(), $shortCode, $queryString);
        $parsedBody = $this->callApi($url, ['X-Api-Key' => $params->apiKey(), 'Accept' => 'application/json']);

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
                isset($visit['date'])
                    ? DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $visit['date'])
                    : $this->importStartTime,
                $location,
            );
        });
    }

    private function shouldContinue(array $pagination): bool
    {
        $currentPage = $pagination['currentPage'] ?? 0;
        $pagesCount = $pagination['pagesCount'] ?? 0;

        return $currentPage < $pagesCount;
    }
}
