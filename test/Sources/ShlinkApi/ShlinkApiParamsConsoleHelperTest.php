<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\ShlinkApi;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Sources\ShlinkApi\ShlinkApiParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class ShlinkApiParamsConsoleHelperTest extends TestCase
{
    use ProphecyTrait;

    private ShlinkApiParamsConsoleHelper $helper;
    private ObjectProphecy $io;

    public function setUp(): void
    {
        $this->helper = new ShlinkApiParamsConsoleHelper();
        $this->io = $this->prophesize(StyleInterface::class);
    }

    /** @test */
    public function expectedQuestionsAreAsked(): void
    {
        $askBaseUrl = $this->io->ask('What is your Shlink instance base URL?')->willReturn('foo.com');
        $askApiKey = $this->io->ask('What is your Shlink instance API key?')->willReturn('abc-123');

        $result = $this->helper->requestParams($this->io->reveal());

        self::assertEquals([
            'import_short_codes' => true,
            'base_url' => 'foo.com',
            'api_key' => 'abc-123',
        ], $result);
        $askBaseUrl->shouldHaveBeenCalledOnce();
        $askApiKey->shouldHaveBeenCalledOnce();
    }
}
