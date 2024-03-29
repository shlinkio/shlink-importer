<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Shlink;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Shlink\ShlinkParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class ShlinkParamsConsoleHelperTest extends TestCase
{
    private ShlinkParamsConsoleHelper $helper;
    private MockObject & StyleInterface $io;

    public function setUp(): void
    {
        $this->helper = new ShlinkParamsConsoleHelper();
        $this->io = $this->createMock(StyleInterface::class);
    }

    #[Test]
    public function expectedQuestionsAreAsked(): void
    {
        $this->io->expects($this->exactly(2))->method('ask')->willReturnMap([
            ['What is your Shlink instance base URL?', null, null, 'foo.com'],
            ['What is your Shlink instance API key?', null, null, 'abc-123'],
        ]);
        $this->io->expects($this->exactly(2))->method('confirm')->willReturnMap([
            ['Do you want to import each short URL\'s visits too?', true, true],
            ['Do you want to import orphan visits too?', true, true],
        ]);

        $result = ParamsUtils::invokeCallbacks($this->helper->requestParams($this->io));

        self::assertEquals([
            'base_url' => 'foo.com',
            'api_key' => 'abc-123',
            'import_visits' => true,
            'import_orphan_visits' => true,
        ], $result);
    }
}
