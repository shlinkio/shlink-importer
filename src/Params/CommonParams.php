<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params;

final class CommonParams
{
    private function __construct(
        private string $source,
        private bool $importShortCodes,
        private bool $importVisits,
        private array $extraParams,
    ) {
    }

    /**
     * @param array<string, callable> $callableMap
     */
    public static function fromSourceAndCallableMap(string $source, array $callableMap): self
    {
        $importShortCodes = self::extractParamWithDefault($callableMap, 'import_short_codes', true);
        $importVisits = self::extractParamWithDefault($callableMap, 'import_visits', false);

        return new self(
            $source,
            $importShortCodes(),
            $importVisits(),
            ParamsUtils::invokeCallbacks($callableMap),
        );
    }

    public static function fromSource(string $source): self
    {
        return new self($source, true, false, []);
    }

    private static function extractParamWithDefault(array &$callableMap, string $key, mixed $default): callable
    {
        $extracted = $callableMap[$key] ?? static fn () => $default;
        unset($callableMap[$key]);

        return $extracted;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function importShortCodes(): bool
    {
        return $this->importShortCodes;
    }

    public function importVisits(): bool
    {
        return $this->importVisits;
    }

    public function extraParam(string $key): mixed
    {
        return $this->extraParams[$key] ?? null;
    }
}
