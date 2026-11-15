<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PtsGonderim;
use App\Models\StokKalemi;
use App\Services\Its\ItsClient;
use App\Support\PtsXmlYazici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Satış sonrası PTS verisinin (XML+zip) alıcıya gönderimi. Her gönderim kullanıcı onayıyla ve tek sefer yapılır. */
class PtsGonderimController extends Controller
{
    public function index(Request $request)
    {
        $v = $request->validate(['ara' => 'nullable|string|max:100', 'durum' => 'nullable|in:bekliyor,gonderildi,hata,vazgecildi']);
        $ara = trim($v['ara'] ?? '');
        $unvanVar = \Illuminate\Support\Facades\Schema::hasColumn('pts_gonderimleri', 'alici_unvan');
        $kayitlar = PtsGonderim::where('tenant_id', $request->user()->tenant_id)
            ->when($v['durum'] ?? null, fn ($q, $d) => $q->where('durum', $d))
            ->when($ara !== '', fn ($q) => $q->where(fn ($w) => $w->where('alici_gln', 'like', '%'.$ara.'%')
                ->when($unvanVar, fn ($x) => $x->orWhere('alici_unvan', 'like', '%'.$ara.'%'))
                ->orWhere('transfer_id', 'like', '%'.$ara.'%')))
            ->latest()->limit(200)->get();

        return view('pts.gonderimler', [
            'kayitlar' => $kayitlar, 'f' => ['ara' => $ara, 'durum' => $v['durum'] ?? ''],
            'ptsAcik' => (bool) config('its.pts_gonderim') && (bool) config('its.yazma'), 'yeniId' => session('yeni_id'),
        ]);
    }

    public function gonder(Request $request, PtsGonderim $gonderim, ItsClient $its)
    {
        abort_unless($gonderim->tenant_id === $request->user()->tenant_id, 404);
        $v = $this->dogrula($request);

        if (! config('its.pts_gonderim') || ! config('its.yazma')) {
            return back()->with('hata', 'PTS paket gönderimi bu ortamda henüz etkinleştirilmedi. Paket gönderilmedi.');
        }
        // Çift gönderimi önlemek için kaydı atomik olarak "gonderiliyor" durumuna al.
        $alindi = PtsGonderim::where('id', $gonderim->id)->where('durum', 'bekliyor')->update(['durum' => 'gonderiliyor']);
        if (! $alindi) {
            return back()->with('hata', 'Bu gönderim zaten işlenmiş ya da işleniyor.');
        }
        $gonderim->refresh();

        return $this->paketiGonder($request, $gonderim, $v['belge_no'] ?? null, $its, 'PTS verisi gönderildi');
    }

    /**
     * Daha önce gönderilmiş/başarısız olmuş bir gönderimin ürünleriyle, aynı alıcı GLN'ine yeni bir PTS paketi oluşturur ve gönderir.
     * Yeni kayıt ve yeni Transfer No oluşur; eski kayıt değişmez. Yalnızca hâlâ "satıldı" durumundaki ürünler dahil edilir.
     */
    public function tekrar(Request $request, PtsGonderim $gonderim, ItsClient $its)
    {
        abort_unless($gonderim->tenant_id === $request->user()->tenant_id, 404);
        $v = $this->dogrula($request);
        if (! config('its.pts_gonderim') || ! config('its.yazma')) {
            return back()->with('hata', 'PTS paket gönderimi bu ortamda henüz etkinleştirilmedi. Paket gönderilmedi.');
        }
        if (in_array($gonderim->durum, ['bekliyor', 'gonderiliyor'], true)) {
            return back()->with('hata', 'Bu gönderim henüz tamamlanmadı; bekleyen gönderimi doğrudan gönderin.');
        }
        if (! Cache::add('pts-tekrar:'.$gonderim->id, 1, 20)) {
            return back()->with('hata', 'Bu gönderim için istek zaten işleniyor, lütfen birkaç saniye bekleyin.');
        }

        $tenantId = $request->user()->tenant_id;
        $guncel = collect($gonderim->kalemler)->filter(fn ($k) => StokKalemi::where(['tenant_id' => $tenantId, 'gtin' => $k['gtin'], 'sn' => $k['sn'], 'durum' => 'satis'])->exists())->values();
        $dislanan = count($gonderim->kalemler) - $guncel->count();
        if ($guncel->isEmpty()) {
            return back()->with('hata', 'Bu gönderimdeki ürünlerin hiçbiri artık "satıldı" durumunda değil (satış geri alınmış olabilir); yeni PTS verisi oluşturulmadı.');
        }

        $yeni = PtsGonderim::create(PtsGonderim::mevcutAlanlar([
            'tenant_id' => $tenantId, 'user_id' => $request->user()->id, 'kaynak_id' => $gonderim->id,
            'alici_gln' => $gonderim->alici_gln, 'alici_unvan' => $gonderim->alici_unvan ?? PtsGonderim::unvanBul($gonderim->alici_gln),
            'bildirim_id' => $gonderim->bildirim_id, 'adet' => $guncel->count(), 'kalemler' => $guncel->all(), 'durum' => 'gonderiliyor',
        ]));
        AuditLog::kaydet('pts_gonderim_tekrar', ['kaynak' => $gonderim->id, 'yeni' => $yeni->id, 'alici' => $yeni->alici_gln]);

        return $this->paketiGonder($request, $yeni, $v['belge_no'] ?? null, $its, 'PTS verisi yeniden oluşturuldu ve gönderildi', $dislanan);
    }

