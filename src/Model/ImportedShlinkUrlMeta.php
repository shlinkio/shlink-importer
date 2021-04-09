<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

class ImportedShlinkUrlMeta
{
    private ?DateTimeInterface $validSince;
    private ?DateTimeInterface $validUntil;
    private ?int $maxVisits;

    public function __construct(?DateTimeInterface $validSince, ?DateTimeInterface $validUntil, ?int $maxVisits)
    {
        $this->validSince = $validSince;
        $this->validUntil = $validUntil;
        $this->maxVisits = $maxVisits;
    }

    public static function emptyInstance(): self
    {
        return new self(null, null, null);
    }

    public function validSince(): ?DateTimeInterface
    {
        return $this->validSince;
    }

    public function validUntil(): ?DateTimeInterface
    {
        return $this->validUntil;
    }

    public function maxVisits(): ?int
    {
        return $this->maxVisits;
    }
}
