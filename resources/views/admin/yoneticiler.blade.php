@extends('layouts.app')
@section('baslik', 'Kullanıcılar')
@section('icerik')
<h4>Kullanıcılar <small class="text-muted">Yönetim paneli yöneticileri</small></h4>
<p class="small text-muted">Buradaki kullanıcılar tüm müşterileri, destek taleplerini ve yönetim ekranlarını görebilir. Müşterilerin kendi kullanıcıları için <a href="{{ route('admin.tenants.index') }}">Müşteriler</a> sayfasını kullanın.</p>
@if($errors->has('yonetici'))<div class="alert alert-danger">{{ $errors->first('yonetici') }}</div>@endif
<table class="table table-sm bg-white">
<thead><tr><th>Ad</th><th>E-posta</th><th>Son Giriş</th><th>Durum</th><th></th></tr></thead><tbody>
@foreach($yoneticiler as $k)
<tr><td>{{ $k->name }} @if($k->id === auth()->id())<span class="badge text-bg-info">Siz</span>@endif</td><td>{{ $k->email }}</td>
<td>{{ $k->son_giris ? \Illuminate\Support\Carbon::parse($k->son_giris)->timezone('Europe/Istanbul')->format('d.m.Y H:i') : '—' }}</td>
<td>{!! $k->aktif ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Pasif</span>' !!}</td>
<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#sf{{ $k->id }}"><i class="bi bi-key me-1"></i>Şifre Değiştir</button>
@if($k->id !== auth()->id())<form method="post" action="{{ route('admin.yoneticiler.aktif', $k) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" onclick="return confirm('{{ addslashes($k->name) }} için erişim {{ $k->aktif ? 'kapatılacak' : 'açılacak' }}. Emin misiniz?')">{{ $k->aktif ? 'Pasifleştir' : 'Aktifleştir' }}</button></form>@endif</td></tr>
<tr class="collapse {{ $errors->has('password') && old('_k') == $k->id ? 'show' : '' }}" id="sf{{ $k->id }}"><td colspan="5" class="bg-light">
<form method="post" action="{{ route('admin.yoneticiler.sifre', $k) }}" class="row g-2 align-items-center">@csrf<input type="hidden" name="_k" value="{{ $k->id }}">
<div class="col-auto small fw-semibold">{{ $k->name }} için yeni şifre:</div>
<div class="col-md-4"><div class="input-group input-group-sm"><input type="password" name="password" class="form-control" placeholder="Yeni şifre (min. 10, harf+rakam)" required autocomplete="new-password"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
<div class="col-auto"><button class="btn btn-sm btn-primary" onclick="return confirm('{{ addslashes($k->name) }} kullanıcısının şifresi değiştirilecek. Emin misiniz?')">Kaydet</button></div></form></td></tr>
@endforeach
</tbody></table>

<h5 class="mt-4">Yeni Yönetici Ekle</h5>
@if($errors->any() && ! $errors->has('yonetici') && ! $errors->has('password') || ($errors->has('password') && ! old('_k')))<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('admin.yoneticiler.store') }}" class="row g-2">@csrf
  <div class="col-md-3"><input name="name" value="{{ old('_k') ? '' : old('name') }}" class="form-control" placeholder="Ad Soyad" required></div>
  <div class="col-md-3"><input type="email" name="email" value="{{ old('_k') ? '' : old('email') }}" class="form-control" placeholder="E-posta (giriş adı)" required></div>
  <div class="col-md-4"><div class="input-group"><input type="password" name="password" class="form-control" placeholder="Giriş şifresi (min. 10, harf+rakam)" required autocomplete="new-password"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Ekle</button></div>
</form>
<p class="small text-muted mt-2">Yönetici, giriş ekranında <b>e-posta + şifresi</b> ile girer. Geçici şifreyi kendisine iletin; ilk girişte "Şifre Değiştir" ekranından değiştirmesini isteyin. Silme yoktur; erişimi kapatmak için "Pasifleştir"i kullanın (kayıtlar korunur).</p>
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
