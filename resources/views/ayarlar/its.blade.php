@extends('layouts.app')
@section('baslik', 'İTS Bağlantısı')
@section('icerik')
<h4>İTS Bağlantısı</h4>
<p class="text-muted small">İTS kullanıcı adı ve şifreniz DepoWork ekibi tarafından tanımlanır ve şifreli saklanır; bu ekrandan görüntülenemez veya değiştirilemez. Bilgilerde bir değişiklik (ör. şifre yenileme) gerekirse DepoWork ekibiyle iletişime geçin. Sürücü: <code>{{ $surucu }}</code>{{ $surucu === 'mock' ? ' (gerçek İTS\'e bağlanmaz, deneme verisi kullanılır)' : '' }}</p>
<div class="card col-md-6"><div class="card-body">
  <div class="mb-3">Kimlik durumu: {!! $kayitli ? '<span class="badge text-bg-success">Tanımlı</span>' : '<span class="badge text-bg-warning">Tanımlı değil, DepoWork ekibiyle iletişime geçin</span>' !!}
    @if($guncelleme)<div class="text-muted small mt-1">Son güncelleme: {{ $guncelleme->format('d.m.Y H:i') }}</div>@endif</div>
  @if($kayitli)<form method="post" action="{{ route('ayarlar.its.test') }}">@csrf<button class="btn btn-primary"><i class="bi bi-plug me-1"></i>Bağlantıyı Sına</button></form>@endif
</div></div>
@endsection
