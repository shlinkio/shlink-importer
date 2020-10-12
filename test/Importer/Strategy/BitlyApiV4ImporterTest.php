<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Importer\Strategy;

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
use Shlinkio\Shlink\Importer\Importer\Strategy\BitlyApiV4Importer;
use Shlinkio\Shlink\Importer\Model\ShlinkUrl;

use function json_encode;
use function sprintf;
use function stripos;

use const JSON_THROW_ON_ERROR;

class BitlyApiV4ImporterTest extends TestCase
{
    use ProphecyTrait;

    private BitlyApiV4Importer $importer;
    private ObjectProphecy $httpClient;
    private ObjectProphecy $requestFactory;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $this->importer = new BitlyApiV4Importer($this->httpClient->reveal(), $this->requestFactory->reveal());
    }

    /** @test */
    public function groupsAndUrlsAreRecursivelyFetched(): void
    {
        $accessToken = 'abc123';

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
            $url = (string) $request->getUri();
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
                            'link' => 'http://bit.ly/ddd',
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

        $generator = $this->importer->import(['access_token' => $accessToken]);
        $urls = [];
        foreach ($generator as $url) {
            $urls[] = $url;
        }

        self::assertCount(8, $urls);
        self::assertEquals([
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
        ], $urls);
        $createGroupsRequest->shouldHaveBeenCalledOnce();
        $sendGroupsRequest->shouldHaveBeenCalledOnce();
        $createUrlsRequest->shouldBeCalledTimes(4);
        $sendUrlsRequest->shouldHaveBeenCalledTimes(4);
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
