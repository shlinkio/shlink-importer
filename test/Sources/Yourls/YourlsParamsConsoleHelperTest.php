<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Yourls;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Sources\Yourls\YourlsParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

class YourlsParamsConsoleHelperTest extends TestCase
{
    use ProphecyTrait;

    private YourlsParamsConsoleHelper $helper;
    private ObjectProphecy $io;

    public function setUp(): void
    {
        $this->helper = new YourlsParamsConsoleHelper();
        $this->io = $this->prophesize(StyleInterface::class);
    }

    /** @test */
    public function expectedQuestionsAreAsked(): void
    {
        $askBaseUrl = $this->io->ask('What is your YOURLS instance base URL?')->willReturn('foo.com');
        $askUser = $this->io->ask('What is your YOURLS instance username?')->willReturn('user');
        $askPassword = $this->io->ask('What is your YOURLS instance password?')->willReturn('pass');
        $importVisits = $this->io->confirm('Do you want to import each short URL\'s visits too?')->willReturn(true);

        $result = $this->helper->requestParams($this->io->reveal());

        self::assertEquals([
            'base_url' => 'foo.com',
            'username' => 'user',
            'password' => 'pass',
            'import_visits' => true,
        ], $result);
        $askBaseUrl->shouldHaveBeenCalledOnce();
        $askUser->shouldHaveBeenCalledOnce();
        $askPassword->shouldHaveBeenCalledOnce();
        $importVisits->shouldHaveBeenCalledOnce();
    }
}
