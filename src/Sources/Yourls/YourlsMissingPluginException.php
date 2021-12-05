<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Throwable;

class YourlsMissingPluginException extends ImportException
{
    public static function forMissingPlugin(Throwable $prev): self
    {
        return new self(
            'The YOURLS instance from where you are trying to import links, does not have the '
            . '"yourls-shlink-import-plugin" installed, or it is not enabled. Go to https://slnk.to/yourls-import '
            . 'and follow the installation instructions, then try to import again.',
            null,
            $prev->getCode(),
            $prev,
        );
    }
}
