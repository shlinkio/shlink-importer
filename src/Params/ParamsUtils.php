<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params;

use function Functional\map;

final class ParamsUtils
{
    /**
     * @param array<string, callable> $callbacksMap
     * @return array<string, mixed>
     */
    public static function invokeCallbacks(array $callbacksMap): array
    {
        return map($callbacksMap, static fn (callable $callback) => $callback());
    }
}
