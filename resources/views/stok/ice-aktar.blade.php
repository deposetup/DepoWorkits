@extends('layouts.app')
@section('baslik', 'Stok İçe Aktar')
@section('icerik')
<h4>Stok İçe Aktar (İTS Doğrulamalı)</h4>
<div class="alert alert-info small">
  İTS, depo stok listesini servis olarak sunmuyor. Bu ekran aday ürünleri İTS'te tek tek sorgular; <b>son sahibi sizin GLN'iniz olan ve arada olmayan</b> ürünleri stoğunuza alır. Alınan ürünler <b>Stok Listesi (Karekod)</b> ekranında görünür ve satış/sarf yapılabilir. Bu işlem İTS'e hiçbir bildirim göndermez (yalnızca sorgu).
</div>
@if(session('hata'))<div class="alert alert-danger">{{ session('hata') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@isset($csv)
<div class="alert alert-success"><b>Dosya içe aktarıldı.</b> {{ number_format($csv['okunan'], 0, ',', '.') }} satır okundu: <b>{{ number_format($csv['eklenen'], 0, ',', '.') }}</b> yeni ürün stoğa eklendi, {{ number_format($csv['guncellenen'], 0, ',', '.') }} ürün zaten kayıtlıydı (güncellendi)@if($csv['atlanan_durum']), {{ $csv['atlanan_durum'] }} ürün satış/sarf gibi başka durumda olduğu için atlandı @endif @if($csv['gecersiz']), <span class="text-danger">{{ $csv['gecersiz'] }} geçersiz satır</span>@endif.
@foreach($csv['ornek_hatalar'] as $h)<div class="small text-danger">{{ $h }}</div>@endforeach
<div class="mt-2"><a href="{{ route('stok.barkod') }}" class="btn btn-sm btn-success">Barkod Bazlı Stok</a> <a href="{{ route('stok.liste') }}" class="btn btn-sm btn-outline-success">Stok Listesi</a></div></div>
@endisset
<div class="card mb-3"><div class="card-body">
  <h6><i class="bi bi-filetype-csv me-1"></i>İTS portalından indirilen stok dosyası (CSV / ZIP)</h6>
  <p class="small text-muted mb-2">İTS portalından kendi GLN'inizle indirdiğiniz stok dosyasını (<code>GTIN;SN;BN;EXP_DATE</code>) yükleyin. Dosya İTS'in kendi kaydı olduğu için ürünler tek tek sorgulanmadan stoğunuza alınır; İTS'e bildirim gönderilmez. Dosya adında GLN varsa sizin GLN'inizle aynı olmalıdır. Büyük dosyalar için .zip yükleyebilirsiniz (en fazla 50 MB). Satış/sarf gibi başka durumdaki ürünler ezilmez, SKT'si geçenler otomatik Red Odası'na alınır.</p>
  <form method="post" action="{{ route('stok.ice-aktar.csv') }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Aktarılıyor, bekleyin…'">@csrf
    <input type="file" name="dosya" accept=".csv,.txt,.zip" class="form-control w-auto" required>
    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Dosyayı Stoğa Aktar</button></form>
</div></div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-archive me-1"></i>Aldığım PTS paketlerinden</h6>
    <p class="small text-muted">PTS Sorgu ekranından "Paketi Al" ile indirdiğiniz {{ $paketSayisi }} paketteki tüm ürünler İTS'te doğrulanır. Geçmiş stoğunuzu kapsamak için PTS Sorgu'dan önceki tarih aralıklarını (31 gün sınırı) da paket olarak alın.</p>
    <form method="post" action="{{ route('stok.ice-aktar.calistir') }}" onsubmit="return confirm('Paketlerdeki ürünler İTS\'te sorgulanacak. Devam edilsin mi?')">@csrf<input type="hidden" name="kaynak" value="pts">
      <button class="btn btn-primary" @disabled(! $paketSayisi)><i class="bi bi-cloud-download me-1"></i>Paketleri Tara ve Stoğa Al</button></form>
  </div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-upc-scan me-1"></i>Karekod listesinden</h6>
    <form method="post" action="{{ route('stok.ice-aktar.calistir') }}">@csrf<input type="hidden" name="kaynak" value="kodlar">
      <textarea name="kodlar" rows="5" class="form-control font-monospace mb-2" placeholder="Her satıra bir karekod (okuyucu ile okutun)">{{ old('kodlar') }}</textarea>
      <button class="btn btn-primary"><i class="bi bi-shield-check me-1"></i>Doğrula ve Stoğa Al</button></form>
  </div></div></div>
</div>
@if($sonuc)
<div class="card mt-4"><div class="card-header fw-semibold">Sonuç</div><div class="card-body">
  <p class="mb-1"><span class="badge text-bg-success">{{ $sonuc['alinan'] }}</span> ürün stoğa alındı · <span class="badge text-bg-secondary">{{ $sonuc['zatenVar'] }}</span> zaten stokta · <span class="badge text-bg-warning">{{ count($sonuc['digerleri']) }}</span> alınmadı · {{ $sonuc['sorgulanan'] }} ürün sorgulandı</p>
  @foreach($sonuc['hatalar'] as $h)<div class="text-danger small">{{ $h }}</div>@endforeach
  @if($sonuc['digerleri'])
  <table class="table table-sm mt-3 mb-0"><thead><tr><th>GTIN</th><th>Seri</th><th>Neden</th><th>Son sahibi (GLN)</th></tr></thead><tbody>
  @foreach(array_slice($sonuc['digerleri'], 0, 300) as $d)<tr><td class="font-monospace">{{ $d['gtin'] }}</td><td class="font-monospace">{{ $d['sn'] }}</td><td>{{ $d['neden'] }}</td><td class="font-monospace">{{ $d['gln1'] ?: '—' }}</td></tr>@endforeach
  </tbody></table>
  @endif
</div></div>
@endif
@endsection
