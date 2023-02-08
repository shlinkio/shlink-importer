<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ImportException;

class ImportExceptionTest extends TestCase
{
    #[Test]
    public function fromErrorCreatesExceptionWithPrevious(): void
    {
        $prev = new RuntimeException('Some error');
        $e = ImportException::fromError($prev);

        self::assertEquals('An error occurred while importing URLs', $e->getMessage());
        self::assertEquals($prev, $e->getPrevious());
        self::assertEquals(-1, $e->getCode());
        self::assertNull($e->continueToken);
    }
}
