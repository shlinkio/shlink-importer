<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Csv;

final class CsvParams
{
    /** @var resource */
    private $stream;
    private string $delimiter;

    private function __construct()
    {
    }

    public static function fromRawParams(array $params): self
    {
        $instance = new self();
        $instance->delimiter = $params['delimiter'] ?? '';
        $instance->stream = $params['stream'] ?? '';

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
