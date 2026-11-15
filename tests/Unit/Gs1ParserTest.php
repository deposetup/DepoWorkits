<?php

namespace Tests\Unit;

use App\Support\Gs1Parser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class Gs1ParserTest extends TestCase
{
    public function test_tam_karekod_ayristirilir(): void
    {
        $gs = "\x1D";
        $r = Gs1Parser::parse("010869961765015317281231" . "10LOT123{$gs}21SN9876543210");
        $this->assertSame('08699617650153', $r['gtin']);
        $this->assertSame('2028-12-31', $r['skt']);
        $this->assertSame('LOT123', $r['lot']);
        $this->assertSame('SN9876543210', $r['seri']);
    }

    public function test_sira_farki_ve_gs_ayraci(): void
    {
        $gs = "\x1D";
        $r = Gs1Parser::parse("010869961765015321ABC{$gs}10L1{$gs}1728063" . "0");
        $this->assertSame('ABC', $r['seri']);
        $this->assertSame('L1', $r['lot']);
        $this->assertSame('2028-06-30', $r['skt']);
    }

    public function test_gun_00_ayin_son_gunu(): void
    {
        $r = Gs1Parser::parse('0108699617650153172802' . '00');
        $this->assertSame('2028-02-29', $r['skt']);
    }

    public function test_gtin_yoksa_hata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Gs1Parser::parse('21ABC');
    }

    public function test_bilinmeyen_ai_hata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Gs1Parser::parse('0108699617650153' . '99XYZ');
    }

    public function test_okunabilir_parantezli_bicim(): void
    {
        $k = \App\Support\Gs1Parser::parse('(01)08699617650153(21)SN1(10)LOT1(17)301231');
        $this->assertSame('08699617650153', $k['gtin']);
        $this->assertSame('SN1', $k['seri']);
        $this->assertSame('LOT1', $k['lot']);
        $this->assertSame('2030-12-31', $k['skt']);
    }
}
