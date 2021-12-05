<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Symfony\Component\Console\Style\StyleInterface;

class YourlsParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    public function requestParams(StyleInterface $io): array
    {
        return [
            'base_url' => $io->ask('What is your YOURLS instance base URL?'),
            'username' => $io->ask('What is your YOURLS instance username?'),
            'password' => $io->ask('What is your YOURLS instance password?'),
            'import_visits' => $io->confirm('Do you want to import each short URL\'s visits too?'),
        ];
    }
}
