---
title: Waypoint Export Fixed
description: Fixed-width record files for Waypoint Export, in Windows-1252, UTF-8 or an EBCDIC code page.
order: 1
---

Many tills, wholesalers' order systems, host systems and older ERP importers read their interface files as fixed-width records: every field has a set width and position, there are no separators and no quoting, and the file comes in a single-byte encoding such as Windows-1252 or an EBCDIC code page. `systonia/waypoint-export-fixed` adds that format to [Waypoint Export](https://github.com/systonia/Waypoint-Export).

A route returns a `Sheet` as it would for a CSV or XLSX download. Each column carries a [`#[Fixed]`](reference/Waypoint/Export/Fixed/Fixed.md) layout (width, alignment, fill character), and the writer turns every row into one record: values formatted, cut to their width, padded, joined, and encoded.

## Install

```bash
composer require systonia/waypoint-export-fixed
```

This also installs `systonia/waypoint-export`. Register both plugins, the base plugin first:

```php
use Waypoint\Export\ExportPlugin;
use Waypoint\Export\Fixed\FixedWidthPlugin;

$app->plugin(new ExportPlugin());
$app->plugin(new FixedWidthPlugin());   // after ExportPlugin, or registering it throws
```

The plugin adds its writer to the export plugin's writer registry under two names: `txt`, so a file named `*.txt` is written as fixed-width records, and `fixed`, for any other file name.

## A first export

Sonnenkorn, the bakery of the [Waypoint example app](https://github.com/systonia/Waypoint-Bakery), hands the day's pickups to its till every morning: one record per order line, so the cashier only scans the order code. The row class describes the record:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Export\{Column, ExportOptions, ExportPlugin, Sheet};
use Waypoint\Export\Fixed\{Fixed, FixedWidthPlugin};
use Waypoint\Waypoint;

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
    ) {
    }
}

#[Controller('/backoffice')]
final class TillController
{
    #[Get('/pickups.txt')]
    public function pickups(): Sheet
    {
        $day = new DateTimeImmutable('2026-10-17');
        return Sheet::file('pickups.txt')
            ->columnsOf(PickupLine::class)
            ->rows([
                new PickupLine('3f9a1c07b2', $day, 'Lena Schäfer', 4, 'Butter croissant', 6, 10.8),
                new PickupLine('3f9a1c07b2', $day, 'Lena Schäfer', 1, 'Sourdough loaf', 1, 5.2),
                new PickupLine('a04e6d915c', $day, 'Maximilian Großmann', 3, 'Lye pretzel', 12, 14.4),
            ]);
    }
}

$app = Waypoint::create();
$app->plugin(new ExportPlugin());
$app->plugin(new FixedWidthPlugin());
$app->configure(fn (ExportOptions $export) => $export->dateFormat = 'Ymd');
$app->attach([TillController::class]);
$app->serve();
```

`GET /backoffice/pickups.txt` downloads three records of 66 characters, each ended by CR LF, in Windows-1252:

```text
3f9a1c07b220261017Lena Schäfer    0004Butter croissant  006   10,8
3f9a1c07b220261017Lena Schäfer    0001Sourdough loaf    001    5,2
a04e6d915c20261017Maximilian Großm0003Lye pretzel       012   14,4
```

The response has `Content-Type: text/plain; charset=windows-1252` and `Content-Disposition: attachment; filename="pickups.txt"; filename*=UTF-8''pickups.txt`. The article number and the quantity are right-aligned and filled with zeros, the customer's name is cut at 16 characters, the amount uses the export plugin's decimal comma, and the date follows `ExportOptions::$dateFormat`. [Record layouts](guides/layouts.md#on-a-row-class) builds the same lines from the bakery's open orders, behind the owner's sign-in.

## How these docs are organised

- **[Guides](guides/index.md)**: [record layouts](guides/layouts.md) with `#[Fixed]` and `Sheet`, the [file format](guides/file-format.md) set by `FixedWidthOptions` (encodings including EBCDIC, line endings, header, record length), and [testing](guides/testing.md) an export.
- **[Reference](reference/index.md)**: one page per class, generated from the code with `tenma docs`.
