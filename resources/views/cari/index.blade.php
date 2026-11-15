@extends('layouts.app')
@section('baslik', 'Cari Kartları')
@section('icerik')
<h4>Cari Kartları</h4>
<div class="alert alert-secondary small">Sık işlem yaptığınız karşı paydaşlar (alıcı, tedarikçi). Cariler <a href="{{ route('paydaslar') }}">Paydaşlar</a> ekranından <b>Cari Ekle</b> ile eklenir; satış, devir ve iade ekranlarında GLN yazmak yerine cari adını arayarak seçersiniz. Durum bilgisi, paydaşın cariye eklendiği andaki İTS durumudur; güncel durum için Paydaşlar ekranına bakın.</div>
<form method="get" class="row g-2 mb-3"><div class="col-md-5"><input name="ara" value="{{ $ara }}" class="form-control form-control-sm" placeholder="Cari adı, GLN ya da il ara"></div>
<div class="col-auto"><button class="btn btn-sm btn-primary">Ara</button> <a href="{{ route('cari.index') }}" class="btn btn-sm btn-outline-secondary">Sıfırla</a> <a href="{{ route('paydaslar') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Paydaşlardan Cari Ekle</a></div></form>
<table class="table table-sm bg-white align-middle">
<thead><tr><th>Cari Adı</th><th>GLN</th><th>Tip</th><th>İl / İlçe</th><th>Telefon</th><th>Paydaş Durumu</th><th>Eklenme</th><th></th></tr></thead><tbody>
@forelse($cariler as $c)
<tr><td>{{ $c->unvan }}</td><td class="font-monospace">{{ $c->gln }}</td><td>{{ $tipler[$c->tip] ?? '—' }}</td><td>{{ $c->il }} {{ $c->ilce ? '/ '.$c->ilce : '' }}</td><td>{{ $c->telefon ?: '—' }}</td>
<td>@if($c->aktif)<span class="badge text-bg-success">Aktif</span>@else<span class="badge text-bg-secondary">Pasif</span>@endif <span class="badge text-bg-primary"><i class="bi bi-person-check"></i> Cari ekli</span></td>
<td class="small text-nowrap">{{ $c->created_at->format('d.m.Y') }}</td>
<td><form method="post" action="{{ route('cari.sil', $c) }}" onsubmit="return confirm('{{ addslashes($c->unvan) }} cariden çıkarılacak. Geçmiş işlemler etkilenmez. Emin misiniz?')">@csrf<button class="btn btn-sm btn-outline-danger" title="Cariden çıkar"><i class="bi bi-x-lg"></i></button></form></td></tr>
@empty<tr><td colspan="8" class="text-muted">Henüz cari eklenmedi. <a href="{{ route('paydaslar') }}">Paydaşlar</a> ekranından cari ekleyin.</td></tr>@endforelse
</tbody></table>
{{ $cariler->links('pagination::bootstrap-5') }}
@endsection
