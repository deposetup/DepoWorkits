<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaydasDegisimController extends Controller
{
    public const OLAYLAR = ['yeni' => ['Yeni eklendi', 'success'], 'pasif' => ['Pasife alındı', 'danger'], 'aktif' => ['Yeniden aktif oldu', 'primary'], 'kaldirildi' => ['Listeden kalktı', 'dark']];

    public function index(Request $request)
    {
        $v = $request->validate(['olay' => 'nullable|in:'.implode(',', array_keys(self::OLAYLAR)), 'ara' => 'nullable|string|max:100']);
        $hazir = Schema::hasTable('paydas_degisimleri');
        $kayitlar = $hazir
            ? DB::table('paydas_degisimleri')->when($v['olay'] ?? null, fn ($q, $o) => $q->where('olay', $o))
                ->when(trim($v['ara'] ?? ''), fn ($q, $a) => $q->where(fn ($w) => $w->where('unvan', 'like', "%$a%")->orWhere('gln', 'like', "%$a%")))
                ->orderByDesc('id')->paginate(50)->withQueryString()
            : null;
        $izlenen = $hazir ? DB::table('paydas_anliklari')->where('tip', 'depo')->count() : 0;
        $sonKontrol = $hazir ? DB::table('paydas_anliklari')->where('tip', 'depo')->max('updated_at') : null;

        return view('admin.paydas-degisimleri', ['kayitlar' => $kayitlar, 'olaylar' => self::OLAYLAR, 'f' => $v, 'izlenen' => $izlenen, 'sonKontrol' => $sonKontrol, 'alici' => implode(', ', \App\Support\MailAyari::alicilar()), 'mailAyarli' => \App\Support\MailAyari::gonderimAcik()]);
    }

    /** Mail ayarlarını denemek için bildirim adresine test e-postası yollar. */
    public function testMail(Request $request)
    {
        if (! \App\Support\MailAyari::gonderimAcik()) {
            return back()->with('hata', 'E-posta gönderimi kapalı veya ayarlanmamış. Yönetim > Mail Ayarları ekranından SMTP bilgilerini girip gönderimi açın.');
        }
        $alici = \App\Support\MailAyari::alicilar();
        try {
            \Illuminate\Support\Facades\Mail::raw("Bu bir test e-postasıdır. DepoWork İTS paydaş değişikliği bildirimleri bu ayarlarla gönderilecek.\n\nGönderen yönetici: ".$request->user()->email, function ($m) use ($alici) {
                $m->to($alici)->subject('DepoWork İTS test e-postası');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Test e-postası gönderilemedi: '.$e->getMessage());

            return back()->with('hata', 'Test e-postası gönderilemedi: '.\Illuminate\Support\Str::limit($e->getMessage(), 300));
        }
        \App\Models\AuditLog::kaydet('mail_test', ['alici' => $alici]);

        return back()->with('ok', 'Test e-postası '.implode(', ', $alici).' adresine gönderildi. Gelen kutusunu (ve spam klasörünü) kontrol edin.');
    }
}
