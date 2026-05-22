<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Kutt;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Symfony\Component\Console\Style\StyleInterface;

class KuttParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    /**
     * @return array<string, callable>
     */
    public function requestParams(StyleInterface $io): array
    {
        return [
            'base_url' => static fn () => $io->ask('What is your Kutt.it instance base URL?'),
            'api_key' => static fn () => $io->ask('What is your Kutt.it instance API key?'),
            'import_all_urls' => static fn () => $io->confirm('Do you want to import URLs created anonymously?', false),
        ];
    }
}
