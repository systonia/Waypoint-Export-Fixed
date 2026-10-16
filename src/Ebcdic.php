<?php

namespace Waypoint\Export\Fixed;

use InvalidArgumentException;

/**
 * Converts UTF-8 text to an EBCDIC code page for IBM host files.
 *
 * The code pages are built-in byte tables (generated from the standard mappings),
 * because neither mbstring nor iconv can be relied on to know IBM code pages on
 * every host. Known pages: cp037 (US/Canada), cp273 (Germany/Austria), cp500
 * (international) and cp1140 (cp037 with the euro sign).
 *
 * The text goes through Latin-1 first (Windows-1252 for cp1140, so the euro
 * survives there); characters outside it become '?' (0x6F).
 */
final class Ebcdic
{
    /** @var array<string, string> code page => 256 hex pairs, Latin-1 byte index -> EBCDIC byte */
    private const TABLES = [
        'cp037' => '00010203372d2e2f1605250b0c0d0e0f101112133c3d322618193f271c1d1e1f405a7f7b5b6c507d4d5d5c4e6b604b61f0f1f2f3f4f5f6f7f8f97a5e4c7e6e6f7cc1c2c3c4c5c6c7c8c9d1d2d3d4d5d6d7d8d9e2e3e4e5e6e7e8e9bae0bbb06d79818283848586878889919293949596979899a2a3a4a5a6a7a8a9c04fd0a107202122232415061728292a2b2c090a1b30311a333435360838393a3b04143eff41aa4ab19fb26ab5bdb49a8a5fcaafbc908feafabea0b6b39dda9b8bb7b8b9ab6465626663679e687471727378757677ac69edeeebefecbf80fdfefbfcadae594445424643479c4854515253585556578c49cdcecbcfcce170dddedbdc8d8edf', // IBM 037 (US/Canada)
        'cp273' => '00010203372d2e2f1605250b0c0d0e0f101112133c3d322618193f271c1d1e1f404f7f7b5b6c507d4d5d5c4e6b604b61f0f1f2f3f4f5f6f7f8f97a5e4c7e6e6fb5c1c2c3c4c5c6c7c8c9d1d2d3d4d5d6d7d8d9e2e3e4e5e6e7e8e963ecfc5f6d79818283848586878889919293949596979899a2a3a4a5a6a7a8a943bbdc5907202122232415061728292a2b2c090a1b30311a333435360838393a3b04143eff41aab0b19fb2cc7cbdb49a8abacaaf6f908feafabea0b6b39dda9b8bb7b8b9ab646562664a679e687471727378757677ac69edeeebefe0bf80fdfefb5aadaea144454246c0479c4854515253585556578c49cdcecbcf6ae170dddedbd08d8edf', // IBM 273 (Germany/Austria)
        'cp500' => '00010203372d2e2f1605250b0c0d0e0f101112133c3d322618193f271c1d1e1f404f7f7b5b6c507d4d5d5c4e6b604b61f0f1f2f3f4f5f6f7f8f97a5e4c7e6e6f7cc1c2c3c4c5c6c7c8c9d1d2d3d4d5d6d7d8d9e2e3e4e5e6e7e8e94ae05a5f6d79818283848586878889919293949596979899a2a3a4a5a6a7a8a9c0bbd0a107202122232415061728292a2b2c090a1b30311a333435360838393a3b04143eff41aab0b19fb26ab5bdb49a8abacaafbc908feafabea0b6b39dda9b8bb7b8b9ab6465626663679e687471727378757677ac69edeeebefecbf80fdfefbfcadae594445424643479c4854515253585556578c49cdcecbcfcce170dddedbdc8d8edf', // IBM 500 (international)
        'cp1140' => '00010203372d2e2f1605250b0c0d0e0f101112133c3d322618193f271c1d1e1f405a7f7b5b6c507d4d5d5c4e6b604b61f0f1f2f3f4f5f6f7f8f97a5e4c7e6e6f7cc1c2c3c4c5c6c7c8c9d1d2d3d4d5d6d7d8d9e2e3e4e5e6e7e8e9bae0bbb06d79818283848586878889919293949596979899a2a3a4a5a6a7a8a9c04fd0a1079f2122232415061728292a2b2c090a1b30311a333435360838393a3b04143eff41aa4ab16fb26ab5bdb49a8a5fcaafbc908feafabea0b6b39dda9b8bb7b8b9ab6465626663679e687471727378757677ac69edeeebefecbf80fdfefbfcadae594445424643479c4854515253585556578c49cdcecbcfcce170dddedbdc8d8edf', // IBM 1140 (037 with the euro sign)
    ];

    /**
     * Whether $encoding names one of the built-in code pages.
     *
     * The name starts with 'cp', 'ibm' or 'ebcdic' (any case) followed by the page
     * number: 'cp273', 'IBM037', 'IBM-1140', 'EBCDIC-500' are all accepted; a bare
     * '273' is not.
     */
    public static function supports(string $encoding): bool
    {
        return self::page($encoding) !== null;
    }

    /**
     * Converts UTF-8 text to the EBCDIC code page $encoding, byte by byte.
     *
     * ```php
     * Ebcdic::convert('Aü', 'cp273');   // "\xC1\xD0"
     * ```
     *
     * @param string $utf8 the text, UTF-8 encoded
     * @param string $encoding a name supports() accepts, such as 'cp273', 'IBM037' or 'EBCDIC-500'
     * @return string the EBCDIC bytes, one per character
     * @throws InvalidArgumentException when $encoding is not a known EBCDIC code page
     */
    public static function convert(string $utf8, string $encoding): string
    {
        $page = self::page($encoding) ?? throw new InvalidArgumentException("Unknown EBCDIC code page '$encoding'; known: " . implode(', ', array_keys(self::TABLES)) . '.');
        $latin = mb_convert_encoding($utf8, $page === 'cp1140' ? 'Windows-1252' : 'ISO-8859-1', 'UTF-8');
        $from = '';
        for ($i = 0; $i < 256; $i++) {
            $from .= chr($i);
        }
        return strtr($latin, $from, (string) hex2bin(self::TABLES[$page]));
    }

    private static function page(string $encoding): ?string
    {
        $digits = preg_replace('/\D+/', '', $encoding) ?? '';
        $name = sprintf('cp%03d', (int) $digits);
        return preg_match('/^(cp|ibm|ebcdic)/i', $encoding) && isset(self::TABLES[$name]) ? $name : null;
    }
}
