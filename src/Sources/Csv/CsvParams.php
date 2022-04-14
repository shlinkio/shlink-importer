<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Csv;

use Shlinkio\Shlink\Importer\Params\ImportParams;

final class CsvParams
{
    /** @var resource */
    private $stream;
    private string $delimiter;

    private function __construct()
    {
    }

    public static function fromImportParams(ImportParams $params): self
    {
        $instance = new self();
        $instance->delimiter = $params->extraParam('delimiter') ?? '';
        $instance->stream = $params->extraParam('stream') ?? '';

        return $instance;
    }

    /**
     * @return resource
     */
    public function stream()
    {
        return $this->stream;
    }

    public function delimiter(): string
    {
        return $this->delimiter;
    }
}
