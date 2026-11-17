@extends('layouts.app')
@section('baslik', 'Manuel Ekle')
@section('icerik')
<h4><i class="bi bi-plus-square me-1"></i>Manuel Ekle <small class="text-muted">PTS verisi olmayan, arada kalan ürünler</small></h4>
<div class="alert alert-info small">PTS paketi olmayan ama İTS'te <b>arada</b> görünen ürünler için: <code>GTIN;SN;BN;SKT</code> kolonlu CSV dosyasını yükleyin (SKT: YYYY-AA-GG). Her ürün için <b>karekod</b> üretilir; isterseniz karekodları yazdırabilir ya da indirebilir, ürünleri aynı sayfadan <b>Mal Kabul</b> bildirimiyle stoğunuza alabilirsiniz. Bir seferde en fazla 1.000 ürün işlenir.</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('manuel.onizle') }}" enctype="multipart/form-data" class="card card-body mb-3">@csrf
  <div class="row g-3">
    <div class="col-lg-5"><label class="form-label fw-semibold">CSV dosyası</label><input type="file" name="dosya" accept=".csv,.txt" class="form-control"></div>
    <div class="col-lg-7"><label class="form-label fw-semibold">ya da satırları yapıştırın</label><textarea name="metin" rows="3" class="form-control font-monospace small" placeholder="GTIN;SN;BN;SKT&#10;08680199751130;36000024820004;405002980;2028-04-30">{{ old('metin') }}</textarea></div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-eye me-1"></i>Oku ve Karekodları Oluştur</button></div>
</form>

@if($onizleme)
@php
  $sat = $onizleme['satirlar'];
  $gecerli = collect($sat)->where('gecmis', false);
  $jsKodlar = collect($sat)->map(fn ($r) => ['kod' => $r['kod'], 'sn' => $r['sn'], 'gtin' => $r['gtin']])->values()->all();
@endphp
@foreach($onizleme['gecersiz'] as $h)<div class="small text-danger">{{ $h }}</div>@endforeach
@if($onizleme['fazla'])<div class="alert alert-warning small mt-2">1.000 ürün sınırı aşıldı; {{ $onizleme['fazla'] }} satır işlenmedi. Kalanlar için dosyayı bölüp tekrar yükleyin.</div>@endif
@if($sat)
@if($gecerli->isNotEmpty())
<form method="post" action="{{ route('islem.calistir', 'kabul') }}" class="card card-body mt-3 mb-3 border-warning" onsubmit="return confirm('{{ $gecerli->count() }} ürün için Mal Kabul bildirimi İTS\'e GERÇEK olarak gönderilecek. Ürünlerin gerçekten sizde (arada) olduğundan emin misiniz?')">@csrf
  <input type="hidden" name="kodlar" value="{{ $gecerli->pluck('kod')->implode("\n") }}">
  <div class="fw-semibold mb-1">İTS'e Mal Kabul bildir ({{ $gecerli->count() }} ürün)</div>
  <div class="small text-muted mb-2">Bu işlem İTS'e Mal Alım bildirimi gönderir; başarılı ürünler stoğunuza girer. Ürün sizde arada değilse İTS satır bazında hata verir.</div>
  <div class="row g-2 align-items-center"><div class="col-md-5"><input name="lokasyon" class="form-control form-control-sm" placeholder="Lokasyon (isteğe bağlı): reyon / raf" maxlength="80"></div>
  <div class="col-auto"><button class="btn btn-warning"><i class="bi bi-box-arrow-in-down me-1"></i>Mal Kabul Bildir</button></div></div>
</form>
@endif

<div class="card mt-3"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
  <span><b>{{ count($sat) }}</b> ürün okundu @if($onizleme['gecmis'])· <span class="text-danger">{{ $onizleme['gecmis'] }} ürünün SKT'si geçmiş (Mal Kabul'e dahil edilmez)</span>@endif</span>
  <span class="d-flex gap-2">
    <button type="button" class="btn btn-sm btn-outline-dark" id="gorselBtn"><i class="bi bi-qr-code me-1"></i>Karekod Görsellerini Göster / Yazdır</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="txtBtn"><i class="bi bi-download me-1"></i>Karekod Listesi (.txt)</button>
  </span></div>
<div class="table-responsive"><table class="table table-sm mb-0 align-middle"><thead><tr><th>#</th><th>GTIN</th><th>Seri</th><th>Parti</th><th>SKT</th><th>Karekod</th></tr></thead><tbody>
@foreach($sat as $i => $r)
<tr class="{{ $r['gecmis'] ? 'table-danger' : '' }}"><td>{{ $i + 1 }}</td><td class="font-monospace">{{ $r['gtin'] }}</td><td class="font-monospace">{{ $r['sn'] }}</td><td class="font-monospace">{{ $r['bn'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($r['skt'])->format('d.m.Y') }} @if($r['gecmis'])<span class="badge text-bg-danger">SKT geçmiş</span>@endif</td><td class="font-monospace small">{{ $r['kod'] }}</td></tr>
@endforeach
</tbody></table></div></div>

<div id="gorseller" class="d-none mt-3"><div class="row g-3" id="kareler"></div></div>


<script src="https://cdn.jsdelivr.net/npm/bwip-js@4.5.1/dist/bwip-js-min.js"></script>
<script>
(function () {
  var kodlar = {!! json_encode($jsKodlar) !!};
  var ciz = false;
  document.getElementById('gorselBtn').addEventListener('click', function () {
    var kap = document.getElementById('gorseller'), kareler = document.getElementById('kareler');
    if (!ciz) {
      ciz = true;
      kodlar.slice(0, 200).forEach(function (k) {
        var col = document.createElement('div'); col.className = 'col-6 col-md-3 col-xl-2 text-center';
        var cv = document.createElement('canvas');
        try { bwipjs.toCanvas(cv, { bcid: 'gs1datamatrix', text: k.kod, scale: 3, padding: 4, backgroundcolor: 'FFFFFF' }); } catch (e) { cv = document.createElement('div'); cv.textContent = 'Üretilemedi'; cv.className = 'text-danger small'; }
        var t = document.createElement('div'); t.className = 'small font-monospace'; t.textContent = k.gtin + ' / ' + k.sn;
        col.appendChild(cv); col.appendChild(t); kareler.appendChild(col);
      });
      if (kodlar.length > 200) { var u = document.createElement('div'); u.className = 'small text-muted'; u.textContent = 'İlk 200 karekod gösterildi.'; kareler.parentNode.appendChild(u); }
      kap.classList.remove('d-none');
    } else { kap.classList.toggle('d-none'); }
    if (!kap.classList.contains('d-none')) { window.print(); }
  });
  document.getElementById('txtBtn').addEventListener('click', function () {
    // Parantezsiz ham karekod: (01)..(17)..(10)parti<GS>(21)seri -> 01..17..10parti<GS>21seri (parti değişken uzunlukta olduğundan sonrasına GS ayracı konur)
    var duz = kodlar.map(function (k) { return k.kod.replace(/\(10\)([^()]*)\(21\)/, '10$1\u001d21').replace(/[()]/g, ''); });
    var b = new Blob([duz.join('\r\n')], { type: 'text/plain' });
    var a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = 'karekodlar.txt'; document.body.appendChild(a); a.click(); a.remove();
  });
})();
</script>
@endif
@endif
@endsection
