{{-- Cari seçici: ad ya da GLN ile arar, seçilince hedef GLN alanını doldurur. @include('cari._secici', ['hedef' => 'karsi_gln_input']) --}}
<div class="cari-secici position-relative mb-2" data-hedef="{{ $hedef }}">
  <div class="input-group input-group-sm">
    <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
    <input type="text" class="form-control cari-ara" placeholder="Cari adı ile ara ve seç (GLN yazmanıza gerek yok)" autocomplete="off">
    <button type="button" class="btn btn-outline-secondary cari-temizle d-none" title="Seçimi kaldır"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="list-group position-absolute w-100 shadow-sm cari-liste d-none" style="z-index:1050;max-height:260px;overflow:auto"></div>
  <div class="form-text cari-secili"></div>
</div>
@once
<script>
document.querySelectorAll('.cari-secici').forEach(function (kutu) {
  var hedefAl = function () { return document.getElementById(kutu.dataset.hedef); }, ara = kutu.querySelector('.cari-ara'), liste = kutu.querySelector('.cari-liste'),
      secili = kutu.querySelector('.cari-secili'), temizle = kutu.querySelector('.cari-temizle'), zaman = null;
  function kapat() { liste.classList.add('d-none'); }
  function sec(c) {
    hedefAl().value = c.gln; ara.value = c.unvan; kapat(); temizle.classList.remove('d-none');
    secili.className = 'form-text cari-secili ' + (c.aktif ? 'text-success' : 'text-warning');
    secili.textContent = c.unvan + ' — GLN ' + c.gln + (c.il ? ' — ' + c.il + (c.ilce ? ' / ' + c.ilce : '') : '') + (c.aktif ? '' : ' (paydaş eklendiği an İTS\'te PASİFTİ)');
  }
  function getir() {
    fetch('{{ route('cari.ara') }}?q=' + encodeURIComponent(ara.value.trim()), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (l) {
        liste.innerHTML = '';
        if (!l.length) { var b = document.createElement('div'); b.className = 'list-group-item small text-muted'; b.innerHTML = 'Cari bulunamadı. <a href="{{ route('paydaslar') }}">Paydaşlar</a> ekranından cari ekleyebilir ya da GLN\'i aşağıya yazabilirsiniz.'; liste.appendChild(b); }
        l.forEach(function (c) {
          var a = document.createElement('button'); a.type = 'button'; a.className = 'list-group-item list-group-item-action small';
          a.innerHTML = '<strong></strong> <span class="text-muted"></span>';
          a.querySelector('strong').textContent = c.unvan; a.querySelector('span').textContent = c.gln + (c.il ? ' · ' + c.il : '');
          a.addEventListener('click', function () { sec(c); }); liste.appendChild(a);
        });
        liste.classList.remove('d-none');
      });
  }
  ara.addEventListener('input', function () { clearTimeout(zaman); zaman = setTimeout(getir, 250); });
  ara.addEventListener('focus', getir);
  temizle.addEventListener('click', function () { hedefAl().value = ''; ara.value = ''; secili.textContent = ''; temizle.classList.add('d-none'); });
  document.addEventListener('click', function (e) { if (!kutu.contains(e.target)) kapat(); });
});
</script>
@endonce
