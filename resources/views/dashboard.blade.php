@extends('layouts.app')
@section('baslik', 'Ana Sayfa')
@section('icerik')
@if($tenant && ! $tenant->kvkk_onay_tarihi)
<div class="alert alert-warning d-flex justify-content-between align-items-center">
  <span>Devam etmek için KVKK aydınlatma metnini onaylamanız gerekir.</span>
  <form method="post" action="{{ route('kvkk.onay') }}">@csrf<button class="btn btn-warning btn-sm">ONAYLIYORUM</button></form>
</div>
@endif
@if(! $tenant)
<h4>Ana Sayfa</h4>
@endif
@if($tenant)
@php $gun = $tenant->kalanGun(); @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Ana Sayfa</h4>
  <span class="badge fs-6 px-3 py-2 text-bg-{{ ! $tenant->lisansAktifMi() || $gun <= 7 ? 'danger' : ($gun <= 30 ? 'warning' : 'success') }}" title="Lisans bitiş: {{ $tenant->lisans_bitis->format('d.m.Y') }}">
    <i class="bi bi-hourglass-split me-1"></i>Kalan süre: {{ $gun }} gün
  </span>
</div>
<div class="row g-3">
  <div class="col-md-4"><div class="card {{ $gun <= 7 ? 'border-danger' : '' }}"><div class="card-body">
    <div class="text-muted small">Kalan Süre</div>
    <div class="fs-3">{{ $gun }} gün</div>
    <div class="small">Bitiş Tarihi: {{ $tenant->lisans_bitis->format('d.m.Y') }}</div>
    @if(! $tenant->lisansAktifMi())<span class="badge text-bg-danger">Lisans süresi doldu</span>@endif
  </div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body">
    <div class="text-muted small">KVKK Onay Tarihi</div>
    <div class="fs-5">{{ $tenant->kvkk_onay_tarihi?->format('d.m.Y H:i') ?? '—' }}</div>
  </div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body">
    <div class="text-muted small">İletişim</div>
    <div>{{ $tenant->eposta ?? '—' }}</div><div>{{ $tenant->telefon ?? '' }}</div>
  </div></div></div>
</div>
@endif
@php
  $kisayol = auth()->user()->superAdminMi() ? [
    ['Müşteriler', 'Müşteri ve lisans yönetimi', 'bi-people', route('admin.tenants.index')],
  ] : [
    ['Mal Kabul', 'Gelen ürünü kabul et', 'bi-check2-square', route('islem', 'kabul')],
    ['PTS Sorgu', 'Paketleri sorgula, XML yükle', 'bi-search', route('pts')],
    ['Satış', 'Ürün satış bildirimi', 'bi-cart-check', route('islem', 'satis')],
    ['Stok Listesi', 'Karekodlu stok, Excel', 'bi-upc-scan', route('stok.liste')],
    ['Durum Sorgu', 'Ürünün İTS durumu', 'bi-shield-check', route('durum')],
    ['Kritik / Miad', 'Yaklaşan SKT ve eşikler', 'bi-exclamation-triangle', route('kritik-miad')],
    ['Paydaşlar', 'Aktif / pasif paydaş listesi', 'bi-building', route('paydaslar')],
    ['İTS Ayarları', 'Bağlantı ve kimlik bilgileri', 'bi-gear', route('ayarlar.its')],
  ];
@endphp
<h5 class="mt-4 mb-3" style="color:#0b2447;font-weight:700">Hızlı Erişim</h5>
<div class="row g-3">
  @foreach($kisayol as [$ad, $acik, $ikon, $url])
  <div class="col-sm-6 col-xl-3"><a class="hizli" href="{{ $url }}"><span class="ikon"><i class="bi {{ $ikon }}"></i></span><span><strong>{{ $ad }}</strong><small>{{ $acik }}</small></span></a></div>
  @endforeach
</div>
@endsection
