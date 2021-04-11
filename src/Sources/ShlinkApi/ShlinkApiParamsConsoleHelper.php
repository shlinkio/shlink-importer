<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Symfony\Component\Console\Style\StyleInterface;

class ShlinkApiParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    public function requestParams(StyleInterface $io): array
    {
        return [
            'base_url' => $io->ask('What is your Shlink instance base URL?'),
            'api_key' => $io->ask('What is your Shlink instance API key?'),
            'import_visits' => $io->confirm('Do you want to import each short URL\'s visits too?'),
        ];
    }
}
