<?php

namespace Tests\Support;

/**
 * Builds a minimal but structurally valid .xlsx for tests.
 *
 * The production workbook is a 330 KB government file that is deliberately kept out of
 * version control, so the seeder tests generate their own workbook instead. Entries are
 * written with the "stored" method (no deflate) which keeps this readable and avoids
 * depending on ext-zip on either side.
 */
class MiniXlsx
{
    /**
     * @param  array<string, list<array<string, string|null>>>  $sheets  Sheet name => rows of column-letter => value.
     */
    public static function write(string $path, array $sheets, string $summaryName = 'Summary'): string
    {
        $ordered = array_merge([$summaryName => []], $sheets);

        $shared = [];
        $sharedIndex = [];

        $intern = function (?string $value) use (&$shared, &$sharedIndex): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            if (! isset($sharedIndex[$value])) {
                $sharedIndex[$value] = count($shared);
                $shared[] = $value;
            }

            return (string) $sharedIndex[$value];
        };

        $parts = [];
        $sheetTags = [];
        $rels = [];
        $index = 0;

        foreach ($ordered as $name => $rows) {
            $index++;
            $target = 'worksheets/sheet' . $index . '.xml';
            $relationId = 'rId' . $index;
            $sheetTags[] = '<sheet name="' . $name . '" sheetId="' . $index . '" r:id="' . $relationId . '"/>';
            $rels[] = '<Relationship Id="' . $relationId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="' . $target . '"/>';

            $parts['xl/' . $target] = self::worksheetXml($rows, $intern);
        }

        $parts['xl/workbook.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . implode('', $sheetTags) . '</sheets></workbook>';

        $parts['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . implode('', $rels) . '</Relationships>';

        $items = '';
        foreach ($shared as $value) {
            $items .= '<si><t>' . self::escape($value) . '</t></si>';
        }

        $parts['xl/sharedStrings.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">'
            . $items . '</sst>';

        file_put_contents($path, self::zip($parts));

        return $path;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @param  callable(?string): ?string  $intern
     */
    private static function worksheetXml(array $rows, callable $intern): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowNumber => $row) {
            $xml .= '<row r="' . ($rowNumber + 1) . '">';

            foreach ($row as $column => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $index = $intern((string) $value);
                $reference = $column . ($rowNumber + 1);

                if ($index === null) {
                    continue;
                }

                $xml .= '<c r="' . $reference . '" t="s"><v>' . $index . '</v></c>';
            }

            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @param  array<string, string>  $parts
     */
    private static function zip(array $parts): string
    {
        $local = '';
        $central = '';
        $offset = 0;

        foreach ($parts as $name => $contents) {
            $crc = crc32($contents);
            $size = strlen($contents);

            $header = "PK\x03\x04"
                . pack('v', 20) . pack('v', 0) . pack('v', 0) . pack('v', 0) . pack('v', 0)
                . pack('V', $crc) . pack('V', $size) . pack('V', $size)
                . pack('v', strlen($name)) . pack('v', 0)
                . $name;

            $local .= $header . $contents;

            $central .= "PK\x01\x02"
                . pack('v', 20) . pack('v', 20) . pack('v', 0) . pack('v', 0) . pack('v', 0) . pack('v', 0)
                . pack('V', $crc) . pack('V', $size) . pack('V', $size)
                . pack('v', strlen($name)) . pack('v', 0) . pack('v', 0)
                . pack('v', 0) . pack('v', 0) . pack('V', 0)
                . pack('V', $offset) . $name;

            $offset += strlen($header) + $size;
        }

        return $local . $central . "PK\x05\x06"
            . pack('v', 0) . pack('v', 0)
            . pack('v', count($parts)) . pack('v', count($parts))
            . pack('V', strlen($central)) . pack('V', $offset)
            . pack('v', 0);
    }
}
