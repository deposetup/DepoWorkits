<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IlacRecete;
use App\Support\IlacReceteYukleyici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** İlaç.xlsx'ten GTIN => reçete tipi listesini yönetir (yalnızca yönetici). */
class IlacReceteController extends Controller
{
    public function index(Request $request)
    {
        $hazir = Schema::hasTable('ilac_receteleri');
        $ara = trim((string) $request->query('ara', ''));
        $bulunan = collect();
        if ($hazir && $ara !== '') {
            $rakam = preg_replace('/\D/', '', $ara);
            $bulunan = IlacRecete::query()
                ->where(fn ($q) => $q->where('ad', 'like', '%'.$ara.'%')->when($rakam !== '', fn ($w) => $w->orWhere('gtin', 'like', '%'.ltrim($rakam, '0').'%')))
                ->orderBy('ad')->limit(50)->get();
        }

        return view('admin.ilac-recete', [
            'hazir' => $hazir,
            'toplam' => $hazir ? IlacRecete::count() : 0,
            'tipler' => $hazir ? IlacRecete::selectRaw('recete_tipi, count(*) as adet')->groupBy('recete_tipi')->orderByDesc('adet')->pluck('adet', 'recete_tipi')->all() : [],
            'sonYukleme' => $hazir ? IlacRecete::max('updated_at') : null,
            'ara' => $ara,
            'bulunan' => $bulunan,
        ]);
    }

    public function yukle(Request $request)
    {
        $request->validate(['dosya' => ['required', 'file', 'max:30720', 'extensions:xlsx']], [
            'dosya.required' => 'Bir .xlsx dosyası seçin.', 'dosya.max' => 'Dosya en fazla 30 MB olabilir.', 'dosya.extensions' => 'Yalnızca .xlsx (Excel) dosyası yüklenebilir.',
        ]);
        set_time_limit(300);
        try {
            $s = IlacReceteYukleyici::yukle($request->file('dosya')->getRealPath());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['dosya' => $e->getMessage()]);
        }
        AuditLog::kaydet('ilac_recete_yukle', ['yazilan' => $s['yazilan'], 'atlanan' => $s['atlanan']]);

        return back()->with('ok', number_format($s['yazilan'], 0, ',', '.').' ilaç yüklendi ('.number_format($s['atlanan'], 0, ',', '.').' satır geçersiz GTIN nedeniyle atlandı). Liste tamamen yenilendi.');
    }
}
