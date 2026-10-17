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

final class TillControllerTest extends TestCase
{
    public function testThePickupsAreWrittenAsFixedWidthRecords(): void
    {
        $app = $this->app();
        $app->plugin(new ExportPlugin());
        $app->plugin(new FixedWidthPlugin());
        $app->configure(fn (ExportOptions $export) => $export->dateFormat = 'Ymd');
        $app->attach([TillController::class]);

        $this->get('/backoffice/pickups.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=windows-1252')
            ->assertHeader('Content-Disposition', 'attachment; filename="pickups.txt"; filename*=UTF-8\'\'pickups.txt')
            ->assertBody(
                "3f9a1c07b220261017Lena Sch\xE4fer    0004Butter croissant  006   10,8\r\n"
                . "3f9a1c07b220261017Lena Sch\xE4fer    0001Sourdough loaf    001    5,2\r\n"
                . "a04e6d915c20261017Maximilian Gro\xDFm0003Lye pretzel       012   14,4\r\n"
            );
    }
}
```

The test uses `TillController` from the [overview](../index.md#a-first-export). For the route behind `#[Role('owner')]` in the bakery's back office, with `JWTOptions` configured, the request carries the owner's token:

```php
$this->request('GET', '/backoffice/pickups.txt')
    ->withJwt(['sub' => 1, 'role' => 'owner'])
    ->send()
    ->assertOk();
```

The expected body is written as bytes: `\xE4` is `ä` and `\xDF` is `ß` in Windows-1252. Writing the test in the encoding of the file catches a wrong `encoding` setting, which a test with `encoding = 'UTF-8'` would not.

For an EBCDIC file, compare hex strings; they are easier to read in a failure than raw bytes:

```php
$app->configure(function (FixedWidthOptions $fixed) {
    $fixed->encoding = 'cp273';
    $fixed->lineEnding = '';
});

$this->assertSame('f386f981f183f0f782f2', substr(bin2hex($this->get('/backoffice/pickups.txt')->body()), 0, 20));   // "3f9a1c07b2"
```

## The writer alone

A test of the record layout needs no routes. `FixedWidthWriter` takes the export plugin's `Cells`, which turns values into text with `ExportOptions`, and a `FixedWidthOptions`; `stream()` yields the file record by record:

```php
<?php

use PHPUnit\Framework\TestCase;
use Waypoint\Export\{Cells, ExportOptions, Sheet};
use Waypoint\Export\Fixed\{FixedWidthOptions, FixedWidthWriter};

final class PickupLineTest extends TestCase
{
    public function testTheRecordIsSixtySixCharactersLong(): void
    {
        $export = new ExportOptions();
        $export->dateFormat = 'Ymd';
        $options = new FixedWidthOptions();
        $options->encoding = 'UTF-8';
        $writer = new FixedWidthWriter(new Cells($export), $options);

        $sheet = Sheet::file('pickups.txt')
            ->columnsOf(PickupLine::class)
            ->rows([new PickupLine('3f9a1c07b2', new DateTimeImmutable('2026-10-17'), 'Lena Schäfer', 4, 'Butter croissant', 6, 10.8)]);
        $records = iterator_to_array($writer->stream($sheet), false);

        $this->assertSame(["3f9a1c07b220261017Lena Schäfer    0004Butter croissant  006   10,8\r\n"], $records);
        $this->assertSame(66, mb_strlen(rtrim($records[0], "\r\n")));
    }
}
```

One field is checked with the static `FixedWidthWriter::field()`, which applies a layout to one value:

```php
$this->assertSame('0004', FixedWidthWriter::field('4', new Fixed(4, 'right', '0')));
```

## What to cover

- **Every field once** with a value that fills it exactly and one that is too long: a wrong width shifts every field after it.
- **Characters outside ASCII** (`ä`, `ß`, `€`) in the target encoding, as bytes.
- **Empty values** (`null`, missing keys): they become a field of fill characters.
- **Negative numbers** in right-aligned, zero-filled fields, if the data can have them ([Amounts, signs and implied decimals](layouts.md#amounts-signs-and-implied-decimals)).
- **The record count**, for a receiver that checks it: one record per row, plus one with `header = true`.

See also: [Record layouts](layouts.md), [File format](file-format.md), [`FixedWidthWriter`](../reference/Waypoint/Export/Fixed/FixedWidthWriter.md).
