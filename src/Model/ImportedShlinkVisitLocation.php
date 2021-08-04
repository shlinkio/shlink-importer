<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

class ImportedShlinkVisitLocation
{
    public function __construct(
        private string $countryCode,
        private string $countryName,
        private string $regionName,
        private string $cityName,
        private string $timezone,
        private float $latitude,
        private float $longitude,
    ) {
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function countryName(): string
    {
        return $this->countryName;
    }

    public function regionName(): string
    {
        return $this->regionName;
    }

    public function cityName(): string
    {
        return $this->cityName;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }
}
