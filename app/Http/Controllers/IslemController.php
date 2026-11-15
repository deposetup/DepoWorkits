<?php

namespace App\Http\Controllers;

use App\Services\StokServisi;
use Illuminate\Http\Request;

class IslemController extends Controller
{
    public function form(string $tur)
    {
        abort_unless(isset(StokServisi::TURLER[$tur]), 404);

        return view('islem.form', ['tur' => $tur, 'baslik' => StokServisi::TURLER[$tur][2], 'sebepler' => StokServisi::DEAKTIVE_SEBEPLER]);
    }

    public function calistir(Request $request, string $tur, StokServisi $servis)
    {
        abort_unless(isset(StokServisi::TURLER[$tur]), 404);
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate([
            'kodlar' => 'required|string|max:50000',
            'sebep' => 'nullable|in:'.implode(',', array_keys(StokServisi::DEAKTIVE_SEBEPLER)),
            'aciklama' => 'nullable|string|max:255',
            'karsi_gln' => 'nullable|digits:13',
        ]);
        if (in_array($tur, ['iade', 'satis', 'devir'], true)) {
            $request->validate(['karsi_gln' => 'required|digits:13']);
        }
        if (in_array($tur, ['deaktive', 'deaktive_iptal'], true)) {
            $request->validate(['sebep' => 'required']);
        }

        $sonuc = $servis->isle($request->user(), $tur, $v['kodlar'], $v['sebep'] ?? null, $v['aciklama'] ?? null, $v['karsi_gln'] ?? null);

        return view('islem.form', [
            'tur' => $tur, 'baslik' => StokServisi::TURLER[$tur][2], 'sebepler' => StokServisi::DEAKTIVE_SEBEPLER, 'sonuc' => $sonuc,
        ]);
    }
}
