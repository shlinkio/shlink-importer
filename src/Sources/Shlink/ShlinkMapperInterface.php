<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use DateTimeInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectRule;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

interface ShlinkMapperInterface
{
    /**
     * @param iterable<ImportedShlinkVisit> $visits
     * @param ImportedShlinkRedirectRule[] $redirectRules
     */
    public function mapShortUrl(
        array $url,
        iterable $visits,
        array $redirectRules,
        DateTimeInterface $fallbackDate,
    ): ImportedShlinkUrl;

    public function mapVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkVisit;

    public function mapOrphanVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkOrphanVisit;

    public function mapRedirectRule(array $redirectRule): ImportedShlinkRedirectRule;
}
