<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * @extends AbstractPluginManager<ImporterStrategyInterface>
 */
class ImporterStrategyManager extends AbstractPluginManager implements ImporterStrategyManagerInterface
{
    /** @var class-string<ImporterStrategyInterface> */
    protected $instanceOf = ImporterStrategyInterface::class; // phpcs:ignore
}
