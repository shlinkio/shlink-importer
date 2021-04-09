<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Http;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;

class InvalidRequestExceptionTest extends TestCase
{
    /** @test */
    public function exceptionIsCreatedAsExpected(): void
    {
        $url = 'foo.com';
        $statusCode = 403;
        $body = 'the body';

        $e = InvalidRequestException::fromResponseData($url, $statusCode, $body);

        self::assertEquals('Request to foo.com failed with status code 403', $e->getMessage());
        self::assertEquals($url, $e->url());
        self::assertEquals($statusCode, $e->statusCode());
        self::assertEquals($body, $e->body());
    }
}
