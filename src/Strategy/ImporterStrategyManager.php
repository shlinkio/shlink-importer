<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;

use function get_debug_type;
use function sprintf;

/**
 * @extends AbstractSingleInstancePluginManager<ImporterStrategyInterface>
 */
class ImporterStrategyManager extends AbstractSingleInstancePluginManager implements ImporterStrategyManagerInterface
{
    /** @var class-string<ImporterStrategyInterface> */
    protected string $instanceOf = ImporterStrategyInterface::class;

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
