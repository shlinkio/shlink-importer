<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Util;

use DateTimeImmutable;

trait DateHelpersTrait
{
    private function dateFromAtom(string $atomDate): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $atomDate);
    }

    private function nullableDateFromAtom(?string $atomDate): ?DateTimeImmutable
    {
        if ($atomDate === null) {
            return null;
        }

        return $this->dateFromAtom($atomDate);
    }
}
