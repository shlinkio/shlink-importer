<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params;

use function array_map;

final class ParamsUtils
{
    /**
     * @param array<string, callable> $callbacksMap
     * @return array<string, mixed>
     */
    public static function invokeCallbacks(array $callbacksMap): array
    {
        return array_map(static fn (callable $callback) => $callback(), $callbacksMap);
    }
}
