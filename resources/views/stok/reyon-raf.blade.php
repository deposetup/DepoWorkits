@extends('layouts.app')
@section('baslik', 'Reyon - Raf')
@section('icerik')
<h4><i class="bi bi-grid-3x3-gap me-1"></i>Reyon - Raf</h4>
<div class="alert alert-secondary small">Stoktaki ürünlerin lokasyonlara göre dağılımı. Lokasyon atamak için <a href="{{ route('stok.liste') }}">Stok Listesi</a>'nde ürünleri seçip <b>Lokasyon Ata</b> kullanın. Özel odalar: <a href="{{ route('stok.red-odasi') }}">Red Odası</a> (SKT'si geçenler otomatik), <a href="{{ route('stok.karantina') }}">Karantina Odası</a>.</div>
@unless($hazir)<div class="alert alert-warning">Lokasyon özelliği henüz etkinleştirilmedi (veritabanı güncellemesi bekleniyor).</div>@endunless
<div class="table-responsive"><table class="table table-sm bg-white align-middle">
<thead><tr><th>Lokasyon</th><th class="text-end">Ürün çeşidi</th><th class="text-end">Adet</th><th></th></tr></thead><tbody>
@forelse($satirlar as $s)
<tr><td>@if($s->lokasyon === \App\Models\StokKalemi::RED_ODASI)<span class="badge text-bg-danger">{{ $s->lokasyon }}</span>@elseif($s->lokasyon === \App\Models\StokKalemi::KARANTINA_ODASI)<span class="badge text-bg-warning">{{ $s->lokasyon }}</span>@else{{ $s->lokasyon ?: 'Lokasyonsuz' }}@endif</td>
<td class="text-end">{{ $s->cesit }}</td><td class="text-end fw-semibold">{{ $s->adet }}</td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('stok.liste', ['lokasyon' => $s->lokasyon ?: '-']) }}"><i class="bi bi-upc-scan me-1"></i>Ürünler</a></td></tr>
@empty<tr><td colspan="4" class="text-muted">Stokta ürün yok.</td></tr>@endforelse
</tbody></table></div>
@endsection
