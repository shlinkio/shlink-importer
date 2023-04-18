<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Http;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

use function Shlinkio\Shlink\Json\json_decode;

class RestApiConsumer implements RestApiConsumerInterface
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    public function callApi(string $url, array $headers = [], string $method = 'GET'): array
    {
        $request = $this->requestFactory->createRequest($method, $url);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $resp = $this->httpClient->sendRequest($request);
        $statusCode = $resp->getStatusCode();
        $body = $resp->getBody()->__toString();

        if ($statusCode >= 400) {
            throw InvalidRequestException::fromResponseData($url, $statusCode, $body);
        }

        return json_decode($body);
    }
}
