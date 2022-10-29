<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Kutt;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Kutt\KuttParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class KuttParamsConsoleHelperTest extends TestCase
{
    private KuttParamsConsoleHelper $paramsHelper;
    private MockObject & StyleInterface $io;

    public function setUp(): void
    {
        $this->io = $this->createMock(StyleInterface::class);
        $this->paramsHelper = new KuttParamsConsoleHelper();
    }

    /** @test */
    public function expectedQuestionsAreAsked(): void
    {
        $this->io->expects($this->exactly(2))->method('ask')->willReturnMap([
            ['What is your Kutt.it instance base URL?', null, null, 'bar.com'],
            ['What is your Kutt.it instance API key?', null, null, 'def-456'],
        ]);
        $this->io->expects($this->once())->method('confirm')->with(
            'Do you want to import URLs created anonymously?',
            false,
        )->willReturn(true);

        $result = ParamsUtils::invokeCallbacks($this->paramsHelper->requestParams($this->io));

        self::assertEquals([
            'base_url' => 'bar.com',
            'api_key' => 'def-456',
            'import_all_urls' => true,
        ], $result);
    }
}
