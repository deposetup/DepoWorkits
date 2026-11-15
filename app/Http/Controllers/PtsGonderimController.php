<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PtsGonderim;
use App\Services\Its\ItsClient;
use App\Support\PtsXmlYazici;
use Illuminate\Http\Request;

/** Satış sonrası PTS verisinin (XML+zip) alıcıya gönderimi. Her gönderim kullanıcı onayıyla ve tek sefer yapılır. */
class PtsGonderimController extends Controller
{
    public function index(Request $request)
    {
        $kayitlar = PtsGonderim::where('tenant_id', $request->user()->tenant_id)->latest()->limit(100)->get();

        return view('pts.gonderimler', ['kayitlar' => $kayitlar, 'ptsAcik' => (bool) config('its.pts_gonderim') && (bool) config('its.yazma')]);
    }

    public function gonder(Request $request, PtsGonderim $gonderim, ItsClient $its)
    {
        abort_unless($gonderim->tenant_id === $request->user()->tenant_id, 404);
        $request->validate(['onay' => 'accepted'], ['onay.accepted' => 'PTS verisini göndermek için onay kutusunu işaretleyin.']);

        if (! config('its.pts_gonderim') || ! config('its.yazma')) {
            return back()->with('hata', 'PTS paket gönderimi bu ortamda henüz etkinleştirilmedi. Paket gönderilmedi.');
        }
        // Çift gönderimi önlemek için kaydı atomik olarak "gonderiliyor" durumuna al.
        $alindi = PtsGonderim::where('id', $gonderim->id)->where('durum', 'bekliyor')->update(['durum' => 'gonderiliyor']);
        if (! $alindi) {
            return back()->with('hata', 'Bu gönderim zaten işlenmiş ya da işleniyor.');
        }
        $gonderim->refresh();
        $tenant = $request->user()->tenant;

        try {
            $xml = PtsXmlYazici::xml($tenant->gln, $gonderim->alici_gln, 'DW'.$gonderim->id.'-'.now()->format('Ymd'), $gonderim->kalemler);
            $sonuc = $its->ptsPaketGonder($tenant, $gonderim->alici_gln, PtsXmlYazici::zip($xml));
        } catch (\Throwable $e) {
            $gonderim->update(['durum' => 'hata', 'hata' => mb_substr($e->getMessage(), 0, 500)]);
            AuditLog::kaydet('pts_gonderim_hata', ['gonderim' => $gonderim->id, 'alici' => $gonderim->alici_gln]);

            return back()->with('hata', $e->getMessage());
        }

        $gonderim->update(['durum' => 'gonderildi', 'transfer_id' => $sonuc['transferId'], 'md5' => $sonuc['md5'], 'gonderildi_at' => now(), 'hata' => null]);
        AuditLog::kaydet('pts_gonderim', ['gonderim' => $gonderim->id, 'alici' => $gonderim->alici_gln, 'adet' => $gonderim->adet, 'transfer' => $sonuc['transferId']]);

        return redirect()->route('pts.gonderimler')->with('ok', 'PTS verisi gönderildi (Transfer No: '.$sonuc['transferId'].', '.$gonderim->adet.' ürün).');
    }

    public function vazgec(Request $request, PtsGonderim $gonderim)
    {
        abort_unless($gonderim->tenant_id === $request->user()->tenant_id, 404);
        PtsGonderim::where('id', $gonderim->id)->where('durum', 'bekliyor')->update(['durum' => 'vazgecildi']);
        AuditLog::kaydet('pts_gonderim_vazgec', ['gonderim' => $gonderim->id]);

        return redirect()->route('pts.gonderimler')->with('ok', 'PTS gönderimi iptal edildi (İTS Satış bildirimi etkilenmez).');
    }
}
