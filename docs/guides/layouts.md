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

/** One line of an order, as the till reads it. */
final class PickupLine
{
    public function __construct(
        #[Column('Order')]
        #[Fixed(10)]
        public string $code,
        #[Column('Pickup')]
        #[Fixed(8)]
        public DateTimeImmutable $pickup,
        #[Column('Customer')]
        #[Fixed(16)]
        public string $customer,
        #[Column('Article')]
        #[Fixed(
            4,
            align: 'right',
            pad: '0'
        )]
        public int $product,
        #[Column('Name')]
        #[Fixed(18)]
        public string $name,
        #[Column('Quantity')]
        #[Fixed(
            3,
            align: 'right',
            pad: '0'
        )]
        public int $quantity,
        #[Column('Amount')]
        #[Fixed(
            7,
            align: 'right'
        )]
        public float $amount,
        public bool $paid = false,
    ) {
    }
}
```

In the bakery's back office, the owner downloads the open orders for the till. The route sits next to the other exports of `BackofficeController` and turns every line of an `Order` into a `PickupLine`:

```php
#[Get('/pickups.txt')]
#[Role('owner')]
public function pickups(): Sheet
{
    $lines = [];
    foreach ($this->orders->open() as $order) {
        foreach ($order->lines as $line) {
            $lines[] = new PickupLine(
                $order->code,
                new DateTimeImmutable($order->pickupDate),
                $order->customerName,
                $line->product,
                $line->name,
                $line->quantity,
                $line->totalCents() / 100,
            );
        }
    }
    return Sheet::file('pickups.txt')
        ->columnsOf(PickupLine::class)
        ->rows($lines);
}
```

With `ExportOptions::$dateFormat` set to `'Ymd'`, six croissants for Lena Schäfer become a 66-character record:

```text
3f9a1c07b220261017Lena Schäfer    0004Butter croissant  006   10,8
```

Promoted constructor properties, as above, and plain properties work the same way. A property without `#[Column]`, like `$paid`, is not a field; a value closure can still use it ([below](#amounts-signs-and-implied-decimals)). The record layout is the order of the properties in the class: moving a property moves its field in the file.

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
#[Get('/stock.dat')]
#[Role('owner')]
public function stock(): Sheet
{
    return Sheet::file('stock.dat', format: 'fixed')
        ->column('id', 'Article')->meta(new Fixed(4, 'right', '0'))
        ->column('name', 'Name', fn (array $row): string => mb_strtoupper($row['name']))->meta(new Fixed(14))
        ->column('stock', 'Stock')->meta(new Fixed(3, 'right', '0'))
        ->rows([['id' => 2, 'name' => 'Dinkelbrötchen (4)', 'stock' => 30]]);
}
```

```text
0002DINKELBRÖTCHEN030
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

FixedWidthWriter::field('Butter croissant', new Fixed(8));       // 'Butter c'
FixedWidthWriter::field('4', new Fixed(4, 'right', '0'));         // '0004'
FixedWidthWriter::field("No nuts,\nplease", new Fixed(16));       // 'No nuts, please '
```

## Amounts, signs and implied decimals

The fill character goes in front of the whole text, sign included: `-3` in `new Fixed(6, 'right', '0')` becomes `0000-3`. Many tills and order systems instead expect amounts in cents with the sign in a field of its own, here for a cancelled order that goes to the till as a refund. Compute such fields in a value closure:

```php
Sheet::file('till.txt')
    ->column('code', 'Order')->meta(new Fixed(10))
    ->column('cents', 'Amount', fn (PickupLine $l): string => sprintf('%07d', (int) round(abs($l->amount) * 100)))->meta(new Fixed(7))
    ->column('sign', 'Sign', fn (PickupLine $l): string => $l->amount < 0 ? '-' : '+')->meta(new Fixed(1))
    ->column('paid', 'Paid', fn (PickupLine $l): string => $l->paid ? 'J' : 'N')->meta(new Fixed(1))
    ->rows($lines);
```

```text
a04e6d915c0001440+N
c71b0e4a280000360-J
```

`sprintf('%07d', 1440)` gives `0001440`: seven characters, zero-filled, so the field needs no padding. `$paid` has no `#[Column]` and still feeds the last field.

## Width is counted in characters

`width` counts characters, not bytes. In Windows-1252, ISO-8859-x and the EBCDIC code pages every character is one byte, so the records of a file all have the same byte length. In UTF-8, `ü` takes two bytes; a record with umlauts is longer in bytes than one without. A receiver that cuts records by byte positions needs a single-byte [encoding](file-format.md#encoding).

## A column without a layout

The writer checks every column before the first record and stops with an `InvalidArgumentException` naming the column and the file, for example `Column 'b' of 'x.txt' has no #[Fixed] layout.` A missing layout is a mistake in the code, so there is no default width. The exception is thrown when the response is written, before any record; Waypoint logs it and answers with a 500 error response.

## File names and formats

The export plugin picks the writer by the file's extension, or by `format:` when given:

```php
Sheet::file('pickups.txt');                      // the fixed-width writer, by extension
Sheet::file('STOCK.DAT', format: 'fixed');       // any other name
Sheet::file('till', format: 'fixed');            // a name without extension
```

The extension is lowercased, so `PICKUPS.TXT` works too; a `format:` must be lowercase letters and digits, or `Sheet::file()` throws. The download always gets `Content-Disposition: attachment` with the file name, `Cache-Control: no-store` and `Content-Type: text/plain` with the [encoding](file-format.md#encoding) as charset. In the OpenAPI spec, a route returning `Sheet` lists `text/plain` among its content types.

## Large files

The writer produces the file one record at a time. With a generator as `rows()`, each `PickupLine` is built when its record is written, so the export never holds a list of all lines next to the orders; with a generator over a database cursor, it never holds all orders either:

```php
#[Get('/pickups.txt')]
#[Role('owner')]
public function pickups(): Sheet
{
    return Sheet::file('pickups.txt')
        ->columnsOf(PickupLine::class)
        ->rows((function () {
            foreach ($this->orders->open() as $order) {
                foreach ($order->lines as $line) {
                    yield new PickupLine($order->code, new DateTimeImmutable($order->pickupDate), $order->customerName, $line->product, $line->name, $line->quantity, $line->totalCents() / 100);
                }
            }
        })());
}
```

See also: [`Fixed`](../reference/Waypoint/Export/Fixed/Fixed.md), [`FixedWidthWriter`](../reference/Waypoint/Export/Fixed/FixedWidthWriter.md), [File format](file-format.md).
