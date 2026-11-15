@extends('layouts.app')
@section('baslik', 'Stok')
@section('icerik')
<h4>Stok</h4>
<form class="mb-3 d-flex gap-2" method="get"><input name="ara" value="{{ $ara }}" class="form-control w-auto" placeholder="GTIN ara"><button class="btn btn-outline-secondary">Ara</button></form>
<table class="table table-sm bg-white"><thead><tr><th>GTIN</th><th>İlaç</th><th class="text-end">Adet</th><th>En Yakın SKT</th></tr></thead><tbody>
@forelse($satirlar as $s)
<tr><td class="font-monospace">{{ $s->gtin }}</td><td>{{ $adlar[$s->gtin] ?? '—' }}</td><td class="text-end">{{ $s->adet }}</td><td>{{ $s->en_yakin_skt ? \Illuminate\Support\Carbon::parse($s->en_yakin_skt)->format('d.m.Y') : '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-muted">Stokta ürün yok. Mal Kabul ekranından ürün alın.</td></tr>@endforelse
</tbody></table>
@endsection
