@extends('layouts.app')
@section('baslik', 'Durum Sorgulama')
@section('icerik')
<h4>Ürün Durum Sorgulama @if($baslik)<small class="text-muted">{{ $baslik }}</small>@endif</h4>
<p class="text-muted small">İTS'te ürünün kayıtlı olup olmadığını, son kullanma tarihini ve son sahibini sorgular. Veri değiştirmez.</p>
<form method="post" action="{{ route('durum.sorgula') }}" class="mb-4">@csrf
  <label class="form-label">Karekodlar (her satıra bir ürün)</label>
  <textarea name="kodlar" rows="5" class="form-control font-monospace">{{ old('kodlar') }}</textarea>
  <button class="btn btn-primary mt-2">Sorgula</button>
</form>
@foreach($hatalar as $h)<div class="alert alert-warning py-2">{{ $h }}</div>@endforeach
@if(is_array($sonuc))
@php($ozet = collect($sonuc)->groupBy('uc')->map->count())
<div class="mb-2 small">@foreach($ozet as $uc => $n)<span class="badge text-bg-{{ $uc === '00000' ? 'success' : 'secondary' }} me-1">{{ $uc ?: '—' }}: {{ $n }}</span>@endforeach</div>
<table class="table table-sm bg-white"><thead><tr><th>GTIN</th><th>Seri</th><th>Durum</th><th>Son Sahip (GLN1)</th><th>GLN2</th></tr></thead><tbody>
@forelse(array_slice($sonuc, 0, 300) as $r)
<tr class="{{ $r['uc'] === '00000' || $r['uc'] === '60014' ? '' : 'table-warning' }}"><td class="font-monospace">{{ $r['gtin'] }}</td><td class="font-monospace small">{{ $r['sn'] }}</td>
<td>{{ $r['aciklama'] }} <small class="text-muted">({{ $r['uc'] }})</small></td><td>{{ $r['gln1'] ?? '—' }}</td><td>{{ $r['gln2'] ?? '—' }}</td></tr>
@empty<tr><td colspan="5" class="text-muted">Sonuç dönmedi.</td></tr>@endforelse
</tbody></table>@if(count($sonuc) > 300)<div class="small text-muted">İlk 300 satır gösteriliyor.</div>@endif
@endif
@endsection
