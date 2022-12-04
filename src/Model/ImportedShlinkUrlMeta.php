<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final class ImportedShlinkUrlMeta
{
    public function __construct(
        public readonly ?DateTimeInterface $validSince = null,
        public readonly ?DateTimeInterface $validUntil = null,
        public readonly ?int $maxVisits = null,
    ) {
    }
}
