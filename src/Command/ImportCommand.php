<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Command;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Exception\InvalidSourceException;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ConsoleHelperManagerInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Functional\contains;
use function implode;
use function sprintf;

class ImportCommand extends Command
{
    public const NAME = 'short-url:import';

    private ImporterStrategyManagerInterface $importerStrategyManager;
    private ConsoleHelperManagerInterface $consoleHelperManager;
    private ImportedLinksProcessorInterface $importedLinksProcessor;

    public function __construct(
        ImporterStrategyManagerInterface $importerStrategyManager,
        ConsoleHelperManagerInterface $consoleHelperManager,
        ImportedLinksProcessorInterface $importedLinksProcessor
    ) {
        parent::__construct(null);
        $this->importerStrategyManager = $importerStrategyManager;
        $this->consoleHelperManager = $consoleHelperManager;
        $this->importedLinksProcessor = $importedLinksProcessor;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Allows to import short URLs from third party sources')
            ->addArgument('source', InputArgument::REQUIRED, sprintf(
                'The source from which you want to import. Supported sources: ["%s"]',
                implode('", "', ImportSources::getAll()),
            ));
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $source = $input->getArgument('source');
        $validSources = ImportSources::getAll();

        if ($source !== null && ! contains($validSources, $source)) {
            throw InvalidSourceException::fromInvalidSource($source);
        }

        if ($source === null) {
            $source = (new SymfonyStyle($input, $output))->choice(
                'What is the source you want to import from:',
                $validSources,
            );
            $input->setArgument('source', $source);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('source');

        /** @var ParamsConsoleHelperInterface $paramsHelper */
        $paramsHelper = $this->consoleHelperManager->get($source);
        /** @var ImporterStrategyInterface $importerStrategy */
        $importerStrategy = $this->importerStrategyManager->get($source);

        try {
            $params = $paramsHelper->requestParams($io);
            $links = $importerStrategy->import($params);
            $this->importedLinksProcessor->process($io, $links, $params);
        } catch (ImportException $e) {
            $this->handleImportError($e, $io);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function handleImportError(ImportException $e, SymfonyStyle $io): void
    {
        $continueToken = $e->continueToken();

        if ($continueToken === null) {
            $io->error('An error occurred while importing URLs.');
        } else {
            $io->warning(sprintf(
                'Not all URLs were properly imported. Wait a few minutes, and then try executing this command again, '
                . 'providing "%s" when the "continue token" is requested. That will ensure already processed URLs '
                . 'are skipped.',
                $continueToken,
            ));
        }

        if ($io->isVerbose()) {
            $this->getApplication()->renderThrowable($e, $io);
        }
    }
}
