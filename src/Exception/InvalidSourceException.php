<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Exception;

use InvalidArgumentException;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

use function implode;
use function sprintf;

class InvalidSourceException extends InvalidArgumentException implements ExceptionInterface
{
    public static function fromInvalidSource(string $source): self
    {
        return new self(sprintf(
            'Provided source "%s" is not valid. Expected one of ["%s"]',
            $source,
            implode('", "', ImportSource::values()),
        ));
    }
}
