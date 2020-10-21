<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Command\ImportCommand;
use Shlinkio\Shlink\Importer\Exception\InvalidSourceException;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ConsoleHelperManagerInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyManagerInterface;
use Shlinkio\Shlink\Importer\Strategy\ImportSources;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $importerStrategyManager;
    private ObjectProphecy $consoleHelperManager;
    private ObjectProphecy $importedLinksProcessor;
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $this->importerStrategyManager = $this->prophesize(ImporterStrategyManagerInterface::class);
        $this->consoleHelperManager = $this->prophesize(ConsoleHelperManagerInterface::class);
        $this->importedLinksProcessor = $this->prophesize(ImportedLinksProcessorInterface::class);

        $command = new ImportCommand(
            $this->importerStrategyManager->reveal(),
            $this->consoleHelperManager->reveal(),
            $this->importedLinksProcessor->reveal(),
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function dependenciesAreInvokedAsExpected(): void
    {
        $source = ImportSources::BITLY;

        $paramsHelper = $this->prophesize(ParamsConsoleHelperInterface::class);
        $importerStrategy = $this->prophesize(ImporterStrategyInterface::class);

        $getParamsHelper = $this->consoleHelperManager->get($source)->willReturn($paramsHelper->reveal());
        $getStrategy = $this->importerStrategyManager->get($source)->willReturn($importerStrategy->reveal());
        $requestParams = $paramsHelper->requestParams(Argument::type(StyleInterface::class))->willReturn([]);
        $import = $importerStrategy->import([])->willReturn([]);
        $process = $this->importedLinksProcessor->process([], $source, []);

        $this->commandTester->execute(['source' => $source]);

        $getParamsHelper->shouldHaveBeenCalledOnce();
        $getStrategy->shouldHaveBeenCalledOnce();
        $requestParams->shouldHaveBeenCalledOnce();
        $import->shouldHaveBeenCalledOnce();
        $process->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function exceptionIsThrownWhenInvalidSourceIsProvided(): void
    {
        $this->expectException(InvalidSourceException::class);

        $this->commandTester->execute(['source' => 'invalid']);
    }
}
