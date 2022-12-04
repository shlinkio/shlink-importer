<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

final class ImportResult
{
    /**
     * @param iterable<ImportedShlinkUrl> $shlinkUrls
     * @param iterable<ImportedShlinkOrphanVisit> $orphanVisits
     */
    private function __construct(
        public readonly iterable $shlinkUrls,
        public readonly iterable $orphanVisits,
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
