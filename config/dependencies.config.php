<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

return [

    'dependencies' => [
        'factories' => [
            Command\ImportCommand::class => ConfigAbstractFactory::class,
            Strategy\ImporterStrategyManager::class => fn (
                ContainerInterface $container
            ) => new Strategy\ImporterStrategyManager(
                $container,
                $container->get('config')['cli']['importer_strategies'],
            ),
            Params\ConsoleHelper\ConsoleHelperManager::class => fn (
                ContainerInterface $container
            ) => new Params\ConsoleHelper\ConsoleHelperManager(
                $container,
                $container->get('config')['cli']['params_console_helpers'],
            ),
        ],
    ],

    'cli' => [
        'importer_strategies' => [
            'factories' => [
                Strategy\BitlyApiV4Importer::class => ConfigAbstractFactory::class,
            ],

            'aliases' => [
                Strategy\ImportSources::BITLY => Strategy\BitlyApiV4Importer::class,
            ],
        ],

        'params_console_helpers' => [
            'factories' => [
                Params\ConsoleHelper\BitlyApiV4ParamsConsoleHelper::class => InvokableFactory::class,
            ],

            'aliases' => [
                Strategy\ImportSources::BITLY => Params\ConsoleHelper\BitlyApiV4ParamsConsoleHelper::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        Strategy\BitlyApiV4Importer::class => [ClientInterface::class, RequestFactoryInterface::class],
        Command\ImportCommand::class => [
            Strategy\ImporterStrategyManager::class,
            Params\ConsoleHelper\ConsoleHelperManager::class,
            ImportedLinksProcessorInterface::class,
        ],
    ],

];
