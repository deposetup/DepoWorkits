<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GirisKaydi;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GirisKayitController extends Controller
{
    public function index(Request $request)
    {
        $v = $request->validate(['tenant' => 'nullable|integer', 'durum' => 'nullable|in:basarili,basarisiz', 'tarih' => 'nullable|date']);
        $var = Schema::hasTable('giris_kayitlari');
        $kayitlar = $var
            ? GirisKaydi::with(['tenant:id,unvan', 'user:id,name'])
                ->when($v['tenant'] ?? null, fn ($q, $t) => $q->where('tenant_id', $t))
                ->when($v['durum'] ?? null, fn ($q, $d) => $q->where('basarili', $d === 'basarili'))
                ->when($v['tarih'] ?? null, fn ($q, $t) => $q->whereDate('created_at', $t))
                ->latest('created_at')->latest('id')->paginate(50)->withQueryString()
            : null;

        return view('admin.giris-kayitlari', ['kayitlar' => $kayitlar, 'tenantlar' => Tenant::orderBy('unvan')->pluck('unvan', 'id'), 'f' => $v]);
    }
}
