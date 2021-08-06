<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

class ImportedShlinkUrl
{
    /**
     * @param iterable<ImportedShlinkVisit> $visits
     */
    public function __construct(
        private string $source,
        private string $longUrl,
        private array $tags,
        private DateTimeInterface $createdAt,
        private ?string $domain,
        private string $shortCode,
        private ?string $title,
        private iterable $visits = [],
        private ?int $visitsCount = null,
        private ?ImportedShlinkUrlMeta $meta = null,
    ) {
    }

    public function source(): string
    {
        return $this->source;
    }

    public function longUrl(): string
    {
        return $this->longUrl;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function createdAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }

    public function shortCode(): string
    {
        return $this->shortCode;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    /**
     * @return iterable<ImportedShlinkVisit>
     */
    public function visits(): iterable
    {
        return $this->visits;
    }

    public function visitsCount(): ?int
    {
        return $this->visitsCount;
    }

    public function meta(): ImportedShlinkUrlMeta
    {
        $this->meta = $this->meta ?? ImportedShlinkUrlMeta::emptyInstance();
        return $this->meta;
    }
}
