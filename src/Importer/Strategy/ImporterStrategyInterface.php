<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Importer\Strategy;

use Shlinkio\Shlink\Importer\Model\ShlinkUrl;

interface ImporterStrategyInterface
{
    /**
     * @return ShlinkUrl[]
     */
    public function import(): iterable;
}
