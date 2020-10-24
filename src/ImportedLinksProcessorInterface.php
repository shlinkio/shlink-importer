<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;

interface ImportedLinksProcessorInterface
{
    /**
     * @param ImportedShlinkUrl[] $shlinkUrls
     */
    public function process(iterable $shlinkUrls, string $source, array $params): void;
}
