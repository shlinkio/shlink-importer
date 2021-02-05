<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Bitly;

use Shlinkio\Shlink\Importer\Exception\ImportException;

use function sprintf;

class BitlyApiException extends ImportException
{
    public static function fromInvalidRequest(
        string $url,
        int $statusCode,
        string $body,
        ?string $continueToken = null
    ): self {
        return new self(sprintf(
            'Request to Bitly API v4 to URL "%s" failed with status code "%s" and body "%s"',
            $url,
            $statusCode,
            $body,
        ), $continueToken);
    }
}
