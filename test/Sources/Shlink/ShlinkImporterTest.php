<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Shlink;

use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Http\InvalidRequestException;
use Shlinkio\Shlink\Importer\Http\RestApiConsumerInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Sources\Shlink\ShlinkImporter;
use Shlinkio\Shlink\Importer\Sources\Shlink\ShlinkMapper;

use function array_merge;
use function in_array;
use function sprintf;
use function str_contains;

class ShlinkImporterTest extends TestCase
{
    private ShlinkImporter $importer;
    private MockObject & RestApiConsumerInterface $apiConsumer;

    public function setUp(): void
    {
        $this->apiConsumer = $this->createMock(RestApiConsumerInterface::class);
        $this->importer = new ShlinkImporter($this->apiConsumer, new ShlinkMapper());
    }

    #[Test]
    public function exceptionsThrownByApiConsumerAreWrapped(): void
    {
        $e = new RuntimeException('Error');
        $this->apiConsumer->expects($this->once())->method('callApi')->willThrowException($e);

        $this->expectException(ImportException::class);

        // The result is a generator, so we need to iterate it in order to trigger its logic
        [...$this->importer->import(ImportSource::SHLINK->toParams())->shlinkUrls];
    }

    #[Test, DataProvider('provideLoadParams')]
    public function expectedAmountOfCallsIsPerformedBasedOnPaginationResults(
        bool $doLoadVisits,
        int $expectedVisitsCalls,
    ): void {
        $apiKey = 'abc-123';
        $shortUrl = [
            'shortCode' => 'rY9zd',
            'shortUrl' => 'https://acel.me/rY9zd',
            'longUrl' => 'https://www.alejandrocelaya.com/foo',
            'dateCreated' => '2016-05-02T17:49:53+02:00',
            'visitsSummary' => [
                'total' => 48,
            ],
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
        $this->apiConsumer->expects(
            // 3 extra calls for the 3 pages of short URLs
            // 9 extra calls for the redirect rules of 3 short URLs times 3 pages
            $this->exactly($expectedVisitsCalls + 3 + 9),
        )->method('callApi')->willReturnCallback(
            function (string $url) use (&$urlsCallNum, $shortUrl, $visit1, $visit2): array {
                if (str_contains($url, 'short-urls?')) {
                    $urlsCallNum++;

                    Assert::assertEquals(sprintf('/rest/v3/short-urls?page=%s&itemsPerPage=50', $urlsCallNum), $url);

                    return [
                        'shortUrls' => [
                            'data' => [$shortUrl, $shortUrl, $shortUrl],
                            'pagination' => [
                                'currentPage' => $urlsCallNum,
                                'pagesCount' => 3,
                            ],
                        ],
                    ];
                }

                if (str_contains($url, 'visits')) {
                    Assert::assertEquals('/rest/v3/short-urls/rY9zd/visits?page=1&itemsPerPage=300', $url);

                    return [
                        'visits' => [
                            'data' => [$visit1, $visit1, $visit2, $visit2, $visit2],
                        ],
                    ];
                }

                if (str_contains($url, 'redirect-rules?')) {
                    Assert::assertEquals('/rest/v3/short-urls/rY9zd/redirect-rules?', $url);

                    if ($urlsCallNum === 2) {
                        throw InvalidRequestException::fromResponseData($url, 404, '');
                    }

                    if ($urlsCallNum === 3) {
                        return ['redirectRules' => []];
                    }

                    return [
                        'redirectRules' => [
                            [
                                'longUrl' => 'https://www.example.com',
                                'conditions' => [
                                    [
                                        'type' => 'query-param',
                                        'matchValue' => 'foo',
                                        'matchKey' => 'bar',
                                    ],
                                ],
                            ],
                        ],
                    ];
                }

                return [];
            },
        );

        $result = $this->importer->import(ImportSource::SHLINK->toParamsWithCallableMap([
            'api_key' => fn () => $apiKey,
            ImportParams::IMPORT_VISITS_PARAM => fn () => $doLoadVisits,
        ]));

        $urls = [];
        $visits = [];
        $urlIndex = 0;
        foreach ($result->shlinkUrls as $url) {
            $urls[] = $url;

            self::assertEquals(ImportSource::SHLINK, $url->source);
            self::assertEquals('https://www.alejandrocelaya.com/foo', $url->longUrl);
            self::assertEquals(['bar', 'foo', 'website'], $url->tags);
            self::assertEquals(
                DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, '2016-05-02T17:49:53+02:00'),
                $url->createdAt,
            );
            self::assertNull($url->domain);
            self::assertEquals('rY9zd', $url->shortCode);
            self::assertEquals('', $url->title);
            self::assertEquals(48, $url->visitsCount);
            self::assertNull($url->meta->validSince);
            self::assertEquals(
                DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, '2020-05-02T17:49:53+02:00'),
                $url->meta->validUntil,
            );
            self::assertNull($url->meta->maxVisits);

            // First page of URLs (first 3) include redirect rules
            self::assertEquals(empty($url->redirectRules), $urlIndex > 2);
            $urlIndex++;

            foreach ($url->visits as $index => $visit) {
                $visits[] = $visit;

                self::assertEquals(in_array($index, [3, 4], true) ? 'visit1' : 'visit2', $visit->referer);
                self::assertEquals(
                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.10',
                    $visit->userAgent,
                );
                self::assertEquals('countryCode', $visit->location?->countryCode);
                self::assertEquals('countryName', $visit->location?->countryName);
                self::assertEquals('regionName', $visit->location?->regionName);
                self::assertEquals('cityName', $visit->location?->cityName);
                self::assertEquals('timezone', $visit->location?->timezone);
                self::assertEquals(0.0, $visit->location?->latitude);
                self::assertEquals(0.0, $visit->location?->longitude);
            }
        }

