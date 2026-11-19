@extends('layouts.app')
@section('baslik', 'Barkod Bazlı Stok')
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h4 class="mb-0">Barkod Bazlı Stok</h4>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer me-1"></i>Yazdır</button>
    <a class="btn btn-success" href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
  </div>
</div>
<style>
  .yazdir-alan, .yazdir-baslik, .yazdir-alt { display: none; }
  @media print {
    @page { size: A4; margin: 12mm 12mm 16mm; }
    body * { visibility: hidden !important; }
    #yazdirAlan, #yazdirAlan * { visibility: visible !important; }
    #yazdirAlan { display: block; position: absolute; left: 0; top: 0; width: 100%; }
    .yazdir-baslik { display: block; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
    .yazdir-baslik .musteri { font-size: 15px; font-weight: 700; }
    .yazdir-baslik .detay { font-size: 11px; color: #333; }
    .yazdir-baslik .oda { font-size: 13px; font-weight: 600; margin-top: 6px; }
    .yazdir-alt { display: block; position: fixed; left: 0; right: 0; bottom: 0; text-align: center; font-size: 10px; color: #555; border-top: 1px solid #999; padding-top: 4px; }
    #yazdirAlan table { table-layout: fixed; width: 100%; font-size: 11px; border-collapse: collapse; }
    #yazdirAlan th, #yazdirAlan td { border: 1px solid #999 !important; padding: 4px 6px !important; vertical-align: top; word-wrap: break-word; }
    #yazdirAlan thead { display: table-header-group; }
    #yazdirAlan tr { break-inside: avoid; }
    #yazdirAlan .col-gtin { width: 16%; } #yazdirAlan .col-ilac { width: 32%; } #yazdirAlan .col-recete { width: 12%; } #yazdirAlan .col-adet { width: 8%; } #yazdirAlan .col-parti { width: 32%; }
    #yazdirAlan .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
<div class="alert alert-info small">Bu liste DepoWork İTS üzerinden yapılan bildirimlere ve içe aktarılan ürünlere göre hesaplanır; Bakanlık portalındaki stok ekranının birebir kopyası değildir. Sistem dışında girilmiş ürünler için <a href="{{ route('stok.ice-aktar') }}">Stok İçe Aktar</a> ekranını kullanın.</div>
<form class="mb-3 d-flex gap-2" method="get">
  <input type="hidden" name="sirala" value="{{ $sirala }}"><input type="hidden" name="yon" value="{{ $yon }}">
  <input name="ara" value="{{ $ara }}" class="form-control w-auto" style="min-width:18rem" placeholder="GTIN veya ilaç adı ara">
  <button class="btn btn-outline-secondary">Ara</button>
  @if($ara !== '')<a class="btn btn-link" href="{{ route('stok.barkod') }}">Temizle</a>@endif
</form>
<div class="d-flex gap-3 mb-2 small text-muted"><span>Ürün çeşidi: <strong>{{ $urunler->count() }}</strong></span><span>Toplam adet: <strong>{{ $toplam }}</strong></span></div>
<div class="table-responsive"><table class="table table-sm bg-white align-middle">
@php
  $baslik = function (string $alan, string $ad, string $sinif = '') use ($sirala, $yon) {
      $aktif = $sirala === $alan;
      $url = request()->fullUrlWithQuery(['sirala' => $alan, 'yon' => $aktif && $yon === 'asc' ? 'desc' : 'asc']);
      $ok = $aktif ? ($yon === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted';
      return '<th class="'.$sinif.'"><a href="'.e($url).'" class="text-decoration-none text-dark text-nowrap">'.e($ad).' <i class="bi '.$ok.'"></i></a></th>';
  };
@endphp
<thead><tr>{!! $baslik('gtin', 'GTIN') !!}{!! $baslik('ad', 'İlaç') !!}{!! $baslik('adet', 'Adet', 'text-end') !!}<th>Parti</th>{!! $baslik('skt', 'En Yakın SKT') !!}<th></th></tr></thead><tbody>
@forelse($urunler as $i => $u)
@php
  $gun = $u->en_yakin_skt ? (int) now()->startOfDay()->diffInDays($u->en_yakin_skt->startOfDay(), false) : null;
  $rozet = $gun === null ? null : ($gun < 0 ? 'danger' : ($gun <= 90 ? 'warning' : null));
@endphp
<tr>
  <td class="font-monospace">{{ $u->gtin }}</td>
  <td>{{ $u->ad ?? '—' }}</td>
  <td class="text-end fw-semibold">{{ $u->adet }}</td>
  <td>{{ $u->partiler->count() }}</td>
  <td>{{ $u->en_yakin_skt?->format('d.m.Y') ?? '—' }} @if($rozet)<span class="badge text-bg-{{ $rozet }}">{{ $gun < 0 ? 'SKT geçti' : $gun.' gün' }}</span>@endif</td>
  <td class="text-end text-nowrap">
    <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#p{{ $i }}"><i class="bi bi-list-ul me-1"></i>Partiler</button>
    <a class="btn btn-sm btn-outline-primary" href="{{ route('stok.liste', ['ara' => $u->gtin]) }}"><i class="bi bi-upc-scan me-1"></i>Karekodlar</a>
  </td>
</tr>
<tr class="collapse" id="p{{ $i }}"><td colspan="6" class="bg-light p-0">
  <table class="table table-sm mb-0 w-auto ms-4 my-2"><thead><tr><th>Parti</th><th>SKT</th><th class="text-end">Adet</th></tr></thead><tbody>
  @foreach($u->partiler as $p)
    <tr><td class="font-monospace">{{ $p->bn ?: '—' }}</td><td>{{ $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '—' }}</td><td class="text-end">{{ $p->adet }}</td></tr>
  @endforeach
  </tbody></table>
</td></tr>
@empty
<tr><td colspan="6" class="text-muted">Stokta ürün yok. Mal Kabul veya Stok İçe Aktar ekranından ürün alın.</td></tr>
@endforelse
</tbody></table></div>

@php $t = auth()->user()->tenant; @endphp
<div id="yazdirAlan" class="yazdir-alan">
<div class="yazdir-baslik">
  <div class="musteri">{{ $t?->unvan }}</div>
  <div class="detay">@if($t?->gln)GLN: {{ $t->gln }}@endif @if($t?->il || $t?->ilce) · {{ collect([$t->ilce, $t->il])->filter()->implode(' / ') }}@endif @if($t?->telefon) · Tel: {{ $t->telefon }}@endif</div>
  <div class="oda">Barkod Bazlı Stok{{ '' }}@if($ara !== '') · Arama: {{ $ara }}@endif · {{ now()->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</div>
</div>
<div class="mb-2 small">Ürün çeşidi: <strong>{{ $urunler->count() }}</strong> · Toplam adet: <strong>{{ $toplam }}</strong></div>
<table>
<thead><tr><th class="col-gtin">GTIN</th><th class="col-ilac">İlaç</th><th class="col-recete">Reçete</th><th class="col-adet text-end">Adet</th><th class="col-parti">Parti / SKT</th></tr></thead><tbody>
@forelse($urunler as $u)
<tr><td class="font-monospace">{{ $u->gtin }}</td><td>{{ $u->ad ?? '—' }}</td><td>{!! \App\Models\IlacRecete::rozet($receteler[$u->gtin] ?? null, true) ?: '—' !!}</td><td class="text-end fw-semibold">{{ $u->adet }}</td>
<td class="small">@foreach($u->partiler as $p)<div><span class="font-monospace">{{ $p->bn ?: '—' }}</span> · {{ $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '—' }}</div>@endforeach</td></tr>
@empty<tr><td colspan="5">Stokta ürün yok.</td></tr>@endforelse
</tbody></table>
<div class="yazdir-alt">Bu çıktı its.depowork.com.tr aracılığı ile hazırlanmıştır.</div>
</div>
@endsection
