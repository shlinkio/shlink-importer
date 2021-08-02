<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\ShlinkApi;

use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Sources\ShlinkApi\ShlinkApiImporter;

use function array_merge;
use function Functional\contains;
use function sprintf;

class ShlinkApiImporterTest extends TestCase
{
    use ProphecyTrait;

    private ShlinkApiImporter $importer;
    private ObjectProphecy $apiConsumer;

    public function setUp(): void
    {
        $this->apiConsumer = $this->prophesize(RestApiConsumerInterface::class);
        $this->importer = new ShlinkApiImporter($this->apiConsumer->reveal());
    }

    /** @test */
    public function exceptionsThrownByApiConsumerAreWrapped(): void
    {
        $e = new RuntimeException('Error');
        $callApi = $this->apiConsumer->callApi(Argument::cetera())->willThrow($e);

        $this->expectException(ImportException::class);
        $callApi->shouldBeCalledOnce();

        $result = $this->importer->import([]);

        // The result is a generator, so we need to iterate it in order to trigger its logic
        foreach ($result as $element) {
        }
    }

    /** @test */
    public function expectedAmountOfCallsIsPerformedBasedOnPaginationResults(): void
    {
        $apiKey = 'abc-123';
        $shortUrl = [
            'shortCode' => 'rY9zd',
            'shortUrl' => 'https://acel.me/rY9zd',
            'longUrl' => 'https://www.alejandrocelaya.com/foo',
            'dateCreated' => '2016-05-02T17:49:53+02:00',
            'visitsCount' => 48,
            'tags' => ['bar', 'foo', 'website'],
            'meta' => [
                'validUntil' => '2020-05-02T17:49:53+02:00',
                'maxVisits' => null,
            ],
            'domain' => null,
            'title' => '',
        ];
        $visit1 = [
            'referer' => 'visit1',
            'date' => '2020-09-12T11:49:59+02:00',
            'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.10',
            'visitLocation' => [
                'countryCode' => 'countryCode',
                'countryName' => 'countryName',
                'regionName' => 'regionName',
                'cityName' => 'cityName',
                'timezone' => 'timezone',
            ],
        ];
        $visit2 = array_merge($visit1, ['referer' => 'visit2']);

        $urlsCallNum = 0;
        $loadUrls = $this->apiConsumer->callApi(
            Argument::containingString('short-urls?'),
            ['X-Api-Key' => $apiKey, 'Accept' => 'application/json'],
        )->will(
            function (array $args) use (&$urlsCallNum, $shortUrl): array {
                $urlsCallNum++;

                [$url] = $args;
                Assert::assertEquals(sprintf('/rest/v2/short-urls?page=%s&itemsPerPage=50', $urlsCallNum), $url);

                return [
                    'shortUrls' => [
                        'data' => [$shortUrl, $shortUrl, $shortUrl],
                        'pagination' => [
                            'currentPage' => $urlsCallNum,
                            'pagesCount' => 3,
                        ],
                    ],
                ];
            },
        );

        $loadVisits = $this->apiConsumer->callApi(
            Argument::containingString('visits'),
            ['X-Api-Key' => $apiKey, 'Accept' => 'application/json'],
        )->will(
            function (array $args) use ($visit1, $visit2): array {
                [$url] = $args;
                Assert::assertEquals('/rest/v2/short-urls/rY9zd/visits?page=1&itemsPerPage=300', $url);

                return [
                    'visits' => [
                        'data' => [$visit1, $visit1, $visit2, $visit2, $visit2],
                    ],
                ];
            },
        );

        /** @var ImportedShlinkUrl[] $result */
        $result = $this->importer->import(['api_key' => $apiKey]);

        $urls = [];
        $visits = [];
        foreach ($result as $url) {
            $urls[] = $url;

            self::assertEquals(ImportSources::SHLINK, $url->source());
            self::assertEquals('https://www.alejandrocelaya.com/foo', $url->longUrl());
            self::assertEquals(['bar', 'foo', 'website'], $url->tags());
            self::assertEquals(
                DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, '2016-05-02T17:49:53+02:00'),
                $url->createdAt(),
            );
            self::assertNull($url->domain());
            self::assertEquals('rY9zd', $url->shortCode());
            self::assertEquals('', $url->title());
            self::assertEquals(48, $url->visitsCount());
            self::assertNull($url->meta()->validSince());
            self::assertEquals(
                DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, '2020-05-02T17:49:53+02:00'),
                $url->meta()->validUntil(),
            );
            self::assertNull($url->meta()->maxVisits());

            foreach ($url->visits() as $index => $visit) {
                $visits[] = $visit;

                self::assertEquals(contains([3, 4], $index) ? 'visit1' : 'visit2', $visit->referer());
                self::assertEquals(
                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.10',
                    $visit->userAgent(),
                );
                self::assertEquals('countryCode', $visit->location()->countryCode());
                self::assertEquals('countryName', $visit->location()->countryName());
                self::assertEquals('regionName', $visit->location()->regionName());
                self::assertEquals('cityName', $visit->location()->cityName());
                self::assertEquals('timezone', $visit->location()->timezone());
                self::assertEquals(0.0, $visit->location()->latitude());
                self::assertEquals(0.0, $visit->location()->longitude());
            }
        }

        self::assertCount(9, $urls);
        self::assertCount(9 * 5, $visits);
        $loadUrls->shouldHaveBeenCalledTimes(3);
        $loadVisits->shouldHaveBeenCalledTimes(9);
    }

    /** @test */
    public function noVisitsApiallIsperformedForShortUrlsWithoutVisits(): void
    {
        $shortUrl = [
            'shortCode' => 'abc123',
            'shortUrl' => 'https://acel.me/abc123',
            'longUrl' => 'https://shlink.io',
            'dateCreated' => '2017-05-02T17:49:53+02:00',
            'visitsCount' => 0,
            'tags' => [],
            'meta' => [],
            'domain' => null,
            'title' => '',
        ];

        $loadUrls = $this->apiConsumer->callApi(
            Argument::containingString('short-urls?'),
            Argument::cetera(),
        )->will(
            function (array $args) use (&$urlsCallNum, $shortUrl): array {
                $urlsCallNum++;

                [$url] = $args;
                Assert::assertEquals(sprintf('/rest/v2/short-urls?page=%s&itemsPerPage=50', $urlsCallNum), $url);

                return [
                    'shortUrls' => [
                        'data' => [$shortUrl, $shortUrl, $shortUrl],
                        'pagination' => [
                            'currentPage' => $urlsCallNum,
                            'pagesCount' => 3,
                        ],
                    ],
                ];
            },
        );

        $loadVisits = $this->apiConsumer->callApi(
            Argument::containingString('visits'),
            Argument::cetera(),
        )->willReturn([]);

        $result = $this->importer->import(['api_key' => 'foo']);
        foreach ($result as $url) {
            // The result needs to be iterated in order to perfomr the calls
            foreach ($url->visits() as $visit) {
            }
        }

        $loadUrls->shouldHaveBeenCalledTimes(3);
        $loadVisits->shouldNotHaveBeenCalled();
    }
}
