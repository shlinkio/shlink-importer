<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Kutt;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class KuttParams
{
    private function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
        public readonly bool $importAllUrls,
    ) {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('base_url') ?? '',
            $params->extraParam('api_key') ?? '',
            $params->extraParam('import_all_urls') ?? false,
        );
    }
}
