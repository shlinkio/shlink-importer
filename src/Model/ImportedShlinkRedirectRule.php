<?php

namespace Shlinkio\Shlink\Importer\Model;

final readonly class ImportedShlinkRedirectRule
{
    /**
     * @param ImportedShlinkRedirectCondition[] $conditions
     */
    public function __construct(public string $longUrl, public array $conditions)
    {
    }
}
