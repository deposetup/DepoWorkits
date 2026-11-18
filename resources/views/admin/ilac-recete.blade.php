@extends('layouts.app')
@section('baslik', 'İlaç Reçete Listesi')
@section('icerik')
<h4>İlaç Reçete Listesi <small class="text-muted">GTIN &rarr; reçete tipi</small></h4>
<p class="small text-muted">Yüklenen liste <b>tüm müşteriler</b> için ortaktır. Sarf, mal kabul, satış, iade, deaktivasyon sonuç tablolarında ve PTS paket ekranında ürün GTIN'i ile eşleşip tüm reçete tipleri (<span class="badge text-bg-danger">Kırmızı</span> <span class="badge text-bg-success">Yeşil</span> <span class="badge text-bg-warning">Turuncu</span> <span class="badge text-bg-secondary" style="background:#7c4dbd!important">Mor</span> <span class="badge text-bg-light border">Beyaz</span>) müşteriler tarafından da görülür.</p>
@unless($hazir)<div class="alert alert-warning">Reçete tablosu henüz oluşturulmadı (migration çalıştırılmalı).</div>@else
<div class="row g-3 mb-3">
  <div class="col-md-5"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-upload me-1"></i>İlaç.xlsx yükle</h6>
    @if($errors->has('dosya'))<div class="alert alert-danger py-2 small">{{ $errors->first('dosya') }}</div>@endif
    <form method="post" action="{{ route('admin.ilac-recete.yukle') }}" enctype="multipart/form-data" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Yükleniyor, lütfen bekleyin…';">@csrf
      <input type="file" name="dosya" accept=".xlsx" class="form-control mb-2" required>
      <button class="btn btn-primary" onclick="return confirm('Mevcut reçete listesi silinip dosyadaki liste yüklenecek. Devam edilsin mi?')">Yükle</button>
    </form>
    <div class="form-text mt-2">Gerekli sütunlar: <b>GTIN</b> ve <b>Reçete Tipi</b> (Adı isteğe bağlı). Her yüklemede liste tamamen yenilenir; büyük dosyada işlem birkaç saniye sürebilir.</div>
  </div></div></div>
  <div class="col-md-7"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-capsule me-1"></i>Mevcut liste</h6>
    @if($toplam)
      <div class="mb-2">Toplam <b>{{ number_format($toplam, 0, ',', '.') }}</b> ilaç · Son yükleme: <b>{{ \Illuminate\Support\Carbon::parse($sonYukleme)->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</b></div>
      <div class="d-flex flex-wrap gap-2">@foreach($tipler as $tip => $adet)<span class="border rounded px-2 py-1 small">{!! \App\Models\IlacRecete::rozet($tip) ?: e($tip ?: '(boş)') !!} <b>{{ number_format($adet, 0, ',', '.') }}</b></span>@endforeach</div>
    @else<div class="text-muted">Henüz liste yüklenmedi.</div>@endif
  </div></div></div>
</div>

<form method="get" class="d-flex gap-2 mb-2"><input name="ara" value="{{ $ara }}" class="form-control w-auto" style="min-width:20rem" placeholder="Eşleşmeyi dene: GTIN veya ilaç adı"><button class="btn btn-outline-secondary">Ara</button></form>
@if($ara !== '')
<table class="table table-sm bg-white"><thead><tr><th>GTIN</th><th>İlaç</th><th>Reçete Tipi</th></tr></thead><tbody>
@forelse($bulunan as $b)<tr><td class="font-monospace">{{ $b->gtin }}</td><td>{{ $b->ad ?? '—' }}</td><td>{!! \App\Models\IlacRecete::rozet($b->recete_tipi) ?: e($b->recete_tipi ?: '—') !!}</td></tr>
@empty<tr><td colspan="3" class="text-muted">Eşleşen kayıt yok.</td></tr>@endforelse
</tbody></table>@if($bulunan->count() === 50)<div class="small text-muted">İlk 50 kayıt gösteriliyor.</div>@endif
@endif
@endunless
@endsection
