<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiException;

class BitlyApiExceptionTest extends TestCase
{
    #[Test, DataProvider('provideContinueToken')]
    public function generatesExpectedMessage(?string $continueToken): void
    {
        $e = BitlyApiException::fromInvalidRequest(
            InvalidRequestException::fromResponseData('something.com', 500, 'Error body'),
            $continueToken,
        );

        self::assertEquals(
            'Request to Bitly API v4 to URL "something.com" failed with status code "500" and body "Error body"',
            $e->getMessage(),
        );
        self::assertEquals($continueToken, $e->continueToken);
    }

    public static function provideContinueToken(): iterable
    {
        yield 'no token' => [null];
        yield 'some token' => ['foobar'];
    }
}
