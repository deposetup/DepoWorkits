@extends('layouts.app')
@section('baslik', 'Stok')
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
  <h4 class="mb-0">Stok</h4>
  <div class="d-flex align-items-center gap-3">
    <span class="badge text-bg-primary fs-6">Kalem: {{ number_format($kalem, 0, ',', '.') }}</span>
    <span class="badge text-bg-success fs-6">Toplam kutu: {{ number_format($kutu, 0, ',', '.') }}</span>
    @if($miadGecen > 0)<span class="badge text-bg-danger fs-6"><i class="bi bi-exclamation-triangle-fill me-1"></i>SKT geçen (Red Odası): {{ number_format($miadGecen, 0, ',', '.') }}</span>@endif
    <a href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel'e Aktar</a>
  </div>
</div>
<form class="mb-3 d-flex gap-2" method="get"><input type="hidden" name="sirala" value="{{ $sirala }}"><input type="hidden" name="yon" value="{{ $yon }}"><input name="ara" value="{{ $ara }}" class="form-control w-auto" placeholder="GTIN ara"><button class="btn btn-outline-secondary">Ara</button></form>
@if($miadGecen > 0)<div class="alert alert-warning small py-2"><i class="bi bi-exclamation-triangle me-1"></i>Adet sütunundaki sayıya <b>son kullanma tarihi geçmiş</b> ürünler de dahildir. Bunlar <a href="{{ route('stok.red-odasi') }}">Red Odası</a>'ndadır ve İTS canlı sistemde stok olarak görünmeyebilir. Satırlardaki <span class="badge text-bg-danger"><i class="bi bi-exclamation-triangle-fill"></i> Red Odası</span> işaretinin üzerine gelerek kaç adedin etkilendiğini görebilirsiniz.</div>@endif
@php
  $baslik = function (string $alan, string $ad, string $sinif = '') use ($sirala, $yon, $ara) {
      $aktif = $sirala === $alan;
      $url = request()->fullUrlWithQuery(['sirala' => $alan, 'yon' => $aktif && $yon === 'asc' ? 'desc' : 'asc']);
      $ok = $aktif ? ($yon === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up text-muted';
      return '<th class="'.$sinif.'"><a href="'.e($url).'" class="text-decoration-none text-dark text-nowrap">'.e($ad).' <i class="bi '.$ok.'"></i></a></th>';
  };
@endphp
<table class="table table-sm bg-white"><thead><tr>{!! $baslik('gtin', 'GTIN') !!}{!! $baslik('ad', 'İlaç') !!}{!! $baslik('recete', 'Reçete') !!}{!! $baslik('adet', 'Adet', 'text-end') !!}{!! $baslik('skt', 'En Yakın SKT') !!}</tr></thead><tbody>
@forelse($satirlar as $s)
<tr><td class="font-monospace">{{ $s->gtin }}</td><td>{{ $adlar[$s->gtin] ?? '—' }}</td><td>{!! \App\Models\IlacRecete::rozet($receteler[$s->gtin] ?? null, true) !!}</td><td class="text-end text-nowrap">@if($s->miad_gecen > 0)<span class="badge text-bg-danger me-1 miad-ipucu" role="img" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" title="Bu ürünün {{ $s->miad_gecen }} adedinin son kullanma tarihi geçmiş ve Red Odası'nda.{{ $s->miad_gecen < $s->adet ? ' Geçerli stok: '.($s->adet - $s->miad_gecen).' adet.' : ' Tamamı SKT\'si geçmiş.' }} İTS canlı sistemde bu adetler görünmeyebilir."><i class="bi bi-exclamation-triangle-fill"></i> {{ $s->miad_gecen }}</span>@endif{{ $s->adet }}</td><td>{{ $s->en_yakin_skt ? \Illuminate\Support\Carbon::parse($s->en_yakin_skt)->format('d.m.Y') : '—' }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">Stokta ürün yok. Mal Kabul ekranından ürün alın.</td></tr>@endforelse
</tbody></table>
<script>document.querySelectorAll('.miad-ipucu').forEach(function (e) { new bootstrap.Tooltip(e); });</script>
@endsection
