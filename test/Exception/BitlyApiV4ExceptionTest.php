<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Exception\BitlyApiV4Exception;

class BitlyApiV4ExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideContinueToken
     */
    public function generatesExpectedMessage(?string $continueToken): void
    {
        $e = BitlyApiV4Exception::fromInvalidRequest('something.com', 500, 'Error body', $continueToken);

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
