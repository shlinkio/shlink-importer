<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Shlink;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Symfony\Component\Console\Style\StyleInterface;

class ShlinkParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    /**
     * @return array<string, callable>
     */
    public function requestParams(StyleInterface $io): array
    {
        return [
            ShlinkParams::BASE_URL => fn () => $io->ask('What is your Shlink instance base URL?'),
            ShlinkParams::API_KEY => fn () => $io->ask('What is your Shlink instance API key?'),
            ImportParams::IMPORT_VISITS_PARAM => fn () => $io->confirm(
                'Do you want to import each short URL\'s visits too?',
            ),
            ImportParams::IMPORT_ORPHAN_VISITS_PARAM => fn () => $io->confirm(
                'Do you want to import orphan visits too?',
            ),
        ];
    }
}
