<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

return [

    'dependencies' => [
        Strategy\BitlyApiV4Importer::class => ConfigAbstractFactory::class,
    ],

    ConfigAbstractFactory::class => [
        Strategy\BitlyApiV4Importer::class => [ClientInterface::class, RequestFactoryInterface::class],
    ],

];
