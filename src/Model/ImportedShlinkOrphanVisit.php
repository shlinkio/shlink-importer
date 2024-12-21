<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final readonly class ImportedShlinkOrphanVisit
{
    public function __construct(
        public string $referer,
        public string $userAgent,
        public DateTimeInterface $date,
        public string $visitedUrl,
        public string $type,
        public ImportedShlinkVisitLocation|null $location,
    ) {
    }
}
