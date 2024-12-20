<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

final readonly class ImportResult
{
    /**
     * @param iterable<ImportedShlinkUrl> $shlinkUrls
     * @param iterable<ImportedShlinkOrphanVisit> $orphanVisits
     */
    private function __construct(
        public iterable $shlinkUrls,
        public iterable $orphanVisits,
    ) {
    }

    public static function emptyInstance(): self
    {
        return new self([], []);
    }

    public static function withShortUrls(iterable $shlinkUrls): self
    {
        return new self($shlinkUrls, []);
    }

    public static function withShortUrlsAndOrphanVisits(iterable $shlinkUrls, iterable $orphanVisits): self
    {
        return new self($shlinkUrls, $orphanVisits);
    }
}
