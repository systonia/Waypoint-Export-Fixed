<?php

namespace Waypoint\Export\Fixed;

/** Configure with `$app->configure(function (FixedWidthOptions $opts) { ... })`. */
class FixedWidthOptions
{
    /** Output encoding: 'Windows-1252', 'UTF-8', any mbstring encoding, or an EBCDIC code page ('cp037', 'cp273' for German hosts, 'cp500', 'cp1140'). */
    public string $encoding = 'Windows-1252';

    /** Record terminator; '' for a pure block file where records are only known by their length. */
    public string $lineEnding = "\r\n";

    /** Write the column labels as a first record (padded like data). Legacy interfaces usually expect none. */
    public bool $header = false;

    /** Pad every record with spaces to this length; null keeps the sum of the field widths. */
    public ?int $recordLength = null;
}
