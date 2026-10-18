<?php

namespace Waypoint\Export\Fixed\Tests;

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Export\Sheet;

#[Controller]
class SatzController
{
    #[Get('/lieferung.txt')]
    public function txt(): Sheet
    {
        return Sheet::file('lieferung.txt')->columnsOf(Satz::class)->rows([new Satz(42, 'Müller', 1234.5)]);
    }

    #[Get('/lieferung.dat')]
    public function dat(): Sheet
    {
        return Sheet::file('lieferung.dat', 'fixed')->columnsOf(Satz::class)->rows([new Satz(1, 'A', 1.0)]);
    }
}
