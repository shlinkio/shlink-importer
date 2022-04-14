<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Yourls;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\Yourls\YourlsImporter;
use Shlinkio\Shlink\Importer\Sources\Yourls\YourlsMissingPluginException;
use Throwable;

use function str_contains;

class YourlsImporterTest extends TestCase
{
    use ProphecyTrait;

    private YourlsImporter $importer;
    private ObjectProphecy $apiConsumer;

    public function setUp(): void
    {
        $this->apiConsumer = $this->prophesize(RestApiConsumerInterface::class);
        $this->importer = new YourlsImporter($this->apiConsumer->reveal());
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function exceptionsThrownByApiConsumerAreWrapped(
        Throwable $e,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $callApi = $this->apiConsumer->callApi(Argument::cetera())->willThrow($e);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $callApi->shouldBeCalledOnce();

        $result = $this->importer->import(ImportParams::fromSource(''));

        // The result is a generator, so we need to iterate it in order to trigger its logic
        foreach ($result as $element) {
        }
    }

    public function provideExceptions(): iterable
    {
        yield 'unknown exception' => [
            new RuntimeException('Error'),
            ImportException::class,
            'An error occurred while importing URLs',
        ];
        yield 'unknown request exception' => [
            InvalidRequestException::fromResponseData('', 1, ''),
            ImportException::class,
            'An error occurred while importing URLs',
        ];
        yield 'plugin request exception' => [
            InvalidRequestException::fromResponseData('', 1, '"message":"Unknown or missing \"action\" parameter"'),
            YourlsMissingPluginException::class,
            'The YOURLS instance from where you are trying to import links, does not have the '
            . '"yourls-shlink-import-plugin" installed, or it is not enabled. Go to https://slnk.to/yourls-import '
            . 'and follow the installation instructions, then try to import again.',
        ];
    }

    /**
     * @test
     * @dataProvider provideLoadParams
     */
    public function linksAndVisitsAreLoadedFromYourls(bool $doLoadVisits, int $expectedVisitsCallas): void
    {
        $loadUrls = $this->apiConsumer->callApi(Argument::that(function (string $arg): bool {
            return str_contains($arg, 'format=json&action=shlink-list')
                && str_contains($arg, 'username=the_username&password=the_password');
        }))->willReturn([
            'result' => [
                [
                    'keyword' => 'keyword_0',
                    'url' => 'url_0',
                    'timestamp' => '2021-01-01 00:00:00',
                    'title' => 'title_0',
                    'clicks' => 0,
                ],
                [
                    'keyword' => 'keyword_1',
                    'url' => 'url_1',
                    'timestamp' => '2021-01-01 00:00:00',
                    'title' => 'title_1',
                    'clicks' => 3,
                ],
            ],
        ]);
        $loadVisits = $this->apiConsumer->callApi(Argument::containingString('action=shlink-link-visits'))->will(
            function (array $args) {
                [$url] = $args;

                $result = str_contains($url, 'keyword_1') ? [] : [
                    [
                        'referrer' => 'referrer_0',
                        'user_agent' => 'user_agent_0',
                        'click_time' => '2021-01-01 00:00:00',
                        'country_code' => 'country_code_0',
                    ],
                    [
                        'referrer' => 'direct',
                        'user_agent' => 'user_agent_1',
                        'click_time' => '2021-01-01 00:00:00',
                        'country_code' => 'country_code_1',
                    ],
                    [
                        'referrer' => 'direct',
                        'user_agent' => 'user_agent_2',
                        'click_time' => '2021-01-01 00:00:00',
                        'country_code' => 'country_code_2',
                    ],
                ];

                return ['result' => $result];
            },
        );

        $result = $this->importer->import(ImportParams::fromSourceAndCallableMap('', [
            'username' => fn () => 'the_username',
            'password' => fn () => 'the_password',
            ImportParams::IMPORT_VISITS_PARAM => fn () => $doLoadVisits,
        ]));

        foreach ($result as $urlIndex => $url) {
            self::assertEquals('keyword_' . $urlIndex, $url->shortCode());
            self::assertEquals('url_' . $urlIndex, $url->longUrl());
            self::assertEquals('title_' . $urlIndex, $url->title());

            foreach ($url->visits() as $visitIndex => $visit) {
                self::assertEquals('user_agent_' . $visitIndex, $visit->userAgent());
                self::assertEquals('country_code_' . $visitIndex, $visit->location()->countryCode());
                self::assertEmpty($visit->location()->cityName());
                self::assertEmpty($visit->location()->countryName());
                self::assertEmpty($visit->location()->regionName());
                self::assertEmpty($visit->location()->timezone());

                if ($visitIndex === 0) {
                    self::assertEquals('referrer_' . $visitIndex, $visit->referer());
                } else {
                    self::assertEmpty($visit->referer());
                }
            }
        }

        $loadUrls->shouldHaveBeenCalledOnce();
        $loadVisits->shouldHaveBeenCalledTimes($expectedVisitsCallas);
    }

    public function provideLoadParams(): iterable
    {
        yield 'visits loaded' => [true, 2];
        yield 'no visits loaded' => [false, 0];
    }
}
