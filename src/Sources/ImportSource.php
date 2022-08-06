<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources;

use Shlinkio\Shlink\Importer\Params\ImportParams;

use function Functional\map;

enum ImportSource: string
{
    case BITLY = 'bitly';
    case YOURLS = 'yourls';
    case CSV = 'csv';
    case SHLINK = 'shlink';
    case KUTT = 'kutt';

    public function toParams(): ImportParams
    {
        return ImportParams::fromSource($this);
    }

    /**
     * @param array<string, callable> $callableMap
     */
    public function toParamsWithCallableMap(array $callableMap): ImportParams
    {
        return ImportParams::fromSourceAndCallableMap($this, $callableMap);
    }

    public static function values(): array
    {
        return map(self::cases(), static fn (ImportSource $source) => $source->value);
    }
}
