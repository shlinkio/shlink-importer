<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class ShlinkParams
{
    private function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
        public readonly bool $importVisits,
    ) {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('base_url') ?? '',
            $params->extraParam('api_key') ?? '',
            $params->importVisits,
        );
    }
}
