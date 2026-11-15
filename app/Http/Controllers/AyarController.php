<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DestekTalebi;
use App\Services\Its\ItsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AyarController extends Controller
{
    public function its(Request $request)
    {
        $t = $request->user()->tenant;
        abort_unless($t, 404);

        return view('ayarlar.its', ['tenant' => $t, 'kayitli' => (bool) $t->itsCredential, 'guncelleme' => $t->itsCredential?->updated_at,
            'konular' => DestekTalebi::KONULAR, 'talepler' => Schema::hasTable('destek_talepleri') ? DestekTalebi::where('tenant_id', $t->id)->latest()->limit(5)->get() : collect(),
            'onKonu' => array_key_exists(request('konu'), DestekTalebi::KONULAR) ? request('konu') : 'its_sifre', 'surucu' => config('its.driver')]);
    }

    public function itsTest(Request $request, ItsClient $its)
    {
        $s = $its->baglantiTest($request->user()->tenant);
        AuditLog::kaydet('its_baglanti_test', ['basarili' => $s->basarili]);

        return back()->with($s->basarili ? 'ok' : 'hata', $s->basarili ? 'İTS bağlantısı başarılı.' : 'Bağlantı başarısız: '.$s->hataMesaji);
    }

    /** İTS bilgisi değişikliği / bağlantı sorunu için DepoWork ekibine talep (e-posta bildirimi sonraki aşamada eklenecek). */
    public function talep(Request $request)
    {
        $t = $request->user()->tenant;
        abort_unless($t, 404);
        if (! Schema::hasTable('destek_talepleri')) {
            return back()->with('hata', 'Talep sistemi şu an hazırlanıyor, lütfen kısa süre sonra tekrar deneyin.');
        }
        $v = $request->validate([
            'konu' => 'required|in:'.implode(',', array_keys(DestekTalebi::KONULAR)),
            'mesaj' => 'nullable|string|max:1000',
            'telefon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
        ], ['telefon.regex' => 'Telefon numarasını rakamlarla yazın.']);
        if ($v['konu'] === 'diger' && blank($v['mesaj'] ?? null)) {
            return back()->withErrors(['mesaj' => 'Lütfen talebinizi kısaca yazın.'])->withInput();
        }

        $talep = DestekTalebi::create(['tenant_id' => $t->id, 'user_id' => $request->user()->id] + $v);
        AuditLog::kaydet('destek_talebi', ['talep_id' => $talep->id, 'konu' => $v['konu']]);

        return redirect()->route('ayarlar.its')->with('ok', 'Talebiniz DepoWork ekibine iletildi. En kısa sürede sizinle iletişime geçeceğiz.');
    }
}
