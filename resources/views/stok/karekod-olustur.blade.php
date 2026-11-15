@extends('layouts.app')
@section('baslik', 'Karekod Oluşturucu')
@section('icerik')
<style>
  .etiket-alan { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: .9rem; }
  .etiket { background: #fff; border: 1px dashed #aab6d0; border-radius: .5rem; padding: .7rem; text-align: center; break-inside: avoid; }
  .etiket canvas { max-width: 100%; height: auto; image-rendering: pixelated; }
  .etiket .yazi { font-family: ui-monospace, Menlo, monospace; font-size: .68rem; line-height: 1.25; word-break: break-all; margin-top: .35rem; }
  .etiket .ad { font-size: .7rem; color: #555; }
  @media print {
    body * { visibility: hidden; }
    #etiketler, #etiketler * { visibility: visible; }
    #etiketler { position: absolute; inset: 0 auto auto 0; width: 100%; }
    .etiket { border: 1px solid #000; border-radius: 0; }
    .yan, .ust-serit, .alt-bilgi { display: none !important; }
  }
</style>
<h4>Karekod Oluşturucu</h4>
<div class="alert alert-warning small">
  <b>Ne zaman kullanılır?</b> Ürün etiketindeki karekod silinmiş ya da okunamayacak kadar zarar görmüşse, ambalajın üzerinde <b>yazılı olan</b> bilgileri girerek aynı içerikte (GS1 DataMatrix) bir karekod üretip sisteme okutabilirsiniz.
  Bilgileri ambalajdan <b>birebir</b> girin; yanlış girilen seri numarası İTS'te hatalı bildirime yol açar. Bu karekod, üreticinin orijinal karekodunun yerine geçmez; yalnızca iç işlemleriniz (okutma, sayım, bildirim) içindir.
</div>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <div class="mb-3"><label class="form-label fw-semibold">Barkod / GTIN <span class="text-danger">*</span></label>
        <input id="gtin" class="form-control font-monospace" inputmode="numeric" maxlength="14" placeholder="8 ila 14 haneli, örn. 8699617650153" autocomplete="off">
        <div class="form-text" id="gtinNot">Ambalajdaki barkodun altındaki rakamlar. 13 haneliyse başına 0 eklenir.</div></div>
      <div class="row g-3">
        <div class="col-sm-6"><label class="form-label fw-semibold">Son kullanma tarihi</label><input id="skt" type="date" class="form-control"></div>
        <div class="col-sm-6"><label class="form-label fw-semibold">Parti no (LOT)</label><input id="lot" class="form-control font-monospace" maxlength="20" autocomplete="off"></div>
      </div>
      <div class="mt-3"><label class="form-label fw-semibold">Seri no <span class="text-danger">*</span> <small class="text-muted fw-normal">(her satıra bir tane, en fazla 60)</small></label>
        <textarea id="seri" rows="4" class="form-control font-monospace" placeholder="Örn. 2699717003005858"></textarea></div>
      <div class="mt-3"><label class="form-label fw-semibold">Etiket üzerindeki ürün adı <small class="text-muted fw-normal">(isteğe bağlı, yalnızca yazdırmada görünür)</small></label><input id="ad" class="form-control" maxlength="60" autocomplete="off"></div>
      <div class="row g-3 mt-1 align-items-end">
        <div class="col-sm-6"><label class="form-label fw-semibold">Boyut</label>
          <select id="boyut" class="form-select"><option value="3">Küçük</option><option value="4" selected>Orta</option><option value="6">Büyük</option></select></div>
        <div class="col-sm-6 form-check ms-0 ps-4"><input class="form-check-input" type="checkbox" id="yaziGoster" checked><label class="form-check-label" for="yaziGoster">Okunabilir yazıyı göster</label></div>
      </div>
      <div id="hata" class="alert alert-danger small mt-3 d-none"></div>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-primary" id="olustur"><i class="bi bi-qr-code me-1"></i>Karekod Oluştur</button>
        <button class="btn btn-outline-secondary" id="temizle" type="button">Temizle</button>
      </div>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card"><div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold"><i class="bi bi-upc-scan me-1"></i>Oluşan karekodlar <span class="badge text-bg-secondary" id="adet">0</span></span>
      <span class="d-flex gap-2"><button class="btn btn-sm btn-success" id="yazdir" disabled><i class="bi bi-printer me-1"></i>Yazdır</button>
      <button class="btn btn-sm btn-outline-primary" id="indir" disabled><i class="bi bi-download me-1"></i>PNG indir</button></span></div>
      <div class="card-body"><div id="bos" class="text-muted">Bilgileri doldurup <b>Karekod Oluştur</b>'a basın. Karekodlar burada görünür.</div>
      <div id="etiketler" class="etiket-alan"></div></div></div>
    <div class="small text-muted mt-2">Oluşan karekod GS1 DataMatrix biçimindedir ve alanlar (01) GTIN, (17) SKT, (10) parti, (21) seri sırasıyla kodlanır. Karekod okuyucunuzla okutup <a href="{{ route('islem', 'kabul') }}">Mal Kabul</a>, <a href="{{ route('islem', 'satis') }}">Satış</a> vb. ekranlarda kullanabilirsiniz.</div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bwip-js@4.5.1/dist/bwip-js-min.js"></script>
<script>
(function () {
  var $ = function (i) { return document.getElementById(i); };
  var kodlar = [];

  function gtinKontrol(g) { // GS1 mod-10
    var t = 0, r = g.split('').reverse();
    for (var i = 1; i < r.length; i++) t += (+r[i]) * (i % 2 === 1 ? 3 : 1);
    return ((10 - t % 10) % 10) === +r[0];
  }
  function hataGoster(m) { var e = $('hata'); e.textContent = m; e.classList.toggle('d-none', !m); }
  function temizMetin(v, ad) {
    if (/[()\s]/.test(v) || /[^\x21-\x7E]/.test(v)) throw new Error(ad + ' boşluk, parantez veya Türkçe karakter içeremez.');
    return v;
  }

  function olustur() {
    hataGoster('');
    try {
      var g = $('gtin').value.replace(/\D/g, '');
      if (g.length < 8 || g.length > 14) throw new Error('GTIN 8 ila 14 haneli olmalıdır.');
      g = g.padStart(14, '0');
      if (!gtinKontrol(g)) throw new Error('GTIN kontrol hanesi hatalı. Rakamları ambalajdan tekrar kontrol edin.');

      var seriler = $('seri').value.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
      if (!seriler.length) throw new Error('En az bir seri no girin.');
      if (seriler.length > 60) throw new Error('Bir seferde en fazla 60 seri no girilebilir.');
      var gorulen = {};
      seriler.forEach(function (s) {
        temizMetin(s, 'Seri no');
        if (s.length > 20) throw new Error('Seri no en fazla 20 karakter olabilir: ' + s);
        if (gorulen[s]) throw new Error('Aynı seri no iki kez girilmiş: ' + s);
        gorulen[s] = 1;
      });

      var lot = $('lot').value.trim();
      if (lot) { temizMetin(lot, 'Parti no'); if (lot.length > 20) throw new Error('Parti no en fazla 20 karakter olabilir.'); }

      var skt = '';
      if ($('skt').value) { var p = $('skt').value.split('-'); skt = p[0].slice(2) + p[1] + p[2]; }

      var ad = $('ad').value.trim();
      var olcek = +$('boyut').value;
      var alan = $('etiketler'); alan.innerHTML = ''; kodlar = [];

      seriler.forEach(function (s) {
        var metin = '(01)' + g + (skt ? '(17)' + skt : '') + (lot ? '(10)' + lot : '') + '(21)' + s;
        var kutu = document.createElement('div'); kutu.className = 'etiket';
        var cv = document.createElement('canvas');
        bwipjs.toCanvas(cv, { bcid: 'gs1datamatrix', text: metin, scale: olcek, padding: 4, backgroundcolor: 'FFFFFF' });
        kutu.appendChild(cv);
        if (ad) { var a = document.createElement('div'); a.className = 'ad'; a.textContent = ad; kutu.appendChild(a); }
        if ($('yaziGoster').checked) { var y = document.createElement('div'); y.className = 'yazi'; y.textContent = metin; kutu.appendChild(y); }
        alan.appendChild(kutu);
        kodlar.push({ ad: g + '_' + s, cv: cv });
      });
      $('bos').classList.add('d-none'); $('adet').textContent = kodlar.length;
      $('yazdir').disabled = $('indir').disabled = false;
    } catch (e) {
      hataGoster(e.message || 'Karekod oluşturulamadı.');
    }
  }

  $('olustur').addEventListener('click', olustur);
  ['boyut', 'yaziGoster'].forEach(function (i) { $(i).addEventListener('change', function () { if (kodlar.length) olustur(); }); });
  $('temizle').addEventListener('click', function () {
    ['gtin', 'skt', 'lot', 'seri', 'ad'].forEach(function (i) { $(i).value = ''; });
    $('etiketler').innerHTML = ''; kodlar = []; $('adet').textContent = 0; $('bos').classList.remove('d-none');
    $('yazdir').disabled = $('indir').disabled = true; hataGoster('');
  });
  $('yazdir').addEventListener('click', function () { window.print(); });
  $('indir').addEventListener('click', function () {
    kodlar.forEach(function (k, i) {
      setTimeout(function () { var a = document.createElement('a'); a.href = k.cv.toDataURL('image/png'); a.download = 'karekod_' + k.ad + '.png'; document.body.appendChild(a); a.click(); a.remove(); }, i * 250);
    });
  });
})();
</script>
@endsection
