---
title: "Ebcdic"
description: "Converts UTF-8 text to an EBCDIC code page for IBM host files."
order: 100
generated: true
---

`Waypoint\Export\Fixed\Ebcdic` · final class · namespace [`Waypoint\Export\Fixed`](index.md)

```php
final class Ebcdic
```

Converts UTF-8 text to an EBCDIC code page for IBM host files.

The code pages are built-in byte tables (generated from the standard mappings),
because neither mbstring nor iconv can be relied on to know IBM code pages on
every host. Known pages: cp037 (US/Canada), cp273 (Germany/Austria), cp500
(international) and cp1140 (cp037 with the euro sign).

The text goes through Latin-1 first (Windows-1252 for cp1140, so the euro
survives there); characters outside it become '?' (0x6F).

## Methods

### supports()

```php
public static function supports(string $encoding): bool
```

Whether $encoding names one of the built-in code pages.

The name starts with 'cp', 'ibm' or 'ebcdic' (any case) followed by the page
number: 'cp273', 'IBM037', 'IBM-1140', 'EBCDIC-500' are all accepted; a bare
'273' is not.

**Parameters**

- `$encoding` `string`

**Returns** `bool`

### convert()

```php
public static function convert(string $utf8, string $encoding): string
```

Converts UTF-8 text to the EBCDIC code page $encoding, byte by byte.

```php
Ebcdic::convert('Aü', 'cp273');   // "\xC1\xD0"
```

**Parameters**

- `$utf8` `string`: the text, UTF-8 encoded
- `$encoding` `string`: a name supports() accepts, such as 'cp273', 'IBM037' or 'EBCDIC-500'

**Returns** `string`: the EBCDIC bytes, one per character

**Throws**

- [`InvalidArgumentException`](https://www.php.net/manual/en/class.invalidargumentexception.php): when $encoding is not a known EBCDIC code page
