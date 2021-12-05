<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

final class ShlinkApiParams
{
    private function __construct(private string $baseUrl, private string $apiKey, private bool $importVisits)
    {
    }

    public static function fromRawParams(array $params): self
    {
        return new self($params['base_url'] ?? '', $params['api_key'] ?? '', $params['import_visits'] ?? true);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function importVisits(): bool
    {
        return $this->importVisits;
    }
}
