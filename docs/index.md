---
title: Waypoint Export Fixed
description: Fixed-width record files for Waypoint Export, in Windows-1252, UTF-8 or an EBCDIC code page.
order: 1
---

Many host systems, AS/400 batches and older ERP importers read their interface files as fixed-width records: every field has a set width and position, there are no separators and no quoting, and the file comes in a single-byte encoding such as Windows-1252 or an EBCDIC code page. `systonia/waypoint-export-fixed` adds that format to [Waypoint Export](https://github.com/systonia/Waypoint-Export).

A route returns a `Sheet` as it would for a CSV or XLSX download. Each column carries a [`#[Fixed]`](reference/Waypoint/Export/Fixed/Fixed.md) layout — width, alignment, fill character — and the writer turns every row into one record: values formatted, cut to their width, padded, joined, and encoded.

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

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Export\{Column, ExportOptions, ExportPlugin, Sheet};
use Waypoint\Export\Fixed\{Fixed, FixedWidthPlugin};
use Waypoint\Waypoint;

final class Booking
{
    public function __construct(
        #[Column('Customer')]
        #[Fixed(
            8,
            align: 'right',
            pad: '0'
        )]
        public int $customer,
        #[Column('Name')]
        #[Fixed(20)]
        public string $name,
        #[Column('Amount')]
        #[Fixed(
            10,
            align: 'right'
        )]
        public float $amount,
        #[Column('Booked')]
        #[Fixed(8)]
        public DateTimeImmutable $booked,
    ) {
    }
}

#[Controller('/exports')]
final class BookingExportController
{
    #[Get('/bookings.txt')]
    public function bookings(): Sheet
    {
        return Sheet::file('bookings.txt')
            ->columnsOf(Booking::class)
            ->rows([
                new Booking(42, 'Müller GmbH', 1234.5, new DateTimeImmutable('2026-03-01')),
                new Booking(7, 'Schröder & Söhne Handelsgesellschaft', -80.25, new DateTimeImmutable('2026-03-02')),
            ]);
    }
}

$app = Waypoint::create();
$app->plugin(new ExportPlugin());
$app->plugin(new FixedWidthPlugin());
$app->configure(fn(ExportOptions $export) => $export->dateFormat = 'Ymd');
$app->attach([BookingExportController::class]);
$app->serve();
```

`GET /exports/bookings.txt` downloads two records of 46 characters, each ended by CR LF, in Windows-1252:

```text
00000042Müller GmbH             1234,520260301
00000007Schröder & Söhne Han    -80,2520260302
```

The response has `Content-Type: text/plain; charset=windows-1252` and `Content-Disposition: attachment; filename="bookings.txt"`. The customer number is right-aligned and filled with zeros, the name is cut at 20 characters, the amount uses the export plugin's decimal comma, and the date follows `ExportOptions::$dateFormat`.

## How these docs are organised

- **[Guides](guides/index.md)**: [record layouts](guides/layouts.md) with `#[Fixed]` and `Sheet`, the [file format](guides/file-format.md) set by `FixedWidthOptions` (encodings including EBCDIC, line endings, header, record length), and [testing](guides/testing.md) an export.
- **[Reference](reference/index.md)**: one page per class, generated from the code with `tenma docs`.
