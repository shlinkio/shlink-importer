<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;

interface ImporterStrategyInterface
{
    /**
     * @return ImportedShlinkUrl[]
     * @throws ImportException
     */
    public function import(array $params): iterable;
}
