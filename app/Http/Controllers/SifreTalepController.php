<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DestekTalebi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Giriş yapmadan şifre talebi: GLN ya da e-posta yazılır; talep DepoWork ekibine düşer (e-posta bildirimi sonra). */
class SifreTalepController extends Controller
{
    public function form()
    {
        return view('auth.sifre-unuttum');
    }

    public function gonder(Request $request)
    {
        $v = $request->validate([
            'kimlik' => 'required|string|max:255',
            'telefon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
            'mesaj' => 'nullable|string|max:500',
        ], ['kimlik.required' => 'GLN numaranızı ya da e-posta adresinizi yazın.', 'telefon.regex' => 'Telefon numarasını rakamlarla yazın.']);
        $kimlik = trim($v['kimlik']);

        // Hesabın var olup olmadığı dışarıya sızdırılmaz: yanıt her durumda aynıdır.
        $cevap = redirect()->route('sifre.unuttum')->with('ok', 'Talebiniz alındı. Bilgiler kayıtlı bir hesapla eşleşiyorsa DepoWork ekibi sizinle iletişime geçecektir.');
        if (! Schema::hasTable('destek_talepleri')) {
            return $cevap;
        }

        $user = null;
        if (preg_match('/^\d{13}$/', $kimlik)) {
            $tenant = Tenant::where('gln', $kimlik)->first();
        } else {
            $user = User::where('email', $kimlik)->whereNotNull('tenant_id')->first();
            $tenant = $user?->tenant ?? Tenant::where('eposta', $kimlik)->first();
        }
        if (! $tenant) {
            return $cevap;
        }
        // Aynı müşteriden son 1 saatte açık şifre talebi varsa yenisi açılmaz.
        $acik = DestekTalebi::where(['tenant_id' => $tenant->id, 'konu' => 'sifre_unuttum', 'durum' => 'yeni'])->where('created_at', '>=', now()->subHour())->exists();
        if (! $acik) {
            $talep = DestekTalebi::create([
                'tenant_id' => $tenant->id, 'user_id' => $user?->id, 'konu' => 'sifre_unuttum',
                'telefon' => $v['telefon'] ?? null,
                'mesaj' => 'Şifremi unuttum. Yazılan bilgi: '.$kimlik.(filled($v['mesaj'] ?? null) ? "\n".$v['mesaj'] : ''),
            ]);
            AuditLog::create(['tenant_id' => $tenant->id, 'user_id' => $user?->id, 'islem' => 'sifre_talebi', 'detay' => ['talep_id' => $talep->id], 'ip' => $request->ip()]);
        }

        return $cevap;
    }
}
