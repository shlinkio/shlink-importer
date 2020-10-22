<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Strategy;

use DateTimeImmutable;
use DateTimeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Shlinkio\Shlink\Importer\Exception\BitlyApiException;
use Shlinkio\Shlink\Importer\Model\ShlinkUrl;
use Shlinkio\Shlink\Importer\Strategy\BitlyApiImporter;

use function explode;
use function json_encode;
use function sprintf;
use function stripos;

use const JSON_THROW_ON_ERROR;

class BitlyApiImporterTest extends TestCase
{
    use ProphecyTrait;

    private BitlyApiImporter $importer;
    private ObjectProphecy $httpClient;
    private ObjectProphecy $requestFactory;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $this->importer = new BitlyApiImporter($this->httpClient->reveal(), $this->requestFactory->reveal());
    }

    /**
     * @test
     * @dataProvider provideParams
     */
    public function groupsAndUrlsAreRecursivelyFetched(array $params, array $expected): void
    {
        $params['access_token'] = $accessToken = 'abc123';

        $groupsRequest = new Request('GET', 'groups');
        $createGroupsRequest = $this->requestFactory->createRequest(
            'GET',
            'https://api-ssl.bitly.com/v4/groups',
        )->willReturn($groupsRequest);

        $groupsResponse = new Response(200, [], $this->jsonEncode([
            'groups' => [
                ['guid' => 'abc'],
                ['guid' => 'def'],
                ['guid' => 'ghi'],
            ],
        ]));
        $sendGroupsRequest = $this->httpClient->sendRequest(
            $groupsRequest->withHeader('Authorization', sprintf('Bearer %s', $accessToken)),
        )->willReturn($groupsResponse);

        $createUrlsRequest = $this->requestFactory->createRequest(
            'GET',
            Argument::containingString('https://api-ssl.bitly.com/v4/groups/'),
        )->will(function (array $args) {
            [, $url] = $args;
            return new Request('GET', $url);
        });

        $callCounts = [];
        $test = $this;
        $sendUrlsRequest = $this->httpClient->sendRequest(
            Argument::that(fn (RequestInterface $request) => 'groups' !== (string) $request->getUri()),
        )->will(function (array $args) use (&$callCounts, $test): Response {
            /** @var RequestInterface $request */
            [$request] = $args;
            [$url] = explode('?', (string) $request->getUri());
            $callCounts[$url] = ($callCounts[$url] ?? 0) + 1;

            if ($callCounts[$url] === 1 && stripos($url, 'def') !== false) {
                return new Response(200, [], $test->jsonEncode([
                    'links' => [
                        [
                            'created_at' => '2020-03-01T00:00:00+0000',
                            'link' => 'http://bit.ly/ccc',
                            'long_url' => 'https://shlink.io',
                        ],
                        [
                            'created_at' => '2020-04-01T00:00:00+0000',
                            'link' => 'http://customdom.com/ddd',
                            'long_url' => 'https://github.com',
                            'tags' => ['bar'],
                        ],
                    ],
                    'pagination' => [
                        'next' => 'https://api-ssl.bitly.com/v4/groups/def/bitlinks',
                    ],
                ]));
            }

            return new Response(200, [], $test->jsonEncode([
                'links' => [
                    [
                        'created_at' => '2020-01-01T00:00:00+0000',
                        'link' => 'http://bit.ly/aaa',
                        'long_url' => 'https://shlink.io',
                    ],
                    [
                        'created_at' => '2020-02-01T00:00:00+0000',
                        'link' => 'http://bit.ly/bbb',
                        'long_url' => 'https://github.com',
                        'tags' => ['foo', 'bar'],
                    ],
                ],
                'pagination' => [
                    'next' => '',
                ],
            ]));
        });

        $generator = $this->importer->import($params);
        $urls = [];
        foreach ($generator as $url) {
            $urls[] = $url;
        }

        self::assertEquals($expected, $urls);
        $createGroupsRequest->shouldHaveBeenCalledOnce();
        $sendGroupsRequest->shouldHaveBeenCalledOnce();
        $createUrlsRequest->shouldBeCalledTimes(4);
        $sendUrlsRequest->shouldHaveBeenCalledTimes(4);
    }

    public function provideParams(): iterable
    {
        yield 'default options' => [[], [
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-03-01T00:00:00+0000'), null, 'ccc'),
            new ShlinkUrl('https://github.com', ['bar'], $this->createDate('2020-04-01T00:00:00+0000'), null, 'ddd'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
        ]];
        yield 'ignore archived' => [['ignore_archived' => true], [
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-03-01T00:00:00+0000'), null, 'ccc'),
            new ShlinkUrl('https://github.com', ['bar'], $this->createDate('2020-04-01T00:00:00+0000'), null, 'ddd'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
        ]];
        yield 'ignore tags' => [['import_tags' => false], [
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', [], $this->createDate('2020-02-01T00:00:00+0000'), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-03-01T00:00:00+0000'), null, 'ccc'),
            new ShlinkUrl('https://github.com', [], $this->createDate('2020-04-01T00:00:00+0000'), null, 'ddd'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', [], $this->createDate('2020-02-01T00:00:00+0000'), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', [], $this->createDate('2020-02-01T00:00:00+0000'), null, 'bbb'),
        ]];
        yield 'import custom domains' => [['import_custom_domains' => true], [
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-03-01T00:00:00+0000'), null, 'ccc'),
            new ShlinkUrl('https://github.com', ['bar'], $this->createDate(
                '2020-04-01T00:00:00+0000',
            ), 'customdom.com', 'ddd'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
            new ShlinkUrl('https://shlink.io', [], $this->createDate('2020-01-01T00:00:00+0000'), null, 'aaa'),
            new ShlinkUrl('https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb'),
        ]];
    }

    /**
     * @test
     * @dataProvider provideErrorStatusCodes
     */
    public function throwsExceptionWhenStatusCodeReturnedByApiIsError(int $statusCode): void
    {
        $request = new Request('GET', '/groups');
        $createRequest = $this->requestFactory->createRequest(Argument::cetera())->willReturn($request);
        $sendRequest = $this->httpClient->sendRequest(Argument::cetera())->willReturn(
            new Response($statusCode, [], 'Error'),
        );

        $this->expectException(BitlyApiException::class);
        $this->expectErrorMessage('Request to Bitly API v4 to URL');
        $this->expectErrorMessage(sprintf('failed with status code "%s" and body "Error"', $statusCode));
        $createRequest->shouldBeCalledOnce();
        $sendRequest->shouldBeCalledOnce();

        $list = $this->importer->import(['access_token' => 'abc']);
        foreach ($list as $item) {
            // Iteration needed to trigger generator code
        }
    }

    public function provideErrorStatusCodes(): iterable
    {
        yield '400' => [400];
        yield '401' => [401];
        yield '403' => [403];
        yield '404' => [404];
        yield '500' => [500];
    }

    private function jsonEncode(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function createDate(string $date): DateTimeInterface
    {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $date);
    }
}
