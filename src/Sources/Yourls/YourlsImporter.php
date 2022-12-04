<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

use Generator;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Util\DateHelper;
use Throwable;

use function Functional\map;
use function http_build_query;
use function sprintf;

class YourlsImporter implements ImporterStrategyInterface
{
    private const LINKS_ACTION = 'shlink-list';
    private const VISITS_ACTION = 'shlink-link-visits';
    private const YOURLS_DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly RestApiConsumerInterface $apiConsumer)
    {
    }

    /**
     * @throws ImportException
     */
    public function import(ImportParams $importParams): ImportResult
    {
        $params = YourlsParams::fromImportParams($importParams);
        return ImportResult::withShortUrls($this->importShortUrls($params));
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     * @throws ImportException
     */
    private function importShortUrls(YourlsParams $params): iterable
    {
        try {
            yield from $this->loadUrls($params);
        } catch (InvalidRequestException $e) {
            if ($e->isShlinkPluginMissingError()) {
                throw YourlsMissingPluginException::forMissingPlugin($e);
            }

            throw ImportException::fromError($e);
        } catch (Throwable $e) {
            throw ImportException::fromError($e);
        }
    }

    private function loadUrls(YourlsParams $params): Generator
    {
        $result = $this->callYourlsApi(self::LINKS_ACTION, $params);

        yield from map($result, function (array $url) use ($params) {
            $shortCode = $url['keyword'] ?? '';

            return new ImportedShlinkUrl(
                ImportSource::YOURLS,
                $url['url'] ?? '',
                [],
                DateHelper::dateFromFormat(self::YOURLS_DATE_FORMAT, $url['timestamp'] ?? ''),
                $params->domain,
                $shortCode,
                $url['title'] ?? null,
                $params->importVisits ? $this->loadVisits($shortCode, $params) : [],
                (int) ($url['clicks'] ?? 0),
            );
        });
    }

    private function loadVisits(string $shortCode, YourlsParams $params): Generator
    {
        $result = $this->callYourlsApi(self::VISITS_ACTION, $params, $shortCode);

        yield from map($result, function (array $visit) {
            $referer = $visit['referrer'] ?? '';

            return new ImportedShlinkVisit(
                $referer === 'direct' ? '' : $referer,
                $visit['user_agent'] ?? '',
                DateHelper::nullableDateFromFormatWithDefault(self::YOURLS_DATE_FORMAT, $visit['click_time'] ?? null),
                new ImportedShlinkVisitLocation($visit['country_code'], '', '', '', '', 0.0, 0.0),
            );
        });
    }

    private function callYourlsApi(string $action, YourlsParams $params, string $shortCode = ''): array
    {
        $query = http_build_query([
            'format' => 'json',
            'action' => $action,
            'shortCode' => $shortCode,
            'username' => $params->username,
            'password' => $params->password,
        ]);
        $resp = $this->apiConsumer->callApi(sprintf('%s/yourls-api.php?%s', $params->baseUrl, $query));

        return $resp['result'] ?? [];
    }
}
