<?php

namespace Tests\Unit;

use App\Support\Karekod;
use PHPUnit\Framework\TestCase;

class KarekodTest extends TestCase
{
    public function test_parses_karekod_with_gs_separator(): void
    {
        $this->assertSame([
            'gtin' => '08699828760047',
            'sn' => '459725419',
            'xd' => '2027-01-07',
            'bn' => '20210107',
        ], Karekod::parse("010869982876004721459725419\x1D1727010710".'20210107'));
    }

    public function test_parses_karekod_without_separator(): void
    {
        $this->assertSame([
            'gtin' => '08699828760047',
            'sn' => 'ABC123',
            'xd' => '2027-01-31',
            'bn' => 'P55',
        ], Karekod::parse('010869982876004721ABC1231727010010P55'));
    }

    public function test_returns_null_for_invalid_input(): void
    {
        $this->assertNull(Karekod::parse('merhaba'));
    }
}
