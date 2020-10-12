<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Exception;

use RuntimeException;
use Throwable;

class ImportException extends RuntimeException implements ExceptionInterface
{
    public static function fromError(Throwable $e): self
    {
        return new self('An error occurred while importing URLs', -1, $e);
    }
}
