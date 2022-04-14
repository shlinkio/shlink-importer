<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class ShlinkParams
{
    private function __construct(private string $baseUrl, private string $apiKey, private bool $importVisits)
    {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('base_url') ?? '',
            $params->extraParam('api_key') ?? '',
            $params->importVisits(),
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
