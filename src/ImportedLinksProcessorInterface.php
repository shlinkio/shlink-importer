<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Symfony\Component\Console\Style\StyleInterface;

interface ImportedLinksProcessorInterface
{
    public function process(StyleInterface $io, ImportResult $result, ImportParams $params): void;
}
