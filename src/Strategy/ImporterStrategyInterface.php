<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;

interface ImporterStrategyInterface
{
    /**
     * @throws ImportException
     */
    public function import(ImportParams $importParams): ImportResult;
}
