<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\MailAyari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MailAyarController extends Controller
{
    public function form()
    {
        return view('admin.mail-ayarlari', [
            'tabloVar' => MailAyari::tabloVar(),
            'a' => [
                'aktif' => MailAyari::gonderimAcik(),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'sifreleme' => config('mail.mailers.smtp.scheme') === 'smtps' ? 'ssl' : 'tls',
                'kullanici' => config('mail.mailers.smtp.username'),
                'gonderen_adres' => config('mail.from.address'),
                'gonderen_ad' => config('mail.from.name'),
                'alicilar' => implode("\n", MailAyari::alicilar()),
            ],
            'sifreVar' => MailAyari::sifreKayitliMi() || filled(config('mail.mailers.smtp.password')),
        ]);
    }

    public function kaydet(Request $request)
    {
        abort_unless(MailAyari::tabloVar(), 409, 'Ayarlar tablosu henüz oluşturulmadı (php artisan migrate çalıştırılmalı).');
        $v = $request->validate([
            'aktif' => 'required|in:0,1',
            'host' => 'required_if:aktif,1|nullable|string|max:200',
            'port' => 'required_if:aktif,1|nullable|integer|min:1|max:65535',
            'sifreleme' => 'required|in:ssl,tls',
            'kullanici' => 'nullable|string|max:200',
            'sifre' => 'nullable|string|max:200',
            'gonderen_adres' => 'required_if:aktif,1|nullable|email|max:200',
            'gonderen_ad' => 'nullable|string|max:120',
            'alicilar' => 'required|string|max:1000',
        ], [
            'host.required_if' => 'Gönderim açıkken SMTP sunucusu zorunludur.',
            'port.required_if' => 'Gönderim açıkken port zorunludur.',
            'gonderen_adres.required_if' => 'Gönderim açıkken gönderen adresi zorunludur.',
            'alicilar.required' => 'En az bir alıcı e-posta adresi girin.',
        ]);

        $alicilar = preg_split('/[\s,;]+/', $v['alicilar'], -1, PREG_SPLIT_NO_EMPTY);
        foreach ($alicilar as $adres) {
            if (! filter_var($adres, FILTER_VALIDATE_EMAIL)) {
                return back()->withInput($request->except('sifre'))->withErrors(['alicilar' => '"'.$adres.'" geçerli bir e-posta adresi değil.']);
            }
        }

        $kayit = [
            'aktif' => $v['aktif'], 'host' => $v['host'] ?? null, 'port' => $v['port'] ?? null, 'sifreleme' => $v['sifreleme'],
            'kullanici' => $v['kullanici'] ?? null, 'gonderen_adres' => $v['gonderen_adres'] ?? null, 'gonderen_ad' => $v['gonderen_ad'] ?? null,
            'alicilar' => implode(', ', array_unique($alicilar)),
        ];
        if (filled($v['sifre'] ?? null)) { // boş bırakılırsa mevcut şifre korunur
            $kayit['sifre'] = Crypt::encryptString($v['sifre']);
        }
        MailAyari::kaydet($kayit);
        AuditLog::kaydet('mail_ayarlari', ['aktif' => $v['aktif'], 'host' => $v['host'] ?? null, 'alicilar' => count($alicilar), 'sifre_degisti' => filled($v['sifre'] ?? null)]);

        return redirect()->route('admin.mail-ayarlari')->with('ok', 'Mail ayarları kaydedildi. Ayarları denemek için "Test maili gönder" düğmesini kullanın.');
    }
}
