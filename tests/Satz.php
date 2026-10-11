<?php

namespace Waypoint\Export\Fixed\Tests;

use Waypoint\Export\Column;
use Waypoint\Export\Fixed\Fixed;

final class Satz
{
    public function __construct(
        #[Column('Kundennummer')]
        #[Fixed(8, align: 'right', pad: '0')]
        public int $kunde,
        #[Column('Name')]
        #[Fixed(10)]
        public string $name,
        #[Column('Betrag')]
        #[Fixed(9, align: 'right')]
        public float $betrag,
    ) {
    }
}
