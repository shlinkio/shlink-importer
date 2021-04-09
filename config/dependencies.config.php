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
            Http\RestApiConsumer::class => ConfigAbstractFactory::class,
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
                Sources\Bitly\BitlyApiImporter::class => ConfigAbstractFactory::class,
                Sources\Csv\CsvImporter::class => InvokableFactory::class,
                Sources\ShlinkApi\ShlinkApiImporter::class => ConfigAbstractFactory::class,
            ],

            'aliases' => [
                Sources\ImportSources::BITLY => Sources\Bitly\BitlyApiImporter::class,
                Sources\ImportSources::CSV => Sources\Csv\CsvImporter::class,
                Sources\ImportSources::SHLINK => Sources\ShlinkApi\ShlinkApiImporter::class,
            ],
        ],

        'params_console_helpers' => [
            'factories' => [
                Sources\Bitly\BitlyApiParamsConsoleHelper::class => InvokableFactory::class,
                Sources\Csv\CsvParamsConsoleHelper::class => InvokableFactory::class,
                Sources\ShlinkApi\ShlinkApiParamsConsoleHelper::class => InvokableFactory::class,
            ],

            'aliases' => [
                Sources\ImportSources::BITLY => Sources\Bitly\BitlyApiParamsConsoleHelper::class,
                Sources\ImportSources::CSV => Sources\Csv\CsvParamsConsoleHelper::class,
                Sources\ImportSources::SHLINK => Sources\ShlinkApi\ShlinkApiParamsConsoleHelper::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        Http\RestApiConsumer::class => [ClientInterface::class, RequestFactoryInterface::class],

        Sources\Bitly\BitlyApiImporter::class => [ClientInterface::class, RequestFactoryInterface::class],
        Sources\ShlinkApi\ShlinkApiImporter::class => [Http\RestApiConsumer::class],

        Command\ImportCommand::class => [
            Strategy\ImporterStrategyManager::class,
            Params\ConsoleHelper\ConsoleHelperManager::class,
            ImportedLinksProcessorInterface::class,
        ],
    ],

];
