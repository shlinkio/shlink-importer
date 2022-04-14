<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Importer\Sources\Kutt;

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
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrlMeta;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
use Shlinkio\Shlink\Importer\Sources\Kutt\KuttImporter;

use function sprintf;

class KuttImporterTest extends TestCase
{
    use ProphecyTrait;

    private KuttImporter $importer;
    private ObjectProphecy $apiConsumer;

    public function setUp(): void
    {
        $this->apiConsumer = $this->prophesize(RestApiConsumerInterface::class);
        $this->importer = new KuttImporter($this->apiConsumer->reveal());
    }

    /** @test */
    public function exceptionsThrownByApiConsumerAreWrapped(): void
    {
        $e = new RuntimeException('Error');
        $callApi = $this->apiConsumer->callApi(Argument::cetera())->willThrow($e);

        $this->expectException(ImportException::class);
        $callApi->shouldBeCalledOnce();

        $result = $this->importer->import(ImportParams::fromSource(''));

        // The result is a generator, so we need to iterate it in order to trigger its logic
        foreach ($result as $element) {
        }
    }

    /**
     * @test
     * @dataProvider provideParams
     */
    public function expectedAmountOfCallsIsPerformed(bool $loadAll): void
    {
        $urlsCallNum = 0;
        $loadUrls = $this->apiConsumer->callApi(Argument::containingString('/api/v2/links'), [
            'X-Api-Key' => 'my_api_key',
            'Accept' => 'application/json',
        ])->will(
            function (array $args) use (&$urlsCallNum, $loadAll): array {
                [$url] = $args;
                Assert::assertEquals(
                    sprintf('/api/v2/links?limit=50&skip=%s&all=%s', $urlsCallNum * 50, $loadAll ? 'true' : 'false'),
                    $url,
                );
                $urlsCallNum++;

                return [
                    'total' => 51,
                    'data' => [
                        [
                            'visit_count' => 3,
                            'target' => 'https://longurl.com',
                            'created_at' => '2022-04-14T08:28:57.155Z',
                            'domain' => 'doma.in',
                            'address' => 'short-code',
                            'expire_in' => '2023-04-16T00:00:00.000Z',
                        ],
                        [
                            'visit_count' => 25,
                            'target' => 'https://longurl-2.com',
                            'created_at' => '2022-04-16T00:00:00.000Z',
                            'address' => 'short-code-2',
                            'description' => 'foo link',
                        ],
                    ],
                ];
            },
        );

        /** @var ImportedShlinkUrl[] $result */
        $result = $this->importer->import(ImportParams::fromSourceAndCallableMap('', [
            'api_key' => static fn () => 'my_api_key',
            'import_all_urls' => static fn () => $loadAll,
        ]));

        foreach ($result as $index => $url) {
            self::assertEquals(ImportSources::KUTT, $url->source());

            if ($index % 2 === 0) {
                self::assertEquals(3, $url->visitsCount());
                self::assertEquals('https://longurl.com', $url->longUrl());
                self::assertEquals('doma.in', $url->domain());
                self::assertEquals(new DateTimeImmutable('2022-04-14T08:28:57.155Z'), $url->createdAt());
                self::assertEquals('short-code', $url->shortCode());
                self::assertNull($url->title());
                self::assertEquals(
                    new ImportedShlinkUrlMeta(null, new DateTimeImmutable('2023-04-16T00:00:00.000Z'), null),
                    $url->meta(),
                );
            } else {
                self::assertEquals(25, $url->visitsCount());
                self::assertEquals('https://longurl-2.com', $url->longUrl());
                self::assertNull($url->domain());
                self::assertEquals(new DateTimeImmutable('2022-04-16T00:00:00.000Z'), $url->createdAt());
                self::assertEquals('short-code-2', $url->shortCode());
                self::assertEquals('foo link', $url->title());
                self::assertEquals(new ImportedShlinkUrlMeta(null, null, null), $url->meta());
            }
        }

        $loadUrls->shouldHaveBeenCalledTimes(2);
    }

    public function provideParams(): iterable
    {
        yield 'all URLs' => [true];
        yield 'not all URLs' => [false];
    }
}
