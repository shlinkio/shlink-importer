<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Bitly;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;

use function sprintf;

class BitlyApiException extends ImportException
{
    public static function fromInvalidRequest(InvalidRequestException $e, ?string $continueToken = null): self
    {
        return new self(sprintf(
            'Request to Bitly API v4 to URL "%s" failed with status code "%s" and body "%s"',
            $e->url,
            $e->statusCode,
            $e->body,
        ), $continueToken);
    }
}
