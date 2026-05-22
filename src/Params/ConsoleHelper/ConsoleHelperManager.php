<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Params\ConsoleHelper;

use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;

use function get_debug_type;
use function sprintf;

/**
 * @extends AbstractSingleInstancePluginManager<ParamsConsoleHelperInterface>
 */
class ConsoleHelperManager extends AbstractSingleInstancePluginManager implements ConsoleHelperManagerInterface
{
    /** @var class-string<ParamsConsoleHelperInterface> */
    protected string $instanceOf = ParamsConsoleHelperInterface::class;

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
