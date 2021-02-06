<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Csv;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Sources\Csv\InvalidPathException;

use function sprintf;

class InvalidPathExceptionTest extends TestCase
{
    /** @test */
    public function pathNotProvidedCreatesExceptionAsExpected(): void
    {
        $e = InvalidPathException::pathNotProvided();

        self::assertEquals('The path of the file is required.', $e->getMessage());
    }

    /**
     * @test
     * @dataProvider providePaths
     */
    public function pathIsNotFileCreatesExceptionAsExpected(string $path): void
    {
        $e = InvalidPathException::pathIsNotFile($path);

        self::assertEquals(sprintf('The file "%s" does not seem to exist. Try another one.', $path), $e->getMessage());
    }

    public function providePaths(): iterable
    {
        yield ['/foo'];
        yield ['/bar'];
        yield ['/bar/baz'];
    }
}
