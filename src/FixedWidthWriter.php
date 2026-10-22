<?php

namespace Waypoint\Export\Fixed;

use InvalidArgumentException;
use Waypoint\Export\{Cells, Sheet, Writer};

/**
 * Writes a Sheet as fixed-width records, one per row.
 *
 * It answers to the `txt` extension (or `format: 'fixed'` via the second instance the
 * plugin registers). Every column needs a #[Fixed] layout. Values are turned into text
 * by Cells (ExportOptions), cut and padded to their field, joined into a record and
 * encoded as FixedWidthOptions says.
 */
final class FixedWidthWriter implements Writer
{
    /** @param string $extension the format name this instance answers to */
    public function __construct(private readonly Cells $cells, private readonly FixedWidthOptions $options, private readonly string $extension = 'txt')
    {
    }

    public function extension(): string
    {
        return $this->extension;
    }

    /** `text/plain` with the configured encoding as charset, lowercased: `text/plain; charset=windows-1252`. */
    public function contentType(): string
    {
        return 'text/plain; charset=' . strtolower($this->options->encoding);
    }

    public function stream(Sheet $sheet): iterable
    {
        $columns = $sheet->getColumns();
        $layouts = [];
        foreach ($columns as $column) {
            $layout = $column['meta'][Fixed::class] ?? null;
            if (!$layout instanceof Fixed) {
                throw new InvalidArgumentException("Column '{$column['key']}' of '{$sheet->getFilename()}' has no #[Fixed] layout.");
            }
            $layouts[] = $layout;
        }
        if ($this->options->header) {
            yield $this->record(array_map(fn (array $c): string => $c['label'], $columns), $layouts);
        }
        foreach ($sheet->getRows() as $row) {
            yield $this->record(array_map($this->cells->text(...), $this->cells->row($columns, $row)), $layouts);
        }
    }

    /**
     * @param list<string> $values
     * @param list<Fixed> $layouts
     */
    private function record(array $values, array $layouts): string
    {
        $record = '';
        foreach ($values as $i => $value) {
            $record .= self::field($value, $layouts[$i]);
        }
        if ($this->options->recordLength !== null) {
            $length = $this->options->recordLength;
            $record = mb_substr(mb_str_pad($record, $length), 0, $length);
        }
        return $this->encode($record . $this->options->lineEnding);
    }

    private function encode(string $utf8): string
    {
        $encoding = $this->options->encoding;
        return match (true) {
            strcasecmp($encoding, 'UTF-8') === 0 => $utf8,
            Ebcdic::supports($encoding) => Ebcdic::convert($utf8, $encoding),
            default => mb_convert_encoding($utf8, $encoding, 'UTF-8'),
        };
    }

    /**
     * One field: line breaks become spaces, then the value is cut to the width and padded.
     *
     * ```php
     * FixedWidthWriter::field('42', new Fixed(5, 'right', '0'));   // '00042'
     * ```
     */
    public static function field(string $value, Fixed $layout): string
    {
        $value = mb_substr(str_replace(["\r", "\n"], ' ', $value), 0, $layout->width);
        $side = $layout->align === 'right' ? STR_PAD_LEFT : STR_PAD_RIGHT;
        return mb_str_pad($value, $layout->width, $layout->pad, $side);
    }
}
