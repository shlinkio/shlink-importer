<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Importer\Sources\Csv;

use DateTimeImmutable;
use DateTimeInterface;
use League\Csv\Reader;
use Shlinkio\Shlink\Importer\Exception\ImportException;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Shlinkio\Shlink\Importer\Strategy\ImporterStrategyInterface;

use function array_filter;
use function explode;
use function Functional\reduce_left;
use function str_replace;
use function strtolower;
use function trim;

class CsvImporter implements ImporterStrategyInterface
{
    private const TAG_SEPARATOR = '|';

    public function __construct(private readonly ?DateTimeInterface $date = null)
    {
    }

    /**
     * @throws ImportException
     */
    public function import(ImportParams $importParams): ImportResult
    {
        $params = CsvParams::fromImportParams($importParams);
        return ImportResult::withShortUrls($this->importShortUrls($params));
    }

    /**
     * @return iterable<ImportedShlinkUrl>
     * @throws ImportException
     */
    private function importShortUrls(CsvParams $params): iterable
    {
        $now = $this->date ?? new DateTimeImmutable();
        $csvReader = Reader::createFromStream($params->stream)->setDelimiter($params->delimiter)
                                                              ->setHeaderOffset(0);

        foreach ($csvReader as $record) {
            $record = $this->remapRecordHeaders($record);

            yield new ImportedShlinkUrl(
                ImportSource::CSV,
                $record['longurl'],
                $this->parseTags($record),
                $now,
                $this->nonEmptyValueOrNull($record, 'domain'),
                $record['shortcode'],
                $this->nonEmptyValueOrNull($record, 'title'),
            );
        }
    }

    private function remapRecordHeaders(array $record): array
    {
        return reduce_left($record, static function ($value, string $index, array $c, array $acc) {
            $normalizedKey = strtolower(str_replace(' ', '', $index));
            $acc[$normalizedKey] = $value;

            return $acc;
        }, []);
    }

    private function nonEmptyValueOrNull(array $record, string $key): ?string
    {
        $value = $record[$key] ?? null;
        if (empty($value)) {
            return null;
        }

        $trimmedValue = trim($value);
        if (empty($trimmedValue)) {
            return null;
        }

        return $trimmedValue;
    }

    private function parseTags(array $record): array
    {
        return array_filter(explode(self::TAG_SEPARATOR, $this->nonEmptyValueOrNull($record, 'tags') ?? ''));
    }
}
