<?php

namespace Waypoint\Export\Fixed;

use InvalidArgumentException;
use Waypoint\Export\{Cells, Sheet, Writer};

/** Answers to the `txt` extension (or `format: 'fixed'` via the alias registered by the plugin). Every column needs a #[Fixed] layout. */
final class FixedWidthWriter implements Writer
{
    public function __construct(private readonly Cells $cells, private readonly FixedWidthOptions $options, private readonly string $extension = 'txt')
    {
    }

    public function extension(): string
    {
        return $this->extension;
    }

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
            yield $this->record(array_map(fn(array $c): string => $c['label'], $columns), $layouts);
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
            $record = mb_substr($record . str_repeat(' ', max(0, $this->options->recordLength - mb_strlen($record))), 0, $this->options->recordLength);
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

    public static function field(string $value, Fixed $layout): string
    {
        $value = mb_substr(str_replace(["\r", "\n"], ' ', $value), 0, $layout->width);
        $fill = str_repeat($layout->pad, $layout->width - mb_strlen($value));
        return $layout->align === 'right' ? $fill . $value : $value . $fill;
    }
}
