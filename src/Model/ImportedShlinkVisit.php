<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

class ImportedShlinkVisit
{
    public function __construct(
        private string $referer,
        private string $userAgent,
        private DateTimeInterface $date,
        private ?ImportedShlinkVisitLocation $location
    ) {
    }

    public function referer(): string
    {
        return $this->referer;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    public function date(): DateTimeInterface
    {
        return $this->date;
    }

    public function location(): ?ImportedShlinkVisitLocation
    {
        return $this->location;
    }
}
