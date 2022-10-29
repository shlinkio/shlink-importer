<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Yourls;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Yourls\YourlsParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class YourlsParamsConsoleHelperTest extends TestCase
{
    private YourlsParamsConsoleHelper $helper;
    private MockObject & StyleInterface $io;

    public function setUp(): void
    {
        $this->helper = new YourlsParamsConsoleHelper();
        $this->io = $this->createMock(StyleInterface::class);
    }

    /** @test */
    public function expectedQuestionsAreAsked(): void
    {
        $this->io->expects($this->exactly(4))->method('ask')->willReturnMap([
            ['What is your YOURLS instance base URL?', null, null, 'foo.com'],
            ['What is your YOURLS instance username?', null, null, 'user'],
            ['What is your YOURLS instance password?', null, null, 'pass'],
            [
                'To what domain do you want the URLs to be linked? (leave empty to link them to default domain)',
                null,
                null,
                'domain',
            ],
        ]);
        $this->io->expects($this->once())->method('confirm')->with(
            'Do you want to import each short URL\'s visits too?',
        )->willReturn(true);

        $result = ParamsUtils::invokeCallbacks($this->helper->requestParams($this->io));

        self::assertEquals([
            'base_url' => 'foo.com',
            'username' => 'user',
            'password' => 'pass',
            'import_visits' => true,
            'domain' => 'domain',
        ], $result);
    }
}
