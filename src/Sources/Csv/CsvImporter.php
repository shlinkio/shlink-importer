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
use function array_key_exists;
use function explode;
use function parse_url;
use function str_contains;
use function str_replace;
use function strtolower;
use function substr;
use function trim;

class CsvImporter implements ImporterStrategyInterface
{
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
            [$shortCode, $domain] = $this->parseShortCodeAndDomain($record);

            yield new ImportedShlinkUrl(
                source: ImportSource::CSV,
                longUrl: $record['longurl'],
                tags: $this->parseTags($record),
                createdAt: $now,
                domain: $domain,
                shortCode: $shortCode,
                title: $this->nonEmptyValueOrNull($record, 'title'),
            );
        }
    }

    private function remapRecordHeaders(array $record): array
    {
        $normalized = [];
        foreach ($record as $index => $value) {
            $normalizedKey = strtolower(str_replace(' ', '', $index));
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * @return non-empty-string|null
     */
    private function nonEmptyValueOrNull(array $record, string $key): ?string
    {
        $value = trim($record[$key] ?? '');
        return empty($value) ? null : $value;
    }

    private function parseTags(array $record): array
    {
        $rawTags = $this->nonEmptyValueOrNull($record, 'tags') ?? '';
        $separator = str_contains($rawTags, ',') ? ',' : '|';

        return array_filter(explode($separator, $rawTags));
    }

    /**
     * @return array{string, string | null}
     */
    private function parseShortCodeAndDomain(array $record): array
    {
        $longUrl = $record['shorturl'] ?? '';
        $parsing = parse_url($longUrl);

        // If shortCode and/or domain were not provided, try to infer them from the short URL
        return [
            $record['shortcode'] ?? substr($parsing['path'] ?? '', 1),
            array_key_exists('domain', $record)
                ? $this->nonEmptyValueOrNull($record, 'domain')
                : $parsing['host'] ?? null,
        ];
    }
}
