<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final readonly class ImportedShlinkVisit
{
    public function __construct(
        public string $referer,
        public string $userAgent,
        public DateTimeInterface $date,
        public ImportedShlinkVisitLocation|null $location,
    ) {
    }
}
