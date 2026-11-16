@extends('layouts.app')
@section('baslik', 'Analiz')
@section('icerik')
<h4>Analiz (son 30 gün)</h4>
<div class="row g-3 mb-4">
@foreach($gorunen as $k => $t)
<div class="col-6 col-md-2"><div class="card"><div class="card-body"><div class="text-muted small">{{ $t[2] }}</div><div class="fs-3">{{ $ozet[$k] ?? 0 }}</div></div></div></div>
@endforeach
</div>
<h5>Hareketler</h5>
<form method="get" class="mb-2"><select name="tur" class="form-select w-auto d-inline" onchange="this.form.submit()"><option value="">Tüm türler</option>@foreach($gorunen as $k => $t)<option value="{{ $k }}" @selected(request('tur')===$k)>{{ $t[2] }}</option>@endforeach</select></form>
<table class="table table-sm bg-white"><thead><tr><th>Tarih</th><th>Tür</th><th>GTIN</th><th>Seri</th><th>Bildirim</th><th>Durum</th></tr></thead><tbody>
@forelse($hareketler as $h)<tr><td>{{ $h->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</td><td>{{ $turler[$h->tur][2] ?? $h->tur }}</td><td class="font-monospace">{{ $h->gtin }}</td><td class="font-monospace small">{{ $h->sn }}</td><td class="small">{{ $h->bildirim_id }}</td><td>{!! $h->basarili ? '<span class="badge text-bg-success">Başarılı</span>' : '<span class="badge text-bg-danger" title="'.e($h->hata).'">Hata</span>' !!}</td></tr>
@empty<tr><td colspan="6" class="text-muted">Hareket yok.</td></tr>@endforelse</tbody></table>
{{ $hareketler->links() }}
@endsection
