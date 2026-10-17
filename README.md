# waypoint-export-fixed

Fixed-width record files for [waypoint-export](https://github.com/systonia/Waypoint-Export): the interface format of
host systems, AS/400 batches and old ERP importers. Each column gets a `#[Fixed]` layout, the
writer cuts and pads, and the file goes out in Windows-1252, UTF-8 or an EBCDIC code page.

**Documentation:** [docs/](docs/index.md), with [guides](docs/guides/index.md) to record layouts, the file format and testing, and the [reference](docs/reference/index.md).

## Setup

```php
$app->plugin(new Waypoint\Export\ExportPlugin());
$app->plugin(new Waypoint\Export\Fixed\FixedWidthPlugin());   // after the base plugin
```

## Usage

The day's pickups of a bakery, as the records its till imports:

```php
use Waypoint\Attributes\{Get, Role};
use Waypoint\Export\{Column, Sheet};
use Waypoint\Export\Fixed\Fixed;

final class PickupLine
{
    public function __construct(
        #[Column('Order')]
        #[Fixed(10)]
        public string $code,
        #[Column('Article')]
        #[Fixed(
            4,
            align: 'right',
            pad: '0'
        )]
        public int $product,
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

#[Get('/pickups.txt')]
#[Role('owner')]
public function pickups(): Sheet
{
    return Sheet::file('pickups.txt')->columnsOf(PickupLine::class)->rows($this->pickupLines());
}
```

`.txt` selects the writer; any other extension works with `Sheet::file('stock.dat', format: 'fixed')`.
Without a row class, attach layouts by hand: `->column('product', 'Article')->meta(new Fixed(4, 'right', '0'))`.
A column without a layout is an error, not a guess.

Values are formatted by the base plugin's `ExportOptions` (decimal separator, date format, boolean words),
then cut to the width and padded. Line breaks inside a value become spaces.

## Options (`FixedWidthOptions`)

| Setting | Default | Description |
|---|---|---|
| `encoding` | `'Windows-1252'` | Or `'UTF-8'`, any mbstring encoding, or an EBCDIC page from built-in tables: `cp037`, `cp273` (German hosts), `cp500`, `cp1140`. |
| `lineEnding` | `"\r\n"` | `''` for a block file where records are known only by their length. |
| `header` | `false` | Write the labels as a first record. |
| `recordLength` | `null` | Pad or cut every record to this many characters. |

## Development

```bash
composer install && composer test && composer stan
```

## License

MIT or Apache-2.0, at your option.
