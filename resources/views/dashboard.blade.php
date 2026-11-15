@extends('layouts.app')
@section('baslik', 'Ana Sayfa')
@section('icerik')
@if($tenant && ! $tenant->kvkk_onay_tarihi)
<div class="alert alert-warning d-flex justify-content-between align-items-center">
  <span>Devam etmek için KVKK aydınlatma metnini onaylamanız gerekir.</span>
  <form method="post" action="{{ route('kvkk.onay') }}">@csrf<button class="btn btn-warning btn-sm">ONAYLIYORUM</button></form>
</div>
@endif
@if($tenant)
@php($gun = $tenant->kalanGun())
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
@endsection
