@extends('layouts.app')
@section('baslik', 'PTS Gönderimleri')
@section('icerik')
<h4>PTS Gönderimleri</h4>
<p class="text-muted small">Satış bildiriminiz tamamen başarılı olduktan sonra alıcıya gönderilen (ya da onayınızı bekleyen) PTS paketleri. PTS göndermek, İTS Satış bildiriminin yerine geçmez; Satış bildirimi ayrıca yapılır.</p>
@unless($ptsAcik)<div class="alert alert-warning small">PTS paket gönderimi bu ortamda henüz etkinleştirilmedi; bekleyen kayıtlar gönderilemez.</div>@endunless
<table class="table table-sm align-middle">
<thead><tr><th>Tarih</th><th>Alıcı GLN</th><th>Ürün</th><th>Satış Bildirim No</th><th>Durum</th><th>Transfer No</th><th></th></tr></thead>
<tbody>
@forelse($kayitlar as $k)
@php $rozet = ['bekliyor' => 'warning', 'gonderiliyor' => 'info', 'gonderildi' => 'success', 'hata' => 'danger', 'vazgecildi' => 'secondary'][$k->durum] ?? 'secondary'; $ad = ['bekliyor' => 'Onay bekliyor', 'gonderiliyor' => 'Gönderiliyor', 'gonderildi' => 'Gönderildi', 'hata' => 'Hata', 'vazgecildi' => 'İptal'][$k->durum] ?? $k->durum; @endphp
<tr>
<td>{{ $k->created_at->format('d.m.Y H:i') }}</td><td class="font-monospace">{{ $k->alici_gln }}</td><td>{{ $k->adet }}</td><td>{{ $k->bildirim_id ?? '—' }}</td>
<td><span class="badge text-bg-{{ $rozet }}">{{ $ad }}</span>@if($k->hata)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($k->hata, 120) }}</div>@endif</td>
<td>{{ $k->transfer_id ?? '—' }}</td>
<td class="text-end">@if($k->durum === 'bekliyor')
<form method="post" action="{{ route('pts.gonder', $k) }}" class="d-inline-flex gap-2 align-items-center">@csrf
<div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="onay" value="1" required id="o{{ $k->id }}"><label class="form-check-label small" for="o{{ $k->id }}">Onaylıyorum</label></div>
<button class="btn btn-sm btn-primary" @disabled(! $ptsAcik)>Gönder</button></form>
<form method="post" action="{{ route('pts.vazgec', $k) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Vazgeç</button></form>
@endif</td></tr>
@empty<tr><td colspan="7" class="text-muted">Kayıt yok.</td></tr>@endforelse
</tbody></table>
@endsection
