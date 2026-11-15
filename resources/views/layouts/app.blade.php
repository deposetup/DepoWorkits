<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('baslik', 'DepoWork ITS')</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --yan-genislik: 270px; --lacivert: #0b2447; --lacivert2: #19376d; --vurgu: #5fd0ff; }
  body { background: #f4f6fa; }
  .yan { width: var(--yan-genislik); background: linear-gradient(180deg, var(--lacivert), #0d2b57 60%, var(--lacivert2)); color: #fff; }
  @media (min-width: 992px) {
    .yan { position: fixed; inset: 0 auto 0 0; display: flex !important; flex-direction: column; z-index: 1030; box-shadow: 2px 0 12px rgba(0,0,0,.15); }
    .icerik-alan { margin-left: var(--yan-genislik); }
  }
  .yan .offcanvas-body { display: flex; flex-direction: column; padding: 0; overflow: hidden; }
  .yan-marka { display: flex; align-items: center; gap: .6rem; padding: 1.1rem 1.25rem; font-weight: 700; font-size: 1.15rem; color: #fff; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.08); }
  .yan-marka .nokta { color: var(--vurgu); }
  .yan-marka small { display: block; font-weight: 400; font-size: .7rem; color: #9fb3d9; letter-spacing: .04em; }
  .yan-menu { flex: 1; overflow-y: auto; padding: .75rem .65rem; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.2) transparent; }
  .yan-bolum { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: #8196c4; padding: .6rem .75rem .3rem; }
  .yan-link, .yan-grup { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .6rem .8rem; border: 0; background: transparent; color: rgba(255,255,255,.85); font-weight: 500; border-radius: .55rem; text-decoration: none; text-align: left; transition: background .2s, color .2s; }
  .yan-link:hover, .yan-grup:hover { background: rgba(255,255,255,.09); color: #fff; }
  .yan-link i, .yan-grup > i:first-child { font-size: 1.05rem; width: 1.2rem; text-align: center; color: var(--vurgu); }
  .yan-link.aktif, .yan-grup.aktif { background: rgba(95,208,255,.18); color: #fff; }
  .yan-grup .ok { margin-left: auto; font-size: .75rem; transition: transform .3s; }
  .yan-grup:not(.collapsed) .ok { transform: rotate(180deg); }
  .yan-alt { list-style: none; margin: .15rem 0 .35rem 1.15rem; padding: 0 0 0 .85rem; border-left: 1px solid rgba(255,255,255,.15); }
  .yan-alt .yan-link { font-size: .9rem; padding: .45rem .7rem; font-weight: 400; }
  .yan-alt .yan-link i { font-size: .9rem; color: #9fb3d9; }
  .yan-alt .yan-link.aktif { background: #fff; color: var(--lacivert); font-weight: 600; }
  .yan-alt .yan-link.aktif i { color: var(--lacivert2); }
  .yan-alt-sarmal.collapsing { transition: height .35s ease; }
  .yan-kullanici { border-top: 1px solid rgba(255,255,255,.1); padding: .75rem .65rem; background: rgba(0,0,0,.12); }
  .yan-kullanici .kim { padding: .25rem .8rem .6rem; font-size: .85rem; }
  .yan-kullanici .kim .firma { color: #9fb3d9; font-size: .75rem; display: block; }
  html, body { min-height: 100%; }
  .icerik-alan, .icerik-kap { min-height: 100vh; display: flex; flex-direction: column; }
  .icerik-alan > main, .icerik-kap > main { flex: 1 0 auto; width: 100%; }
  .alt-bilgi { border-top: 1px solid #e3e8f2; background: #fff; color: #6b7a99; font-size: .8rem; padding: .8rem 1rem; display: flex; flex-wrap: wrap; gap: .4rem 1.5rem; justify-content: space-between; }
  .ust-serit { background: #fff; border-bottom: 1px solid #e3e8f2; }
  main .card, main .table { box-shadow: 0 1px 3px rgba(0,0,0,.06); }
  main h4 { font-weight: 700; color: var(--lacivert); margin-bottom: 1rem; }
  .hizli { display: flex; gap: .9rem; align-items: center; padding: 1rem 1.1rem; background: #fff; border: 1px solid #e3e8f2; border-radius: .8rem; text-decoration: none; color: var(--lacivert); height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,.05); transition: transform .15s, box-shadow .15s, border-color .15s; }
  .hizli:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(11,36,71,.12); border-color: var(--vurgu); color: var(--lacivert); }
  .hizli .ikon { width: 2.8rem; height: 2.8rem; flex: none; display: grid; place-items: center; border-radius: .7rem; background: #e8f0ff; color: var(--lacivert2); font-size: 1.35rem; }
  .hizli small { display: block; color: #6b7a99; font-weight: 400; }
  .hizli strong { display: block; }
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
        ['PTS Sorgu', 'bi-search', route('pts'), ['pts', 'pts.paket', 'pts.excel', 'pts.yukle', 'pts.al', 'pts.mal-alim', 'pts.dogrula']],
        ['Mal Kabul', 'bi-check2-square', route('islem', 'kabul'), ['islem:kabul']],
      ]],
      ['Satış & Çıkış', 'bi-box-arrow-up', null, [
        ['Satış', 'bi-cart-check', route('islem', 'satis'), ['islem:satis']],
        ['Satış İptal', 'bi-cart-x', route('islem', 'satis_iptal'), ['islem:satis_iptal']],
        ['PTS Gönderimleri', 'bi-send', route('pts.gonderimler'), ['pts.gonderimler']],
        ...(($u->tenant?->devirYapabilir() ?? true) ? [
          ['Mal Devir', 'bi-arrow-left-right', route('islem', 'devir'), ['islem:devir']],
          ['Mal Devir İptal', 'bi-x-circle', route('islem', 'devir_iptal'), ['islem:devir_iptal']],
        ] : []),
        ...(($u->tenant?->sarfYapabilir() ?? true) ? [
          ['Hastane Sarf', 'bi-hospital', route('islem', 'sarf'), ['islem:sarf']],
          ['Sarf İptal', 'bi-arrow-counterclockwise', route('islem', 'sarf_iptal'), ['islem:sarf_iptal']],
        ] : []),
        ['Ürün İade', 'bi-arrow-return-left', route('islem', 'iade'), ['islem:iade']],
        ['Deaktive', 'bi-slash-circle', route('islem', 'deaktive'), ['islem:deaktive']],
        ['Deaktivasyon İptal', 'bi-check-circle', route('islem', 'deaktive_iptal'), ['islem:deaktive_iptal']],
      ]],
      ['Stok', 'bi-boxes', null, [
        ['Stok Özeti', 'bi-bar-chart-line', route('stok'), ['stok']],
        ['Barkod Bazlı Stok', 'bi-upc', route('stok.barkod'), ['stok.barkod']],
        ['Stok Listesi (Karekod)', 'bi-upc-scan', route('stok.liste'), ['stok.liste']],
        ['Stok İçe Aktar (İTS Doğrulamalı)', 'bi-cloud-download', route('stok.ice-aktar'), ['stok.ice-aktar*']],
        ['Kritik / Miad', 'bi-exclamation-triangle', route('kritik-miad'), ['kritik-miad']],
        ['Red Odası', 'bi-archive', route('stok.red-odasi'), ['stok.red-odasi']],
        ['Karekod Oluşturucu', 'bi-qr-code', route('karekod.olustur'), ['karekod.olustur']],
      ]],
      ['Sorgular', 'bi-patch-question', null, [
        ['Durum Sorgu', 'bi-shield-check', route('durum'), ['durum*']],
        ['Paydaşlar', 'bi-building', route('paydaslar'), ['paydaslar']],
      ]],
      ['Cari Kartlar', 'bi-person-lines-fill', 'cari.index', []],
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
@auth
<aside class="yan offcanvas-lg offcanvas-start" tabindex="-1" id="yanMenu" aria-label="Ana menü">
  <div class="offcanvas-body">
    <a class="yan-marka" href="{{ route('home') }}"><i class="bi bi-capsule-pill fs-3"></i><span>DepoWork <span class="nokta">İTS</span><small>İlaç Takip Sistemi Portalı</small></span></a>
    <nav class="yan-menu" id="yanAkordeon">
      @if($menu)
        @foreach($menu as $n => [$ad, $ikon, $rota, $alt])
          @if(! $alt)
            <a class="yan-link {{ request()->routeIs($rota, str_contains($rota, '.') ? explode('.', $rota)[0].'.*' : $rota) ? 'aktif' : '' }}" href="{{ route($rota) }}"><i class="bi {{ $ikon }}"></i>{{ $ad }}</a>
          @else
            @php $grupAktif = collect($alt)->contains(fn ($a) => $aktifMi($a[3])); @endphp
            <button class="yan-grup {{ $grupAktif ? 'aktif' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#grup{{ $n }}" aria-expanded="{{ $grupAktif ? 'true' : 'false' }}"><i class="bi {{ $ikon }}"></i>{{ $ad }}<i class="bi bi-chevron-down ok"></i></button>
            <div class="collapse yan-alt-sarmal {{ $grupAktif ? 'show' : '' }}" id="grup{{ $n }}" data-bs-parent="#yanAkordeon">
              <ul class="yan-alt">
                @foreach($alt as [$altAd, $altIkon, $url, $desen])
                  <li><a class="yan-link {{ $aktifMi($desen) ? 'aktif' : '' }}" href="{{ $url }}"><i class="bi {{ $altIkon }}"></i>{{ $altAd }}</a></li>
                @endforeach
              </ul>
            </div>
          @endif
        @endforeach
        <div class="yan-bolum">Hesap</div>
        <a class="yan-link {{ request()->routeIs('ayarlar.its') ? 'aktif' : '' }}" href="{{ route('ayarlar.its') }}"><i class="bi bi-plug"></i>İTS Bağlantısı</a>
      @else
        @php $yeniTalep = \Illuminate\Support\Facades\Schema::hasTable('destek_talepleri') ? \App\Models\DestekTalebi::where('durum', 'yeni')->count() : 0; @endphp
        <a class="yan-link {{ request()->routeIs('admin.tenants.*', 'admin.kullanici.*') ? 'aktif' : '' }}" href="{{ route('admin.tenants.index') }}"><i class="bi bi-people"></i>Müşteriler</a>
        <a class="yan-link {{ request()->routeIs('admin.talepler*') ? 'aktif' : '' }}" href="{{ route('admin.talepler') }}"><i class="bi bi-life-preserver"></i>Destek Talepleri @if($yeniTalep)<span class="badge text-bg-warning ms-auto">{{ $yeniTalep }}</span>@endif</a>
        <a class="yan-link {{ request()->routeIs('admin.paydas-degisimleri') ? 'aktif' : '' }}" href="{{ route('admin.paydas-degisimleri') }}"><i class="bi bi-bell"></i>Paydaş Değişimleri</a>
        <a class="yan-link {{ request()->routeIs('admin.giris-kayitlari') ? 'aktif' : '' }}" href="{{ route('admin.giris-kayitlari') }}"><i class="bi bi-clock-history"></i>Giriş Kayıtları</a>
      @endif
      <a class="yan-link {{ request()->routeIs('sifre') ? 'aktif' : '' }}" href="{{ route('sifre') }}"><i class="bi bi-key"></i>Şifre Değiştir</a>
    </nav>
    <div class="yan-kullanici">
      <div class="kim"><i class="bi bi-person-circle me-1"></i><strong>{{ $u->name }}</strong>@if($u->tenant)<span class="firma">{{ $u->tenant->unvan }}</span>@endif</div>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="yan-link"><i class="bi bi-box-arrow-right"></i>Çıkış Yap</button></form>
    </div>
  </div>
</aside>
<div class="icerik-alan">
@if(session('musteri_gozu_yonetici_id'))
<div class="bg-warning-subtle border-bottom border-warning py-2 px-3 d-flex flex-wrap gap-2 justify-content-between align-items-center" style="position:sticky;top:0;z-index:1040">
  <span><i class="bi bi-eye-fill me-1"></i><strong>Müşteri gözü:</strong> {{ auth()->user()->tenant?->unvan }} ekranını görüntülüyorsunuz. Salt okunur, hiçbir işlem yapılmaz.</span>
  <form method="post" action="{{ route('musteri-gozu.bitir') }}">@csrf<button class="btn btn-sm btn-dark"><i class="bi bi-box-arrow-left me-1"></i>Yönetici görünümüne dön</button></form>
</div>
@endif
  <div class="ust-serit d-lg-none d-flex align-items-center gap-2 px-3 py-2 sticky-top">
    <button class="btn btn-outline-dark btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#yanMenu" aria-controls="yanMenu" aria-label="Menüyü aç"><i class="bi bi-list fs-5"></i></button>
    <strong class="text-dark"><i class="bi bi-capsule-pill me-1"></i>DepoWork İTS</strong>
  </div>
@else
<div class="ust-serit px-3 py-3 text-center"><strong style="color:var(--lacivert)"><i class="bi bi-capsule-pill me-1"></i>DepoWork İTS</strong></div>
<div class="icerik-kap">
@endauth
<main class="container-xl py-4">
  @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="alert alert-danger">{{ session('hata') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  @yield('icerik')
</main>
<footer class="alt-bilgi">
  <span>&copy; {{ now()->year }} {{ config('its.firma') }}</span>
  <span>DepoWork İTS &middot; Sürüm {{ config('its.surum') }}</span>
</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
