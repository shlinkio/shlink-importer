<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateInterval;
use DateTimeImmutable;
use Shlinkio\Shlink\Importer\Util\DateHelpersTrait;

use function base64_encode;
use function sprintf;

final class BitlyApiProgressTracker
{
    use DateHelpersTrait;

    private ?string $lastProcessedGroup = null;
    private ?string $lastProcessedUrlDate = null;
    private DateTimeImmutable $startDate;

    public function __construct()
    {
        $this->startDate = new DateTimeImmutable();
    }

    public function updateLastProcessedGroup(string $groupId): void
    {
        $this->lastProcessedGroup = $groupId;
    }

    public function updateLastProcessedUrlDate(string $date): void
    {
        $this->lastProcessedUrlDate = $date;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function generateContinueToken(): ?string
    {
        if ($this->lastProcessedGroup === null || $this->lastProcessedUrlDate === null) {
            return $this->lastProcessedGroup;
        }

        // Generate the timestamp corresponding to 1 second before the last processed URL
        $createdBefore = $this->dateFromAtom($this->lastProcessedUrlDate)->sub(new DateInterval('PT1S'))->format('U');

        return base64_encode(sprintf('%s__%s', $this->lastProcessedGroup, $createdBefore));
    }
}
