<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Exception;

use RuntimeException;
use Throwable;

class ImportException extends RuntimeException implements ExceptionInterface
{
    protected function __construct(
        string $message,
        public readonly ?string $continueToken,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromError(Throwable $e): self
    {
        return new self('An error occurred while importing URLs', null, -1, $e);
    }
}
