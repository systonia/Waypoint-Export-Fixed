---
title: Record layouts
description: Give every column a width, an alignment and a fill character, on a row class or on the Sheet.
order: 10
---

A fixed-width record is the row's fields written one after the other, each in its own width. [`#[Fixed]`](../reference/Waypoint/Export/Fixed/Fixed.md) describes one field; every column of the `Sheet` needs one.

## On a row class

The usual place for the layout is a row class: `#[Column]` from Waypoint Export makes a public property a column, and `#[Fixed]` next to it sets the field. `Sheet::columnsOf()` collects the columns in declaration order and attaches every other attribute on the property to the column, which is how the writer finds the layout.

```php
use Waypoint\Export\Column;
use Waypoint\Export\Fixed\Fixed;

final class Payment
{
    public function __construct(
        #[Column('Account')]
        #[Fixed(
            10,
            align: 'right',
            pad: '0'
        )]
        public int $account,
        #[Column('Payee')]
        #[Fixed(27)]
        public string $payee,
        #[Column('Due')]
        #[Fixed(8)]
        public DateTimeImmutable $due,
        public float $amount,
        #[Column('Reference')]
        #[Fixed(35)]
        public ?string $reference = null,
    ) {
    }
}
```

```php
#[Get('/payments.txt')]
public function payments(): Sheet
{
    return Sheet::file('payments.txt')
        ->columnsOf(Payment::class)
        ->rows($this->payments->open());
}
```

With `ExportOptions::$dateFormat` set to `'Ymd'`, a payment becomes an 80-character record:

```text
0000012345Stadtwerke                 20261101                                   
```

