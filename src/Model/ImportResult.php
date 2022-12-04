<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

final class ImportResult
{
    public function __construct(
        /** @var ImportedShlinkUrl[] $shlinkUrls */
        public readonly iterable $shlinkUrls,
        /** @var ImportedShlinkOrphanVisit[] $shlinkUrls */
        public readonly iterable $orphanVisits = [],
    ) {
    }
}
