---
title: Testing
description: Check a fixed-width export byte by byte, through its route or with the writer alone.
order: 30
---

A fixed-width file is right only when every byte is where the receiver expects it, so tests compare the exact output. There are two levels: the route, with Waypoint's testing kit, and the writer alone, without an app.

## Through the route

`Waypoint\Testing\TestCase` sends requests through the app without a server. Register the plugins and controllers in the test as the app does:

```php
<?php

use Waypoint\Export\{ExportOptions, ExportPlugin};
use Waypoint\Export\Fixed\FixedWidthPlugin;
use Waypoint\Testing\TestCase;

final class BookingExportTest extends TestCase
{
    public function testBookingsAreWrittenAsFixedWidthRecords(): void
    {
        $app = $this->app();
        $app->plugin(new ExportPlugin());
        $app->plugin(new FixedWidthPlugin());
        $app->configure(fn(ExportOptions $export) => $export->dateFormat = 'Ymd');
        $app->attach([BookingExportController::class]);

        $this->get('/exports/bookings.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=windows-1252')
            ->assertHeader('Content-Disposition', 'attachment; filename="bookings.txt"; filename*=UTF-8\'\'bookings.txt')
            ->assertBody(
                "00000042M\xFCller GmbH             1234,520260301\r\n"
                . "00000007Schr\xF6der & S\xF6hne Han    -80,2520260302\r\n"
            );
    }
}
```

The expected body is written as bytes: `\xFC` is `ü` and `\xF6` is `ö` in Windows-1252. Writing the test in the encoding of the file catches a wrong `encoding` setting, which a test with `encoding = 'UTF-8'` would not.

For an EBCDIC file, compare hex strings; they are easier to read in a failure than raw bytes:

```php
$app->configure(function (FixedWidthOptions $fixed) {
    $fixed->encoding = 'cp273';
    $fixed->lineEnding = '';
});

$this->assertSame('f0f0f0f0f0f0f4f2', substr(bin2hex($this->get('/exports/bookings.txt')->body()), 0, 16));   // "00000042"
```

## The writer alone

A test of the record layout needs no routes. `FixedWidthWriter` takes the export plugin's `Cells`, which turns values into text with `ExportOptions`, and a `FixedWidthOptions`; `stream()` yields the file record by record:

```php
<?php

use PHPUnit\Framework\TestCase;
use Waypoint\Export\{Cells, ExportOptions, Sheet};
use Waypoint\Export\Fixed\{FixedWidthOptions, FixedWidthWriter};

final class BookingLayoutTest extends TestCase
{
    public function testTheRecordIsFortySixCharactersLong(): void
    {
        $export = new ExportOptions();
        $export->dateFormat = 'Ymd';
        $options = new FixedWidthOptions();
        $options->encoding = 'UTF-8';
        $writer = new FixedWidthWriter(new Cells($export), $options);

        $sheet = Sheet::file('bookings.txt')
            ->columnsOf(Booking::class)
            ->rows([new Booking(42, 'Müller GmbH', 1234.5, new DateTimeImmutable('2026-03-01'))]);
        $records = iterator_to_array($writer->stream($sheet), false);

        $this->assertSame(["00000042Müller GmbH             1234,520260301\r\n"], $records);
        $this->assertSame(46, mb_strlen(rtrim($records[0], "\r\n")));
    }
}
```

One field is checked with the static `FixedWidthWriter::field()`, which applies a layout to one value:

```php
$this->assertSame('000042', FixedWidthWriter::field('42', new Fixed(6, 'right', '0')));
```

## What to cover

- **Every field once** with a value that fills it exactly and one that is too long: a wrong width shifts every field after it.
- **Characters outside ASCII** (`ä`, `ß`, `€`) in the target encoding, as bytes.
- **Empty values** (`null`, missing keys): they become a field of fill characters.
- **Negative numbers** in right-aligned, zero-filled fields, if the data can have them ([Amounts, signs and implied decimals](layouts.md#amounts-signs-and-implied-decimals)).
- **The record count**, for a receiver that checks it: one record per row, plus one with `header = true`.

See also: [Record layouts](layouts.md), [File format](file-format.md), [`FixedWidthWriter`](../reference/Waypoint/Export/Fixed/FixedWidthWriter.md).
