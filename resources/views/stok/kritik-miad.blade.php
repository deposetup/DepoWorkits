@extends('layouts.app')
@section('baslik', 'Kritik / Miad')
@section('icerik')
<div class="row g-4">
<div class="col-lg-6"><h5>Kritik Stok</h5>
<table class="table table-sm bg-white"><thead><tr><th>İlaç</th><th class="text-end">Stok</th><th class="text-end">Eşik</th></tr></thead><tbody>
@forelse($kritik as $k)<tr class="table-warning"><td>{{ $k['ad'] }}<div class="small text-muted font-monospace">{{ $k['gtin'] }}</div></td><td class="text-end">{{ $k['adet'] }}</td><td class="text-end">{{ $k['esik'] }}</td></tr>
@empty<tr><td colspan="3" class="text-muted">Kritik seviyede ilaç yok.</td></tr>@endforelse</tbody></table></div>
<div class="col-lg-6">
<h5 class="text-danger">Süresi Geçenler <span class="badge text-bg-danger">{{ number_format($gecenAdet, 0, ',', '.') }} adet</span></h5>
<p class="small text-muted mb-1">SKT'si geçmiş, stokta görünen ürünler Red Odası'na alınır. <a href="{{ route('stok.red-odasi') }}">Red Odası'nı aç</a></p>
<table class="table table-sm bg-white"><thead><tr><th>İlaç</th><th>Parti</th><th>SKT</th><th class="text-end">Adet</th><th class="text-end">Geçen Gün</th></tr></thead><tbody>
@forelse(array_slice($gecen, 0, 200) as $m)<tr class="table-danger"><td>{{ $m['ad'] ?? '—' }}<div class="small text-muted font-monospace">{{ $m['gtin'] }}</div></td><td class="font-monospace small">{{ $m['bn'] ?: '—' }}</td><td>{{ $m['skt']->format('d.m.Y') }}</td><td class="text-end">{{ $m['adet'] }}</td><td class="text-end">{{ abs($m['kalan']) }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">Süresi geçmiş ürün yok.</td></tr>@endforelse</tbody></table>
@if(count($gecen) > 200)<div class="small text-muted mb-3">İlk 200 parti gösteriliyor ({{ count($gecen) }} parti). Tamamı için <a href="{{ route('stok.red-odasi') }}">Red Odası</a> ya da Excel çıktısı.</div>@endif
<h5 class="mt-3">Miadı Yaklaşan <span class="badge text-bg-warning">{{ number_format($yaklasanAdet, 0, ',', '.') }} adet</span></h5>
<table class="table table-sm bg-white"><thead><tr><th>İlaç</th><th>Parti</th><th>SKT</th><th class="text-end">Adet</th><th class="text-end">Kalan Gün</th></tr></thead><tbody>
@forelse(array_slice($miad, 0, 200) as $m)<tr class="table-warning"><td>{{ $m['ad'] ?? '—' }}<div class="small text-muted font-monospace">{{ $m['gtin'] }}</div></td><td class="font-monospace small">{{ $m['bn'] ?: '—' }}</td><td>{{ $m['skt']->format('d.m.Y') }}</td><td class="text-end">{{ $m['adet'] }}</td><td class="text-end">{{ $m['kalan'] }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">Miadı yaklaşan ürün yok.</td></tr>@endforelse</tbody></table>
@if(count($miad) > 200)<div class="small text-muted">İlk 200 parti gösteriliyor ({{ count($miad) }} parti).</div>@endif
</div>
</div>
<h5 class="mt-4">Eşik Ayarı</h5>
<form method="post" action="{{ route('kritik-miad.kaydet') }}" class="row g-2 align-items-end">@csrf
  <div class="col-md-5"><label class="form-label">İlaç</label><select name="gtin" class="form-select">@foreach($adlar as $g => $ad)<option value="{{ $g }}">{{ $ad }} ({{ $g }})</option>@endforeach</select></div>
  <div class="col-md-2"><label class="form-label">Kritik stok</label><input type="number" name="kritik_stok" min="0" value="0" class="form-control"></div>
  <div class="col-md-2"><label class="form-label">Miad uyarı (gün)</label><input type="number" name="miad_gun" min="1" value="90" class="form-control"></div>
  <div class="col-md-2"><button class="btn btn-primary">Kaydet</button></div>
</form>
@endsection
