<?php

namespace App\Support\Spreadsheet;

use RuntimeException;

/**
 * Minimal read-only .xlsx reader.
 *
 * The project intentionally has no spreadsheet package (no phpoffice/phpspreadsheet)
 * and PHP's ext-zip is not enabled, so this reads the ZIP container directly from the
 * central directory and inflates each part with gzinflate(). It covers only the subset
 * of SpreadsheetML that Excel actually writes for cell data: shared strings, inline
 * strings, formula string results and numeric values.
 */
class XlsxReader
{
    private const EOCD_SIGNATURE = "PK\x05\x06";

    private const CENTRAL_SIGNATURE = "PK\x01\x02";

    private const LOCAL_SIGNATURE = "PK\x03\x04";

    /** @var array<string, array{method:int,compressed:int,offset:int}> */
    private array $entries = [];

    private string $data;

    /** @var list<string>|null */
    private ?array $sharedStrings = null;

    public function __construct(private readonly string $path)
    {
        if (! is_file($this->path)) {
            throw new RuntimeException("Spreadsheet not found: {$this->path}");
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new RuntimeException("Spreadsheet could not be read: {$this->path}");
        }

        $this->data = $contents;
        $this->readCentralDirectory();
    }

    public static function open(string $path): self
    {
        return new self($path);
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Worksheet names in workbook order.
     *
     * @return list<string>
     */
    public function sheetNames(): array
    {
        $workbook = $this->readPart('xl/workbook.xml');

        if ($workbook === null) {
            return [];
        }

        preg_match_all('~<sheet\b[^>]*?/>~', $workbook, $matches);

        $names = [];

        foreach ($matches[0] as $tag) {
            if (preg_match('~name="([^"]*)"~', $tag, $name) === 1) {
                $names[] = html_entity_decode($name[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        return $names;
    }

    /**
     * Non-empty cells of a worksheet, keyed by row number then column letter.
     *
     * @return array<int, array<string, string>>
     */
    public function rows(string $sheetName): array
    {
        $target = $this->worksheetPath($sheetName);
        $xml = $this->readPart($target);

        if ($xml === null) {
            throw new RuntimeException("Worksheet '{$sheetName}' is missing from the workbook.");
        }

        $rows = [];

        preg_match_all('~<row\b[^>]*?\br="(\d+)"[^>]*?>(.*?)</row>~s', $xml, $rowMatches, PREG_SET_ORDER);

        foreach ($rowMatches as $rowMatch) {
            $cells = $this->parseRow($rowMatch[2]);

            if ($cells !== []) {
                $rows[(int) $rowMatch[1]] = $cells;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function parseRow(string $rowXml): array
    {
        $cells = [];

        preg_match_all('~<c\s+r="([A-Z]+)\d+"([^>]*?)(?:/>|>(.*?)</c>)~s', $rowXml, $cellMatches, PREG_SET_ORDER);

        foreach ($cellMatches as $cell) {
            $attributes = $cell[2] ?? '';
            $inner = $cell[3] ?? '';
            $value = $this->cellValue($attributes, $inner);

            if ($value !== '') {
                $cells[$cell[1]] = $value;
            }
        }

        return $cells;
    }

    private function cellValue(string $attributes, string $inner): string
    {
        $isSharedString = str_contains($attributes, 't="s"');
        $isFormulaString = str_contains($attributes, 't="str"');
        $isInlineString = str_contains($attributes, 't="inlineStr"');

        if (preg_match('~<v>(.*?)</v>~s', $inner, $valueMatch) === 1) {
            $raw = $valueMatch[1];

            if ($isSharedString) {
                $index = (int) $raw;

                return $this->sharedStrings()[$index] ?? '';
            }

            return $isFormulaString
                ? $this->decode($raw)
                : $raw;
        }

        if ($isInlineString && preg_match('~<t[^>]*>(.*?)</t>~s', $inner, $textMatch) === 1) {
            return $this->decode($textMatch[1]);
        }

        return '';
    }

    private function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(): array
    {
        if ($this->sharedStrings !== null) {
            return $this->sharedStrings;
        }

        $this->sharedStrings = [];
        $xml = $this->readPart('xl/sharedStrings.xml');

        if ($xml === null) {
            return $this->sharedStrings;
        }

        preg_match_all('~<si>(.*?)</si>~s', $xml, $matches);

        foreach ($matches[1] as $item) {
            preg_match_all('~<t[^>]*>(.*?)</t>~s', $item, $textMatches);
            $this->sharedStrings[] = $this->decode(implode('', $textMatches[1]));
        }

        return $this->sharedStrings;
    }

    /**
     * Resolve a worksheet name to its part path through the workbook relationships,
     * so column mapping never depends on the physical sheet order.
     */
    private function worksheetPath(string $sheetName): string
    {
        $workbook = $this->readPart('xl/workbook.xml');
        $relationships = $this->readPart('xl/_rels/workbook.xml.rels');

        if ($workbook === null || $relationships === null) {
            throw new RuntimeException('Workbook parts are missing from the spreadsheet.');
        }

        $relationId = null;

        foreach ($this->sheetTags($workbook) as $tag) {
            $name = $this->attribute($tag, 'name');

            if ($name !== null && $this->decode($name) === $sheetName) {
                $relationId = $this->attribute($tag, 'r:id');
                break;
            }
        }

        if ($relationId === null) {
            throw new RuntimeException("Worksheet '{$sheetName}' was not found. Available: " . implode(', ', $this->sheetNames()));
        }

        foreach ($this->sheetTags($relationships) as $tag) {
            if ($this->attribute($tag, 'Id') !== $relationId) {
                continue;
            }

            $target = $this->attribute($tag, 'Target');

            if ($target === null) {
                break;
            }

            return str_starts_with($target, '/')
                ? ltrim($target, '/')
                : 'xl/' . ltrim($target, './');
        }

        throw new RuntimeException("Worksheet relationship {$relationId} could not be resolved.");
    }

    /**
     * @return list<string>
     */
    private function sheetTags(string $xml): array
    {
        preg_match_all('~<(?:sheet|Relationship)\b[^>]*?/>~', $xml, $matches);

        return $matches[0];
    }

    private function attribute(string $tag, string $name): ?string
    {
        return preg_match('~' . preg_quote($name, '~') . '="([^"]*)"~', $tag, $match) === 1
            ? $match[1]
            : null;
    }

    private function readPart(string $name): ?string
    {
        $entry = $this->entries[$name] ?? null;

        if ($entry === null) {
            return null;
        }

        $offset = $entry['offset'];

        if (substr($this->data, $offset, 4) !== self::LOCAL_SIGNATURE) {
            return null;
        }

        $nameLength = $this->readUInt16($offset + 26);
        $extraLength = $this->readUInt16($offset + 28);
        $start = $offset + 30 + $nameLength + $extraLength;
        $raw = substr($this->data, $start, $entry['compressed']);

        if ($raw === false) {
            return null;
        }

        return match ($entry['method']) {
            0 => $raw,
            8 => $this->inflate($raw, $name),
            default => throw new RuntimeException("Unsupported compression method for {$name}."),
        };
    }

    private function inflate(string $raw, string $name): string
    {
        $inflated = @gzinflate($raw);

        if ($inflated === false) {
            throw new RuntimeException("Corrupt deflate stream while reading {$name}.");
        }

        return $inflated;
    }

    private function readCentralDirectory(): void
    {
        $eocd = strrpos($this->data, self::EOCD_SIGNATURE);

        if ($eocd === false) {
            throw new RuntimeException("Not a valid .xlsx file (no end-of-central-directory record): {$this->path}");
        }

        $count = $this->readUInt16($eocd + 10);
        $offset = $this->readUInt32($eocd + 16);

        for ($i = 0; $i < $count; $i++) {
            if (substr($this->data, $offset, 4) !== self::CENTRAL_SIGNATURE) {
                break;
            }

            $method = $this->readUInt16($offset + 10);
            $compressed = $this->readUInt32($offset + 20);
            $nameLength = $this->readUInt16($offset + 28);
            $extraLength = $this->readUInt16($offset + 30);
            $commentLength = $this->readUInt16($offset + 32);
            $localOffset = $this->readUInt32($offset + 42);
            $name = (string) substr($this->data, $offset + 46, $nameLength);

            $this->entries[$name] = [
                'method' => $method,
                'compressed' => $compressed,
                'offset' => $localOffset,
            ];

            $offset += 46 + $nameLength + $extraLength + $commentLength;
        }
    }

    private function readUInt16(int $offset): int
    {
        $value = unpack('v', (string) substr($this->data, $offset, 2));

        return $value === false ? 0 : $value[1];
    }

    private function readUInt32(int $offset): int
    {
        $value = @unpack('V', (string) substr($this->data, $offset, 4));

        return $value === false ? 0 : $value[1];
    }
}
