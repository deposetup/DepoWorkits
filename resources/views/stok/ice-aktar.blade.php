@extends('layouts.app')
@section('baslik', 'Stok İçe Aktar')
@section('icerik')
<h4>Stok İçe Aktar (İTS Doğrulamalı)</h4>
<div class="alert alert-info small">
  İTS, depo stok listesini servis olarak sunmuyor. Bu ekran aday ürünleri İTS'te tek tek sorgular; <b>son sahibi sizin GLN'iniz olan ve arada olmayan</b> ürünleri stoğunuza alır. Alınan ürünler <b>Stok Listesi (Karekod)</b> ekranında görünür ve satış/sarf yapılabilir. Bu işlem İTS'e hiçbir bildirim göndermez (yalnızca sorgu).
</div>
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
