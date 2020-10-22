<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params\ConsoleHelper;

use Symfony\Component\Console\Style\StyleInterface;

class BitlyApiParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    public function requestParams(StyleInterface $io): array
    {
        return [
            'access_token' => $io->ask(
                'What is your Bit.ly\'s API access token? (you can generate one here https://bitly.is/accesstoken)',
            ),
            'import_short_codes' => $io->confirm(
                'Do you want to import short-codes/slugs as they are? Otherwise, new unique short-codes will be '
                . 'generated for every imported URL.',
            ),
            'import_tags' => $io->confirm('Do you want to import tags?'),
            'import_custom_domains' => $io->confirm(
                'Do you want to import custom domains? (any domain other than bit.ly)',
                false,
            ),
            'keep_creation_date' => $io->confirm(
                'Do you want to keep the original creation date? Otherwise, all imported URLs will have current date '
                . 'as its creation date',
            ),
            'ignore_archived' => $io->confirm('Do you want to ignore archived URLs?', false),
            'continue_token' => $io->ask(
                'If you already run this command once and a warning was displayed, you might have been provided with a '
                . '"continue token". If that\'s the case, paste it here. If this is the first time you run this '
                . 'command, ignore this.',
            ),
        ];
    }
}
