---
title: "Fixed"
description: "The field layout of one column in a fixed-width record."
order: 100
generated: true
---

`Waypoint\Export\Fixed\Fixed` · final class · namespace [`Waypoint\Export\Fixed`](index.md)

```php
final class Fixed
```

The field layout of one column in a fixed-width record.

It sets the width in characters, where the value sits ('left' or 'right') and
what fills the rest. Put it next to #[Column] on a row class, or attach it with
`Sheet::column(...)->meta(new Fixed(12))`. Longer values are cut to the width.

```php
#[Column('Customer')]
#[Fixed(
    8,
    align: 'right',
    pad: '0'
)]
public int $customer;
```

## Properties

### $width

```php
public int $width;
```

**Type** `int`

the field's width in characters, at least 1

### $align

```php
public string $align = 'left';
```

**Type** `string`

'left' (text) or 'right' (numbers): the side the value sits on

### $pad

```php
public string $pad = ' ';
```

**Type** `string`

exactly one character that fills the rest of the field

## Methods

### __construct()

```php
public function __construct(
    public int $width,
    public string $align = 'left',
    public string $pad = ' ',
)
```

**Parameters**

- `$width` `int` — the field's width in characters, at least 1
- `$align` `string` — 'left' (text) or 'right' (numbers): the side the value sits on
- `$pad` `string` — exactly one character that fills the rest of the field

**Throws**

- [`InvalidArgumentException`](https://www.php.net/manual/en/class.invalidargumentexception.php) — when an argument is outside these rules
