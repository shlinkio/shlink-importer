<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Bitly;

use DateInterval;
use DateTimeImmutable;
use Shlinkio\Shlink\Importer\Util\DateHelper;

use function base64_decode;
use function base64_encode;
use function explode;
use function sprintf;

final class BitlyApiProgressTracker
{
    private const SEPARATOR = '__';

    private ?string $lastProcessedGroup = null;
    private ?string $lastProcessedUrlDate = null;
    private array $originalDecodedTokenParts = [];
    private DateTimeImmutable $startDate;

    private function __construct()
    {
        $this->startDate = new DateTimeImmutable();
    }

    public static function initFromParams(BitlyApiParams $params): self
    {
        $providedContinueToken = $params->continueToken;
        $instance = new self();
        if ($providedContinueToken === null) {
            return $instance;
        }

        $instance->originalDecodedTokenParts = explode(self::SEPARATOR, base64_decode($providedContinueToken));
        return $instance;
    }

    public function initialGroup(): ?string
    {
        return $this->originalDecodedTokenParts[0] ?? null;
    }

    public function createdBefore(): string
    {
        return $this->originalDecodedTokenParts[1] ?? '';
    }

    public function updateLastProcessedGroup(string $groupId): void
    {
        $this->lastProcessedGroup = $groupId;
    }

    public function updateLastProcessedUrlDate(string $atomDate): void
    {
        $this->lastProcessedUrlDate = $atomDate;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function generateContinueToken(): ?string
    {
        if ($this->lastProcessedGroup === null) {
            return null;
        }

        if ($this->lastProcessedUrlDate === null) {
            return base64_encode($this->lastProcessedGroup);
        }

        // Generate the timestamp corresponding to 1 second before the last processed URL
        $createdBefore = DateHelper::dateFromAtom($this->lastProcessedUrlDate)->sub(new DateInterval('PT1S'))->format(
            'U',
        );

        return base64_encode(sprintf('%s%s%s', $this->lastProcessedGroup, self::SEPARATOR, $createdBefore));
    }
}
