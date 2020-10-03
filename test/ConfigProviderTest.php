<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer;

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
        self::assertEmpty(($this->provider)());
    }
}
