<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Strategy;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Shlinkio\Shlink\Importer\Exception\InvalidRequestException;

use function json_decode;

use const JSON_THROW_ON_ERROR;

abstract class AbstractApiImporterStrategy implements ImporterStrategyInterface
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;

    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws InvalidRequestException
     */
    protected function callApi(string $url, array $headers = [], string $method = 'GET'): array
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

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}
