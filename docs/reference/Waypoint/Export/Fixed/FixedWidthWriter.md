---
title: "FixedWidthWriter"
description: "Writes a Sheet as fixed-width records, one per row."
order: 100
generated: true
---

`Waypoint\Export\Fixed\FixedWidthWriter` · final class · namespace [`Waypoint\Export\Fixed`](index.md)

```php
final class FixedWidthWriter implements \Waypoint\Export\Writer
```

Implements `Waypoint\Export\Writer`

Writes a Sheet as fixed-width records, one per row.

It answers to the `txt` extension (or `format: 'fixed'` via the second instance the
plugin registers). Every column needs a #[Fixed] layout. Values are turned into text
by Cells (ExportOptions), cut and padded to their field, joined into a record and
encoded as FixedWidthOptions says.

## Methods

### __construct()

```php
public function __construct(
    private readonly \Waypoint\Export\Cells $cells,
    private readonly \Waypoint\Export\Fixed\FixedWidthOptions $options,
    private readonly string $extension = 'txt',
)
```

**Parameters**

- `$cells` `\Waypoint\Export\Cells`
- `$options` [`FixedWidthOptions`](FixedWidthOptions.md)
- `$extension` `string` — the format name this instance answers to

### extension()

```php
public function extension(): string
```

The file extension the writer answers to; `Sheet::file('x.<extension>')` selects it.

**Returns** `string`

### contentType()

```php
public function contentType(): string
```

`text/plain` with the configured encoding as charset, lowercased: `text/plain; charset=windows-1252`.

**Returns** `string`

### stream()

```php
public function stream(\Waypoint\Export\Sheet $sheet): iterable
```

The file, chunk by chunk -- a generator, so a large export never sits in memory twice.

**Parameters**

- `$sheet` `\Waypoint\Export\Sheet`

**Returns** `iterable<string>`

### field()

```php
public static function field(string $value, \Waypoint\Export\Fixed\Fixed $layout): string
```

One field: line breaks become spaces, then the value is cut to the width and padded.

```php
FixedWidthWriter::field('42', new Fixed(5, 'right', '0'));   // '00042'
```

**Parameters**

- `$value` `string`
- `$layout` [`Fixed`](Fixed.md)

**Returns** `string`
