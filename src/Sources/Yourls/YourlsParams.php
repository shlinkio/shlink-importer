<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class YourlsParams
{
    private function __construct(
        public readonly string $baseUrl,
        public readonly string $username,
        public readonly string $password,
        public readonly bool $importVisits,
        public readonly ?string $domain,
    ) {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('base_url') ?? '',
            $params->extraParam('username') ?? '',
            $params->extraParam('password') ?? '',
            $params->importVisits,
            $params->extraParam('domain'),
        );
    }
}
