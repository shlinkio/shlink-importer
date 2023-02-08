<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    public function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    #[Test]
    public function configIsReturnedAsExpected(): void
    {
        $config = ($this->provider)();

        self::assertCount(3, $config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey(ConfigAbstractFactory::class, $config);
        self::assertArrayHasKey('cli', $config);

        self::assertCount(3, $config['cli']);
        self::assertArrayHasKey('commands', $config['cli']);
        self::assertArrayHasKey('importer_strategies', $config['cli']);
        self::assertArrayHasKey('params_console_helpers', $config['cli']);
    }
}
