<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;

use function get_debug_type;
use function sprintf;

/**
 * @extends AbstractPluginManager<ImporterStrategyInterface>
 * @todo Extend from AbstractSingleInstancePluginManager once servicemanager 3 is no longer supported
 */
class ImporterStrategyManager extends AbstractPluginManager implements ImporterStrategyManagerInterface
{
    /** @var class-string<ImporterStrategyInterface> */
    protected $instanceOf = ImporterStrategyInterface::class; // phpcs:ignore

    public function validate(mixed $instance): void
    {
        if ($instance instanceof $this->instanceOf) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
            static::class,
            $this->instanceOf,
            get_debug_type($instance),
        ));
    }
}
