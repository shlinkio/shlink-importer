<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

final readonly class ImportedShlinkVisitLocation
{
    public function __construct(
        public string $countryCode,
        public string $countryName,
        public string $regionName,
        public string $cityName,
        public string $timezone,
        public float $latitude,
        public float $longitude,
    ) {
    }
}
