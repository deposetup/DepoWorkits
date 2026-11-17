@extends('layouts.app')
@section('baslik', 'Ana Sayfa')
@section('icerik')
@if($tenant && ! $tenant->kvkk_onay_tarihi)
<div class="alert alert-warning d-flex justify-content-between align-items-center">
  <span>Devam etmek için KVKK aydınlatma metnini onaylamanız gerekir.</span>
  <form method="post" action="{{ route('kvkk.onay') }}">@csrf<button class="btn btn-warning btn-sm">ONAYLIYORUM</button></form>
</div>
@endif
@if(! $tenant)
<h4>Ana Sayfa</h4>
@endif
@if($tenant)
@php $gun = $tenant->kalanGun(); @endphp
<div class="d-flex justify-content-between align-items-start mb-3">
  <h4 class="mb-0">Ana Sayfa</h4>
  <div class="text-end">
    <span class="badge fs-6 px-3 py-2 text-bg-{{ ! $tenant->lisansAktifMi() || $gun <= 7 ? 'danger' : ($gun <= 30 ? 'warning' : 'success') }}" title="Lisans bitiş: {{ $tenant->lisans_bitis->format('d.m.Y') }}">
      <i class="bi bi-hourglass-split me-1"></i>Kalan süre: {{ $gun }} gün
    </span>
    <div class="mt-2 small text-muted">İletişim</div>
    <div class="fw-semibold"><i class="bi bi-telephone me-1"></i><a href="tel:+908509080042" class="text-decoration-none">0850 908 00 42</a></div>
    <div class="small text-muted">Depo Setup Yazılım ve Danışmanlık Ltd. Şti.</div>
  </div>
</div>
@endif
@php
  $kisayol = auth()->user()->superAdminMi() ? [
    ['Müşteriler', 'Müşteri ve lisans yönetimi', 'bi-people', route('admin.tenants.index')],
    ['Destek Talepleri', 'Şifre ve bağlantı talepleri', 'bi-life-preserver', route('admin.talepler')],
    ['Giriş Kayıtları', 'Müşteri giriş saatleri', 'bi-clock-history', route('admin.giris-kayitlari')],
  ] : [
    ['Mal Kabul', 'Gelen ürünü kabul et', 'bi-check2-square', route('islem', 'kabul')],
    ['PTS Sorgu', 'Paketleri sorgula, XML yükle', 'bi-search', route('pts')],
    ...((auth()->user()->tenant?->satisYapabilir() ?? true) ? [['Satış', 'Ürün satış bildirimi', 'bi-cart-check', route('islem', 'satis')]] : [['Hastane Sarf', 'Ürün sarf bildirimi', 'bi-hospital', route('islem', 'sarf')]]),
    ['Barkod Bazlı Stok', 'GTIN bazında adet, parti, SKT', 'bi-upc', route('stok.barkod')],
    ['Stok Listesi', 'Karekodlu stok, Excel', 'bi-upc-scan', route('stok.liste')],
    ['Durum Sorgu', 'Ürünün İTS durumu', 'bi-shield-check', route('durum')],
    ['Kritik / Miad', 'Yaklaşan SKT ve eşikler', 'bi-exclamation-triangle', route('kritik-miad')],
    ['Karekod Oluşturucu', 'Silinen karekodu yeniden üret, yazdır', 'bi-qr-code', route('karekod.olustur')],
    ['Paydaşlar', 'Aktif / pasif paydaş listesi', 'bi-building', route('paydaslar')],
    ['Cari Kartlar', 'Sık çalışılan alıcı/tedarikçiler', 'bi-person-lines-fill', route('cari.index')],
    ['İTS Bağlantısı', 'Bağlantıyı sına', 'bi-plug', route('ayarlar.its')],
  ];
