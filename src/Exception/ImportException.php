<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Exception;

use RuntimeException;
use Throwable;

class ImportException extends RuntimeException implements ExceptionInterface
{
    private ?string $continueToken;

    protected function __construct(string $message, ?string $continueToken, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->continueToken = $continueToken;
    }

    public static function fromError(Throwable $e): self
    {
        return new self('An error occurred while importing URLs', null, -1, $e);
    }

    public function continueToken(): ?string
    {
        return $this->continueToken;
    }
}
