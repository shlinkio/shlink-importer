<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Exception;

use RuntimeException;

use function sprintf;

class InvalidRequestException extends RuntimeException implements ExceptionInterface
{
    private string $url;
    private int $statusCode;
    private string $body;

    private function __construct(string $message, string $url, int $statusCode, string $body)
    {
        parent::__construct($message);
        $this->url = $url;
        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    public static function fromResponseData(string $url, int $statusCode, string $body): self
    {
        return new self(
            sprintf('Request to %s failed with status code %s', $url, $statusCode),
            $url,
            $statusCode,
            $body,
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }
}
