<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Params\ConsoleHelper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Params\ConsoleHelper\BitlyApiV4ParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

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
    public function generatesExpectedParams(string $accessToken, array $responses, array $expected): void
    {
        $this->io->ask(Argument::cetera())->willReturn($accessToken);
        $this->io->confirm(Argument::cetera())->willReturn(...$responses);

        self::assertEquals($expected, $this->paramsHelper->requestParams($this->io->reveal()));
    }

    public function provideResponses(): iterable
    {
        yield [
            $accessToken = 'foobar',
            [true, true, true, true, true],
            [
                'access_token' => $accessToken,
                'import_short_codes' => true,
                'import_tags' => true,
                'import_custom_domains' => true,
                'keep_creation_date' => true,
                'ignore_archived' => true,
            ],
        ];
        yield [
            $accessToken = 'barfoo',
            [false, false, false, false, false],
            [
                'access_token' => $accessToken,
                'import_short_codes' => false,
                'import_tags' => false,
                'import_custom_domains' => false,
                'keep_creation_date' => false,
                'ignore_archived' => false,
            ],
        ];
        yield [
            $accessToken = 'accesstoken',
            [false, true, false, true, false],
            [
                'access_token' => $accessToken,
                'import_short_codes' => false,
                'import_tags' => true,
                'import_custom_domains' => false,
                'keep_creation_date' => true,
                'ignore_archived' => false,
            ],
        ];
        yield [
            $accessToken = 'something',
            [true, true, true, false, false],
            [
                'access_token' => $accessToken,
                'import_short_codes' => true,
                'import_tags' => true,
                'import_custom_domains' => true,
                'keep_creation_date' => false,
                'ignore_archived' => false,
            ],
        ];
    }
}