        self::assertCount(9, $urls);
        self::assertCount($expectedVisitsCalls * 5, $visits);
        self::assertEmpty([...$result->orphanVisits]);
    }

    public static function provideLoadParams(): iterable
    {
        yield 'visits loaded' => [true, 9];
        yield 'no visits loaded' => [false, 0];
    }

    #[Test]
    public function noVisitsApiCallsArePerformedForShortUrlsWithoutVisits(): void
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

        $this->apiConsumer->expects(
            // 3 extra calls for the 3 pages of short URLs
            // 9 extra calls for the redirect rules of 3 short URLs times 3 pages
            $this->exactly(3 + 9),
        )->method('callApi')->with(
            $this->stringContains('/short-urls'),
            $this->anything(),
            $this->anything(),
        )->willReturnCallback(
            function (string $url) use (&$urlsCallNum, $shortUrl): array {
                if (str_contains($url, 'redirect-rules')) {
                    return ['redirectRules' => []];
                }

                $urlsCallNum++;
                Assert::assertEquals(sprintf('/rest/v3/short-urls?page=%s&itemsPerPage=50', $urlsCallNum), $url);

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

        // The result needs to be iterated in order to perform the calls
        [...$this->importer->import(ImportSource::SHLINK->toParamsWithCallableMap([
            'api_key' => fn () => 'foo',
            ImportParams::IMPORT_VISITS_PARAM => fn () => true,
        ]))->shlinkUrls];
    }

    #[Test]
    #[TestWith([['orphanVisitsCount' => 800]])]
    #[TestWith([['orphanVisits' => ['total' => 800]]])]
    public function orphanVisitsAreImportedWhenRequested(array $visitsOverview): void
    {
        $visit1 = [
            'referer' => 'visit1',
            'date' => '2020-09-12T11:49:59+02:00',
            'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.10',
            'type' => 'base_url',
            'visitedUrl' => 'https://s.test',
            'visitLocation' => null,
        ];
        $visit2 = [
            'referer' => 'visit2',
            'date' => '2020-09-12T11:49:59+02:00',
            'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.10',
            'type' => 'regular_not_found',
            'visitedUrl' => 'https://s.test/foo/bar/baz',
            'visitLocation' => [
                'countryCode' => 'countryCode',
                'countryName' => 'countryName',
                'regionName' => 'regionName',
                'cityName' => 'cityName',
                'timezone' => 'timezone',
                'latitude' => 33.33,
                'longitude' => 44.44,
            ],
        ];

        $this->apiConsumer->expects($this->exactly(4))->method('callApi')->willReturnCallback(
            function (string $url) use ($visit1, $visit2, $visitsOverview) {
                if (! str_contains($url, 'orphan')) {
                    return ['visits' => $visitsOverview];
                }

                return [
                    'visits' => [
                        'data' => [$visit1, $visit2],
                    ],
                ];
            },
        );

        $result = $this->importer->import(ImportSource::SHLINK->toParamsWithCallableMap([
            'api_key' => fn () => 'foo',
            ImportParams::IMPORT_ORPHAN_VISITS_PARAM => fn () => true,
        ]));
        /** @var ImportedShlinkOrphanVisit[] $orphanVisits */
        $orphanVisits = [...$result->orphanVisits];

        self::assertCount(6, $orphanVisits);
        foreach ($orphanVisits as $index => $visit) {
            $isEven = $index % 2 === 0;

            self::assertEquals($isEven ? 'visit2' : 'visit1', $visit->referer);
            self::assertEquals(
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.10',
                $visit->userAgent,
            );
            self::assertEquals($isEven ? 'regular_not_found' : 'base_url', $visit->type);
            self::assertEquals($isEven ? 'https://s.test/foo/bar/baz' : 'https://s.test', $visit->visitedUrl);

            if ($isEven) {
                self::assertEquals('countryCode', $visit->location?->countryCode);
                self::assertEquals('countryName', $visit->location?->countryName);
                self::assertEquals('regionName', $visit->location?->regionName);
                self::assertEquals('cityName', $visit->location?->cityName);
                self::assertEquals('timezone', $visit->location?->timezone);
                self::assertEquals(33.33, $visit->location?->latitude);
                self::assertEquals(44.44, $visit->location?->longitude);
            } else {
                self::assertEmpty($visit->location?->countryCode);
                self::assertEmpty($visit->location?->countryName);
                self::assertEmpty($visit->location?->regionName);
                self::assertEmpty($visit->location?->cityName);
                self::assertEmpty($visit->location?->timezone);
                self::assertEquals(0.0, $visit->location?->latitude);
                self::assertEquals(0.0, $visit->location?->longitude);
            }
        }
    }
}
