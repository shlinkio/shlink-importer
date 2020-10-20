<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Exception\BitlyApiV4Exception;

class BitlyApiV4ExceptionTest extends TestCase
{
    /** @test */
    public function generatesExpectedMessage(): void
    {
        $e = BitlyApiV4Exception::fromInvalidRequest('something.com', 500, 'Error body');
        self::assertEquals(
            'Request to Bitly API v4 to URL "something.com" failed with status code "500" and body "Error body"',
            $e->getMessage(),
        );
    }
}
