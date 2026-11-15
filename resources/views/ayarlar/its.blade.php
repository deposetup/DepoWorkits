@extends('layouts.app')
@section('baslik', 'İTS Ayarları')
@section('icerik')
<h4>İTS Bağlantı Ayarları</h4>
<p class="text-muted small">Bakanlık İTS kullanıcı adı (GLN) ve şifreniz şifreli saklanır, ekranda bir daha gösterilmez. Sürücü: <code>{{ $surucu }}</code>{{ $surucu === 'mock' ? ' (gerçek İTS\'e bağlanmaz, deneme verisi kullanılır)' : '' }}</p>
<div class="mb-3">Durum: {!! $kayitli ? '<span class="badge text-bg-success">Bilgiler kayıtlı</span>' : '<span class="badge text-bg-warning">Kayıtlı bilgi yok</span>' !!}</div>
<form method="post" action="{{ route('ayarlar.its.kaydet') }}" class="col-md-5 mb-3" autocomplete="off">@csrf
  <div class="mb-2"><label class="form-label">İTS kullanıcı adı</label><input name="kullanici_adi" class="form-control" required autocomplete="off"></div>
  <div class="mb-3"><label class="form-label">İTS şifresi</label><input type="password" name="sifre" class="form-control" required autocomplete="new-password"></div>
  <button class="btn btn-primary">{{ $kayitli ? 'Güncelle' : 'Kaydet' }}</button>
</form>
@if($kayitli)<form method="post" action="{{ route('ayarlar.its.test') }}">@csrf<button class="btn btn-outline-secondary">Bağlantıyı Sına</button></form>@endif
@endsection
