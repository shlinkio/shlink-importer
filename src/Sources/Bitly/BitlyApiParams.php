<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Bitly;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class BitlyApiParams
{
    private function __construct(
        public readonly string $accessToken,
        public readonly bool $importTags,
        public readonly bool $importCustomDomains,
        public readonly bool $keepCreationDate,
        public readonly bool $ignoreArchived,
        public readonly ?string $continueToken = null,
    ) {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('access_token'),
            (bool) ($params->extraParam('import_tags') ?? true),
            (bool) ($params->extraParam('import_custom_domains') ?? false),
            (bool) ($params->extraParam('keep_creation_date') ?? true),
            (bool) ($params->extraParam('ignore_archived') ?? false),
            $params->extraParam('continue_token'),
        );
    }
}
