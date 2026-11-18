@extends('layouts.app')
@section('baslik', 'İlaç Fiyat Listesi (TEST)')
@section('icerik')
<h4><i class="bi bi-tags me-1"></i>İlaç Fiyat Listesi <span class="badge text-bg-warning align-middle">TEST</span></h4>
<p class="small text-muted">Geçerli ilaç fiyat listesi tüm müşteriler için ortaktır ve yönetici tarafından güncellenir. İlaç adı, üretici ya da barkod (GTIN) yazarak arayın; sonuçlar sayfa başına 25 satır gösterilir.@if($sonYukleme) Son güncelleme: <b>{{ \Illuminate\Support\Carbon::parse($sonYukleme)->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</b>@endif</p>
@unless($hazir)<div class="alert alert-warning">Fiyat listesi henüz hazır değil.</div>
@elseif(! $toplam)<div class="alert alert-info">Fiyat listesi henüz yüklenmedi.</div>
@else
<form method="get" class="d-flex gap-2 mb-3"><input name="ara" value="{{ $ara }}" class="form-control w-auto" style="min-width:22rem" placeholder="İlaç adı, üretici veya GTIN (en az 3 karakter)" autofocus><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Ara</button>@if($ara !== '')<a class="btn btn-link" href="{{ route('ilac-fiyat') }}">Temizle</a>@endif</form>
@if($kisa)<div class="alert alert-warning py-2">Aramak için en az 3 karakter yazın.</div>
@elseif($sonuclar)@include('ilac-fiyat._tablo')
@else<div class="text-muted">Fiyatını görmek istediğiniz ilacı yukarıdan arayın.</div>@endif
@endunless
@endsection
