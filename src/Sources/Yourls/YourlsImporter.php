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
use Shlinkio\Shlink\Importer\Params\CommonParams;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Util\DateHelpersTrait;
use Throwable;

use function Functional\map;
use function http_build_query;
use function sprintf;
use function str_contains;

class YourlsImporter implements ImporterStrategyInterface
{
    use DateHelpersTrait;

    private const LINKS_ACTION = 'shlink-list';
    private const VISITS_ACTION = 'shlink-link-visits';
    private const YOURLS_DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private RestApiConsumerInterface $apiConsumer)
    {
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     * @throws ImportException
     */
    public function import(CommonParams $rawParams): iterable
    {
        try {
            yield from $this->loadUrls(YourlsParams::fromRawParams($rawParams));
        } catch (InvalidRequestException $e) {
            if (str_contains($e->body(), '"message":"Unknown or missing \"action\" parameter"')) {
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
                ImportSources::YOURLS,
                $url['url'] ?? '',
                [],
                $this->dateFromFormat(self::YOURLS_DATE_FORMAT, $url['timestamp'] ?? ''),
                null,
                $shortCode,
                $url['title'] ?? null,
                $params->importVisits() ? $this->loadVisits($shortCode, $params) : [],
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
                $this->nullableDateFromFormatWithDefault(self::YOURLS_DATE_FORMAT, $visit['click_time'] ?? null),
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
            'username' => $params->username(),
            'password' => $params->password(),
        ]);
        $resp = $this->apiConsumer->callApi(sprintf('%s/yourls-api.php?%s', $params->baseUrl(), $query));

        return $resp['result'] ?? [];
    }
}
