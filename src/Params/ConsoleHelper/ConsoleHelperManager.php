<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params\ConsoleHelper;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * @extends AbstractPluginManager<ParamsConsoleHelperInterface>
 */
class ConsoleHelperManager extends AbstractPluginManager implements ConsoleHelperManagerInterface
{
    /** @var class-string<ParamsConsoleHelperInterface> */
    protected $instanceOf = ParamsConsoleHelperInterface::class; // phpcs:ignore
}
