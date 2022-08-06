<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Http;

use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ExceptionInterface;

use function sprintf;
use function str_contains;

class InvalidRequestException extends RuntimeException implements ExceptionInterface
{
    private function __construct(
        string $message,
        public readonly string $url,
        public readonly int $statusCode,
        public readonly string $body,
    ) {
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

    public function isShlinkPluginMissingError(): bool
    {
        return str_contains($this->body, '"message":"Unknown or missing \"action\" parameter"');
    }
}
