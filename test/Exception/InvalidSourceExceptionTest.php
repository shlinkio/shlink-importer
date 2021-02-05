<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Exception;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Exception\InvalidSourceException;

class InvalidSourceExceptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidSources
     */
    public function expectedMessageIsGenerated(string $source, string $expectedMessage): void
    {
        $e = InvalidSourceException::fromInvalidSource($source);
        self::assertEquals($expectedMessage, $e->getMessage());
    }

    public function provideInvalidSources(): iterable
    {
        yield 'foo' => ['foo', 'Provided source "foo" is not valid. Expected one of ["bitly", "csv"]'];
        yield 'bar' => ['bar', 'Provided source "bar" is not valid. Expected one of ["bitly", "csv"]'];
        yield 'baz' => ['baz', 'Provided source "baz" is not valid. Expected one of ["bitly", "csv"]'];
    }
}
