@extends('layouts.app')
@section('baslik', 'PTS Gönderimleri')
@section('icerik')
<h4>PTS Gönderimleri</h4>
<p class="text-muted small">Satış bildiriminiz tamamen başarılı olduktan sonra alıcıya gönderilen (ya da onayınızı bekleyen) PTS paketleri. PTS göndermek, İTS Satış bildiriminin yerine geçmez; Satış bildirimi ayrıca yapılır. Daha önce gönderilmiş bir paketi, aynı alıcıya <b>yeniden oluşturup</b> gönderebilirsiniz; her seferinde yeni bir Transfer No alırsınız.</p>
@unless($ptsAcik)<div class="alert alert-warning small">PTS paket gönderimi bu ortamda henüz etkinleştirilmedi; gönderim yapılamaz.</div>@endunless
<form method="get" class="row g-2 mb-3">
  <div class="col-md-4"><input name="ara" value="{{ $f['ara'] }}" class="form-control form-control-sm" placeholder="Alıcı GLN, alıcı adı ya da Transfer No"></div>
  <div class="col-md-3"><select name="durum" class="form-select form-select-sm"><option value="">Tüm durumlar</option>@foreach(['bekliyor' => 'Onay bekliyor', 'gonderildi' => 'Gönderildi', 'hata' => 'Hata', 'vazgecildi' => 'İptal'] as $k => $ad)<option value="{{ $k }}" @selected($f['durum'] === $k)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-3"><button class="btn btn-sm btn-primary">Ara</button> <a href="{{ route('pts.gonderimler') }}" class="btn btn-sm btn-outline-secondary">Sıfırla</a></div>
</form>
<table class="table table-sm align-middle bg-white">
<thead><tr><th>Tarih</th><th>Alıcı</th><th>Ürün</th><th>Satış Bildirim No</th><th>Belge No</th><th>Durum</th><th>PTS Transfer No</th><th></th></tr></thead>
<tbody>
@forelse($kayitlar as $k)
@php $rozet = ['bekliyor' => 'warning', 'gonderiliyor' => 'info', 'gonderildi' => 'success', 'hata' => 'danger', 'vazgecildi' => 'secondary'][$k->durum] ?? 'secondary'; $ad = ['bekliyor' => 'Onay bekliyor', 'gonderiliyor' => 'Gönderiliyor', 'gonderildi' => 'Gönderildi', 'hata' => 'Hata', 'vazgecildi' => 'İptal'][$k->durum] ?? $k->durum; @endphp
<tr class="{{ (int) $yeniId === $k->id ? 'table-primary' : '' }}">
<td class="text-nowrap">{{ $k->created_at->format('d.m.Y H:i') }}@if($k->kaynak_id)<div class="small text-muted">#{{ $k->kaynak_id }} yeniden</div>@endif</td>
<td><span class="font-monospace">{{ $k->alici_gln }}</span>@if($k->alici_unvan)<div class="small text-muted">{{ $k->alici_unvan }}</div>@endif</td>
<td>{{ $k->adet }}</td><td>{{ $k->bildirim_id ?? '—' }}</td><td>{{ $k->belge_no ?? '—' }}</td>
<td><span class="badge text-bg-{{ $rozet }}">{{ $ad }}</span>@if($k->hata)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($k->hata, 120) }}</div>@endif</td>
<td>@if($k->transfer_id)<strong class="font-monospace">{{ $k->transfer_id }}</strong>@if((int) $yeniId === $k->id)<span class="badge text-bg-primary ms-1">Yeni</span>@endif @else — @endif</td>
<td class="text-end">
@if($k->durum === 'bekliyor')
<form method="post" action="{{ route('pts.gonder', $k) }}" class="d-inline-flex gap-2 align-items-center">@csrf
<div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="onay" value="1" required id="o{{ $k->id }}"><label class="form-check-label small" for="o{{ $k->id }}">Onaylıyorum</label></div>
<button class="btn btn-sm btn-primary" @disabled(! $ptsAcik)>Gönder</button></form>
<form method="post" action="{{ route('pts.vazgec', $k) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Vazgeç</button></form>
@elseif($k->adet > 0 && $k->durum !== 'gonderiliyor')
<details class="d-inline-block text-start"><summary class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Yeniden Oluştur</summary>
<form method="post" action="{{ route('pts.tekrar', $k) }}" class="border rounded p-2 mt-1 bg-light" style="min-width:250px">@csrf
<div class="small mb-1">Aynı {{ $k->adet }} ürün, <b class="font-monospace">{{ $k->alici_gln }}</b> alıcısına yeni PTS paketi olarak gönderilir.</div>
<input name="belge_no" maxlength="30" class="form-control form-control-sm mb-1" placeholder="İrsaliye / belge no (isteğe bağlı)">
<div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="onay" value="1" required id="t{{ $k->id }}"><label class="form-check-label small" for="t{{ $k->id }}">Kontrol ettim, onaylıyorum</label></div>
<button class="btn btn-sm btn-primary w-100" @disabled(! $ptsAcik)><i class="bi bi-send me-1"></i>Oluştur ve Gönder</button></form></details>
@endif</td></tr>
@empty<tr><td colspan="8" class="text-muted">Kayıt yok.</td></tr>@endforelse
</tbody></table>
@endsection
