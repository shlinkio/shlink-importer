<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use DateTimeInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectCondition;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectRule;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrlMeta;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Util\DateHelper;

use function array_map;

final class ShlinkMapper implements ShlinkMapperInterface
{
    /**
     * @inheritDoc
     */
    public function mapShortUrl(
        array $url,
        iterable $visits,
        array $redirectRules,
        DateTimeInterface $fallbackDate,
    ): ImportedShlinkUrl {
        $meta = new ImportedShlinkUrlMeta(
            DateHelper::nullableDateFromAtom($url['meta']['validSince'] ?? null),
            DateHelper::nullableDateFromAtom($url['meta']['validUntil'] ?? null),
            $url['meta']['maxVisits'] ?? null,
        );

        return new ImportedShlinkUrl(
            source: ImportSource::SHLINK,
            longUrl: $url['longUrl'] ?? '',
            tags: $url['tags'] ?? [],
            createdAt: DateHelper::nullableDateFromAtom($url['dateCreated'] ?? null) ?? $fallbackDate,
            domain: $url['domain'] ?? null,
            shortCode: $url['shortCode'],
            title: $url['title'] ?? null,
            visits: $visits,
            visitsCount: $url['visitsCount'] ?? $url['visitsSummary']['total'],
            meta: $meta,
            redirectRules: $redirectRules,
        );
    }

    public function mapVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkVisit
    {
        return new ImportedShlinkVisit(
            referer:$visit['referer'] ?? '',
            userAgent:$visit['userAgent'] ?? '',
            date:DateHelper::nullableDateFromAtom($visit['date'] ?? null) ?? $fallbackDate,
            location: $this->mapVisitLocation($visit['visitLocation'] ?? null),
        );
    }

    public function mapOrphanVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkOrphanVisit
    {
        return new ImportedShlinkOrphanVisit(
            referer: $visit['referer'] ?? '',
            userAgent: $visit['userAgent'] ?? '',
            date: DateHelper::nullableDateFromAtom($visit['date'] ?? null) ?? $fallbackDate,
            visitedUrl: $visit['visitedUrl'] ?? '',
            type: $visit['type'] ?? '',
            location: $this->mapVisitLocation($visit['visitLocation'] ?? null),
        );
    }

    private function mapVisitLocation(array|null $visitLocation): ImportedShlinkVisitLocation|null
    {
        return $visitLocation === null ? null : new ImportedShlinkVisitLocation(
            countryCode: $visitLocation['countryCode'] ?? '',
            countryName: $visitLocation['countryName'] ?? '',
            regionName: $visitLocation['regionName'] ?? '',
            cityName: $visitLocation['cityName'] ?? '',
            timezone: $visitLocation['timezone'] ?? '',
            latitude: $visitLocation['latitude'] ?? 0.0,
            longitude: $visitLocation['longitude'] ?? 0.0,
        );
    }

    public function mapRedirectRule(array $redirectRule): ImportedShlinkRedirectRule
    {
        return new ImportedShlinkRedirectRule(
            longUrl: $redirectRule['longUrl'] ?? '',
            conditions: array_map(
                fn (array $condition) => $this->mapRedirectCondition($condition),
                $redirectRule['conditions'] ?? [],
            ),
        );
    }

    private function mapRedirectCondition(array $redirectCondition): ImportedShlinkRedirectCondition
    {
        return new ImportedShlinkRedirectCondition(
            type: $redirectCondition['type'] ?? '',
            matchValue: $redirectCondition['matchValue'] ?? '',
            matchKey: $redirectCondition['matchKey'] ?? null,
        );
    }
}
