<?php

namespace Waypoint\Export\Fixed;

use Attribute;
use InvalidArgumentException;

/**
 * The field layout of one column in a fixed-width record.
 *
 * It sets the width in characters, where the value sits ('left' or 'right') and
 * what fills the rest. Put it next to #[Column] on a row class, or attach it with
 * `Sheet::column(...)->meta(new Fixed(12))`. Longer values are cut to the width.
 *
 * ```php
 * #[Column('Customer')]
 * #[Fixed(
 *     8,
 *     align: 'right',
 *     pad: '0'
 * )]
 * public int $customer;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Fixed
{
    /**
     * @param int $width the field's width in characters, at least 1
     * @param string $align 'left' (text) or 'right' (numbers): the side the value sits on
     * @param string $pad exactly one character that fills the rest of the field
     * @throws InvalidArgumentException when an argument is outside these rules
     */
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
