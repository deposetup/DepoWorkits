<?php

namespace App\Http\Controllers;

use App\Models\IlacFiyat;
use Illuminate\Http\Request;

/** Müşteriler için geçerli ilaç fiyat listesi: yalnızca arama ile sorgulanır, sayfa başına 25 satır. */
class IlacFiyatController extends Controller
{
    public function index(Request $request)
    {
        $hazir = IlacFiyat::hazir();
        $ara = trim((string) $request->query('ara', ''));
        $kisa = $ara !== '' && mb_strlen($ara) < 3;
        $sonuclar = null;
        if ($hazir && $ara !== '' && ! $kisa) {
            $sonuclar = IlacFiyat::ara($ara)->paginate(Admin\IlacFiyatController::SAYFA)->withQueryString();
        }

        return view('ilac-fiyat.index', [
            'hazir' => $hazir,
            'toplam' => $hazir ? IlacFiyat::count() : 0,
            'sonYukleme' => $hazir ? IlacFiyat::max('updated_at') : null,
            'ara' => $ara, 'kisa' => $kisa, 'sonuclar' => $sonuclar,
        ]);
    }
}
