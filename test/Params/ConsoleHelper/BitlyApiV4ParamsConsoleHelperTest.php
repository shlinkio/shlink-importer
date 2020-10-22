<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Params\ConsoleHelper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\BitlyApiV4ParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

use function count;

class BitlyApiV4ParamsConsoleHelperTest extends TestCase
{
    use ProphecyTrait;

    private BitlyApiV4ParamsConsoleHelper $paramsHelper;
    private ObjectProphecy $io;

    public function setUp(): void
    {
        $this->paramsHelper = new BitlyApiV4ParamsConsoleHelper();
        $this->io = $this->prophesize(StyleInterface::class);
    }

    /**
     * @test
     * @dataProvider provideResponses
     */
    public function generatesExpectedParams(array $askResponses, array $confirmResponses, array $expected): void
    {
        $ask = $this->io->ask(Argument::cetera())->willReturn(...$askResponses);
        $confirm = $this->io->confirm(Argument::cetera())->willReturn(...$confirmResponses);

        self::assertEquals($expected, $this->paramsHelper->requestParams($this->io->reveal()));
        $ask->shouldHaveBeenCalledTimes(count($askResponses));
        $confirm->shouldHaveBeenCalledTimes(count($confirmResponses));
    }

    public function provideResponses(): iterable
    {
        yield [
            ['foobar', null],
            [true, true, true, true, true],
            [
                'access_token' => 'foobar',
                'import_short_codes' => true,
                'import_tags' => true,
                'import_custom_domains' => true,
                'keep_creation_date' => true,
                'ignore_archived' => true,
                'continue_token' => null,
            ],
        ];
        yield [
            ['barfoo', null],
            [false, false, false, false, false],
            [
                'access_token' => 'barfoo',
                'import_short_codes' => false,
                'import_tags' => false,
                'import_custom_domains' => false,
                'keep_creation_date' => false,
                'ignore_archived' => false,
                'continue_token' => null,
            ],
        ];
        yield [
            ['accesstoken', 'continue_from_here'],
            [false, true, false, true, false],
            [
                'access_token' => 'accesstoken',
                'import_short_codes' => false,
                'import_tags' => true,
                'import_custom_domains' => false,
                'keep_creation_date' => true,
                'ignore_archived' => false,
                'continue_token' => 'continue_from_here',
            ],
        ];
        yield [
            ['something', 'token'],
            [true, true, true, false, false],
            [
                'access_token' => 'something',
                'import_short_codes' => true,
                'import_tags' => true,
                'import_custom_domains' => true,
                'keep_creation_date' => false,
                'ignore_archived' => false,
                'continue_token' => 'token',
            ],
        ];
    }
}