Promoted constructor properties, as above, and plain properties work the same way. A property without `#[Column]`, like `$amount`, is not a field; a value closure can still use it ([below](#amounts-signs-and-implied-decimals)). The record layout is the order of the properties in the class: moving a property moves its field in the file.

## The three arguments

```php
new Fixed(width: 8, align: 'right', pad: '0');   // defaults: align 'left', pad ' '
```

| Argument | Rule | Typical use |
|---|---|---|
| `width` | at least 1, counted in characters | the field length from the interface description |
| `align` | `'left'` or `'right'` | `'left'` for text, `'right'` for numbers |
| `pad` | exactly one character | `' '` for text, `'0'` for numbers with leading zeros |

Anything else throws an `InvalidArgumentException` when the `Fixed` object is created. For an attribute, that is when `columnsOf()` reads the class.

## Without a row class

A `Sheet` built column by column gets its layouts through `meta()`, which attaches an object to the column added last:

```php
#[Get('/host.dat')]
public function host(): Sheet
{
    return Sheet::file('host.dat', format: 'fixed')
        ->column('account', 'Account')->meta(new Fixed(6, 'right', '0'))
        ->column('text', 'Text', fn(array $row): string => mb_strtoupper($row['text']))->meta(new Fixed(10))
        ->rows([['account' => 815, 'text' => 'Gebühr']]);
}
```

```text
000815GEBÜHR    
```

A column reads the row's array key or public property of the same name; with a closure as third argument, the closure computes the value from the row. Because `meta()` always goes to the last column, `Sheet::columns([...])`, which adds several columns at once, leaves all but the last without a layout; use one `column()` call per field.

## From value to field

Each value first becomes text the way the export plugin writes text formats, with the settings of `ExportOptions`:

| Value | Text |
|---|---|
| `null`, a missing key or property | empty |
| `int` | as it is: `42` |
| `float` | up to six decimals, without trailing zeros, with `ExportOptions::$decimalSeparator` (default `,`): `1234,5` |
| `bool` | `ExportOptions::$booleans` (default `['1', '0']`) |
| `DateTimeInterface` | `ExportOptions::$dateFormat` (default `'d.m.Y H:i'`) |
| backed enum | its value, formatted as above |
| `string`, `Stringable` | as it is |
| anything else | JSON |

Interfaces mostly want other conventions than a spreadsheet, so the export settings are often changed for an app that writes fixed-width files:

```php
$app->configure(function (ExportOptions $export) {
    $export->dateFormat = 'Ymd';          // 20260301
    $export->decimalSeparator = '.';      // 1234.5
    $export->booleans = ['J', 'N'];
});
```

These settings belong to the export plugin and apply to its CSV and HTML output as well. A format that only this file needs goes into a value closure instead.

Then the writer builds the field:

1. CR and LF inside the value become spaces, so a value never breaks a record.
2. The value is cut to `width` characters.
3. The rest of the field is filled with `pad`: after the value for `'left'`, before it for `'right'`.

```php
use Waypoint\Export\Fixed\{Fixed, FixedWidthWriter};

FixedWidthWriter::field('Müller GmbH', new Fixed(8));          // 'Müller G'
FixedWidthWriter::field('42', new Fixed(6, 'right', '0'));     // '000042'
FixedWidthWriter::field("Line 1\nLine 2", new Fixed(14));     // 'Line 1 Line 2 '
```

## Amounts, signs and implied decimals

The fill character goes in front of the whole text, sign included: `-3` in `new Fixed(6, 'right', '0')` becomes `0000-3`. Many interfaces instead expect amounts in the smallest currency unit with the sign first, or a separate sign field. Compute such fields in a value closure:

```php
Sheet::file('payments.txt')
    ->column('account', 'Account')->meta(new Fixed(10, 'right', '0'))
    ->column('cents', 'Amount', fn(Payment $p): string => sprintf('%011d', (int) round($p->amount * 100)))->meta(new Fixed(11))
    ->column('sign', 'Sign', fn(Payment $p): string => $p->amount < 0 ? 'S' : 'H')->meta(new Fixed(1))
    ->rows($payments);
```

```text
0000000001-0000008025S
000000000200000001230H
```

`sprintf('%011d', -8025)` gives `-0000008025`: eleven characters, zero-filled after the sign, so the field needs no padding.

## Width is counted in characters

`width` counts characters, not bytes. In Windows-1252, ISO-8859-x and the EBCDIC code pages every character is one byte, so the records of a file all have the same byte length. In UTF-8, `ü` takes two bytes; a record with umlauts is longer in bytes than one without. A receiver that cuts records by byte positions needs a single-byte [encoding](file-format.md#encoding).

## A column without a layout

The writer checks every column before the first record and stops with an `InvalidArgumentException` naming the column and the file, for example `Column 'b' of 'x.txt' has no #[Fixed] layout.` A missing layout is a mistake in the code, so there is no default width. The exception is thrown when the response is written, before any record; Waypoint logs it and answers with a 500 error response.

## File names and formats

The export plugin picks the writer by the file's extension, or by `format:` when given:

```php
Sheet::file('bookings.txt');                     // the fixed-width writer, by extension
Sheet::file('BUCHUNG.DAT', format: 'fixed');     // any other name
Sheet::file('export', format: 'fixed');          // a name without extension
```

The extension is lowercased, so `BUCHUNG.TXT` works too; a `format:` must be lowercase letters and digits, or `Sheet::file()` throws. The download always gets `Content-Disposition: attachment` with the file name, `Cache-Control: no-store` and `Content-Type: text/plain` with the [encoding](file-format.md#encoding) as charset. In the OpenAPI spec, a route returning `Sheet` lists `text/plain` among its content types.

## Large files

The writer produces the file one record at a time. With a generator as `rows()`, a large export never holds all rows in memory:

```php
#[Get('/bookings.txt')]
public function bookings(): Sheet
{
    return Sheet::file('bookings.txt')
        ->columnsOf(Booking::class)
        ->rows((function () {
            foreach ($this->bookings->cursor() as $record) {
                yield new Booking($record['customer'], $record['name'], $record['amount'], new DateTimeImmutable($record['booked']));
            }
        })());
}
```

See also: [`Fixed`](../reference/Waypoint/Export/Fixed/Fixed.md), [`FixedWidthWriter`](../reference/Waypoint/Export/Fixed/FixedWidthWriter.md), [File format](file-format.md).
