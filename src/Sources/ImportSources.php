<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources;

final class ImportSources
{
    public const BITLY = 'bitly';
    public const CSV = 'csv';
    public const SHLINK = 'shlink';

    public static function getAll(): array
    {
        return [self::BITLY, self::CSV, self::SHLINK];
    }
}
