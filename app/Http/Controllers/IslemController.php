<?php

namespace App\Http\Controllers;

use App\Models\PtsGonderim;
use App\Models\StokKalemi;
use App\Services\StokServisi;
use Illuminate\Http\Request;

class IslemController extends Controller
{
    public function form(string $tur)
    {
        abort_unless(isset(StokServisi::TURLER[$tur]), 404);
        $this->sarfYetkisi($tur);

        return view('islem.form', ['tur' => $tur, 'baslik' => StokServisi::TURLER[$tur][2], 'sebepler' => StokServisi::DEAKTIVE_SEBEPLER]);
    }

    public function calistir(Request $request, string $tur, StokServisi $servis)
    {
        abort_unless(isset(StokServisi::TURLER[$tur]), 404);
        abort_unless($request->user()->tenant_id, 403);
        $this->sarfYetkisi($tur);
        $v = $request->validate([
            'kodlar' => 'required|string|max:100000',
            'sebep' => 'nullable|in:'.implode(',', array_keys(StokServisi::DEAKTIVE_SEBEPLER)),
            'aciklama' => 'nullable|string|max:255',
            'karsi_gln' => 'nullable|digits:13',
            'lokasyon' => 'nullable|string|max:80',
        ]);
        if (in_array($tur, ['iade', 'satis', 'devir'], true)) {
            $request->validate(['karsi_gln' => 'required|digits:13']);
        }
        if (in_array($tur, ['deaktive', 'deaktive_iptal'], true)) {
            $request->validate(['sebep' => 'required']);
        }

        $sonuc = $servis->isle($request->user(), $tur, $v['kodlar'], $v['sebep'] ?? null, $v['aciklama'] ?? null, $v['karsi_gln'] ?? null);

        // Mal Kabul'de girilen reyon/raf bilgisi, başarıyla kabul edilen ürünlere yazılır (SKT'si geçenler Red Odası'na gider).
        if ($tur === 'kabul' && StokKalemi::lokasyonVar()) {
            $lok = \App\Support\Metin::baslik(trim((string) ($v['lokasyon'] ?? '')));
            foreach ($sonuc['satirlar'] as $satir) {
                if (($satir['durum'] ?? '') !== 'tamam') { continue; }
                try { $k = \App\Support\Gs1Parser::parse($satir['kod']); } catch (\Throwable) { continue; }
                if ($lok !== '') {
                    StokKalemi::where(['tenant_id' => $request->user()->tenant_id, 'gtin' => $k['gtin'], 'sn' => $k['seri']])->update(['lokasyon' => $lok]);
                }
            }
            StokKalemi::redOdasinaAl($request->user()->tenant_id);
        }

        // Ekran gruplar halinde (JSON) çağırır; ilerleme tarayıcıda gösterilir, sonuçlar sunucuda birikir.
        if ($request->expectsJson()) {
            \App\Support\TopluSonuc::ekle($request, $tur, $sonuc);

            return response()->json(['satirlar' => $sonuc['satirlar'], 'bildirim' => $sonuc['bildirim']]);
        }

        return $this->sonucEkrani($request, $tur, $sonuc, $v['karsi_gln'] ?? null);
    }

    /** Parça parça gönderim bittikten sonra birikmiş sonuçla ayrıntılı sonuç ekranını açar. */
    public function sonuc(Request $request, string $tur)
    {
        abort_unless(isset(StokServisi::TURLER[$tur]), 404);
        abort_unless($request->user()->tenant_id, 403);
        $this->sarfYetkisi($tur);
        $v = $request->validate(['toplu_id' => 'required|string', 'karsi_gln' => 'nullable|digits:13', 'geri' => 'nullable|in:stok']);
        $sonuc = \App\Support\TopluSonuc::al($request, $tur);
        if (! $sonuc) {
            $yon = ($v['geri'] ?? null) === 'stok' ? redirect()->route('stok.liste') : redirect()->route('islem', $tur);

            return $yon->withErrors(['kodlar' => 'Sonuç bulunamadı veya süresi doldu. Bildirim sonuçları için Hareketler ekranına bakabilirsiniz.']);
        }
        if (($v['geri'] ?? null) === 'stok') {
            return view('islem.form', ['tur' => $tur, 'baslik' => StokServisi::TURLER[$tur][2], 'sebepler' => StokServisi::DEAKTIVE_SEBEPLER, 'sonuc' => $sonuc, 'geri' => route('stok.liste')]);
        }

        return $this->sonucEkrani($request, $tur, $sonuc, $v['karsi_gln'] ?? null);
    }

