<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Util;

use DateTimeImmutable;
use DateTimeInterface;

class DateHelper
{
    public static function dateFromFormat(string $format, string $date): DateTimeImmutable
    {
        // @phpstan-ignore-next-line
        return DateTimeImmutable::createFromFormat($format, $date);
    }

    public static function dateFromAtom(string $atomDate): DateTimeImmutable
    {
        return self::dateFromFormat(DateTimeInterface::ATOM, $atomDate);
    }

    public static function nullableDateFromFormatWithDefault(string $format, ?string $date): DateTimeImmutable
    {
        if ($date === null) {
            return new DateTimeImmutable();
        }

        return self::dateFromFormat($format, $date);
    }

    public static function nullableDateFromAtom(?string $atomDate): ?DateTimeImmutable
    {
        if ($atomDate === null) {
            return null;
        }

        return self::dateFromAtom($atomDate);
    }
}
