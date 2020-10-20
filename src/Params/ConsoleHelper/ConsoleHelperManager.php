<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params\ConsoleHelper;

use Laminas\ServiceManager\AbstractPluginManager;

class ConsoleHelperManager extends AbstractPluginManager implements ConsoleHelperManagerInterface
{
    protected $instanceOf = ParamsConsoleHelperInterface::class; // phpcs:ignore
}