    private function sonucEkrani(Request $request, string $tur, array $sonuc, ?string $karsiGln)
    {
        // PTS gönderimi yalnızca satış bildirimi TAMAMEN başarılıysa (hiç hatalı satır yok) kullanıcı onayına sunulur.
        $ptsGonderim = null;
        $tamBasarili = $sonuc['satirlar'] !== [] && collect($sonuc['satirlar'])->every(fn ($s) => $s['durum'] === 'tamam');
        if ($tur === 'satis' && $tamBasarili && ! empty($sonuc['kalemler_ok']) && $karsiGln && \Illuminate\Support\Facades\Schema::hasTable('pts_gonderimleri')) {
            $ptsGonderim = PtsGonderim::create(PtsGonderim::mevcutAlanlar([
                'tenant_id' => $request->user()->tenant_id, 'user_id' => $request->user()->id,
                'alici_gln' => $karsiGln, 'alici_unvan' => PtsGonderim::unvanBul($karsiGln, $request->user()->tenant_id), 'bildirim_id' => $sonuc['bildirim'],
                'adet' => count($sonuc['kalemler_ok']), 'kalemler' => $sonuc['kalemler_ok'],
            ]));
        }

        return view('islem.form', [
            'tur' => $tur, 'baslik' => StokServisi::TURLER[$tur][2], 'sebepler' => StokServisi::DEAKTIVE_SEBEPLER, 'sonuc' => $sonuc,
            'ptsGonderim' => $ptsGonderim, 'ptsAcik' => (bool) config('its.pts_gonderim') && (bool) config('its.yazma'),
        ]);
    }

    /** Sonuç ekranındaki satırları Excel olarak indirir (başarısızlar üstte). */
    public function sonucExcel(Request $request)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate(['veri' => 'required|string|max:2000000', 'baslik' => 'nullable|string|max:60']);
        $satirlar = json_decode($v['veri'], true);
        abort_unless(is_array($satirlar), 422);
        $adlar = \App\Support\IlacAdlari::harita();
        $sirali = collect($satirlar)->filter(fn ($s) => is_array($s) && isset($s['kod']))->sortBy(fn ($s) => ($s['durum'] ?? '') === 'tamam' ? 1 : 0)->values();

        $receteler = \App\Models\IlacRecete::tipler($sirali->map(fn ($s) => \App\Support\IlacAdlari::gtinBul((string) $s['kod'])));
        $gosterilen = fn ($g) => isset(\App\Models\IlacRecete::GOSTERILEN[$receteler[$g] ?? '']) ? $receteler[$g] : '';

        return \App\Support\XlsxYazici::indir('sonuc-'.now()->format('Ymd-His').'.xlsx', ['Sıra', 'İlaç', 'Reçete', 'Karekod', 'Sonuç', 'Mesaj'],
            $sirali->map(fn ($s, $i) => [
                $i + 1, $adlar[\App\Support\IlacAdlari::gtinBul((string) $s['kod'])] ?? '', $gosterilen(\App\Support\IlacAdlari::gtinBul((string) $s['kod'])), \App\Support\IlacAdlari::parantezsiz((string) $s['kod']),
                ($s['durum'] ?? '') === 'tamam' ? 'Başarılı' : 'Başarısız', ($s['durum'] ?? '') === 'tamam' ? '' : (string) ($s['mesaj'] ?? ''),
            ])->all(), mb_substr($v['baslik'] ?? 'Sonuç', 0, 31));
    }

    /** Ecza depolarında Hastane Sarf / Sarf İptal kapalıdır (depolar sarf yapamaz). */
    private function sarfYetkisi(string $tur): void
    {
        if (in_array($tur, ['sarf', 'sarf_iptal'], true)) {
            $tenant = auth()->user()->tenant;
            abort_if($tenant && ! $tenant->sarfYapabilir(), 403, 'Ecza depoları sarf bildirimi yapamaz.');
        }
        if (in_array($tur, ['satis', 'satis_iptal'], true)) {
            $tenant = auth()->user()->tenant;
            abort_if($tenant && ! $tenant->satisYapabilir(), 403, 'Hastaneler satış yapamaz; yalnızca sarf ve iade bildirir.');
        }
        if (in_array($tur, ['devir', 'devir_iptal'], true)) {
            $tenant = auth()->user()->tenant;
            abort_if($tenant && ! $tenant->devirYapabilir(), 403, 'Ecza depoları ve hastaneler Mal Devir kullanmaz.');
        }
    }
}
