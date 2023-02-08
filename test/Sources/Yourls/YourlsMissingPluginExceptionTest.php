<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Yourls;

use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Sources\Yourls\YourlsMissingPluginException;
use Throwable;

class YourlsMissingPluginExceptionTest extends TestCase
{
    #[Test, DataProvider('providePrevious')]
    public function createsExceptionProperly(Throwable $prev): void
    {
        $e = YourlsMissingPluginException::forMissingPlugin($prev);

        self::assertEquals(
            'The YOURLS instance from where you are trying to import links, does not have the '
            . '"yourls-shlink-import-plugin" installed, or it is not enabled. Go to https://slnk.to/yourls-import '
            . 'and follow the installation instructions, then try to import again.',
            $e->getMessage(),
        );
        self::assertSame($e->getPrevious(), $prev);
        self::assertSame($e->getCode(), $prev->getCode());
    }

    public static function providePrevious(): iterable
    {
        yield [new RuntimeException('', -3)];
        yield [new DomainException('', 33)];
        yield [ImportException::fromError(new Exception('', 5))];
    }
}
