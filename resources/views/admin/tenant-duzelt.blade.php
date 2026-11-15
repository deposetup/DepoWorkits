@extends('layouts.app')
@section('baslik', 'Müşteri Düzelt')
@section('icerik')
<a href="{{ route('admin.tenants.index') }}" class="small">&larr; Müşteriler</a>
<h4 class="mt-2">{{ $tenant->unvan }} <small class="text-muted">Müşteri Kartı</small></h4>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card mb-4"><div class="card-header bg-white fw-semibold"><i class="bi bi-pencil-square me-1"></i>Bilgileri Düzelt</div><div class="card-body">
<form method="post" action="{{ route('admin.tenants.guncelle', $tenant) }}" class="row g-3">@csrf
  <div class="col-md-8"><label class="form-label">Ünvan <small class="text-muted">(kayıtta Her Kelimenin Baş Harfi Büyük olur)</small></label><input name="unvan" value="{{ old('unvan', $tenant->unvan) }}" class="form-control" required maxlength="255"></div>
  <div class="col-md-4"><label class="form-label">GLN</label><input name="gln" value="{{ old('gln', $tenant->gln) }}" class="form-control font-monospace" maxlength="13" required>
    <div class="form-text">GLN değişirse müşterinin İTS bildirimlerinde kullanılan GLN de değişir.</div></div>
  <div class="col-md-4"><label class="form-label">Tür</label><select name="tur" class="form-select">
    @foreach(['eczane' => 'Eczane', 'hastane' => 'Hastane', 'depo' => 'Depo', 'uretici' => 'Üretici', 'ihracatci' => 'İhracatçı'] as $k => $ad)<option value="{{ $k }}" @selected(old('tur', $tenant->tur) === $k)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">İl</label><input name="il" value="{{ old('il', $tenant->il) }}" class="form-control" maxlength="60"></div>
  <div class="col-md-4"><label class="form-label">İlçe</label><input name="ilce" value="{{ old('ilce', $tenant->ilce) }}" class="form-control" maxlength="60"></div>
  <div class="col-md-6"><label class="form-label">E-posta <small class="text-muted">(isteğe bağlı)</small></label><input type="email" name="eposta" value="{{ old('eposta', $tenant->eposta) }}" class="form-control" maxlength="255"></div>
  <div class="col-md-6"><label class="form-label">Telefon <small class="text-muted">(isteğe bağlı)</small></label><input name="telefon" value="{{ old('telefon', $tenant->telefon) }}" class="form-control" maxlength="30"></div>
  <div class="col-md-3"><label class="form-label">Lisans başlangıç</label><input type="date" name="lisans_baslangic" value="{{ old('lisans_baslangic', $tenant->lisans_baslangic->toDateString()) }}" class="form-control" required></div>
  <div class="col-md-3"><label class="form-label">Lisans bitiş</label><input type="date" name="lisans_bitis" value="{{ old('lisans_bitis', $tenant->lisans_bitis->toDateString()) }}" class="form-control" required></div>
  <div class="col-md-6 d-flex flex-column justify-content-end gap-1">
    <div class="form-check"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktif" @checked(old('aktif', $tenant->aktif))><label class="form-check-label" for="aktif">Aktif</label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="bitince_salt_okunur" value="1" id="so" @checked(old('bitince_salt_okunur', $tenant->bitince_salt_okunur))><label class="form-check-label" for="so">Lisans bitince raporlar salt okunur açık kalsın</label></div>
  </div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Kaydet</button><a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-secondary">Vazgeç</a></div>
</form></div></div>

<div class="card border-danger"><div class="card-header bg-danger text-white fw-semibold"><i class="bi bi-exclamation-octagon me-1"></i>Müşteriyi Sil</div><div class="card-body">
  <p class="mb-2">Bu işlem <b>geri alınamaz</b>. Müşteri ve ona bağlı tüm kayıtlar kalıcı olarak silinir:</p>
  <ul class="mb-3">@foreach($sayilar as $ad => $n)<li>{{ $ad }}: <b>{{ $n }}</b></li>@endforeach<li>İTS bağlantı bilgileri</li></ul>
  <div class="alert alert-warning small">Geçici olarak kapatmak istiyorsanız silmek yerine yukarıdaki <b>Aktif</b> kutusunu kaldırıp kaydedin; veriler korunur. İşlem hareketleri İTS bildirim kayıtlarınızdır, silinince bu portalda geri getirilemez.</div>
  <form method="post" action="{{ route('admin.tenants.sil', $tenant) }}" class="row g-2 align-items-end" onsubmit="return confirm('{{ addslashes($tenant->unvan) }} ve tüm kayıtları KALICI olarak silinecek. Emin misiniz?')">@csrf
    <div class="col-md-5"><label class="form-label">Onay için müşterinin GLN'ini yazın: <span class="font-monospace">{{ $tenant->gln }}</span></label><input name="onay_gln" class="form-control font-monospace" maxlength="13" autocomplete="off" required></div>
    <div class="col-md-3"><button class="btn btn-danger"><i class="bi bi-trash me-1"></i>Müşteriyi Kalıcı Sil</button></div>
  </form>
</div></div>
@endsection