@endphp
<div class="d-flex justify-content-end mt-2"><button type="button" id="duzenBtn" class="btn btn-sm btn-outline-secondary"><i class="bi bi-sliders me-1"></i>Özetleri düzenle</button></div>
<div id="duzenBar" class="alert alert-light border small d-none mt-2 mb-0">
  <div class="d-flex flex-wrap align-items-center gap-2"><span class="text-muted">Kartların köşesindeki oklarla sırayı değiştirin, <i class="bi bi-eye-slash"></i> ile gizleyin.</span>
    <span id="gizliListe" class="d-flex flex-wrap gap-1"></span>
    <span class="ms-auto d-flex gap-2"><button type="button" id="varsayilanBtn" class="btn btn-sm btn-outline-secondary">Varsayılana dön</button><button type="button" id="bitirBtn" class="btn btn-sm btn-primary">Bitti</button></span></div>
</div>
<style>
  .ozet { position: relative; }
  .ozet-arac { display: none; position: absolute; top: .4rem; right: calc(var(--bs-gutter-x) * .5 + .4rem); z-index: 5; gap: .25rem; }
  #ozetler.duzen .ozet-arac { display: flex; }
  #ozetler.duzen .ozet > .card, #ozetler.duzen .ozet > h5, #ozetler.duzen .ozet > .row { outline: 2px dashed #9fb3d9; outline-offset: 2px; }
</style>
<div class="row g-3 mt-1" id="ozetler">
@isset($bugun)
<div class="col-lg-5 ozet" data-id="bugun" data-baslik="Bugün Stoğa Giren"><div class="card h-100"><div class="card-header bg-white d-flex justify-content-between align-items-center"><span class="fw-semibold"><i class="bi bi-box-arrow-in-down me-1"></i>Bugün Stoğa Giren Ürünler</span>
  <span><span class="badge text-bg-primary">Kalem: {{ $bugun['kalem'] }}</span> <span class="badge text-bg-success">Toplam kutu: {{ number_format($bugun['kutu'], 0, ',', '.') }}</span></span></div>
  @if($bugun['liste']->isEmpty())
  <div class="card-body small text-muted">Bugün stoğa giren ürün yok.</div>
  @else
  <table class="table table-sm align-middle mb-0"><thead><tr><th>İlaç</th><th class="text-end">Adet</th></tr></thead><tbody>
  @foreach($bugun['liste'] as $b)<tr><td>{{ $b['ad'] ?? '—' }}</td><td class="text-end">{{ $b['adet'] }}</td></tr>@endforeach
  </tbody></table>
  @if($bugun['kalem'] > $bugun['liste']->count())<div class="p-2 small text-muted">İlk {{ $bugun['liste']->count() }} kalem gösteriliyor; tamamı için <a href="{{ route('stok') }}">Stok</a> sayfasına bakın.</div>@endif
  @endif
