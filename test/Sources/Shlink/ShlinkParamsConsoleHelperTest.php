<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Shlink;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Shlink\ShlinkParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class ShlinkParamsConsoleHelperTest extends TestCase
{
    use ProphecyTrait;

    private ShlinkParamsConsoleHelper $helper;
    private ObjectProphecy $io;

    public function setUp(): void
    {
        $this->helper = new ShlinkParamsConsoleHelper();
        $this->io = $this->prophesize(StyleInterface::class);
    }

    /** @test */
    public function expectedQuestionsAreAsked(): void
    {
        $askBaseUrl = $this->io->ask('What is your Shlink instance base URL?')->willReturn('foo.com');
        $askApiKey = $this->io->ask('What is your Shlink instance API key?')->willReturn('abc-123');
        $importVisits = $this->io->confirm('Do you want to import each short URL\'s visits too?')->willReturn(true);

        $result = ParamsUtils::invokeCallbacks($this->helper->requestParams($this->io->reveal()));

        self::assertEquals([
            'base_url' => 'foo.com',
            'api_key' => 'abc-123',
            'import_visits' => true,
        ], $result);
        $askBaseUrl->shouldHaveBeenCalledOnce();
        $askApiKey->shouldHaveBeenCalledOnce();
        $importVisits->shouldHaveBeenCalledOnce();
    }
}
