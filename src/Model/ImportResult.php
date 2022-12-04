<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

final class ImportResult
{
    private function __construct(
        /** @var ImportedShlinkUrl[] $shlinkUrls */
        public readonly iterable $shlinkUrls,
        /** @var ImportedShlinkOrphanVisit[] $shlinkUrls */
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
