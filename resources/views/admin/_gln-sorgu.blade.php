{{-- GLN yazılınca İTS paydaş listesinden ünvan/il/ilçe getirir. Form alanları id ile bağlanır: gln, unvan, tur, il, ilce, eposta, telefon --}}
@php($ow = $overwrite ?? false)
<div id="glnDurum" class="small mt-1"></div>
<script>
(function () {
  var g = document.getElementById('gln'); if (!g) return;
  var d = document.getElementById('glnDurum'), zaman = null, son = null, ow = {{ $ow ? 'true' : 'false' }};
  function el(i) { return document.getElementById(i); }
  function yaz(i, v, zorla) { var e = el(i); if (!e || v === null || v === undefined || v === '') return; if (zorla || !e.value.trim()) e.value = v; }
  function sorgula() {
    var v = g.value.replace(/\D/g, '');
    if (v.length !== 13 || v === son) return;
    son = v; d.className = 'small mt-1 text-muted'; d.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>İTS paydaş listesinde aranıyor… (ilk sorguda bir süre alabilir)';
    var f = new FormData(); f.append('gln', v);
    fetch('{{ route('admin.gln-sorgu') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: f, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'Sorgu başarısız.'); return j; }); })
      .then(function (j) {
        if (!j.bulundu) { d.className = 'small mt-1 text-danger'; d.textContent = j.hata || 'Bulunamadı.'; return; }
        yaz('unvan', j.unvan, ow); yaz('tur', j.tur, true); yaz('il', j.il, true); yaz('ilce', j.ilce, true); yaz('eposta', j.eposta, ow); yaz('telefon', j.telefon, ow);
        d.className = 'small mt-1 ' + (j.aktif ? 'text-success' : 'text-warning');
        d.textContent = 'İTS\'ten getirildi: ' + j.unvan + (j.il ? ' — ' + j.il + (j.ilce ? ' / ' + j.ilce : '') : '') + (j.aktif ? '' : ' (İTS\'te PASİF)');
      })
      .catch(function (e) { d.className = 'small mt-1 text-danger'; d.textContent = e.message || 'Sorgu başarısız.'; son = null; });
  }
  g.addEventListener('input', function () { clearTimeout(zaman); zaman = setTimeout(sorgula, 500); });
  g.addEventListener('blur', sorgula);
  var b = el('glnGetir'); if (b) b.addEventListener('click', function () { son = null; sorgula(); });
})();
</script>
