@extends('layouts.app')
@section('baslik', 'Kullanıcılar')
@section('icerik')
<a href="{{ route('admin.tenants.index') }}" class="small">&larr; Müşteriler</a>
<h4 class="mt-2">{{ $tenant->unvan }} <small class="text-muted">Kullanıcılar</small></h4>
<table class="table table-sm bg-white">
<thead><tr><th>Ad</th><th>E-posta</th><th>Rol</th><th>Son Giriş</th><th>Durum</th><th></th></tr></thead><tbody>
@forelse($kullanicilar as $k)
<tr><td>{{ $k->name }}</td><td>{{ $k->email ?? '—' }}</td><td>{{ $k->rol }}</td><td>{{ $k->son_giris ? \Illuminate\Support\Carbon::parse($k->son_giris)->format('d.m.Y H:i') : '—' }}</td>
<td>{!! $k->aktif ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Pasif</span>' !!}</td>
<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#sf{{ $k->id }}"><i class="bi bi-key me-1"></i>Şifre Değiştir</button>
<form method="post" action="{{ route('admin.kullanici.aktif', [$tenant, $k]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $k->aktif ? 'Pasifleştir' : 'Aktifleştir' }}</button></form></td></tr>
<tr class="collapse {{ $errors->has('password') && old('_k') == $k->id ? 'show' : '' }}" id="sf{{ $k->id }}"><td colspan="6" class="bg-light">
<form method="post" action="{{ route('admin.kullanici.sifre', [$tenant, $k]) }}" class="row g-2 align-items-center">@csrf<input type="hidden" name="_k" value="{{ $k->id }}">
<div class="col-auto small fw-semibold">{{ $k->name }} için yeni şifre:</div>
<div class="col-md-4"><div class="input-group input-group-sm"><input type="password" name="password" class="form-control" placeholder="Yeni şifre (min. 10, harf+rakam)" required autocomplete="new-password"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
<div class="col-auto"><button class="btn btn-sm btn-primary" onclick="return confirm('{{ addslashes($k->name) }} kullanıcısının şifresi değiştirilecek. Emin misiniz?')">Kaydet</button></div></form></td></tr>
@empty<tr><td colspan="6" class="text-muted">Bu müşteriye bağlı kullanıcı yok.</td></tr>@endforelse
</tbody></table>
<h5 class="mt-4">Yeni Kullanıcı</h5>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('admin.kullanici.store', $tenant) }}" class="row g-2">@csrf
  <div class="col-md-3"><input name="name" class="form-control" placeholder="Ad Soyad" required></div>
  <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="E-posta (isteğe bağlı)"></div>
  <div class="col-md-2"><select name="rol" class="form-select"><option value="tenant_admin">Yönetici</option><option value="kullanici">Kullanıcı</option></select></div>
  <div class="col-md-3"><div class="input-group"><input type="password" name="password" class="form-control" placeholder="Giriş şifresi (min. 10, harf+rakam)" required autocomplete="new-password"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
  <div class="col-md-1"><button class="btn btn-primary">Ekle</button></div>
</form>
<p class="small text-muted mt-2">Kullanıcı giriş ekranında <b>GLN ({{ $tenant->gln }}) + kendi şifresi</b> ile girer; e-posta zorunlu değildir. Aynı müşterideki iki kullanıcıya aynı şifre verilemez (GLN ile girişte kullanıcı şifreden bulunur). Geçici şifreyi müşteriye iletin; ilk girişte "Şifre Değiştir" ekranından değiştirmesini isteyin.</p>
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
