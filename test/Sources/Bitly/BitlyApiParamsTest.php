<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiParams;

class BitlyApiParamsTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideRawParams
     */
    public function rawParamsAreProperlyParsed(array $rawParams, callable $runAssertions): void
    {
        $params = BitlyApiParams::fromRawParams($rawParams);
        $runAssertions($params);
    }

    public function provideRawParams(): iterable
    {
        yield [[
            'access_token' => 'token',
            'import_tags' => true,
            'import_custom_domains' => true,
            'keep_creation_date' => true,
            'ignore_archived' => true,
            'continue_token' => null,
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertTrue($params->importTags());
            self::assertTrue($params->importCustomDomains());
            self::assertTrue($params->keepCreationDate());
            self::assertTrue($params->ignoreArchived());
            self::assertNull($params->continueToken());
        }];
        yield [[
            'access_token' => 'token',
            'import_tags' => false,
            'import_custom_domains' => false,
            'keep_creation_date' => false,
            'ignore_archived' => false,
            'continue_token' => 'foobar',
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertFalse($params->importTags());
            self::assertFalse($params->importCustomDomains());
            self::assertFalse($params->keepCreationDate());
            self::assertFalse($params->ignoreArchived());
            self::assertEquals('foobar', $params->continueToken());
        }];
        yield [['access_token' => 'token'], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertTrue($params->importTags());
            self::assertFalse($params->importCustomDomains());
            self::assertTrue($params->keepCreationDate());
            self::assertFalse($params->ignoreArchived());
            self::assertNull($params->continueToken());
        }];
        yield [[
            'access_token' => 'token',
            'import_tags' => 'not bool',
            'import_custom_domains' => 'not bool',
            'keep_creation_date' => 'not bool',
            'ignore_archived' => 'not bool',
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertTrue($params->importTags());
            self::assertTrue($params->importCustomDomains());
            self::assertTrue($params->keepCreationDate());
            self::assertTrue($params->ignoreArchived());
        }];
        yield [[
            'access_token' => 'token',
            'import_tags' => 0,
            'import_custom_domains' => 0,
            'keep_creation_date' => 0,
            'ignore_archived' => 0,
        ], static function (BitlyApiParams $params): void {
            self::assertEquals('token', $params->accessToken());
            self::assertFalse($params->importTags());
            self::assertFalse($params->importCustomDomains());
            self::assertFalse($params->keepCreationDate());
            self::assertFalse($params->ignoreArchived());
        }];
    }
}
