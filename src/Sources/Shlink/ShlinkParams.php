<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class ShlinkParams
{
    public const BASE_URL = 'base_url';
    public const API_KEY = 'api_key';

    private function __construct(
        public readonly string $baseUrl,
        public readonly string $apiKey,
        public readonly bool $importVisits,
        public readonly bool $importOrphanVisits,
    ) {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam(self::BASE_URL) ?? '',
            $params->extraParam(self::API_KEY) ?? '',
            $params->importVisits,
            $params->importOrphanVisits,
        );
    }
}
