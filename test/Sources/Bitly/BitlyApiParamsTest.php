<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Params\CommonParams;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiParams;

class BitlyApiParamsTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideRawParams
     */
    public function rawParamsAreProperlyParsed(array $rawParams, callable $runAssertions): void
    {
        $params = BitlyApiParams::fromRawParams(CommonParams::fromSourceAndCallableMap('', $rawParams));
        $runAssertions($params);
    }

    public function provideRawParams(): iterable
    {
        yield [[
            'access_token' => fn () => 'token',
            'import_tags' => fn () => true,
            'import_custom_domains' => fn () => true,
            'keep_creation_date' => fn () => true,
            'ignore_archived' => fn () => true,
            'continue_token' => fn () => null,
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertTrue($params->importTags());
            self::assertTrue($params->importCustomDomains());
            self::assertTrue($params->keepCreationDate());
            self::assertTrue($params->ignoreArchived());
            self::assertNull($params->continueToken());
        }];
        yield [[
            'access_token' => fn () => 'token',
            'import_tags' => fn () => false,
            'import_custom_domains' => fn () => false,
            'keep_creation_date' => fn () => false,
            'ignore_archived' => fn () => false,
            'continue_token' => fn () => 'foobar',
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertFalse($params->importTags());
            self::assertFalse($params->importCustomDomains());
            self::assertFalse($params->keepCreationDate());
            self::assertFalse($params->ignoreArchived());
            self::assertEquals('foobar', $params->continueToken());
        }];
        yield [['access_token' => fn () => 'token'], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertTrue($params->importTags());
            self::assertFalse($params->importCustomDomains());
            self::assertTrue($params->keepCreationDate());
            self::assertFalse($params->ignoreArchived());
            self::assertNull($params->continueToken());
        }];
        yield [[
            'access_token' => fn () => 'token',
            'import_tags' => fn () => 'not bool',
            'import_custom_domains' => fn () => 'not bool',
            'keep_creation_date' => fn () => 'not bool',
            'ignore_archived' => fn () => 'not bool',
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertTrue($params->importTags());
            self::assertTrue($params->importCustomDomains());
            self::assertTrue($params->keepCreationDate());
            self::assertTrue($params->ignoreArchived());
        }];
        yield [[
            'access_token' => fn () => 'token',
            'import_tags' => fn () => 0,
            'import_custom_domains' => fn () => 0,
            'keep_creation_date' => fn () => 0,
            'ignore_archived' => fn () => 0,
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertFalse($params->importTags());
            self::assertFalse($params->importCustomDomains());
            self::assertFalse($params->keepCreationDate());
            self::assertFalse($params->ignoreArchived());
        }];
    }
}
