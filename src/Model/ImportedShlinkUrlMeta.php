<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final class ImportedShlinkUrlMeta
{
    public function __construct(
        public readonly DateTimeInterface|null $validSince = null,
        public readonly DateTimeInterface|null $validUntil = null,
        public readonly int|null $maxVisits = null,
    ) {
    }
}
