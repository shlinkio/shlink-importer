<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

final class ImportedShlinkUrl
{
    /**
     * @param iterable<ImportedShlinkVisit> $visits
     */
    public function __construct(
        public readonly ImportSource $source,
        public readonly string $longUrl,
        public readonly array $tags,
        public readonly DateTimeInterface $createdAt,
        public readonly ?string $domain,
        public readonly string $shortCode,
        public readonly ?string $title,
        public readonly iterable $visits = [],
        public readonly ?int $visitsCount = null,
        public ImportedShlinkUrlMeta $meta = new ImportedShlinkUrlMeta(),
    ) {
    }
}
