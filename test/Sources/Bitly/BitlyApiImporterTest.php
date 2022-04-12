<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Bitly;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl as ShlinkUrl;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiException;
use Shlinkio\Shlink\Importer\Sources\Bitly\BitlyApiImporter;
use Shlinkio\Shlink\Importer\Sources\ImportSources;

use function explode;
use function sprintf;
use function str_contains;
use function str_starts_with;

class BitlyApiImporterTest extends TestCase
{
    use ProphecyTrait;

    private BitlyApiImporter $importer;
    private ObjectProphecy $apiConsumer;

    public function setUp(): void
    {
        $this->apiConsumer = $this->prophesize(RestApiConsumerInterface::class);
        $this->importer = new BitlyApiImporter($this->apiConsumer->reveal());
    }

    /**
     * @test
     * @dataProvider provideParams
     */
    public function groupsAndUrlsAreRecursivelyFetched(array $paramsMap, array $expected): void
    {
        $paramsMap['access_token'] = static fn () => 'abc123';
        $params = ImportParams::fromSourceAndCallableMap('', $paramsMap);

        $sendGroupsRequest = $this->apiConsumer->callApi(
            'https://api-ssl.bitly.com/v4/groups',
            Argument::cetera(),
        )->willReturn([
            'groups' => [
                ['guid' => 'abc'],
                ['guid' => 'def'],
                ['guid' => 'ghi'],
            ],
        ]);

        $callCounts = [];
        $sendUrlsRequest = $this->apiConsumer->callApi(
            Argument::that(fn (string $uri) => str_starts_with($uri, 'https://api-ssl.bitly.com/v4/groups/')),
            Argument::cetera(),
        )->will(function (array $args) use (&$callCounts): array {
            [$uri] = $args;
            [$url] = explode('?', $uri);
            $callCounts[$url] = ($callCounts[$url] ?? 0) + 1;

            if ($callCounts[$url] === 1 && str_contains($url, 'def')) {
                return [
                    'links' => [
                        [
                            'created_at' => '2020-03-01T00:00:00+0000',
                            'link' => 'http://bit.ly/ccc',
                            'long_url' => 'https://shlink.io',
                            'title' => 'Cool title',
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
                ];
            }

            return [
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
            ];
        });

        $generator = $this->importer->import($params);
        $urls = [];
        foreach ($generator as $url) {
            $urls[] = $url;
        }

        self::assertEquals($expected, $urls);
        $sendGroupsRequest->shouldHaveBeenCalledOnce();
        $sendUrlsRequest->shouldHaveBeenCalledTimes(4);
    }

    public function provideParams(): iterable
    {
        $source = ImportSources::BITLY;

        yield 'default options' => [[], [
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-03-01T00:00:00+0000',
            ), null, 'ccc', 'Cool title'),
            new ShlinkUrl($source, 'https://github.com', ['bar'], $this->createDate(
                '2020-04-01T00:00:00+0000',
            ), null, 'ddd', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
        ]];
        yield 'ignore archived' => [['ignore_archived' => fn () => true], [
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-03-01T00:00:00+0000',
            ), null, 'ccc', 'Cool title'),
            new ShlinkUrl($source, 'https://github.com', ['bar'], $this->createDate(
                '2020-04-01T00:00:00+0000',
            ), null, 'ddd', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
        ]];
        yield 'ignore tags' => [['import_tags' => fn () => false], [
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', [], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-03-01T00:00:00+0000',
            ), null, 'ccc', 'Cool title'),
            new ShlinkUrl($source, 'https://github.com', [], $this->createDate(
                '2020-04-01T00:00:00+0000',
            ), null, 'ddd', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', [], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', [], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
        ]];
        yield 'import custom domains' => [['import_custom_domains' => fn () => true], [
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-03-01T00:00:00+0000',
            ), null, 'ccc', 'Cool title'),
            new ShlinkUrl($source, 'https://github.com', ['bar'], $this->createDate(
                '2020-04-01T00:00:00+0000',
            ), 'customdom.com', 'ddd', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
            new ShlinkUrl($source, 'https://shlink.io', [], $this->createDate(
                '2020-01-01T00:00:00+0000',
            ), null, 'aaa', null),
            new ShlinkUrl($source, 'https://github.com', ['foo', 'bar'], $this->createDate(
                '2020-02-01T00:00:00+0000',
            ), null, 'bbb', null),
        ]];
    }

    /**
     * @test
     * @dataProvider provideErrorStatusCodes
     */
    public function throwsExceptionWhenStatusCodeReturnedByApiIsError(int $statusCode): void
    {
        $sendRequest = $this->apiConsumer->callApi(Argument::cetera())->willThrow(
            InvalidRequestException::fromResponseData('', $statusCode, 'Error'),
        );

        $this->expectException(BitlyApiException::class);
        $this->expectErrorMessage('Request to Bitly API v4 to URL');
        $this->expectErrorMessage(sprintf('failed with status code "%s" and body "Error"', $statusCode));
        $sendRequest->shouldBeCalledOnce();

        $list = $this->importer->import(ImportParams::fromSourceAndCallableMap('', ['access_token' => fn () => 'abc']));
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

    private function createDate(string $date): DateTimeInterface
    {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $date);
    }
}
