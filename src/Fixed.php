<?php

namespace Waypoint\Export\Fixed;

use Attribute;
use InvalidArgumentException;

/**
 * The field layout of one column in a fixed-width record: its width in
 * characters, where the value sits ('left' or 'right') and what fills the
 * rest. Put it next to #[Column] on a row class, or attach it with
 * `Sheet::column(...)->meta(new Fixed(12))`. Longer values are cut.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Fixed
{
    public function __construct(public int $width, public string $align = 'left', public string $pad = ' ')
    {
        if ($width < 1) {
            throw new InvalidArgumentException('A fixed-width field needs a width of at least 1.');
        }
        if (!in_array($align, ['left', 'right'], true)) {
            throw new InvalidArgumentException("Fixed align must be 'left' or 'right', got '$align'.");
        }
        if (mb_strlen($pad) !== 1) {
            throw new InvalidArgumentException('Fixed pad must be exactly one character.');
        }
    }
}