    private function dogrula(Request $request): array
    {
        return $request->validate(
            ['onay' => 'accepted', 'belge_no' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._\\-\/]+$/']],
            ['onay.accepted' => 'PTS verisini göndermek için onay kutusunu işaretleyin.', 'belge_no.regex' => 'İrsaliye/belge no yalnızca harf, rakam ve . _ - / içerebilir.']
        );
    }

    /** XML+zip üretir, PTS'e gönderir, kaydı günceller ve sonucu (yeni Transfer No ile) döndürür. */
    private function paketiGonder(Request $request, PtsGonderim $g, ?string $belgeNo, ItsClient $its, string $basariMesaji, int $dislanan = 0)
    {
        $tenant = $request->user()->tenant;
        $belgeNo = $belgeNo ?: 'DW'.$g->id.now()->format('Ymd');
        try {
            $xml = PtsXmlYazici::xml($tenant->gln, $g->alici_gln, $belgeNo, $g->kalemler, PtsXmlYazici::carrierLabel($tenant->gln, $g->id));
            $sonuc = $its->ptsPaketGonder($tenant, $g->alici_gln, PtsXmlYazici::zip($xml, $belgeNo.'.xml'));
        } catch (\Throwable $e) {
            $g->update(PtsGonderim::mevcutAlanlar(['durum' => 'hata', 'hata' => mb_substr($e->getMessage(), 0, 500), 'belge_no' => $belgeNo]));
            AuditLog::kaydet('pts_gonderim_hata', ['gonderim' => $g->id, 'alici' => $g->alici_gln]);

            return redirect()->route('pts.gonderimler')->with('hata', $e->getMessage())->with('yeni_id', $g->id);
        }

        $g->update(PtsGonderim::mevcutAlanlar([
            'durum' => 'gonderildi', 'transfer_id' => $sonuc['transferId'], 'md5' => $sonuc['md5'], 'gonderildi_at' => now(), 'hata' => null,
            'belge_no' => $belgeNo, 'alici_unvan' => $g->alici_unvan ?? PtsGonderim::unvanBul($g->alici_gln),
        ]));
        AuditLog::kaydet('pts_gonderim', ['gonderim' => $g->id, 'alici' => $g->alici_gln, 'adet' => $g->adet, 'transfer' => $sonuc['transferId']]);

        return redirect()->route('pts.gonderimler')->with('yeni_id', $g->id)
            ->with('ok', $basariMesaji.': Yeni PTS Transfer No: '.$sonuc['transferId'].' ('.$g->adet.' ürün, alıcı '.$g->alici_gln.')'.($dislanan ? '. '.$dislanan.' ürün artık satıldı durumunda olmadığı için dahil edilmedi' : '').'.');
    }

    public function vazgec(Request $request, PtsGonderim $gonderim)
    {
        abort_unless($gonderim->tenant_id === $request->user()->tenant_id, 404);
        PtsGonderim::where('id', $gonderim->id)->where('durum', 'bekliyor')->update(['durum' => 'vazgecildi']);
        AuditLog::kaydet('pts_gonderim_vazgec', ['gonderim' => $gonderim->id]);

        return redirect()->route('pts.gonderimler')->with('ok', 'PTS gönderimi iptal edildi (İTS Satış bildirimi etkilenmez).');
    }
}
