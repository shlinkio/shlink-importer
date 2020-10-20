<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params\ConsoleHelper;

use Symfony\Component\Console\Style\StyleInterface;

interface ParamsConsoleHelperInterface
{
    public function requestParams(StyleInterface $io): array;
}
