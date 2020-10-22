<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Importer\Command\ImportCommand;
use Shlinkio\Shlink\Importer\Exception\BitlyApiV4Exception;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Exception\InvalidSourceException;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ConsoleHelperManagerInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyManagerInterface;
use Shlinkio\Shlink\Importer\Strategy\ImportSources;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function putenv;

class ImportCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $importerStrategyManager;
    private ObjectProphecy $consoleHelperManager;
    private ObjectProphecy $importedLinksProcessor;
    private ObjectProphecy $paramsHelper;
    private ObjectProphecy $importerStrategy;
    private CommandTester $commandTester;

    public function setUp(): void
    {
        putenv('COLUMNS=120'); // This ensures a consistent output length

        $this->importerStrategyManager = $this->prophesize(ImporterStrategyManagerInterface::class);
        $this->consoleHelperManager = $this->prophesize(ConsoleHelperManagerInterface::class);
        $this->importedLinksProcessor = $this->prophesize(ImportedLinksProcessorInterface::class);
        $this->paramsHelper = $this->prophesize(ParamsConsoleHelperInterface::class);
        $this->importerStrategy = $this->prophesize(ImporterStrategyInterface::class);
        $this->consoleHelperManager->get(Argument::any())->willReturn($this->paramsHelper->reveal());
        $this->importerStrategyManager->get(Argument::any())->willReturn($this->importerStrategy->reveal());

        $command = new ImportCommand(
            $this->importerStrategyManager->reveal(),
            $this->consoleHelperManager->reveal(),
            $this->importedLinksProcessor->reveal(),
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

    /** @test */
    public function dependenciesAreInvokedAsExpected(): void
    {
        $source = ImportSources::BITLY;

        $requestParams = $this->paramsHelper->requestParams(Argument::type(StyleInterface::class))->willReturn([]);
        $import = $this->importerStrategy->import([])->willReturn([]);
        $process = $this->importedLinksProcessor->process([], $source, []);

        $exitCode = $this->commandTester->execute(['source' => $source]);

        self::assertEquals(ImportCommand::SUCCESS, $exitCode);
        $this->consoleHelperManager->get($source)->shouldHaveBeenCalledOnce();
        $this->importerStrategyManager->get($source)->shouldHaveBeenCalledOnce();
        $requestParams->shouldHaveBeenCalledOnce();
        $import->shouldHaveBeenCalledOnce();
        $process->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideImportExceptions
     */
    public function importErrorsAreProperlyHandled(
        ImportException $e,
        int $verbosity,
        array $expectedOutputs,
        array $notExpectedOutputs
    ): void {
        $requestParams = $this->paramsHelper->requestParams(Argument::type(StyleInterface::class))->willReturn([]);
        $import = $this->importerStrategy->import([])->willThrow($e);
        $process = $this->importedLinksProcessor->process(Argument::cetera());

        $exitCode = $this->commandTester->execute(['source' => ImportSources::BITLY], ['verbosity' => $verbosity]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ImportCommand::FAILURE, $exitCode);
        foreach ($expectedOutputs as $expectedOutput) {
            self::assertStringContainsString($expectedOutput, $output);
        }
        foreach ($notExpectedOutputs as $notExpectedOutput) {
            self::assertStringNotContainsString($notExpectedOutput, $output);
        }
        $requestParams->shouldHaveBeenCalledOnce();
        $import->shouldHaveBeenCalledOnce();
        $process->shouldNotHaveBeenCalled();
    }

    public function provideImportExceptions(): iterable
    {
        yield 'no continue token, no verbose' => [
            ImportException::fromError(new RuntimeException('')),
            OutputInterface::VERBOSITY_NORMAL,
            ['[ERROR] An error occurred while importing URLs.'],
            [
                '[WARNING] Not all URLs were properly imported. Try executing this command again, providing "foobar"',
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
            ['[WARNING] Not all URLs were properly imported. Try executing this command again, providing "foobar"'],
        ];
        yield 'continue token, no verbose' => [
            BitlyApiV4Exception::fromInvalidRequest('', 1, '', 'foobar'),
            OutputInterface::VERBOSITY_NORMAL,
            ['[WARNING] Not all URLs were properly imported. Try executing this command again, providing "foobar"'],
            [
                '[ERROR] An error occurred while importing URLs.',
                '[Shlinkio\Shlink\Importer\Exception\BitlyApiV4Exception]',
            ],
        ];
        yield 'continue token, verbose' => [
            BitlyApiV4Exception::fromInvalidRequest('', 1, '', 'foobar'),
            OutputInterface::VERBOSITY_VERBOSE,
            ['[WARNING] Not all URLs were properly imported. Try executing this command again, providing "foobar"'],
            ['[ERROR] An error occurred while importing URLs.'],
        ];
    }
}
