<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiException;

class BitlyApiExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideContinueToken
     */
    public function generatesExpectedMessage(?string $continueToken): void
    {
        $e = BitlyApiException::fromInvalidRequest('something.com', 500, 'Error body', $continueToken);

        self::assertEquals(
            'Request to Bitly API v4 to URL "something.com" failed with status code "500" and body "Error body"',
            $e->getMessage(),
        );
        self::assertEquals($continueToken, $e->continueToken());
    }

    public function provideContinueToken(): iterable
    {
        yield 'no token' => [null];
        yield 'some token' => ['foobar'];
    }
}
