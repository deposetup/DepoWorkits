@extends('layouts.app')
@section('baslik', 'Müşteri Düzelt')
@section('icerik')
<a href="{{ route('admin.tenants.index') }}" class="small">&larr; Müşteriler</a>
<h4 class="mt-2">{{ $tenant->unvan }} <small class="text-muted">Müşteri Kartı</small></h4>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card mb-4"><div class="card-header bg-white fw-semibold"><i class="bi bi-pencil-square me-1"></i>Bilgileri Düzelt</div><div class="card-body">
<form method="post" action="{{ route('admin.tenants.guncelle', $tenant) }}" class="row g-3">@csrf
  <div class="col-md-8"><label class="form-label">Ünvan <small class="text-muted">(kayıtta Her Kelimenin Baş Harfi Büyük olur)</small></label><input id="unvan" name="unvan" value="{{ old('unvan', $tenant->unvan) }}" class="form-control" required maxlength="255"></div>
  <div class="col-md-4"><label class="form-label">GLN</label><div class="input-group"><input id="gln" name="gln" value="{{ old('gln', $tenant->gln) }}" class="form-control font-monospace" maxlength="13" required><button type="button" class="btn btn-outline-primary" id="glnGetir" title="İTS paydaş listesinden bilgileri getir"><i class="bi bi-cloud-download"></i></button></div>
    <div id="glnDurumYeri"></div><div class="form-text">GLN yanındaki <i class="bi bi-cloud-download"></i> düğmesi İTS paydaş listesinden ünvan, tür, il, ilçe, e-posta ve telefonu getirir (formu doldurur; <b>Kaydet</b>'e basana kadar kaydedilmez). GLN değişirse müşterinin İTS bildirimlerinde kullanılan GLN de değişir.</div></div>
  <div class="col-md-4"><label class="form-label">Tür</label><select id="tur" name="tur" class="form-select">
    @foreach(['eczane' => 'Eczane', 'hastane' => 'Hastane', 'depo' => 'Depo', 'uretici' => 'Üretici', 'ihracatci' => 'İhracatçı'] as $k => $ad)<option value="{{ $k }}" @selected(old('tur', $tenant->tur) === $k)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">İl</label><input id="il" name="il" value="{{ old('il', $tenant->il) }}" class="form-control" maxlength="60"></div>
  <div class="col-md-4"><label class="form-label">İlçe</label><input id="ilce" name="ilce" value="{{ old('ilce', $tenant->ilce) }}" class="form-control" maxlength="60"></div>
  <div class="col-md-6"><label class="form-label">E-posta <small class="text-muted">(isteğe bağlı)</small></label><input id="eposta" type="email" name="eposta" value="{{ old('eposta', $tenant->eposta) }}" class="form-control" maxlength="255"></div>
  <div class="col-md-6"><label class="form-label">Telefon <small class="text-muted">(isteğe bağlı)</small></label><input id="telefon" name="telefon" value="{{ old('telefon', $tenant->telefon) }}" class="form-control" maxlength="30"></div>
  <div class="col-md-3"><label class="form-label">Lisans başlangıç</label><input type="date" name="lisans_baslangic" value="{{ old('lisans_baslangic', $tenant->lisans_baslangic->toDateString()) }}" class="form-control" required></div>
  <div class="col-md-3"><label class="form-label">Lisans bitiş</label><input type="date" name="lisans_bitis" value="{{ old('lisans_bitis', $tenant->lisans_bitis->toDateString()) }}" class="form-control" required></div>
  <div class="col-md-6 d-flex flex-column justify-content-end gap-1">
    <div class="form-check"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktif" @checked(old('aktif', $tenant->aktif))><label class="form-check-label" for="aktif">Aktif</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="bitince_salt_okunur" value="1" id="so" @checked(old('bitince_salt_okunur', $tenant->bitince_salt_okunur))><label class="form-check-label" for="so">Lisans bitince raporlar salt okunur açık kalsın</label></div>
  </div>
  <div class="col-12">@include('admin._gln-sorgu', ['overwrite' => true])</div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Kaydet</button><a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-secondary">Vazgeç</a></div>
</form></div></div>

<div class="card mb-4"><div class="card-header bg-white fw-semibold"><i class="bi bi-key me-1"></i>İTS Portal Kullanıcı Bilgileri</div><div class="card-body">
  <div class="mb-2">Durum: {!! $tenant->itsCredential ? '<span class="badge text-bg-success">Bilgiler kayıtlı</span> <span class="text-muted small">Son güncelleme: '.$tenant->itsCredential->updated_at->format('d.m.Y H:i').'</span>' : '<span class="badge text-bg-warning">Kayıtlı bilgi yok</span>' !!}</div>
  <p class="small text-muted">Müşterinin İTS portalındaki kullanıcı adı ve şifresi. Kullanıcı hesabı oluşturmaz; yalnızca portalın İTS'e bağlanmasında kullanılır. Değerler şifreli saklanır, kaydedildikten sonra bir daha gösterilmez, denetim kaydına yazılmaz. Müşteri kullanıcıları bu bilgileri göremez ve değiştiremez.</p>
  <form method="post" action="{{ route('admin.tenants.its.kaydet', $tenant) }}" class="row g-3" autocomplete="off">@csrf
    <div class="col-md-4"><label class="form-label">İTS kullanıcı adı <small class="text-muted">(genellikle GLN)</small></label><input name="kullanici_adi" class="form-control" required autocomplete="off" maxlength="100"></div>
    <div class="col-md-4"><label class="form-label">İTS şifresi</label><div class="input-group"><input type="password" name="sifre" class="form-control" required autocomplete="new-password" maxlength="200"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle" title="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
    <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary"><i class="bi bi-shield-lock me-1"></i>{{ $tenant->itsCredential ? 'İTS Bilgisini Güncelle' : 'İTS Bilgisini Kaydet' }}</button></div>
  </form>
</div></div>

<div class="card border-danger"><div class="card-header bg-danger text-white fw-semibold"><i class="bi bi-exclamation-octagon me-1"></i>Müşteriyi Sil</div><div class="card-body">
  <p class="mb-2">Bu işlem <b>geri alınamaz</b>. Müşteri ve ona bağlı tüm kayıtlar kalıcı olarak silinir:</p>
  <ul class="mb-3">@foreach($sayilar as $ad => $n)<li>{{ $ad }}: <b>{{ $n }}</b></li>@endforeach<li>İTS bağlantı bilgileri</li></ul>
  <div class="alert alert-warning small">Geçici olarak kapatmak istiyorsanız silmek yerine yukarıdaki <b>Aktif</b> kutusunu kaldırıp kaydedin; veriler korunur. İşlem hareketleri İTS bildirim kayıtlarınızdır, silinince bu portalda geri getirilemez.</div>
  <form method="post" action="{{ route('admin.tenants.sil', $tenant) }}" class="row g-2 align-items-end" onsubmit="return confirm('{{ addslashes($tenant->unvan) }} ve tüm kayıtları KALICI olarak silinecek. Emin misiniz?')">@csrf
    <div class="col-md-5"><label class="form-label">Onay için müşterinin GLN'ini yazın: <span class="font-monospace">{{ $tenant->gln }}</span></label><input name="onay_gln" class="form-control font-monospace" maxlength="13" autocomplete="off" required></div>
    <div class="col-md-3"><button class="btn btn-danger"><i class="bi bi-trash me-1"></i>Müşteriyi Kalıcı Sil</button></div>
  </form>
</div></div>
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
