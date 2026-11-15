<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Services\StokCsvAktar;
use Illuminate\Http\Request;

/** Yönetici, İTS stok dosyasını (CSV/ZIP) seçilen müşterinin stoğuna aktarır. */
class StokCsvController extends Controller
{
    public function form(Tenant $tenant)
    {
        return view('admin.stok-csv', ['tenant' => $tenant, 'mevcut' => StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->count(), 'csv' => null]);
    }

    public function yukle(Request $request, Tenant $tenant, StokCsvAktar $aktar)
    {
        $request->validate(['dosya' => 'required|file|max:51200|mimes:csv,txt,zip']);
        try {
            $csv = $aktar->ice($tenant, $request->file('dosya'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dosya' => $e->getMessage()]);
        }

        return view('admin.stok-csv', ['tenant' => $tenant, 'mevcut' => StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->count(), 'csv' => $csv]);
    }
}
