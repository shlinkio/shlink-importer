<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

class ImportedShlinkUrl
{
    private string $longUrl;
    private array $tags;
    private DateTimeInterface $createdAt;
    private ?string $domain;
    private string $shortCode;
    private string $source;

    public function __construct(
        string $source,
        string $longUrl,
        array $tags,
        DateTimeInterface $createdAt,
        ?string $domain,
        string $shortCode
    ) {
        $this->source = $source;
        $this->longUrl = $longUrl;
        $this->tags = $tags;
        $this->createdAt = $createdAt;
        $this->domain = $domain;
        $this->shortCode = $shortCode;
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
}
