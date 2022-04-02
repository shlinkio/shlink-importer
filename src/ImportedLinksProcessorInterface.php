<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Params\CommonParams;
use Symfony\Component\Console\Style\StyleInterface;

interface ImportedLinksProcessorInterface
{
    /**
     * @param ImportedShlinkUrl[] $shlinkUrls
     */
    public function process(StyleInterface $io, iterable $shlinkUrls, CommonParams $params): void;
}