</div></div>
@endisset
@isset($pts)
  <div class="col-lg-7 ozet" data-id="pts" data-baslik="PTS Havuzu"><div class="card h-100"><div class="card-header bg-white fw-semibold"><i class="bi bi-inbox me-1"></i>PTS Havuzu: Arada Kalanlar</div><div class="card-body">
    <div class="row text-center mb-3">
      <div class="col"><div class="fs-3 {{ $pts['bekleyen_paket'] ? 'text-warning' : 'text-success' }}">{{ $pts['bekleyen_paket'] }}</div><div class="small text-muted">Mal Alım bekleyen paket</div></div>
      <div class="col"><div class="fs-3">{{ $pts['bekleyen_urun'] }}</div><div class="small text-muted">Bekleyen ürün</div></div>
      <div class="col"><div class="fs-3">{{ $pts['son30_alinan'] }}</div><div class="small text-muted">Son 30 gün mal alınan paket</div></div>
    </div>
    @if($pts['liste']->isEmpty())
      <div class="text-success small"><i class="bi bi-check-circle me-1"></i>Mal Alım bekleyen paket yok.</div>
    @else
      <table class="table table-sm align-middle mb-2"><thead><tr><th>Belge / Transfer</th><th>Gönderen</th><th class="text-end">Ürün</th><th>Bekleme</th><th></th></tr></thead><tbody>
      @foreach($pts['liste'] as $p)
        @php $bg = (int) $p->created_at->startOfDay()->diffInDays(now()->startOfDay()); @endphp
        <tr><td class="small">{{ $p->belge_no ?: $p->transfer_id }}</td><td class="small font-monospace">{{ $p->kaynak_gln ?: '—' }}</td><td class="text-end">{{ $p->urun_sayisi }}</td>
          <td><span class="badge text-bg-{{ $bg >= 3 ? 'danger' : ($bg >= 1 ? 'warning' : 'secondary') }}">{{ $bg === 0 ? 'Bugün' : $bg.' gün' }}</span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('pts.paket', $p->id) }}">Mal Alım</a></td></tr>
      @endforeach
      </tbody></table>
      @if($pts['bekleyen_paket'] > $pts['liste']->count())<div class="small text-muted">+ {{ $pts['bekleyen_paket'] - $pts['liste']->count() }} paket daha bekliyor (en eskiler gösterilir).</div>@endif
    @endif
    <div class="small text-muted mt-2">Bu özet, sizde indirilmiş ama Mal Alım bildirimi yapılmamış paketleri gösterir. İTS'te henüz indirmediğiniz paketler için <a href="{{ route('pts', ['baslangic' => now()->subDays(7)->toDateString(), 'bitis' => now()->toDateString(), 'sadece_alinmayan' => 1]) }}">PTS'te alınmayanları sorgulayın</a>.</div>
  </div></div></div>
  @if($sarf)
  <div class="col-lg-6 ozet" data-id="sarf" data-baslik="Aylık Sarf"><div class="card h-100"><div class="card-header bg-white fw-semibold"><i class="bi bi-calendar3 me-1"></i>Aylık Ürün Sarf Miktarı</div><div class="card-body p-0">
    <table class="table table-sm align-middle mb-0"><thead><tr><th>Ay</th><th class="text-end">Sarf</th><th class="text-end">İptal</th><th class="text-end">Net</th><th class="text-end">Ürün çeşidi</th></tr></thead><tbody>
    @foreach($sarf['aylar'] as $a)
      <tr class="{{ $loop->first ? 'table-light fw-semibold' : '' }}"><td>{{ $a['ay']->locale('tr')->translatedFormat('F Y') }}</td><td class="text-end">{{ $a['sarf'] }}</td><td class="text-end text-muted">{{ $a['iptal'] }}</td><td class="text-end">{{ $a['net'] }}</td><td class="text-end">{{ $a['cesit'] }}</td></tr>
    @endforeach
    </tbody></table>
    @if($sarf['en_cok']->isNotEmpty())
    <div class="p-3 border-top"><div class="small text-muted mb-1">Bu ay en çok sarf edilenler</div>
      @foreach($sarf['en_cok'] as $e)<div class="d-flex justify-content-between small"><span>{{ $e['ad'] ?? $e['gtin'] }}</span><strong>{{ $e['adet'] }}</strong></div>@endforeach
    </div>
    @endif
  </div></div></div>
  @endif
@endisset
<div class="col-12 ozet" data-id="hizli" data-baslik="Hızlı Erişim"><h5 class="mt-3 mb-3" style="color:#0b2447;font-weight:700">Hızlı Erişim</h5>
<div class="row g-3">
  @foreach($kisayol as [$ad, $acik, $ikon, $url])
  <div class="col-sm-6 col-xl-3"><a class="hizli" href="{{ $url }}"><span class="ikon"><i class="bi {{ $ikon }}"></i></span><span><strong>{{ $ad }}</strong><small>{{ $acik }}</small></span></a></div>
  @endforeach
  @if($tenant)
  <div class="col-sm-6 col-xl-3"><div class="hizli {{ $gun <= 7 ? 'border-danger' : '' }}"><span class="ikon"><i class="bi bi-hourglass-split"></i></span><span><strong>Kalan Süre: {{ $gun }} gün</strong><small>Bitiş: {{ $tenant->lisans_bitis->format('d.m.Y') }}@if(! $tenant->lisansAktifMi()) · Lisans süresi doldu @endif</small></span></div></div>
  <div class="col-sm-6 col-xl-3"><div class="hizli"><span class="ikon"><i class="bi bi-shield-check"></i></span><span><strong>KVKK Onay Tarihi</strong><small>{{ $tenant->kvkk_onay_tarihi?->timezone('Europe/Istanbul')?->format('d.m.Y H:i') ?? '—' }}</small></span></div></div>
  @endif
