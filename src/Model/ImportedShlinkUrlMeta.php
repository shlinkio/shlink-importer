<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final readonly class ImportedShlinkUrlMeta
{
    public function __construct(
        public DateTimeInterface|null $validSince = null,
        public DateTimeInterface|null $validUntil = null,
        public int|null $maxVisits = null,
    ) {
    }
}
