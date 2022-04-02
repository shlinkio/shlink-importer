<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

use Shlinkio\Shlink\Importer\Params\CommonParams;

final class ShlinkApiParams
{
    private function __construct(private string $baseUrl, private string $apiKey, private bool $importVisits)
    {
    }

    public static function fromRawParams(CommonParams $params): self
    {
        return new self(
            $params->extraParam('base_url') ?? '',
            $params->extraParam('api_key') ?? '',
            $params->extraParam('import_visits') ?? true,
        );
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
