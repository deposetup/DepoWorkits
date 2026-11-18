<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IlacFiyat;
use App\Support\IlacFiyatYukleyici;
use Illuminate\Http\Request;

/** Geçerli ilaç fiyat listesini yönetir (yalnızca yönetici). Tüm müşteriler için ortaktır. */
class IlacFiyatController extends Controller
{
    public const SAYFA = 25;

    public function index(Request $request)
    {
        $hazir = IlacFiyat::hazir();
        $ara = trim((string) $request->query('ara', ''));
        $sonuclar = null;
        if ($hazir && $ara !== '') {
            $sonuclar = IlacFiyat::ara($ara)->paginate(self::SAYFA)->withQueryString();
        }

        return view('admin.ilac-fiyat', [
            'hazir' => $hazir,
            'toplam' => $hazir ? IlacFiyat::count() : 0,
            'aktifSayisi' => $hazir ? IlacFiyat::where('aktif', true)->count() : 0,
            'sonYukleme' => $hazir ? IlacFiyat::max('updated_at') : null,
            'ara' => $ara,
            'sonuclar' => $sonuclar,
        ]);
    }

    public function yukle(Request $request)
    {
        abort_unless(IlacFiyat::hazir(), 503, 'Fiyat tablosu henüz oluşturulmadı (migration çalıştırılmalı).');
        $request->validate(['dosya' => ['required', 'file', 'max:30720', 'extensions:xlsx']], [
            'dosya.required' => 'Bir .xlsx dosyası seçin.', 'dosya.max' => 'Dosya en fazla 30 MB olabilir.', 'dosya.extensions' => 'Yalnızca .xlsx (Excel) dosyası yüklenebilir.',
        ]);
        set_time_limit(300);
        @ini_set('memory_limit', '512M');
        try {
            $s = IlacFiyatYukleyici::yukle($request->file('dosya')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['dosya' => $e->getMessage()]);
        }
        AuditLog::kaydet('ilac_fiyat_yukle', ['yazilan' => $s['yazilan'], 'atlanan' => $s['atlanan']]);

        return back()->with('ok', number_format($s['yazilan'], 0, ',', '.').' ilaç fiyatı yüklendi ('.number_format($s['atlanan'], 0, ',', '.').' satır geçersiz GTIN nedeniyle atlandı). Liste tamamen yenilendi.');
    }
}
