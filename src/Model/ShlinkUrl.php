<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Model;

use DateTimeInterface;

final class ShlinkUrl
{
    private string $longUrl;
    private array $tags;
    private DateTimeInterface $createdAt;
    private ?string $domain;
    private ?string $shortCode;

    public function __construct(
        string $longUrl,
        array $tags,
        DateTimeInterface $createdAt,
        ?string $domain,
        ?string $shortCode
    ) {
        $this->longUrl = $longUrl;
        $this->tags = $tags;
        $this->createdAt = $createdAt;
        $this->domain = $domain;
        $this->shortCode = $shortCode;
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

    public function shortCode(): ?string
    {
        return $this->shortCode;
    }
}
