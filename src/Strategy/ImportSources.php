<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

final class ImportSources
{
    public const BITLY = 'bitly';

    public static function getAll(): array
    {
        return [self::BITLY];
    }
}
