<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Params\ParamsUtils;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiParamsConsoleHelper;
use Symfony\Component\Console\Style\StyleInterface;

use function count;

class BitlyApiParamsConsoleHelperTest extends TestCase
{
    private BitlyApiParamsConsoleHelper $paramsHelper;
    private MockObject & StyleInterface $io;

    public function setUp(): void
    {
        $this->paramsHelper = new BitlyApiParamsConsoleHelper();
        $this->io = $this->createMock(StyleInterface::class);
    }

    /**
     * @param array<int, mixed> $askResponses
     * @param array<int, mixed> $confirmResponses
     */
    #[Test, DataProvider('provideResponses')]
    public function generatesExpectedParams(array $askResponses, array $confirmResponses, array $expected): void
    {
        $this->io->expects($this->exactly(count($askResponses)))->method('ask')->willReturnOnConsecutiveCalls(
            ...$askResponses,
        );
        $this->io->expects($this->exactly(count($confirmResponses)))->method('confirm')->willReturnOnConsecutiveCalls(
            ...$confirmResponses,
        );

        self::assertEquals(
            $expected,
            ParamsUtils::invokeCallbacks($this->paramsHelper->requestParams($this->io)),
        );
    }

    public static function provideResponses(): iterable
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
