<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params;

use Shlinkio\Shlink\Importer\Sources\ImportSources;

final class ImportParams
{
    public const IMPORT_SHORT_CODES_PARAM = 'import_short_codes';
    public const IMPORT_VISITS_PARAM = 'import_visits';

    private function __construct(
        public readonly ImportSources $source,
        public readonly bool $importShortCodes,
        public readonly bool $importVisits,
        private readonly array $extraParams,
    ) {
    }

    /**
     * @param array<string, callable> $callableMap
     */
    public static function fromSourceAndCallableMap(ImportSources $source, array $callableMap): self
    {
        $importShortCodes = self::extractParamWithDefault($callableMap, self::IMPORT_SHORT_CODES_PARAM, true);
        $importVisits = self::extractParamWithDefault($callableMap, self::IMPORT_VISITS_PARAM, false);

        return new self(
            $source,
            $importShortCodes(),
            $importVisits(),
            ParamsUtils::invokeCallbacks($callableMap),
        );
    }

    public static function fromSource(ImportSources $source): self
    {
        return new self($source, true, false, []);
    }

    private static function extractParamWithDefault(array &$callableMap, string $key, mixed $default): callable
    {
        $extracted = $callableMap[$key] ?? static fn () => $default;
        unset($callableMap[$key]);

        return $extracted;
    }

    public function extraParam(string $key): mixed
    {
        return $this->extraParams[$key] ?? null;
    }
}
