<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Csv;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Csv\CsvParamsConsoleHelper;
use Shlinkio\Shlink\Importer\Sources\Csv\InvalidPathException;
use Symfony\Component\Console\Style\StyleInterface;

class CsvParamsConsoleHelperTest extends TestCase
{
    private CsvParamsConsoleHelper $helper;
    private MockObject & StyleInterface $io;

    public function setUp(): void
    {
        $this->helper = new CsvParamsConsoleHelper();
        $this->io = $this->createMock(StyleInterface::class);
    }

    #[Test]
    public function requestsParams(): void
    {
        $this->io->expects($this->once())->method('ask')->willReturn('stream');
        $this->io->expects($this->once())->method('choice')->willReturn(';');

        $result = ParamsUtils::invokeCallbacks($this->helper->requestParams($this->io));

        self::assertEquals([
            'stream' => 'stream',
            'delimiter' => ';',
        ], $result);
    }

    #[Test, DataProvider('provideEmptyStreamValues')]
    public function pathToStreamThrowsExceptionWithInvalidValue(string|null $value, string $expectedMessage): void
    {
        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->helper->pathToStream($value);
    }

    public static function provideEmptyStreamValues(): iterable
    {
        yield 'null' => [null, 'The path of the file is required.'];
        yield 'empty string' => ['', 'The path of the file is required.'];
        yield 'invalid file' => [
            'this is not a file',
            'The file "this is not a file" does not seem to exist. Try another one.',
        ];
    }

    #[Test]
    public function pathIsProperlyParsedToStream(): void
    {
        $result = $this->helper->pathToStream(__FILE__);

        self::assertIsResource($result);
    }
}