</div></div>
</div>
<script>
(function () {
  const kap = document.getElementById('ozetler'); if (!kap) return;
  const anahtar = 'anasayfa-ozet-' + @json(auth()->id());
  const kartlar = [...kap.querySelectorAll(':scope > .ozet')];
  let st = {order: [], hidden: []};
  try { st = Object.assign(st, JSON.parse(localStorage.getItem(anahtar) || '{}')); } catch (e) {}
  if (!Array.isArray(st.order)) st.order = []; if (!Array.isArray(st.hidden)) st.hidden = [];
  const kaydet = () => { try { localStorage.setItem(anahtar, JSON.stringify(st)); } catch (e) {} };
  const harita = Object.fromEntries(kartlar.map(k => [k.dataset.id, k]));
  const sira = () => [...kap.querySelectorAll(':scope > .ozet')].map(k => k.dataset.id);

  kartlar.forEach(k => {
    const a = document.createElement('div'); a.className = 'ozet-arac';
    a.innerHTML = '<button type="button" class="btn btn-sm btn-light border" data-y="yukari" title="Yukarı / sola"><i class="bi bi-arrow-up"></i></button>'
      + '<button type="button" class="btn btn-sm btn-light border" data-y="asagi" title="Aşağı / sağa"><i class="bi bi-arrow-down"></i></button>'
      + '<button type="button" class="btn btn-sm btn-light border" data-y="gizle" title="Gizle"><i class="bi bi-eye-slash"></i></button>';
    k.appendChild(a);
  });

  function uygula() {
    const kayitli = st.order.filter(id => harita[id]);
    const digerleri = sira().filter(id => !kayitli.includes(id));
    kayitli.concat(digerleri).forEach(id => kap.appendChild(harita[id]));
    kartlar.forEach(k => k.classList.toggle('d-none', st.hidden.includes(k.dataset.id)));
    const liste = document.getElementById('gizliListe'); liste.innerHTML = '';
    st.hidden.filter(id => harita[id]).forEach(id => {
      const b = document.createElement('button'); b.type = 'button'; b.className = 'btn btn-sm btn-outline-success';
      b.innerHTML = '<i class="bi bi-plus-lg me-1"></i>' + harita[id].dataset.baslik; b.onclick = () => { st.hidden = st.hidden.filter(x => x !== id); kaydet(); uygula(); };
      liste.appendChild(b);
    });
  }
  kap.addEventListener('click', function (e) {
    const b = e.target.closest('[data-y]'); if (!b) return;
    const kart = b.closest('.ozet'), id = kart.dataset.id, y = b.dataset.y;
    if (y === 'gizle') { if (!st.hidden.includes(id)) st.hidden.push(id); }
    else {
      const gorunen = [...kap.querySelectorAll(':scope > .ozet:not(.d-none)')], i = gorunen.indexOf(kart);
      const hedef = gorunen[y === 'yukari' ? i - 1 : i + 1];
      if (hedef) { y === 'yukari' ? kap.insertBefore(kart, hedef) : kap.insertBefore(hedef, kart); }
      st.order = sira();
    }
    kaydet(); uygula();
  });
  const bar = document.getElementById('duzenBar'), btn = document.getElementById('duzenBtn');
  const ac = (v) => { kap.classList.toggle('duzen', v); bar.classList.toggle('d-none', !v); btn.classList.toggle('d-none', v); };
  btn.onclick = () => ac(true);
  document.getElementById('bitirBtn').onclick = () => ac(false);
  document.getElementById('varsayilanBtn').onclick = () => { st = {order: [], hidden: []}; try { localStorage.removeItem(anahtar); } catch (e) {} location.reload(); };
  uygula();
})();
</script>
@endsection
