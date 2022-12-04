<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use DateTimeInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrlMeta;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisitLocation;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Util\DateHelper;

final class ShlinkMapper implements ShlinkMapperInterface
{
    public function mapShortUrl(array $url, iterable $visits, DateTimeInterface $fallbackDate): ImportedShlinkUrl
    {
        $meta = new ImportedShlinkUrlMeta(
            DateHelper::nullableDateFromAtom($url['meta']['validSince'] ?? null),
            DateHelper::nullableDateFromAtom($url['meta']['validUntil'] ?? null),
            $url['meta']['maxVisits'] ?? null,
        );

        return new ImportedShlinkUrl(
            ImportSource::SHLINK,
            $url['longUrl'] ?? '',
            $url['tags'] ?? [],
            DateHelper::nullableDateFromAtom($url['dateCreated'] ?? null) ?? $fallbackDate,
            $url['domain'] ?? null,
            $url['shortCode'],
            $url['title'] ?? null,
            $visits,
            $url['visitsCount'],
            $meta,
        );
    }

    public function mapVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkVisit
    {
        return new ImportedShlinkVisit(
            $visit['referer'] ?? '',
            $visit['userAgent'] ?? '',
            DateHelper::nullableDateFromAtom($visit['date'] ?? null) ?? $fallbackDate,
            $this->mapVisitLocation($visit['visitLocation'] ?? null),
        );
    }

    public function mapOrphanVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkOrphanVisit
    {
        return new ImportedShlinkOrphanVisit(
            $visit['referer'] ?? '',
            $visit['userAgent'] ?? '',
            DateHelper::nullableDateFromAtom($visit['date'] ?? null) ?? $fallbackDate,
            $visit['visitedUrl'] ?? '',
            $visit['type'] ?? '',
            $this->mapVisitLocation($visit['visitLocation'] ?? null),
        );
    }

    private function mapVisitLocation(?array $visitLocation): ?ImportedShlinkVisitLocation
    {
        return $visitLocation === null ? null : new ImportedShlinkVisitLocation(
            $visitLocation['countryCode'] ?? '',
            $visitLocation['countryName'] ?? '',
            $visitLocation['regionName'] ?? '',
            $visitLocation['cityName'] ?? '',
            $visitLocation['timezone'] ?? '',
            $visitLocation['latitude'] ?? 0.0,
            $visitLocation['longitude'] ?? 0.0,
        );
    }
}
