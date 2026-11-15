<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ItsCredential;
use App\Services\Its\ItsClient;
use Illuminate\Http\Request;

class AyarController extends Controller
{
    public function its(Request $request)
    {
        $t = $request->user()->tenant;
        abort_unless($t, 404);

        return view('ayarlar.its', ['tenant' => $t, 'kayitli' => (bool) $t->itsCredential, 'surucu' => config('its.driver')]);
    }

    public function itsKaydet(Request $request)
    {
        $v = $request->validate(['kullanici_adi' => 'required|string|max:100', 'sifre' => 'required|string|max:200']);
        ItsCredential::updateOrCreate(['tenant_id' => $request->user()->tenant_id], $v);
        AuditLog::kaydet('its_kimlik_kaydet'); // değerler loga yazılmaz

        return back()->with('ok', 'İTS kullanıcı bilgileri şifreli olarak kaydedildi.');
    }

    public function itsTest(Request $request, ItsClient $its)
    {
        $s = $its->baglantiTest($request->user()->tenant);

        return back()->with($s->basarili ? 'ok' : 'hata', $s->basarili ? 'İTS bağlantısı başarılı.' : 'Bağlantı başarısız: '.$s->hataMesaji);
    }
}
