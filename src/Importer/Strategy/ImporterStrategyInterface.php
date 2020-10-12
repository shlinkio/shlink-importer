<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Importer\Strategy;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ShlinkUrl;

interface ImporterStrategyInterface
{
    /**
     * @return ShlinkUrl[]
     * @throws ImportException
     */
    public function import(array $params): iterable;
}
