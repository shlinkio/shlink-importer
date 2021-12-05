<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

final class YourlsParams
{
    private function __construct(
        private string $baseUrl,
        private string $username,
        private string $password,
        private bool $importVisits,
    ) {
    }

    public static function fromRawParams(array $params): self
    {
        return new self(
            $params['base_url'] ?? '',
            $params['username'] ?? '',
            $params['password'] ?? '',
            $params['import_visits'] ?? true,
        );
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function importVisits(): bool
    {
        return $this->importVisits;
    }
}
