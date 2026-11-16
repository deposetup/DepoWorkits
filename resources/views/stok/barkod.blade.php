@extends('layouts.app')
@section('baslik', 'Barkod Bazlı Stok')
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h4 class="mb-0">Barkod Bazlı Stok</h4>
  <a class="btn btn-success" href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
</div>
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
@endsection
