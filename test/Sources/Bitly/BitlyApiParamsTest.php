<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;

class BitlyApiParamsTest extends TestCase
{
    #[Test, DataProvider('provideRawParams')]
    public function rawParamsAreProperlyParsed(array $rawParams, callable $runAssertions): void
    {
        $params = BitlyApiParams::fromImportParams(ImportSource::BITLY->toParamsWithCallableMap($rawParams));
        $runAssertions($params);
    }

    public static function provideRawParams(): iterable
    {
        yield [
            [
                'access_token' => static fn () => 'token',
                'import_tags' => static fn () => true,
                'import_custom_domains' => static fn () => true,
                'keep_creation_date' => static fn () => true,
                'ignore_archived' => static fn () => true,
                'continue_token' => static fn () => null,
            ],
            static function (BitlyApiParams $params): void {
                self::assertEquals('token', $params->accessToken);
                self::assertTrue($params->importTags);
                self::assertTrue($params->importCustomDomains);
                self::assertTrue($params->keepCreationDate);
                self::assertTrue($params->ignoreArchived);
                self::assertNull($params->continueToken);
            },
        ];
        yield [
            [
                'access_token' => static fn () => 'token',
                'import_tags' => static fn () => false,
                'import_custom_domains' => static fn () => false,
                'keep_creation_date' => static fn () => false,
                'ignore_archived' => static fn () => false,
                'continue_token' => static fn () => 'foobar',
            ],
            static function (BitlyApiParams $params): void {
                self::assertEquals('token', $params->accessToken);
                self::assertFalse($params->importTags);
                self::assertFalse($params->importCustomDomains);
                self::assertFalse($params->keepCreationDate);
                self::assertFalse($params->ignoreArchived);
                self::assertEquals('foobar', $params->continueToken);
            },
        ];
        yield [
            ['access_token' => static fn () => 'token'],
            static function (BitlyApiParams $params): void {
                self::assertEquals('token', $params->accessToken);
                self::assertTrue($params->importTags);
                self::assertFalse($params->importCustomDomains);
                self::assertTrue($params->keepCreationDate);
                self::assertFalse($params->ignoreArchived);
                self::assertNull($params->continueToken);
            },
        ];
        yield [
            [
                'access_token' => static fn () => 'token',
                'import_tags' => static fn () => 'not bool',
                'import_custom_domains' => static fn () => 'not bool',
                'keep_creation_date' => static fn () => 'not bool',
                'ignore_archived' => static fn () => 'not bool',
            ],
            static function (BitlyApiParams $params): void {
                self::assertEquals('token', $params->accessToken);
                self::assertTrue($params->importTags);
                self::assertTrue($params->importCustomDomains);
                self::assertTrue($params->keepCreationDate);
                self::assertTrue($params->ignoreArchived);
            },
        ];
        yield [
            [
                'access_token' => static fn () => 'token',
                'import_tags' => static fn () => 0,
                'import_custom_domains' => static fn () => 0,
                'keep_creation_date' => static fn () => 0,
                'ignore_archived' => static fn () => 0,
            ],
            static function (BitlyApiParams $params): void {
                self::assertEquals('token', $params->accessToken);
                self::assertFalse($params->importTags);
                self::assertFalse($params->importCustomDomains);
                self::assertFalse($params->keepCreationDate);
                self::assertFalse($params->ignoreArchived);
            },
        ];
    }
}
