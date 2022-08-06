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
            Strategy\ImporterStrategyManager::class => static fn (
                ContainerInterface $container,
            ) => new Strategy\ImporterStrategyManager(
                $container,
                $container->get('config')['cli']['importer_strategies'],
            ),
            Params\ConsoleHelper\ConsoleHelperManager::class => static fn (
                ContainerInterface $container,
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
                Sources\Shlink\ShlinkImporter::class => ConfigAbstractFactory::class,
                Sources\Yourls\YourlsImporter::class => ConfigAbstractFactory::class,
                Sources\Kutt\KuttImporter::class => ConfigAbstractFactory::class,
            ],

            'aliases' => [
                Sources\ImportSources::BITLY->value => Sources\Bitly\BitlyApiImporter::class,
                Sources\ImportSources::CSV->value => Sources\Csv\CsvImporter::class,
                Sources\ImportSources::SHLINK->value => Sources\Shlink\ShlinkImporter::class,
                Sources\ImportSources::YOURLS->value => Sources\Yourls\YourlsImporter::class,
                Sources\ImportSources::KUTT->value => Sources\Kutt\KuttImporter::class,
            ],
        ],

        'params_console_helpers' => [
            'factories' => [
                Sources\Bitly\BitlyApiParamsConsoleHelper::class => InvokableFactory::class,
                Sources\Csv\CsvParamsConsoleHelper::class => InvokableFactory::class,
                Sources\Shlink\ShlinkParamsConsoleHelper::class => InvokableFactory::class,
                Sources\Yourls\YourlsParamsConsoleHelper::class => InvokableFactory::class,
                Sources\Kutt\KuttParamsConsoleHelper::class => InvokableFactory::class,
            ],

            'aliases' => [
                Sources\ImportSources::BITLY->value => Sources\Bitly\BitlyApiParamsConsoleHelper::class,
                Sources\ImportSources::CSV->value => Sources\Csv\CsvParamsConsoleHelper::class,
                Sources\ImportSources::SHLINK->value => Sources\Shlink\ShlinkParamsConsoleHelper::class,
                Sources\ImportSources::YOURLS->value => Sources\Yourls\YourlsParamsConsoleHelper::class,
                Sources\ImportSources::KUTT->value => Sources\Kutt\KuttParamsConsoleHelper::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        Http\RestApiConsumer::class => [ClientInterface::class, RequestFactoryInterface::class],

        Sources\Bitly\BitlyApiImporter::class => [Http\RestApiConsumer::class],
        Sources\Shlink\ShlinkImporter::class => [Http\RestApiConsumer::class],
        Sources\Yourls\YourlsImporter::class => [Http\RestApiConsumer::class],
        Sources\Kutt\KuttImporter::class => [Http\RestApiConsumer::class],

        Command\ImportCommand::class => [
            Strategy\ImporterStrategyManager::class,
            Params\ConsoleHelper\ConsoleHelperManager::class,
            ImportedLinksProcessorInterface::class,
        ],
    ],

];
