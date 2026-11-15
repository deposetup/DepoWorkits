@extends('layouts.app')
@section('baslik', 'Müşteriler')
@section('icerik')
<h4>Müşteriler</h4>
<table class="table table-sm bg-white">
  <thead><tr><th>Ünvan</th><th>GLN</th><th>Tür</th><th>Bitiş</th><th>Kalan</th><th>Durum</th><th></th></tr></thead>
  <tbody>
  @foreach($tenants as $t)
    <tr>
      <td>{{ $t->unvan }}</td><td>{{ $t->gln }}</td><td><form method="post" action="{{ route('admin.tenants.tur', $t) }}" class="d-flex gap-1">@csrf
        <select name="tur" class="form-select form-select-sm" style="width:115px" onchange="this.form.submit()">
          @foreach(['eczane' => 'Eczane', 'hastane' => 'Hastane', 'depo' => 'Depo', 'uretici' => 'Üretici', 'ihracatci' => 'İhracatçı'] as $k => $ad)<option value="{{ $k }}" @selected($t->tur === $k)>{{ $ad }}</option>@endforeach
        </select></form></td>
      <td>{{ $t->lisans_bitis->format('d.m.Y') }}</td><td>{{ $t->kalanGun() }} gün</td>
      <td>{!! $t->lisansAktifMi() ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-danger">Pasif/Süresi doldu</span>' !!}</td>
      <td class="d-flex gap-1">
        <form method="post" action="{{ route('admin.tenants.musteri-gozu', $t) }}">@csrf<button class="btn btn-sm btn-success" title="Bu müşteriyi, kendi ekranı gibi salt okunur görüntüle"><i class="bi bi-eye me-1"></i>Müşteri Gözü</button></form>
        <a href="{{ route('admin.tenants.duzelt', $t) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square me-1"></i>Düzelt</a>
        <a href="{{ route('admin.kullanici.index', $t) }}" class="btn btn-sm btn-outline-dark">Kullanıcılar</a>
        <a href="{{ route('admin.tenants.its', $t) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-key me-1"></i>İTS Bilgileri</a>
        <form method="post" action="{{ route('admin.tenants.uzat', $t) }}" class="d-flex gap-1">@csrf
          <input type="number" name="gun" value="365" min="1" class="form-control form-control-sm" style="width:80px"><button class="btn btn-sm btn-outline-primary">Uzat</button></form>
        <form method="post" action="{{ route('admin.tenants.aktif', $t) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $t->aktif ? 'Pasifleştir' : 'Aktifleştir' }}</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
<h5 class="mt-4">Yeni Müşteri</h5>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('admin.tenants.store') }}" class="row g-2">@csrf
  <div class="col-md-2"><input id="gln" name="gln" class="form-control" placeholder="GLN (13 hane)" maxlength="13" inputmode="numeric" autocomplete="off" autofocus required></div>
  <div class="col-md-4"><input id="unvan" name="unvan" class="form-control" placeholder="Ünvan (otomatik: Her Kelimenin Baş Harfi Büyük)" required></div>
  <div class="col-md-2"><select id="tur" name="tur" class="form-select"><option value="eczane">Eczane</option><option value="hastane">Hastane</option><option value="depo">Depo</option><option value="uretici">Üretici</option><option value="ihracatci">İhracatçı</option></select></div>
  <div class="col-md-2"><input type="date" name="lisans_baslangic" value="{{ now()->toDateString() }}" class="form-control" required></div>
  <div class="col-md-2"><input type="date" name="lisans_bitis" value="{{ now()->addYear()->toDateString() }}" class="form-control" required></div>
  <div class="col-md-2"><input id="il" name="il" class="form-control" placeholder="İl" maxlength="60"></div>
  <div class="col-md-2"><input id="ilce" name="ilce" class="form-control" placeholder="İlçe" maxlength="60"></div>
  <div class="col-md-4"><input id="eposta" type="email" name="eposta" class="form-control" placeholder="E-posta (isteğe bağlı)"></div>
  <div class="col-md-2"><input id="telefon" name="telefon" class="form-control" placeholder="Telefon" maxlength="30"></div>
  <div class="col-12"><div class="form-text">GLN'i yazınca ünvan, tür, il, ilçe, e-posta ve telefon İTS paydaş listesinden otomatik gelir; dilerseniz düzeltebilirsiniz.</div>@include('admin._gln-sorgu')</div>
  <div class="col-12"><button class="btn btn-primary">Ekle</button></div>
</form>
@endsection
