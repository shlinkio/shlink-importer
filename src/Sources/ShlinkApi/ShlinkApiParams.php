<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

final class ShlinkApiParams
{
    private string $baseUrl;
    private string $apiKey;
    private bool $importVisits;

    private function __construct()
    {
    }

    public static function fromRawParams(array $params): self
    {
        $instance = new self();
        $instance->baseUrl = $params['base_url'] ?? '';
        $instance->apiKey = $params['api_key'] ?? '';
        $instance->importVisits = $params['import_visits'] ?? true;

        return $instance;
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
