<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\Its\ItsClient;
use Illuminate\Http\Request;

class AyarController extends Controller
{
    public function its(Request $request)
    {
        $t = $request->user()->tenant;
        abort_unless($t, 404);

        return view('ayarlar.its', ['tenant' => $t, 'kayitli' => (bool) $t->itsCredential, 'guncelleme' => $t->itsCredential?->updated_at, 'surucu' => config('its.driver')]);
    }

    public function itsTest(Request $request, ItsClient $its)
    {
        $s = $its->baglantiTest($request->user()->tenant);
        AuditLog::kaydet('its_baglanti_test', ['basarili' => $s->basarili]);

        return back()->with($s->basarili ? 'ok' : 'hata', $s->basarili ? 'İTS bağlantısı başarılı.' : 'Bağlantı başarısız: '.$s->hataMesaji);
    }
}
