<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

final readonly class ImportedShlinkUrl
{
    /**
     * @param iterable<ImportedShlinkVisit> $visits
     * @param ImportedShlinkRedirectRule[] $redirectRules
     */
    public function __construct(
        public ImportSource $source,
        public string $longUrl,
        public array $tags,
        public DateTimeInterface $createdAt,
        public string|null $domain,
        public string $shortCode,
        public string|null $title,
        public iterable $visits = [],
        public int|null $visitsCount = null,
        public ImportedShlinkUrlMeta $meta = new ImportedShlinkUrlMeta(),
        public array $redirectRules = [],
    ) {
    }
}
