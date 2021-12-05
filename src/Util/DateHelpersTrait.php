<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Util;

use DateTimeImmutable;
use DateTimeInterface;

trait DateHelpersTrait
{
    private function dateFromFormat(string $format, string $date): DateTimeImmutable
    {
        // @phpstan-ignore-next-line
        return DateTimeImmutable::createFromFormat($format, $date);
    }

    private function dateFromAtom(string $atomDate): DateTimeImmutable
    {
        return $this->dateFromFormat(DateTimeInterface::ATOM, $atomDate);
    }

    private function nullableDateFromFormatWithDefault(string $format, ?string $date): DateTimeImmutable
    {
        if ($date === null) {
            return new DateTimeImmutable();
        }

        return $this->dateFromFormat($format, $date);
    }

    private function nullableDateFromAtom(?string $atomDate): ?DateTimeImmutable
    {
        if ($atomDate === null) {
            return null;
        }

        return $this->dateFromAtom($atomDate);
    }
}
