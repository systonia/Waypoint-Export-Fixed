---
title: File format
description: FixedWidthOptions sets the encoding (Windows-1252, UTF-8, EBCDIC), line endings, a header record and a fixed record length.
order: 20
---

How the records are written to the file is set in [`FixedWidthOptions`](../reference/Waypoint/Export/Fixed/FixedWidthOptions.md), configured like every Waypoint options class:

```php
use Waypoint\Export\Fixed\FixedWidthOptions;

$app->configure(function (FixedWidthOptions $fixed) {
    $fixed->encoding = 'cp273';
    $fixed->lineEnding = "\n";
    $fixed->header = false;
    $fixed->recordLength = 120;
});
```

| Setting | Default | |
|---|---|---|
| `encoding` | `'Windows-1252'` | the file's character encoding |
| `lineEnding` | `"\r\n"` | what ends a record; `''` for none |
| `header` | `false` | write the column labels as a first record |
| `recordLength` | `null` | pad or cut every record to this many characters |

The settings apply to every fixed-width file of the app. Values come from code here; when they differ between environments, read them through `Waypoint\Environment` inside the `configure()` callback.

## Encoding

The writer builds each record as UTF-8 and converts it as the last step.

| `encoding` | Result |
|---|---|
| `'Windows-1252'` (default) | one byte per character; covers German umlauts, `ß` and `€` |
| `'UTF-8'` | the text unchanged |
| another name PHP's mbstring knows, e.g. `'ISO-8859-15'`, `'ISO-8859-1'` | converted by `mb_convert_encoding()` |
| an EBCDIC code page: `'cp037'`, `'cp273'`, `'cp500'`, `'cp1140'` | converted with the plugin's own tables |

Characters the target encoding does not have become `?`. A name that is neither an EBCDIC page nor known to mbstring makes the export fail with a `ValueError` from `mb_convert_encoding()`.

The response's `Content-Type` carries the encoding name, lowercased: `text/plain; charset=windows-1252`, `text/plain; charset=cp273`.

### EBCDIC code pages

IBM mainframes and IBM i (AS/400) systems use EBCDIC, where even letters and digits have other byte values than in ASCII (`A` is `0xC1`, `1` is `0xF1`, a space `0x40`). The plugin has four code pages built in, so they work on every host whatever mbstring or iconv support:

| Code page | Used for | Notes |
|---|---|---|
| `cp037` | USA, Canada | |
| `cp273` | Germany, Austria | `ü` is `0xD0` |
| `cp500` | international | |
| `cp1140` | USA, Canada | cp037 with `€` at `0x9F` |

The name is matched loosely: a prefix `cp`, `ibm` or `ebcdic` in any case, then the number, so `'cp273'`, `'IBM273'`, `'IBM-273'` and `'EBCDIC-273'` all select cp273. A bare `'273'` is not an EBCDIC name.

The conversion goes through Latin-1 (for cp1140: Windows-1252, which has the euro sign). A character outside it (`€` in cp037, cp273 and cp500, or any character beyond Western European scripts) becomes `?`, which is `0x6F` in EBCDIC.

```php
use Waypoint\Export\Fixed\Ebcdic;

bin2hex(Ebcdic::convert('A1 ü', 'cp273'));   // 'c1f140d0'
bin2hex(Ebcdic::convert('A€', 'cp1140'));    // 'c19f'
bin2hex(Ebcdic::convert('A€', 'cp273'));     // 'c16f': no euro in cp273
Ebcdic::supports('EBCDIC-500');              // true
```

Many host transfers read such files as binary records without line ends. With the default `"\r\n"`, the line ending is converted too and arrives as the bytes `0x0D 0x25` (EBCDIC carriage return and line feed); set `lineEnding = ''` when the receiver expects a block file.

## Line endings

`lineEnding` ends every record, including the last one.

| Value | Use |
|---|---|
| `"\r\n"` (default) | Windows-based importers |
| `"\n"` | Unix hosts |
| `''` | block files: records follow each other without separator and are known only by their length |

For a block file, all records must have the same length, which the field widths guarantee in a single-byte encoding. `recordLength` adds a fixed length on top.

## A fixed record length

Some interfaces define a record length larger than the fields actually used, such as 120 or 256 characters with a reserved tail. `recordLength` pads every record with spaces to that length:

```php
$app->configure(fn(FixedWidthOptions $fixed) => $fixed->recordLength = 120);
```

A record longer than `recordLength` is cut to it, so fields beyond that length are lost. Without `recordLength`, a record is as long as the sum of its field widths. The line ending comes after, and does not count towards the length.

## A header record

With `header = true`, the first record holds the column labels, each placed in its field like a value (cut, padded, aligned):

```php
$app->configure(fn(FixedWidthOptions $fixed) => $fixed->header = true);
```

```php
Sheet::file('stock.dat', format: 'fixed')
    ->column('id', 'Art')->meta(new Fixed(4, 'right', '0'))
    ->column('name', 'Name')->meta(new Fixed(14))
    ->column('stock', 'Qty')->meta(new Fixed(3, 'right', '0'))
    ->rows([['id' => 2, 'name' => 'Dinkelbrötchen', 'stock' => 30]]);
```

```text
0ArtName          Qty
0002Dinkelbrötchen030
```

The labels follow the layouts exactly: a right-aligned field puts its label on the right, and the fill character fills the label's field too. Most tills and host interfaces expect no header, so it is off by default.

See also: [`FixedWidthOptions`](../reference/Waypoint/Export/Fixed/FixedWidthOptions.md), [`Ebcdic`](../reference/Waypoint/Export/Fixed/Ebcdic.md), [Record layouts](layouts.md).
