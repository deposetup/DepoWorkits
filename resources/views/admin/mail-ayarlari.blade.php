@extends('layouts.app')
@section('baslik', 'Mail Ayarları')
@section('icerik')
<h4>Mail Ayarları</h4>
@if(! $tabloVar)<div class="alert alert-warning">Ayarlar tablosu henüz oluşturulmadı. Sunucuda <code>php artisan migrate</code> çalıştırıldıktan sonra bu ekrandan ayar yapılabilir.</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<div class="alert alert-secondary small">Paydaş değişikliği bildirimleri (İTS depo listesi kontrolü) bu ayarlarla gönderilir. Burada kaydedilen değerler sunucudaki <code>.env</code> değerlerinin yerine geçer; alanları boş bırakırsanız <code>.env</code> değeri kullanılmaya devam eder.</div>
<form method="post" action="{{ route('admin.mail-ayarlari.kaydet') }}" class="row g-3" autocomplete="off">@csrf
  <div class="col-12"><div class="fw-semibold">Bildirim alıcıları</div></div>
  <div class="col-md-8"><label class="form-label">Alıcı e-posta adresleri</label>
    <textarea name="alicilar" rows="3" class="form-control" placeholder="ornek@firma.com" required>{{ old('alicilar', $a['alicilar']) }}</textarea>
    <div class="form-text">Birden fazla adres için her satıra bir adres yazın (virgül veya noktalı virgül de olur).</div></div>

  <div class="col-12 mt-4"><div class="fw-semibold">Gönderim (SMTP)</div></div>
  <div class="col-md-3"><label class="form-label">Mail gönderimi</label>
    <select name="aktif" class="form-select"><option value="1" @selected(old('aktif', $a['aktif'] ? '1' : '0') == '1')>Açık</option><option value="0" @selected(old('aktif', $a['aktif'] ? '1' : '0') == '0')>Kapalı (yalnızca ekranda göster)</option></select></div>
  <div class="col-md-5"><label class="form-label">SMTP sunucusu</label><input name="host" value="{{ old('host', $a['host']) }}" class="form-control" placeholder="mail.firma.com"></div>
  <div class="col-md-2"><label class="form-label">Port</label><input name="port" type="number" value="{{ old('port', $a['port']) }}" class="form-control" placeholder="465"></div>
  <div class="col-md-2"><label class="form-label">Şifreleme</label>
    <select name="sifreleme" class="form-select"><option value="ssl" @selected(old('sifreleme', $a['sifreleme']) === 'ssl')>SSL (465)</option><option value="tls" @selected(old('sifreleme', $a['sifreleme']) === 'tls')>TLS / STARTTLS (587)</option></select></div>
  <div class="col-md-5"><label class="form-label">Kullanıcı adı</label><input name="kullanici" value="{{ old('kullanici', $a['kullanici']) }}" class="form-control" autocomplete="off"></div>
  <div class="col-md-5"><label class="form-label">Şifre</label>
    <div class="input-group"><input type="password" name="sifre" class="form-control" autocomplete="new-password" placeholder="{{ $sifreVar ? '•••••••• (kayıtlı; değiştirmek için yazın)' : 'Şifre' }}"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div>
    <div class="form-text">Şifre şifrelenerek saklanır; boş bırakırsanız mevcut şifre korunur.</div></div>
  <div class="col-md-5"><label class="form-label">Gönderen adresi</label><input name="gonderen_adres" type="email" value="{{ old('gonderen_adres', $a['gonderen_adres']) }}" class="form-control" placeholder="noreply@firma.com"></div>
  <div class="col-md-5"><label class="form-label">Gönderen adı</label><input name="gonderen_ad" value="{{ old('gonderen_ad', $a['gonderen_ad']) }}" class="form-control"></div>
  <div class="col-12"><button class="btn btn-primary" @disabled(! $tabloVar)><i class="bi bi-save me-1"></i>Kaydet</button></div>
</form>
<hr class="my-4">
<div class="fw-semibold mb-2">Ayarları dene</div>
<form method="post" action="{{ route('admin.paydas-degisimleri.test-mail') }}" class="d-flex align-items-center gap-2">@csrf
  <button class="btn btn-outline-secondary"><i class="bi bi-envelope me-1"></i>Test maili gönder</button>
  <span class="small text-muted">Kaydedilmiş ayarlarla yukarıdaki alıcılara test e-postası gönderilir. Önce <b>Kaydet</b>'e basın.</span>
</form>
<script>
document.querySelectorAll('.goz-btn').forEach(function (b) {
  b.addEventListener('click', function () {
    var i = b.closest('.input-group').querySelector('input'), g = i.type === 'password';
    i.type = g ? 'text' : 'password';
    b.querySelector('i').className = g ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
});
</script>
@endsection
