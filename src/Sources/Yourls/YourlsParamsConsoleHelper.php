<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Yourls;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Symfony\Component\Console\Style\StyleInterface;

class YourlsParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    /**
     * @return array<string, callable>
     */
    public function requestParams(StyleInterface $io): array
    {
        return [
            'base_url' => static fn () => $io->ask('What is your YOURLS instance base URL?'),
            'username' => static fn () => $io->ask('What is your YOURLS instance username?'),
            'password' => static fn () => $io->ask('What is your YOURLS instance password?'),
            ImportParams::IMPORT_VISITS_PARAM => static fn () => $io->confirm(
                'Do you want to import each short URL\'s visits too?',
            ),
            'domain' => static fn () => $io->ask(
                'To what domain do you want the URLs to be linked? (leave empty to link them to default domain)',
            ),
        ];
    }
}
