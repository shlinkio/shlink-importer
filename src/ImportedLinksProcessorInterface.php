<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

use Shlinkio\Shlink\Importer\Model\ShlinkUrl;

interface ImportedLinksProcessorInterface
{
    /**
     * @param ShlinkUrl[] $shlinkUrls
     */
    public function process(iterable $shlinkUrls, string $source, array $params): void;
}
