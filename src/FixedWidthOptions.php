<?php

namespace Waypoint\Export\Fixed;

/**
 * Settings of the fixed-width writer: encoding, record terminator, header and record length.
 *
 * Configure with `$app->configure(function (FixedWidthOptions $opts) { ... })`. How values
 * become text (decimal separator, date format, boolean words) is set in ExportOptions.
 */
class FixedWidthOptions
{
    /** Output encoding: 'Windows-1252', 'UTF-8', any mbstring encoding, or an EBCDIC code page ('cp037', 'cp273' for German hosts, 'cp500', 'cp1140'). */
    public string $encoding = 'Windows-1252';

    /** Record terminator, CR LF by default; "\n" for Unix hosts, '' for a pure block file where records are only known by their length. It is encoded like the data. */
    public string $lineEnding = "\r\n";

    /** Write the column labels as a first record (padded like data). Legacy interfaces usually expect none. */
    public bool $header = false;

    /** Pad every record with spaces to this length, or cut it there; null keeps the sum of the field widths. */
    public ?int $recordLength = null;
}
