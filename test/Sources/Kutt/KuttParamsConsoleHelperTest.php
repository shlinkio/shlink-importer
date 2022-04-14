<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Kutt;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Kutt\KuttParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class KuttParamsConsoleHelperTest extends TestCase
{
    use ProphecyTrait;

    private KuttParamsConsoleHelper $paramsHelper;
    private ObjectProphecy $io;

    public function setUp(): void
    {
        $this->io = $this->prophesize(StyleInterface::class);
        $this->paramsHelper = new KuttParamsConsoleHelper();
    }

    /** @test */
    public function expectedQuestionsAreAsked(): void
    {
        $askBaseUrl = $this->io->ask('What is your Kutt.it instance base URL?')->willReturn('bar.com');
        $askApiKey = $this->io->ask('What is your Kutt.it instance API key?')->willReturn('def-456');
        $importVisits = $this->io->confirm('Do you want to import each short URL\'s visits too?')->willReturn(false);
        $importAllUrls = $this->io->confirm('Do you want to import URLs created anonymously too?', false)->willReturn(
            true,
        );

        $result = ParamsUtils::invokeCallbacks($this->paramsHelper->requestParams($this->io->reveal()));

        self::assertEquals([
            'base_url' => 'bar.com',
            'api_key' => 'def-456',
            'import_visits' => false,
            'import_all_urls' => true,
        ], $result);
        $askBaseUrl->shouldHaveBeenCalledOnce();
        $askApiKey->shouldHaveBeenCalledOnce();
        $importVisits->shouldHaveBeenCalledOnce();
        $importAllUrls->shouldHaveBeenCalledOnce();
    }
}
