<?php

namespace Waypoint\Export\Fixed\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Waypoint\Export\{Cells, ExportOptions, Sheet};
use Waypoint\Export\Fixed\{Ebcdic, Fixed, FixedWidthOptions, FixedWidthWriter};

final class FixedWidthWriterTest extends TestCase
{
    private function writer(?callable $configure = null): FixedWidthWriter
    {
        $options = new FixedWidthOptions();
        if ($configure !== null) {
            $configure($options);
        }
        return new FixedWidthWriter(new Cells(new ExportOptions()), $options);
    }

    private function write(FixedWidthWriter $writer, Sheet $sheet): string
    {
        return implode('', iterator_to_array($writer->stream($sheet), false));
    }

    public function testRecordsFollowTheColumnLayouts(): void
    {
        $sheet = Sheet::file('lieferung.txt')->columnsOf(Satz::class)->rows([new Satz(42, 'Müller', 1234.5), new Satz(7, 'Ein sehr langer Name', -3.0)]);

        $out = $this->write($this->writer(), $sheet);

        $this->assertSame("00000042M\xFCller    " . "   1234,5\r\n" . "00000007Ein sehr l" . "       -3\r\n", $out);
        $this->assertSame('text/plain; charset=windows-1252', $this->writer()->contentType());
        $this->assertSame('txt', $this->writer()->extension());
    }

    public function testHeaderRecordLengthEncodingAndLineEnding(): void
    {
        $writer = $this->writer(function (FixedWidthOptions $o) {
            $o->header = true;
            $o->recordLength = 30;
            $o->encoding = 'UTF-8';
            $o->lineEnding = "\n";
        });
        $sheet = Sheet::file('x.dat', 'fixed')->column('a', 'A')->meta(new Fixed(3))->column('b', 'B')->meta(new Fixed(2, 'right'))->rows([['a' => 'ü', 'b' => "1\n2"]]);

        $this->assertSame("A   B" . str_repeat(' ', 25) . "\n" . "ü  1 " . str_repeat(' ', 25) . "\n", $this->write($writer, $sheet));
    }

    public function testEbcdicOutput(): void
    {
        $writer = $this->writer(function (FixedWidthOptions $o) {
            $o->encoding = 'IBM-273';
            $o->lineEnding = '';
        });
        $out = $this->write($writer, Sheet::file('x.txt')->column('a', 'A')->meta(new Fixed(5))->rows([['a' => 'A1 ü€']]));

        $this->assertSame("\xC1\xF1\x40\xD0\x6F", $out, 'A=C1, 1=F1, space=40, ü=D0 in cp273, euro unmappable');
        $this->assertSame("\xC1\x9F", Ebcdic::convert('A€', 'cp1140'), 'cp1140 maps the euro');
        $this->assertSame("\xC1\xDC", Ebcdic::convert('Aü', 'cp037'), 'ü is 0xDC in cp037');
        $this->assertTrue(Ebcdic::supports('EBCDIC-500'));
        $this->assertFalse(Ebcdic::supports('Windows-1252'));
        $this->assertFalse(Ebcdic::supports('cp1252'));
    }

    public function testAnUnknownEbcdicPageIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Ebcdic::convert('x', 'cp999');
    }

    public function testEveryColumnNeedsALayout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'b' of 'x.txt' has no #[Fixed] layout.");
        $this->write($this->writer(), Sheet::file('x.txt')->column('a', 'A')->meta(new Fixed(1))->column('b', 'B')->rows([]));
    }

    public function testFixedValidatesItsArguments(): void
    {
        $this->assertSame('ab', FixedWidthWriter::field('abc', new Fixed(2)));
        $this->assertSame('**x', FixedWidthWriter::field('x', new Fixed(3, 'right', '*')));

        foreach ([[0, 'left', ' '], [1, 'center', ' '], [1, 'left', '']] as [$width, $align, $pad]) {
            try {
                new Fixed($width, $align, $pad);
                $this->fail('expected an InvalidArgumentException');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
