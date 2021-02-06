<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Csv;

use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ExceptionInterface;

use function sprintf;

class InvalidPathException extends RuntimeException implements ExceptionInterface
{
    public static function pathNotProvided(): self
    {
        return new self('The path of the file is required.');
    }

    public static function pathIsNotFile(string $path): self
    {
        return new self(sprintf('The file "%s" does not seem to exist. Try another one.', $path));
    }
}
