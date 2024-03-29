<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Csv;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\Csv\CsvImporter;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

use function fopen;
use function fwrite;
use function rewind;

class CsvImporterTest extends TestCase
{
    private CsvImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new CsvImporter(self::getDate());
    }

    #[Test, DataProvider('provideCSVs')]
    public function csvIsProperlyImported(string $csv, string $delimiter, array $expectedList): void
    {
        $options = ImportSource::CSV->toParamsWithCallableMap(
            ['delimiter' => fn () => $delimiter, 'stream' => fn () => $this->createCsvStream($csv)],
        );

        $result = $this->importer->import($options);
        $urls = [...$result->shlinkUrls];

        self::assertEquals($expectedList, $urls);
    }

    public static function provideCSVs(): iterable
    {
        yield 'comma separator' => [
            <<<CSV
            Long URL,Tags,Domain  ,Short code, Title
            https://shlink.io,foo|bar|baz,,123,
            https://facebook.com,,example.com,456,my title
            CSV,
            ',',
            [
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://shlink.io',
                    ['foo', 'bar', 'baz'],
                    self::getDate(),
                    null,
                    '123',
                    null,
                ),
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://facebook.com',
                    [],
                    self::getDate(),
                    'example.com',
                    '456',
                    'my title',
                ),
            ],
        ];
        yield 'semicolon separator' => [
            <<<CSV
            longURL;tags;domain;short code;Title
            https://alejandrocelaya.blog;;;abc;
            https://facebook.com;foo|baz;example.com;def;
            https://shlink.io/documentation;;example.com;ghi;the title
            CSV,
            ';',
            [
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://alejandrocelaya.blog',
                    [],
                    self::getDate(),
                    null,
                    'abc',
                    null,
                ),
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://facebook.com',
                    ['foo', 'baz'],
                    self::getDate(),
                    'example.com',
                    'def',
                    null,
                ),
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://shlink.io/documentation',
                    [],
                    self::getDate(),
                    'example.com',
                    'ghi',
                    'the title',
                ),
            ],
        ];
        yield 'comma separator in tags' => [
            <<<CSV
            longURL;tags;domain;short code;Title
            https://facebook.com;foo,baz;example.com;def;
            CSV,
            ';',
            [
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://facebook.com',
                    ['foo', 'baz'],
                    self::getDate(),
                    'example.com',
                    'def',
                    null,
                ),
            ],
        ];
        yield 'unknown separator in tags' => [
            <<<CSV
            longURL;tags;domain;short code;Title
            https://facebook.com;foo-baz;example.com;def;
            CSV,
            ';',
            [
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://facebook.com',
                    ['foo-baz'],
                    self::getDate(),
                    'example.com',
                    'def',
                    null,
                ),
            ],
        ];
        yield 'inferred shortCode and domain' => [
            <<<CSV
            longURL;tags;short URL;Title
            https://facebook.com;foo-baz;https://example.es/inferred-short-code;
            CSV,
            ';',
            [
                new ImportedShlinkUrl(
                    ImportSource::CSV,
                    'https://facebook.com',
                    ['foo-baz'],
                    self::getDate(),
                    'example.es',
                    'inferred-short-code',
                    null,
                ),
            ],
        ];
    }

    /**
     * @return resource
     */
    private function createCsvStream(string $csv)
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, $csv); // @phpstan-ignore-line
        rewind($stream); // @phpstan-ignore-line

        return $stream; // @phpstan-ignore-line
    }

    private static function getDate(): DateTimeInterface
    {
        static $date;
        return $date ?? ($date = new DateTimeImmutable());
    }
}
