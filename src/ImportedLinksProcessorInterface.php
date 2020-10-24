<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Symfony\Component\Console\Style\StyleInterface;

interface ImportedLinksProcessorInterface
{
    /**
     * @param ImportedShlinkUrl[] $shlinkUrls
     */
    public function process(StyleInterface $io, iterable $shlinkUrls, array $params): void;
}
