---
title: "FixedWidthOptions"
description: "Settings of the fixed-width writer: encoding, record terminator, header and record length."
order: 100
generated: true
---

`Waypoint\Export\Fixed\FixedWidthOptions` · class · namespace [`Waypoint\Export\Fixed`](index.md)

```php
class FixedWidthOptions
```

Settings of the fixed-width writer: encoding, record terminator, header and record length.

Configure with `$app->configure(function (FixedWidthOptions $opts) { ... })`. How values
become text (decimal separator, date format, boolean words) is set in ExportOptions.

## Properties

### $encoding

```php
public string $encoding = 'Windows-1252';
```

Output encoding: 'Windows-1252', 'UTF-8', any mbstring encoding, or an EBCDIC code page ('cp037', 'cp273' for German hosts, 'cp500', 'cp1140').

### $lineEnding

```php
public string $lineEnding = '
';
```

Record terminator, CR LF by default; "\n" for Unix hosts, '' for a pure block file where records are only known by their length. It is encoded like the data.

### $header

```php
public bool $header = false;
```

Write the column labels as a first record (padded like data). Legacy interfaces usually expect none.

### $recordLength

```php
public ?int $recordLength = null;
```

Pad every record with spaces to this length, or cut it there; null keeps the sum of the field widths.
