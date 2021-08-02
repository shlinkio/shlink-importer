<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Http;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;

interface RestApiConsumerInterface
{
    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    public function callApi(string $url, array $headers = [], string $method = 'GET'): array;
}
