<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Importer\Command\ImportCommand;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Exception\InvalidSourceException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ConsoleHelperManagerInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiException;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function putenv;

class ImportCommandTest extends TestCase
{
    private MockObject & ImporterStrategyManagerInterface $importerStrategyManager;
    private MockObject & ConsoleHelperManagerInterface $consoleHelperManager;
    private MockObject & ImportedLinksProcessorInterface $importedLinksProcessor;
    private MockObject & ParamsConsoleHelperInterface $paramsHelper;
    private MockObject & ImporterStrategyInterface $importerStrategy;
    private CommandTester $commandTester;

    public function setUp(): void
    {
        putenv('COLUMNS=120'); // This ensures a consistent output length

        $this->importerStrategyManager = $this->createMock(ImporterStrategyManagerInterface::class);
        $this->consoleHelperManager = $this->createMock(ConsoleHelperManagerInterface::class);
        $this->importedLinksProcessor = $this->createMock(ImportedLinksProcessorInterface::class);
        $this->paramsHelper = $this->createMock(ParamsConsoleHelperInterface::class);
        $this->importerStrategy = $this->createMock(ImporterStrategyInterface::class);
        $this->consoleHelperManager->method('get')->willReturn($this->paramsHelper);
        $this->importerStrategyManager->method('get')->willReturn($this->importerStrategy);

        $command = new ImportCommand(
            $this->importerStrategyManager,
            $this->consoleHelperManager,
            $this->importedLinksProcessor,
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        putenv('COLUMNS');
    }

    /** @test */
    public function exceptionIsThrownWhenInvalidSourceIsProvided(): void
    {
        $this->expectException(InvalidSourceException::class);

        $this->commandTester->execute(['source' => 'invalid']);
    }

    /**
     * @test
     * @dataProvider provideSource
     */
    public function dependenciesAreInvokedAsExpected(?string $providedSource, bool $expectSourceQuestion): void
    {
        $source = $providedSource ?? ImportSource::BITLY->value;
        $params = ImportParams::fromSource(ImportSource::from($source));

        $this->paramsHelper->expects($this->once())->method('requestParams')->with(
            $this->isInstanceOf(StyleInterface::class),
        )->willReturn([]);
        $this->importerStrategy->expects($this->once())->method('import')->with($params)->willReturn(
            ImportResult::emptyInstance(),
        );
        $this->importedLinksProcessor->expects($this->once())->method('process')->with(
            $this->isInstanceOf(StyleInterface::class),
            ImportResult::emptyInstance(),
            $params,
        );
        $this->importerStrategyManager->expects($this->once())->method('get')->with($source);
        $this->consoleHelperManager->expects($this->once())->method('get')->with($source);

        if ($expectSourceQuestion) {
            $this->commandTester->setInputs(['0']);
        }
        $exitCode = $this->commandTester->execute(['source' => $providedSource]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ImportCommand::SUCCESS, $exitCode);
        if ($expectSourceQuestion) {
            self::assertStringContainsString('What is the source you want to import from:', $output);
        } else {
            self::assertStringNotContainsString('What is the source you want to import from:', $output);
        }
    }

    public function provideSource(): iterable
    {
        yield 'provided source' => [ImportSource::BITLY->value, false];
        yield 'not provided source' => [null, true];
    }

    /**
     * @test
     * @dataProvider provideImportExceptions
     */
    public function importErrorsAreProperlyHandled(
        ImportException $e,
        int $verbosity,
        array $expectedOutputs,
        array $notExpectedOutputs,
    ): void {
        $this->paramsHelper->expects($this->once())->method('requestParams')->with(
            $this->isInstanceOf(StyleInterface::class),
        )->willReturn([]);
        $this->importerStrategy->expects($this->once())->method('import')->with(
            ImportSource::BITLY->toParams(),
        )->willThrowException($e);
        $this->importedLinksProcessor->expects($this->never())->method('process');

        $exitCode = $this->commandTester->execute(
            ['source' => ImportSource::BITLY->value],
            ['verbosity' => $verbosity],
        );
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ImportCommand::FAILURE, $exitCode);
        foreach ($expectedOutputs as $expectedOutput) {
            self::assertStringContainsString($expectedOutput, $output);
        }
        foreach ($notExpectedOutputs as $notExpectedOutput) {
            self::assertStringNotContainsString($notExpectedOutput, $output);
        }
    }

    public function provideImportExceptions(): iterable
    {
        yield 'no continue token, no verbose' => [
            ImportException::fromError(new RuntimeException('')),
            OutputInterface::VERBOSITY_NORMAL,
            ['[ERROR] An error occurred while importing URLs.'],
            [
                '[WARNING] Not all URLs were properly imported. Wait a few minutes, and then try executing this '
                . 'command again, providing',
                '"foobar" when the "continue token" is requested. That will ensure already processed URLs are skipped.',
                '[Shlinkio\Shlink\Importer\Exception\ImportException (-1)]',
            ],
        ];
        yield 'no continue token, verbose' => [
            ImportException::fromError(new RuntimeException('')),
            OutputInterface::VERBOSITY_VERBOSE,
            [
                '[ERROR] An error occurred while importing URLs.',
                '[Shlinkio\Shlink\Importer\Exception\ImportException (-1)]',
            ],
            [
                '[WARNING] Not all URLs were properly imported. Wait a few minutes, and then try executing this '
                . 'command again, providing',
                '"foobar" when the "continue token" is requested. That will ensure already processed URLs are skipped.',
            ],
        ];
        yield 'continue token, no verbose' => [
            BitlyApiException::fromInvalidRequest(InvalidRequestException::fromResponseData('', 1, ''), 'foobar'),
            OutputInterface::VERBOSITY_NORMAL,
            [
                '[WARNING] Not all URLs were properly imported. Wait a few minutes, and then try executing this '
                . 'command again, providing',
                '"foobar" when the "continue token" is requested. That will ensure already processed URLs are skipped.',
            ],
            [
                '[ERROR] An error occurred while importing URLs.',
                '[Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiException]',
            ],
        ];
        yield 'continue token, verbose' => [
            BitlyApiException::fromInvalidRequest(InvalidRequestException::fromResponseData('', 1, ''), 'foobar'),
            OutputInterface::VERBOSITY_VERBOSE,
            [
                '[WARNING] Not all URLs were properly imported. Wait a few minutes, and then try executing this '
                . 'command again, providing',
                '"foobar" when the "continue token" is requested. That will ensure already processed URLs are skipped.',
            ],
            ['[ERROR] An error occurred while importing URLs.'],
        ];
    }
}
