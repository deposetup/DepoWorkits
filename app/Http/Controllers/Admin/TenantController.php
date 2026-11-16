<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        return view('admin.tenants', ['tenants' => Tenant::orderBy('unvan')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unvan' => 'required|string|max:255',
            'gln' => 'required|digits:13|unique:tenants,gln',
            'tur' => 'required|in:eczane,hastane,depo,uretici,ihracatci',
            'il' => 'nullable|string|max:60',
            'ilce' => 'nullable|string|max:60',
            'eposta' => 'nullable|email',
            'telefon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
            'lisans_baslangic' => 'required|date',
            'lisans_bitis' => 'required|date|after:lisans_baslangic',
            'its_kullanici_adi' => 'nullable|string|max:100|required_with:its_sifre',
            'its_sifre' => 'nullable|string|max:200|required_with:its_kullanici_adi',
        ], ['its_kullanici_adi.required_with' => 'İTS kullanıcı adı ve şifresi birlikte girilmelidir.', 'its_sifre.required_with' => 'İTS kullanıcı adı ve şifresi birlikte girilmelidir.']);
        $its = \Illuminate\Support\Arr::only($data, ['its_kullanici_adi', 'its_sifre']);
        unset($data['its_kullanici_adi'], $data['its_sifre']);
        $tenant = Tenant::create($data);
        if (filled($its['its_kullanici_adi'] ?? null) && filled($its['its_sifre'] ?? null)) {
            \App\Models\ItsCredential::create(['tenant_id' => $tenant->id, 'kullanici_adi' => $its['its_kullanici_adi'], 'sifre' => $its['its_sifre']]);
            AuditLog::kaydet('its_kimlik_kaydet', ['hedef_tenant_id' => $tenant->id]); // değerler loga yazılmaz
        }
        AuditLog::kaydet('tenant_olustur', ['tenant' => $tenant->id]);

        return back()->with('ok', 'Müşteri eklendi.');
    }

    public function uzat(Request $request, Tenant $tenant)
    {
        $gun = $request->validate(['gun' => 'required|integer|min:1|max:3650'])['gun'];
        $baz = $tenant->lisans_bitis->isFuture() ? $tenant->lisans_bitis : now();
        $tenant->update(['lisans_bitis' => $baz->copy()->addDays($gun)]);
        AuditLog::kaydet('lisans_uzat', ['tenant' => $tenant->id, 'gun' => $gun]);

        return back()->with('ok', 'Lisans uzatıldı.');
    }

    /** PTS havuzundaki (mal alımı yapılmamış) paketleri havuzdan çıkarır; kayıtlar silinmez, durumu 'sifirlandi' olur. */
    public function ptsHavuzuSifirla(Tenant $tenant)
    {
        $adet = \App\Models\PtsPaketi::where('tenant_id', $tenant->id)->where('durum', 'indirildi')->update(['durum' => 'sifirlandi']);
        AuditLog::kaydet('pts_havuz_sifirla', ['tenant' => $tenant->id, 'paket' => $adet]);

        return back()->with('ok', $tenant->unvan.': PTS havuzu sıfırlandı ('.$adet.' paket havuzdan çıkarıldı).');
    }

    public function turDegistir(Request $request, Tenant $tenant)
    {
        $tur = $request->validate(['tur' => 'required|in:eczane,hastane,depo,uretici,ihracatci'])['tur'];
        $eski = $tenant->tur;
        $tenant->update(['tur' => $tur]);
        AuditLog::kaydet('tenant_tur_degistir', ['tenant' => $tenant->id, 'eski' => $eski, 'yeni' => $tur]);

        return back()->with('ok', 'Müşteri türü güncellendi.');
    }

    public function aktifToggle(Tenant $tenant)
    {
        $tenant->update(['aktif' => ! $tenant->aktif]);
        AuditLog::kaydet('tenant_aktif_degistir', ['tenant' => $tenant->id, 'aktif' => $tenant->aktif]);

        return back();
    }

    public function duzelt(Tenant $tenant)
    {
        return view('admin.tenant-duzelt', ['tenant' => $tenant, 'sayilar' => $this->sayilar($tenant)]);
    }

    public function guncelle(Request $request, Tenant $tenant)
    {
        $v = $request->validate([
            'unvan' => 'required|string|max:255',
            'gln' => 'required|digits:13|unique:tenants,gln,'.$tenant->id,
            'tur' => 'required|in:eczane,hastane,depo,uretici,ihracatci',
            'il' => 'nullable|string|max:60',
            'ilce' => 'nullable|string|max:60',
            'eposta' => 'nullable|email|max:255',
            'telefon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
            'lisans_baslangic' => 'required|date',
            'lisans_bitis' => 'required|date|after:lisans_baslangic',
        ], ['telefon.regex' => 'Telefon numarasını rakamlarla yazın.', 'gln.unique' => 'Bu GLN başka bir müşteride kayıtlı.']);
        $v['aktif'] = $request->boolean('aktif');
        $v['bitince_salt_okunur'] = $request->boolean('bitince_salt_okunur');

        $tenant->update($v);
        $degisen = array_values(array_diff(array_keys($tenant->getChanges()), ['updated_at']));
        AuditLog::kaydet('tenant_guncelle', ['tenant' => $tenant->id, 'degisen_alanlar' => $degisen]);

        return redirect()->route('admin.tenants.duzelt', $tenant)->with('ok', 'Müşteri bilgileri güncellendi.');
    }

    /** Müşteriyi ve ona bağlı tüm verileri kalıcı olarak siler. GLN yazılarak onaylanır. */
    public function sil(Request $request, Tenant $tenant)
    {
        $request->validate(['onay_gln' => 'required|string'], ['onay_gln.required' => 'Silmek için müşterinin GLN numarasını yazın.']);
        if (trim($request->input('onay_gln')) !== $tenant->gln) {
            return back()->withErrors(['onay_gln' => 'Yazılan GLN, müşterinin GLN\'i ile eşleşmiyor. Müşteri silinmedi.']);
        }

        $sayilar = $this->sayilar($tenant);
        \Illuminate\Support\Facades\DB::transaction(function () use ($tenant) {
            // Kullanıcıların tenant_id'si silinmede boşa düşer (yönetici gibi görünürdü); önce kullanıcılar silinir.
            \App\Models\User::where('tenant_id', $tenant->id)->where('rol', '!=', 'super_admin')->delete();
            $tenant->delete(); // stok, hareket, PTS, destek talebi ve İTS bilgisi cascade ile silinir
        });
        AuditLog::kaydet('tenant_sil', ['tenant' => $tenant->id, 'unvan' => $tenant->unvan, 'gln' => $tenant->gln, 'silinen' => $sayilar]);

        return redirect()->route('admin.tenants.index')->with('ok', $tenant->unvan.' ve bağlı tüm kayıtlar silindi.');
    }

    private function sayilar(Tenant $tenant): array
    {
        $say = fn (string $model) => \Illuminate\Support\Facades\Schema::hasTable((new $model)->getTable()) ? $model::where('tenant_id', $tenant->id)->count() : 0;

        return [
            'Kullanıcı' => \App\Models\User::where('tenant_id', $tenant->id)->count(),
            'Stok kalemi' => $say(\App\Models\StokKalemi::class),
            'İşlem hareketi' => $say(\App\Models\Hareket::class),
            'PTS paketi' => $say(\App\Models\PtsPaketi::class),
            'PTS gönderimi' => $say(\App\Models\PtsGonderim::class),
            'Destek talebi' => $say(\App\Models\DestekTalebi::class),
        ];
    }
}
