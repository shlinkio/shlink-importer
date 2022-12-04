<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

final class ImportedShlinkVisitLocation
{
    public function __construct(
        public readonly string $countryCode,
        public readonly string $countryName,
        public readonly string $regionName,
        public readonly string $cityName,
        public readonly string $timezone,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {
    }
}
