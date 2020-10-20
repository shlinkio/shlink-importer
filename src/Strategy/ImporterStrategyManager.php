<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use Laminas\ServiceManager\AbstractPluginManager;

class ImporterStrategyManager extends AbstractPluginManager implements ImporterStrategyManagerInterface
{
    protected $instanceOf = ImporterStrategyInterface::class; // phpcs:ignore
}
