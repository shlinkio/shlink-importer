<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\ShlinkApi;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Symfony\Component\Console\Style\StyleInterface;

class ShlinkApiParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    /**
     * @return array<string, callable>
     */
    public function requestParams(StyleInterface $io): array
    {
        return [
            'base_url' => fn () => $io->ask('What is your Shlink instance base URL?'),
            'api_key' => fn () => $io->ask('What is your Shlink instance API key?'),
            'import_visits' => fn () => $io->confirm('Do you want to import each short URL\'s visits too?'),
        ];
    }
}
