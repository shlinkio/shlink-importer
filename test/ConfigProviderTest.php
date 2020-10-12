<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    public function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    /** @test */
    public function configIsReturnedAsExpected(): void
    {
        $config = ($this->provider)();

        self::assertCount(2, $config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey(ConfigAbstractFactory::class, $config);
    }
}
