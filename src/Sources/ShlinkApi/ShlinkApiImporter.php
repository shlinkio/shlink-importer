<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

use DateTimeImmutable;
use Generator;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Strategy\AbstractApiImporterStrategy;
use Throwable;

use function Functional\map;
use function http_build_query;
use function sprintf;

class ShlinkApiImporter extends AbstractApiImporterStrategy
{
    /**
     * @return ImportedShlinkUrl[]
     * @throws ImportException
     */
    public function import(array $rawParams): iterable
    {
        try {
            return $this->loadUrls(ShlinkApiParams::fromRawParams($rawParams));
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
        $url = sprintf('%s/rest/v1/short-urls?%s', $params->baseUrl(), $queryString);

        $parsedBody = $this->callApi($url, ['X-Api-Key' => $params->apiKey(), 'Accept' => 'application/json']);
        yield from $this->mapUrls($parsedBody['shortUrls']['data'] ?? []);

        $currentPage = $parsedBody['shortUrls']['pagination']['currentPage'] ?? 0;
        $pagesCount = $parsedBody['shortUrls']['pagination']['pagesCount'] ?? 0;

        if ($currentPage < $pagesCount) {
            yield from $this->loadUrls($params, $page + 1);
        }
    }

    private function mapUrls(array $urls): array
    {
        return map($urls, fn (array $url): ImportedShlinkUrl => new ImportedShlinkUrl(
            ImportSources::SHLINK,
            $url['longUrl'] ?? '',
            $url['tags'] ?? [],
            isset($url['dateCreated'])
                ? DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $url['dateCreated'])
                : null,
            $url['domain'] ?? null,
            $url['shortCode'],
            $url['title'] ?? null,
        ));
    }
}
