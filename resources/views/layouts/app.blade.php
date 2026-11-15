<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('baslik', 'WebİTS')</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background: #f4f6fa; }
  .ust { background: linear-gradient(90deg, #0b2447, #19376d); box-shadow: 0 2px 8px rgba(0,0,0,.15); }
  .ust .navbar-brand { font-weight: 700; letter-spacing: .3px; }
  .ust .navbar-brand .nokta { color: #5fd0ff; }
  .ust .nav-link { color: rgba(255,255,255,.82); font-weight: 500; padding: .6rem .85rem; border-radius: .4rem; }
  .ust .nav-link:hover, .ust .nav-link.show { color: #fff; background: rgba(255,255,255,.1); }
  .ust .nav-link.aktif { color: #fff; background: rgba(95,208,255,.22); }
  .ust .dropdown-menu { border: 0; border-radius: .6rem; box-shadow: 0 10px 30px rgba(11,36,71,.25); padding: .4rem; min-width: 14rem; margin-top: .35rem; }
  .ust .dropdown-item { border-radius: .4rem; padding: .5rem .75rem; display: flex; gap: .6rem; align-items: center; }
  .ust .dropdown-item i { color: #19376d; width: 1.1rem; text-align: center; }
  .ust .dropdown-item.active, .ust .dropdown-item:active { background: #e8f0ff; color: #0b2447; font-weight: 600; }
  .ust .dropdown-header { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; }
  .firma { font-size: .8rem; color: #9fb3d9; }
  main .card, main .table { box-shadow: 0 1px 3px rgba(0,0,0,.06); }
  main h4 { font-weight: 700; color: #0b2447; margin-bottom: 1rem; }
</style>
</head>
<body>
@php
  $u = auth()->user();
  $menu = null;
  if ($u && ! $u->superAdminMi()) {
    $menu = [
      ['Ana Sayfa', 'bi-house-door', 'home', []],
      ['Alım', 'bi-box-arrow-in-down', null, [
        ['PTS Sorgu', 'bi-search', route('pts'), ['pts', 'pts.*']],
        ['Mal Kabul', 'bi-check2-square', route('islem', 'kabul'), ['islem:kabul']],
      ]],
      ['Satış & Çıkış', 'bi-box-arrow-up', null, [
        ['Satış', 'bi-cart-check', route('islem', 'satis'), ['islem:satis']],
        ['Satış İptal', 'bi-cart-x', route('islem', 'satis_iptal'), ['islem:satis_iptal']],
        ['Mal Devir', 'bi-arrow-left-right', route('islem', 'devir'), ['islem:devir']],
        ['Mal Devir İptal', 'bi-x-circle', route('islem', 'devir_iptal'), ['islem:devir_iptal']],
        ['Hastane Sarf', 'bi-hospital', route('islem', 'sarf'), ['islem:sarf']],
        ['Sarf İptal', 'bi-arrow-counterclockwise', route('islem', 'sarf_iptal'), ['islem:sarf_iptal']],
        ['Ürün İade', 'bi-arrow-return-left', route('islem', 'iade'), ['islem:iade']],
        ['Deaktive', 'bi-slash-circle', route('islem', 'deaktive'), ['islem:deaktive']],
        ['Deaktivasyon İptal', 'bi-check-circle', route('islem', 'deaktive_iptal'), ['islem:deaktive_iptal']],
      ]],
      ['Stok', 'bi-boxes', null, [
        ['Stok Özeti', 'bi-bar-chart-line', route('stok'), ['stok']],
        ['Stok Listesi (Karekod)', 'bi-upc-scan', route('stok.liste'), ['stok.liste']],
        ['Stok İçe Aktar (İTS Doğrulamalı)', 'bi-cloud-download', route('stok.ice-aktar'), ['stok.ice-aktar*']],
        ['Kritik / Miad', 'bi-exclamation-triangle', route('kritik-miad'), ['kritik-miad']],
      ]],
      ['Sorgular', 'bi-patch-question', null, [
        ['Durum Sorgu', 'bi-shield-check', route('durum'), ['durum*']],
        ['Paydaşlar', 'bi-building', route('paydaslar'), ['paydaslar']],
      ]],
      ['Analiz', 'bi-graph-up', 'analiz', []],
    ];
  }
  $aktifMi = function (array $desenler) {
    foreach ($desenler as $d) {
      if (str_starts_with($d, 'islem:')) {
        if (request()->routeIs('islem', 'islem.calistir') && request()->route('tur') === substr($d, 6)) return true;
      } elseif (request()->routeIs($d)) return true;
    }
    return false;
  };
@endphp
<nav class="navbar navbar-expand-lg navbar-dark ust sticky-top px-3">
  <a class="navbar-brand me-4" href="{{ $u ? route('home') : '#' }}"><i class="bi bi-capsule-pill me-1"></i>Web<span class="nokta">İTS</span></a>
  @auth
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="menu">
    <ul class="navbar-nav me-auto gap-1">
      @if($menu)
        @foreach($menu as [$ad, $ikon, $rota, $alt])
          @if(! $alt)
            <li class="nav-item"><a class="nav-link {{ request()->routeIs($rota) ? 'aktif' : '' }}" href="{{ route($rota) }}"><i class="bi {{ $ikon }} me-1"></i>{{ $ad }}</a></li>
          @else
            @php $grupAktif = collect($alt)->contains(fn ($a) => $aktifMi($a[3])); @endphp
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle {{ $grupAktif ? 'aktif' : '' }}" href="#" role="button" data-bs-toggle="dropdown"><i class="bi {{ $ikon }} me-1"></i>{{ $ad }}</a>
              <ul class="dropdown-menu">
                @foreach($alt as [$altAd, $altIkon, $url, $desen])
                  <li><a class="dropdown-item {{ $aktifMi($desen) ? 'active' : '' }}" href="{{ $url }}"><i class="bi {{ $altIkon }}"></i>{{ $altAd }}</a></li>
                @endforeach
              </ul>
            </li>
          @endif
        @endforeach
      @else
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.*') ? 'aktif' : '' }}" href="{{ route('admin.tenants.index') }}"><i class="bi bi-people me-1"></i>Müşteriler</a></li>
      @endif
    </ul>
    <ul class="navbar-nav align-items-lg-center">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i>{{ $u->name }}
          @if($u->tenant)<span class="firma d-block d-lg-inline ms-lg-2">{{ $u->tenant->unvan }}</span>@endif
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          @if($menu)<li><a class="dropdown-item {{ request()->routeIs('ayarlar.its') ? 'active' : '' }}" href="{{ route('ayarlar.its') }}"><i class="bi bi-gear"></i>İTS Ayarları</a></li>@endif
          <li><a class="dropdown-item" href="{{ route('sifre') }}"><i class="bi bi-key"></i>Şifre Değiştir</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right"></i>Çıkış Yap</button></form></li>
        </ul>
      </li>
    </ul>
  </div>
  @endauth
</nav>
<main class="container-xl py-4">
  @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="alert alert-danger">{{ session('hata') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  @yield('icerik')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
