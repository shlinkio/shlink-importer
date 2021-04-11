<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Csv;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Sources\Csv\CsvParamsConsoleHelper;
use Shlinkio\Shlink\Importer\Sources\Csv\InvalidPathException;
use Symfony\Component\Console\Style\StyleInterface;

class CsvParamsConsoleHelperTest extends TestCase
{
    use ProphecyTrait;

    private CsvParamsConsoleHelper $helper;
    private ObjectProphecy $io;

    public function setUp(): void
    {
        $this->helper = new CsvParamsConsoleHelper();
        $this->io = $this->prophesize(StyleInterface::class);
    }

    /** @test */
    public function requestsParams(): void
    {
        $ask = $this->io->ask(Argument::cetera())->willReturn('stream');
        $choice = $this->io->choice(Argument::cetera())->willReturn(';');

        $result = $this->helper->requestParams($this->io->reveal());

        self::assertEquals([
            'stream' => 'stream',
            'delimiter' => ';',
        ], $result);
        $ask->shouldHaveBeenCalledOnce();
        $choice->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideEmptyStreamValues
     */
    public function pathToStreamThrowsExceptionWithInvalidValue(?string $value, string $expectedMessage): void
    {
        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->helper->pathToStream($value);
    }

    public function provideEmptyStreamValues(): iterable
    {
        yield 'null' => [null, 'The path of the file is required.'];
        yield 'empty string' => ['', 'The path of the file is required.'];
        yield 'invalid file' => [
            'this is not a file',
            'The file "this is not a file" does not seem to exist. Try another one.',
        ];
    }

    /** @test */
    public function pathIsProperlyParsedToStream(): void
    {
        $result = $this->helper->pathToStream(__FILE__);

        self::assertIsResource($result);
    }
}
