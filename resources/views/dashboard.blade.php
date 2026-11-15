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
    ['Barkod Bazlı Stok', 'GTIN bazında adet, parti, SKT', 'bi-upc', route('stok.barkod')],
    ['Stok Listesi', 'Karekodlu stok, Excel', 'bi-upc-scan', route('stok.liste')],
    ['Durum Sorgu', 'Ürünün İTS durumu', 'bi-shield-check', route('durum')],
    ['Kritik / Miad', 'Yaklaşan SKT ve eşikler', 'bi-exclamation-triangle', route('kritik-miad')],
    ['Paydaşlar', 'Aktif / pasif paydaş listesi', 'bi-building', route('paydaslar')],
    ['İTS Bağlantısı', 'Bağlantıyı sına', 'bi-plug', route('ayarlar.its')],
  ];
@endphp
@isset($pts)
<div class="row g-3 mt-1">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header bg-white fw-semibold"><i class="bi bi-inbox me-1"></i>PTS Havuzu: Arada Kalanlar</div><div class="card-body">
    <div class="row text-center mb-3">
      <div class="col"><div class="fs-3 {{ $pts['bekleyen_paket'] ? 'text-warning' : 'text-success' }}">{{ $pts['bekleyen_paket'] }}</div><div class="small text-muted">Mal Alım bekleyen paket</div></div>
      <div class="col"><div class="fs-3">{{ $pts['bekleyen_urun'] }}</div><div class="small text-muted">Bekleyen ürün</div></div>
      <div class="col"><div class="fs-3">{{ $pts['son30_alinan'] }}</div><div class="small text-muted">Son 30 gün mal alınan paket</div></div>
    </div>
    @if($pts['liste']->isEmpty())
      <div class="text-success small"><i class="bi bi-check-circle me-1"></i>Mal Alım bekleyen paket yok.</div>
    @else
      <table class="table table-sm align-middle mb-2"><thead><tr><th>Belge / Transfer</th><th>Gönderen</th><th class="text-end">Ürün</th><th>Bekleme</th><th></th></tr></thead><tbody>
      @foreach($pts['liste'] as $p)
        @php $bg = (int) $p->created_at->startOfDay()->diffInDays(now()->startOfDay()); @endphp
        <tr><td class="small">{{ $p->belge_no ?: $p->transfer_id }}</td><td class="small font-monospace">{{ $p->kaynak_gln ?: '—' }}</td><td class="text-end">{{ $p->urun_sayisi }}</td>
          <td><span class="badge text-bg-{{ $bg >= 3 ? 'danger' : ($bg >= 1 ? 'warning' : 'secondary') }}">{{ $bg === 0 ? 'Bugün' : $bg.' gün' }}</span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('pts.paket', $p->id) }}">Mal Alım</a></td></tr>
      @endforeach
      </tbody></table>
      @if($pts['bekleyen_paket'] > $pts['liste']->count())<div class="small text-muted">+ {{ $pts['bekleyen_paket'] - $pts['liste']->count() }} paket daha bekliyor (en eskiler gösterilir).</div>@endif
    @endif
    <div class="small text-muted mt-2">Bu özet, sizde indirilmiş ama Mal Alım bildirimi yapılmamış paketleri gösterir. İTS'te henüz indirmediğiniz paketler için <a href="{{ route('pts', ['baslangic' => now()->subDays(7)->toDateString(), 'bitis' => now()->toDateString(), 'sadece_alinmayan' => 1]) }}">PTS'te alınmayanları sorgulayın</a>.</div>
  </div></div></div>
  @if($sarf)
  <div class="col-lg-6"><div class="card h-100"><div class="card-header bg-white fw-semibold"><i class="bi bi-calendar3 me-1"></i>Aylık Ürün Sarf Miktarı</div><div class="card-body p-0">
    <table class="table table-sm align-middle mb-0"><thead><tr><th>Ay</th><th class="text-end">Sarf</th><th class="text-end">İptal</th><th class="text-end">Net</th><th class="text-end">Ürün çeşidi</th></tr></thead><tbody>
    @foreach($sarf['aylar'] as $a)
      <tr class="{{ $loop->first ? 'table-light fw-semibold' : '' }}"><td>{{ $a['ay']->locale('tr')->translatedFormat('F Y') }}</td><td class="text-end">{{ $a['sarf'] }}</td><td class="text-end text-muted">{{ $a['iptal'] }}</td><td class="text-end">{{ $a['net'] }}</td><td class="text-end">{{ $a['cesit'] }}</td></tr>
    @endforeach
    </tbody></table>
    @if($sarf['en_cok']->isNotEmpty())
    <div class="p-3 border-top"><div class="small text-muted mb-1">Bu ay en çok sarf edilenler</div>
      @foreach($sarf['en_cok'] as $e)<div class="d-flex justify-content-between small"><span>{{ $e['ad'] ?? $e['gtin'] }}</span><strong>{{ $e['adet'] }}</strong></div>@endforeach
    </div>
    @endif
  </div></div></div>
  @endif
</div>
@endisset
<h5 class="mt-4 mb-3" style="color:#0b2447;font-weight:700">Hızlı Erişim</h5>
<div class="row g-3">
  @foreach($kisayol as [$ad, $acik, $ikon, $url])
  <div class="col-sm-6 col-xl-3"><a class="hizli" href="{{ $url }}"><span class="ikon"><i class="bi {{ $ikon }}"></i></span><span><strong>{{ $ad }}</strong><small>{{ $acik }}</small></span></a></div>
  @endforeach
</div>
@endsection
