<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AltBilgiTest extends TestCase
{
    use RefreshDatabase;

    public function test_alt_bilgide_firma_surum_ve_guncel_yil_gorunur(): void
    {
        $this->travelTo(now()->setYear(2031));
        $this->get('/giris')->assertOk()
            ->assertSee('Depo Setup Yazılım ve Danışmanlık Ltd. Şti.')
            ->assertSee('&copy; 2031', false)
            ->assertSee('Sürüm '.config('its.surum'));
    }
}
