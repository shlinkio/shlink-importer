<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class YourlsParams
{
    private function __construct(
        private string $baseUrl,
        private string $username,
        private string $password,
        private bool $importVisits,
        private ?string $domain,
    ) {
    }

    public static function fromRawParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('base_url') ?? '',
            $params->extraParam('username') ?? '',
            $params->extraParam('password') ?? '',
            $params->importVisits(),
            $params->extraParam('domain'),
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

    public function domain(): ?string
    {
        return $this->domain;
    }
}
