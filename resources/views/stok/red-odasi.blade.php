@extends('layouts.app')
@section('baslik', $oda)
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h4 class="mb-0"><i class="bi {{ $karantina ? 'bi-shield-exclamation' : 'bi-archive' }} me-1"></i>{{ $oda }}</h4>
  <a class="btn btn-success" href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
</div>
@if($karantina)
<div class="alert alert-warning small">Karantinaya aldığınız ürünler burada listelenir. Ürün eklemek için <a href="{{ route('stok.liste') }}">Stok Listesi</a>'nde ürünleri seçip <b>Karantinaya Al</b> düğmesine basın. Bu işlem yalnızca yerel kayıttır, İTS'e bildirilmez. Karantinadan çıkarmak için ürüne başka bir lokasyon atayın.</div>
@else
<div class="alert alert-info small">Son kullanma tarihi geçmiş, stokta görünen ürünler otomatik olarak <b>Red Odası</b> lokasyonuna alınır. Fiziksel olarak da bu ürünleri ayırın. Ürünleri İTS'ten düşmek için <a href="{{ route('islem', 'deaktive') }}">Deaktive</a> (sebep: Miat) ekranını kullanın.</div>
@endif
@unless($hazir)<div class="alert alert-warning">Lokasyon özelliği henüz etkinleştirilmedi (veritabanı güncellemesi bekleniyor).</div>@endunless
<div class="d-flex gap-3 mb-2 small text-muted"><span>Ürün çeşidi: <strong>{{ $urunler->count() }}</strong></span><span>Toplam adet: <strong>{{ $toplam }}</strong></span></div>
<div class="table-responsive"><table class="table table-sm bg-white align-middle">
<thead><tr><th>GTIN</th><th>İlaç</th><th class="text-end">Adet</th><th>Parti / SKT / Adet</th><th></th></tr></thead><tbody>
@forelse($urunler as $u)
<tr><td class="font-monospace">{{ $u->gtin }}</td><td>{{ $u->ad ?? '—' }}</td><td class="text-end fw-semibold">{{ $u->adet }}</td>
<td class="small">@foreach($u->partiler as $p)<div><span class="font-monospace">{{ $p->bn ?: '—' }}</span> · {{ $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '—' }} · <b>{{ $p->adet }}</b> adet</div>@endforeach</td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('stok.liste', ['ara' => $u->gtin, 'lokasyon' => $oda]) }}"><i class="bi bi-upc-scan me-1"></i>Karekodlar</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">{{ $karantina ? 'Karantina Odası boş.' : 'Red Odası boş: SKT\'si geçmiş ürün yok.' }}</td></tr>@endforelse
</tbody></table></div>
@endsection
