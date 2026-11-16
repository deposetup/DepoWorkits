<?php

namespace Tests\Feature;

use App\Support\Gs1Parser;
use PHPUnit\Framework\TestCase;

class Gs1GsSizTest extends TestCase
{
    public function test_gs_ayraci_kaybolmus_karekod_cozulur(): void
    {
        $r = Gs1Parser::parse('0108681281000242210000000059244610250100417271231');
        $this->assertSame('08681281000242', $r['gtin']);
        $this->assertSame('00000000592446', $r['seri']);
        $this->assertSame('2501004', $r['lot']);
        $this->assertSame('2027-12-31', $r['skt']);
    }

    public function test_standart_sira_gs_siz(): void
    {
        $r = Gs1Parser::parse('010868128100024217271231102501004'.'2100000000592446');
        $this->assertSame('00000000592446', $r['seri']);
        $this->assertSame('2501004', $r['lot']);
    }

    public function test_gs_ile_eski_davranis_bozulmaz(): void
    {
        $r = Gs1Parser::parse("0108681281000242172712311025010041\x1D2100000000592446");
        $this->assertSame('250100 41' === '' ? '' : '25010041', $r['lot']);
        $this->assertSame('00000000592446', $r['seri']);
    }
}
