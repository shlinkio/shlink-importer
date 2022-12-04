<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Command;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Exception\InvalidSourceException;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ConsoleHelperManagerInterface;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\ParamsConsoleHelperInterface;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function implode;
use function sprintf;

class ImportCommand extends Command
{
    public const NAME = 'short-url:import';

    private array $validSources;

    public function __construct(
        private readonly ImporterStrategyManagerInterface $importerStrategyManager,
        private readonly ConsoleHelperManagerInterface $consoleHelperManager,
        private readonly ImportedLinksProcessorInterface $importedLinksProcessor,
    ) {
        $this->validSources = ImportSource::values();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Allows to import short URLs from third party sources')
            ->addArgument('source', InputArgument::REQUIRED, sprintf(
                'The source from which you want to import. Supported sources: [<info>%s</info>]',
                implode('</info>, <info>', $this->validSources),
            ));
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $source = $input->getArgument('source');

        if ($source === null) {
            $source = (new SymfonyStyle($input, $output))->choice(
                'What is the source you want to import from:',
                $this->validSources,
            );
            $input->setArgument('source', $source);
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $source = $input->getArgument('source');
        if ($source !== null && ImportSource::tryFrom($source) === null) {
            throw InvalidSourceException::fromInvalidSource($source);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $source = ImportSource::from($input->getArgument('source'));

        /** @var ParamsConsoleHelperInterface $paramsHelper */
        $paramsHelper = $this->consoleHelperManager->get($source->value);
        /** @var ImporterStrategyInterface $importerStrategy */
        $importerStrategy = $this->importerStrategyManager->get($source->value);

        try {
            $params = $source->toParamsWithCallableMap($paramsHelper->requestParams($io));
            $result = $importerStrategy->import($params);
            $this->importedLinksProcessor->process($io, $result, $params);
        } catch (ImportException $e) {
            $this->handleImportError($e, $io);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function handleImportError(ImportException $e, SymfonyStyle $io): void
    {
        $continueToken = $e->continueToken;

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

        $app = $this->getApplication();
        if ($app !== null && $io->isVerbose()) {
            $app->renderThrowable($e, $io);
        }
    }
}
