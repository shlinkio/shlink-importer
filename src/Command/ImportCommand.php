<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Command;

use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ConsoleHelperManagerInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyManagerInterface;
use Shlinkio\Shlink\Importer\Strategy\ImportSources;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function implode;
use function sprintf;

class ImportCommand extends Command
{
    public const NAME = 'short-urls:import';

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

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('source');

        /** @var ParamsConsoleHelperInterface $paramsHelper */
        $paramsHelper = $this->consoleHelperManager->get($source);
        /** @var ImporterStrategyInterface $importerStrategy */
        $importerStrategy = $this->importerStrategyManager->get($source);

        $params = $paramsHelper->requestParams($io);
        $links = $importerStrategy->import($params);
        $this->importedLinksProcessor->process($links, $source, $params);

        return self::SUCCESS;
    }
}
