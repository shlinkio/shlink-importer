<?php

namespace Shlinkio\Shlink\Importer\Model;

final readonly class ImportedShlinkRedirectCondition
{
    public function __construct(public string $type, public string $matchValue, public string|null $matchKey = null)
    {
    }
}
