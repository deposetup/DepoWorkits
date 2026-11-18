@extends('layouts.app')
@section('baslik', 'İlaç Fiyat Listesi (TEST)')
@section('icerik')
<h4>İlaç Fiyat Listesi <span class="badge text-bg-warning align-middle">TEST</span> <small class="text-muted">geçerli fiyat listesi · tüm müşteriler</small></h4>
<p class="small text-muted">Yüklenen liste <b>tüm müşteriler</b> için ortaktır; müşteriler "Sorgular &rarr; İlaç Fiyat Listesi" ekranında ilacı arayarak (sayfa başına 25 satır) fiyatına bakar. Her yüklemede liste tamamen yenilenir.</p>
@if(session('ok'))<div class="alert alert-success py-2">{{ session('ok') }}</div>@endif
@unless($hazir)<div class="alert alert-warning">Fiyat tablosu henüz oluşturulmadı (sunucuda <code>php artisan migrate</code> çalıştırılmalı).</div>@else
<div class="row g-3 mb-3">
  <div class="col-md-5"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-upload me-1"></i>Fiyat listesi (.xlsx) yükle</h6>
    @if($errors->has('dosya'))<div class="alert alert-danger py-2 small">{{ $errors->first('dosya') }}</div>@endif
    <form method="post" action="{{ route('admin.ilac-fiyat.yukle') }}" enctype="multipart/form-data" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Yükleniyor, lütfen bekleyin…';">@csrf
      <input type="file" name="dosya" accept=".xlsx" class="form-control mb-2" required>
      <button class="btn btn-primary" onclick="return confirm('Mevcut fiyat listesi silinip dosyadaki liste yüklenecek. Devam edilsin mi?')">Yükle</button>
    </form>
    <div class="form-text mt-2">Beklenen sütunlar: <b>İlaç GTIN</b>, İlaç Adı, Üretici, Aktif?, İthal?, Geçerlilik Tarihi, Değişiklik Tarihi, ISF, DSF, PSF (KDV Hariç), PSF, Kurum Isk, Özel Isk. Yalnızca GTIN zorunludur. Büyük dosyada işlem birkaç saniye sürebilir.</div>
  </div></div></div>
  <div class="col-md-7"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-tags me-1"></i>Mevcut liste</h6>
    @if($toplam)
      <div>Toplam <b>{{ number_format($toplam, 0, ',', '.') }}</b> ilaç · Aktif <b>{{ number_format($aktifSayisi, 0, ',', '.') }}</b> · Pasif <b>{{ number_format($toplam - $aktifSayisi, 0, ',', '.') }}</b></div>
      <div>Son yükleme: <b>{{ \Illuminate\Support\Carbon::parse($sonYukleme)->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</b></div>
    @else<div class="text-muted">Henüz liste yüklenmedi.</div>@endif
  </div></div></div>
</div>
<form method="get" class="d-flex gap-2 mb-3"><input name="ara" value="{{ $ara }}" class="form-control w-auto" style="min-width:22rem" placeholder="İlaç adı, üretici veya GTIN ara"><button class="btn btn-outline-secondary">Ara</button>@if($ara !== '')<a class="btn btn-link" href="{{ route('admin.ilac-fiyat') }}">Temizle</a>@endif</form>
@if($sonuclar)@include('ilac-fiyat._tablo')@endif
@endunless
@endsection
