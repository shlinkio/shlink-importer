<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use DateInterval;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiParams;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiProgressTracker;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Util\DateHelper;

use function base64_encode;

class BitlyApiProgressTrackerTest extends TestCase
{
    /** @test */
    public function expectedContinueTokenIsGenerated(): void
    {
        $tracker = BitlyApiProgressTracker::initFromParams(BitlyApiParams::fromImportParams(
            ImportSource::BITLY->toParamsWithCallableMap(['access_token' => fn () => '']),
        ));
        self::assertNull($tracker->generateContinueToken());

        $tracker->updateLastProcessedGroup('foobar');
        self::assertEquals(base64_encode('foobar'), $tracker->generateContinueToken());

        $date = '2020-05-01T20:00:00+0000';
        $tracker->updateLastProcessedUrlDate($date);
        self::assertEquals(
            base64_encode('foobar__' . DateHelper::dateFromAtom($date)->sub(new DateInterval('PT1S'))->format('U')),
            $tracker->generateContinueToken(),
        );
    }

    /** @test */
    public function initializesWithProvidedToken(): void
    {
        $tracker = BitlyApiProgressTracker::initFromParams(BitlyApiParams::fromImportParams(
            ImportSource::BITLY->toParamsWithCallableMap([
                'access_token' => fn () => '',
                'continue_token' => fn () => base64_encode('abc123__1603378130'),
            ]),
        ));

        self::assertEquals('abc123', $tracker->initialGroup());
        self::assertEquals('1603378130', $tracker->createdBefore());
    }
}
