<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params\ConsoleHelper;

use Symfony\Component\Console\Style\StyleInterface;

class BitlyApiV4ParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    public function requestParams(StyleInterface $io): array
    {
        return [
            'access_token' => $io->ask(
                'Bit.ly\'s API access token (you can generate one going to https://bitly.is/accesstoken)',
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
        ];
    }
}
