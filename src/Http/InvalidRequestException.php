<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Http;

use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ExceptionInterface;

use function sprintf;

class InvalidRequestException extends RuntimeException implements ExceptionInterface
{
    private function __construct(string $message, private string $url, private int $statusCode, private string $body)
    {
        parent::__construct($message);
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
