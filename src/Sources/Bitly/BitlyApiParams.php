<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Bitly;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class BitlyApiParams
{
    private string $accessToken;
    private bool $importTags;
    private bool $importCustomDomains;
    private bool $keepCreationDate;
    private bool $ignoreArchived;
    private ?string $continueToken = null;

    private function __construct()
    {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        $instance = new self();

        $instance->accessToken = $params->extraParam('access_token');
        $instance->importTags = (bool) ($params->extraParam('import_tags') ?? true);
        $instance->importCustomDomains = (bool) ($params->extraParam('import_custom_domains') ?? false);
        $instance->keepCreationDate = (bool) ($params->extraParam('keep_creation_date') ?? true);
        $instance->ignoreArchived = (bool) ($params->extraParam('ignore_archived') ?? false);
        $instance->continueToken = $params->extraParam('continue_token');

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

    public function continueToken(): ?string
    {
        return $this->continueToken;
    }
}
