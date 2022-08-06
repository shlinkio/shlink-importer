<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Csv;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class CsvParams
{
    /**
     * @param resource $stream
     */
    private function __construct(
        public readonly mixed $stream,
        public readonly string $delimiter,
    ) {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        return new self(
            $params->extraParam('stream') ?? '',
            $params->extraParam('delimiter') ?? '',
        );
    }
}
