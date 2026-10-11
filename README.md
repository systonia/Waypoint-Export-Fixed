# waypoint-export-fixed

Fixed-width record files for [waypoint-export](https://github.com/systonia/Waypoint-Export): the interface format of
host systems, AS/400 batches and old ERP importers. Each column gets a `#[Fixed]` layout, the
writer cuts and pads, and the file goes out in Windows-1252, UTF-8 or an EBCDIC code page.

## Setup

```php
$app->plugin(new Waypoint\Export\ExportPlugin());
$app->plugin(new Waypoint\Export\Fixed\FixedWidthPlugin());   // after the base plugin
```

## Usage

```php
use Waypoint\Export\{Column, Sheet};
use Waypoint\Export\Fixed\Fixed;

final class Satz
{
    #[Column('Kundennummer')]
    #[Fixed(8, align: 'right', pad: '0')]
    public int $kunde;

    #[Column('Name')]
    #[Fixed(30)]
    public string $name;

    #[Column('Betrag')]
    #[Fixed(11, align: 'right')]
    public float $betrag;
}

#[Get('/lieferung.txt')]
public function lieferung(): Sheet
{
    return Sheet::file('lieferung.txt')->columnsOf(Satz::class)->rows($this->saetze());
}
```

`.txt` selects the writer; any other extension works with `Sheet::file('x.dat', format: 'fixed')`.
Without a row class, attach layouts by hand: `->column('kunde', 'Kundennummer')->meta(new Fixed(8, 'right', '0'))`.
A column without a layout is an error, not a guess.

Values are formatted by the base plugin's `ExportOptions` (decimal separator, date format, boolean words),
then cut to the width and padded. Line breaks inside a value become spaces.

## Options (`FixedWidthOptions`)

| Setting | Default | Description |
|---|---|---|
| `encoding` | `'Windows-1252'` | Or `'UTF-8'`, any mbstring encoding, or an EBCDIC page from built-in tables: `cp037`, `cp273` (German hosts), `cp500`, `cp1140`. |
| `lineEnding` | `"\r\n"` | `''` for a block file where records are known only by their length. |
| `header` | `false` | Write the labels as a first record. |
| `recordLength` | `null` | Pad or cut every record to this length. |

## Development

```bash
composer install && composer test && composer stan
```

## License

MIT or Apache-2.0, at your option.
