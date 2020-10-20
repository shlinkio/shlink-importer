<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params;

class BitlyApiV4Params
{
    private string $accessToken;
    private bool $importTags;
    private bool $importCustomDomains;
    private bool $keepCreationDate;
    private bool $ignoreArchived;

    private function __construct()
    {
    }

    public static function fromRawParams(array $params): self
    {
        $instance = new self();

        $instance->accessToken = $params['access_token'];
        $instance->importTags = (bool) ($params['import_tags'] ?? true);
        $instance->importCustomDomains = (bool) ($params['import_custom_domains'] ?? false);
        $instance->keepCreationDate = (bool) ($params['keep_creation_date'] ?? true);
        $instance->ignoreArchived = (bool) ($params['ignore_archived'] ?? false);

        return $instance;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function importTags(): bool
    {
        return $this->importTags;
    }

    public function importCustomDomains(): bool
    {
        return $this->importCustomDomains;
    }

    public function keepCreationDate(): bool
    {
        return $this->keepCreationDate;
    }

    public function ignoreArchived(): bool
    {
        return $this->ignoreArchived;
    }
}
