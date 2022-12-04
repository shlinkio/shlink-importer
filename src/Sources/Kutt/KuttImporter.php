<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Kutt;

use DateTimeImmutable;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrlMeta;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Throwable;

use function Functional\map;
use function http_build_query;
use function sprintf;

class KuttImporter implements ImporterStrategyInterface
{
    private const SHORT_URLS_PER_PAGE = 50;

    public function __construct(private readonly RestApiConsumerInterface $apiConsumer)
    {
    }

    /**
     * @throws ImportException
     */
    public function import(ImportParams $importParams): ImportResult
    {
        $params = KuttParams::fromImportParams($importParams);
        return ImportResult::withShortUrls($this->importShortUrls($params));
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     * @throws ImportException
     */
    private function importShortUrls(KuttParams $params): iterable
    {
        try {
            yield from $this->loadUrls($params);
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     */
    public function loadUrls(KuttParams $params, int $skip = 0): iterable
    {
        $queryString = http_build_query([
            'limit' => self::SHORT_URLS_PER_PAGE,
            'skip' => $skip,
            'all' => $params->importAllUrls ? 'true' : 'false',
        ]);
        ['data' => $urls, 'total' => $total] = $this->apiConsumer->callApi(
            sprintf('%s/api/v2/links?%s', $params->baseUrl, $queryString),
            [
                'X-Api-Key' => $params->apiKey,
                'Accept' => 'application/json',
            ],
        );

        yield from $this->mapUrls($urls);

        $nextSkip = $skip + self::SHORT_URLS_PER_PAGE;
        if ($total > $nextSkip) {
            yield from $this->loadUrls($params, $nextSkip);
        }
    }

    /**
     * @return ImportedShlinkUrl[]
     */
    private function mapUrls(array $urls): array
    {
        return map($urls, function (array $url): ImportedShlinkUrl {
            $visitsCount = $url['visit_count'];

            return new ImportedShlinkUrl(
                ImportSource::KUTT,
                $url['target'],
                [],
                new DateTimeImmutable($url['created_at']),
                $url['domain'] ?? null,
                $url['address'],
                $url['description'] ?? null,
                [], // TODO
                $visitsCount,
                new ImportedShlinkUrlMeta(
                    null,
                    isset($url['expire_in']) ? new DateTimeImmutable($url['expire_in']) : null,
                    null,
                ),
            );
        });
    }
}
