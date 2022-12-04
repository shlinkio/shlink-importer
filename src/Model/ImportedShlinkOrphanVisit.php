<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final class ImportedShlinkOrphanVisit
{
    public function __construct(
        public readonly string $referer,
        public readonly string $userAgent,
        public readonly DateTimeInterface $date,
        public readonly string $visitedUrl,
        public readonly string $type,
        public readonly ?ImportedShlinkVisitLocation $location,
    ) {
    }
}
