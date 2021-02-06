<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Csv;

use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Symfony\Component\Console\Style\StyleInterface;

use function fopen;

class CsvParamsConsoleHelper implements ParamsConsoleHelperInterface
{
    public function requestParams(StyleInterface $io): array
    {
        return [
            'import_short_codes' => true,
            'stream' => $io->ask('What\'s the path for the CSV file you want to import', null, [$this, 'pathToStream']),
            'delimiter' => $io->choice('What\'s the delimiter used to separate values?', [
                ',' => 'Comma',
                ';' => 'Semicolon',
            ], ','),
        ];
    }

    /**
     * @return resource
     */
    public function pathToStream(?string $value)
    {
        if (empty($value)) {
            throw InvalidPathException::pathNotProvided();
        }

        $file = @fopen($value, 'rb');
        if (! $file) {
            throw InvalidPathException::pathIsNotFile($value);
        }

        return $file;
    }
}
