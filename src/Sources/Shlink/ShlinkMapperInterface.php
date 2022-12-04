<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use DateTimeInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

interface ShlinkMapperInterface
{
    public function mapShortUrl(array $url, iterable $visits, DateTimeInterface $fallbackDate): ImportedShlinkUrl;

    public function mapVisit(array $visit, DateTimeInterface $fallbackDate): ImportedShlinkVisit;
}
