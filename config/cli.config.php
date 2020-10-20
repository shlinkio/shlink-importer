<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer;

return [

    'cli' => [
        'commands' => [
            Command\ImportCommand::NAME => Command\ImportCommand::class,
        ],
    ],

];
