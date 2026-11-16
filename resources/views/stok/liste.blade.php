@extends('layouts.app')
@section('baslik', 'Stok Listesi')
@section('icerik')
@php
  $sarfVar = auth()->user()->tenant?->sarfYapabilir() ?? true;
  $devirVar = auth()->user()->tenant?->devirYapabilir() ?? true;
  $satisVar = auth()->user()->tenant?->satisYapabilir() ?? true;
  $sekmeler = array_filter(['stokta' => ['Stokta', 'bi-box-seam'], 'satis' => ['Satıldı', 'bi-cart-check'], 'devir' => ['Devredildi', 'bi-arrow-left-right'], 'deaktive' => ['Deaktive', 'bi-slash-circle'], 'sarf' => ['Sarf', 'bi-hospital']],
    fn ($k) => ($sarfVar || $k !== 'sarf') && ($devirVar || $k !== 'devir') && ($satisVar || $k !== 'satis'), ARRAY_FILTER_USE_KEY);
  $islemler = array_values(match ($durum) {
    'stokta' => array_filter([
      $satisVar ? ['satis', 'Satış', 'bi-cart-check', 'success'] : null,
      $sarfVar ? ['sarf', 'Hastane Sarf', 'bi-hospital', 'info'] : null,
      $devirVar ? ['devir', 'Mal Devir', 'bi-arrow-left-right', 'primary'] : null,
      ['iade', 'Ürün İade', 'bi-arrow-return-left', 'warning'],
      ['deaktive', 'Deaktive', 'bi-slash-circle', 'dark'],
    ]),
    'satis' => [['satis_iptal', 'Satış İptal', 'bi-cart-x', 'danger']],
    'deaktive' => [['deaktive_iptal', 'Deaktivasyon İptal', 'bi-check-circle', 'danger']],
    'devir' => [['devir_iptal', 'Mal Devir İptal', 'bi-x-circle', 'danger']],
    default => [['sarf_iptal', 'Sarf İptal', 'bi-arrow-counterclockwise', 'danger']],
  });
  $adOf = fn ($g) => $adlar[$g] ?? $g;
  $sayfaIlaclar = $kalemler->getCollection()->groupBy(fn ($k) => $adOf($k->gtin))->map->count()->sortKeys();
  $bugun = now()->startOfDay();
  $bas = function ($alan, $ad) use ($sirala, $yon) {
    $yeni = $sirala === $alan && $yon === 'asc' ? 'desc' : 'asc';
    $ok = $sirala === $alan ? ($yon === 'asc' ? ' <i class="bi bi-sort-up text-primary"></i>' : ' <i class="bi bi-sort-down text-primary"></i>') : ' <i class="bi bi-arrow-down-up text-muted small"></i>';
    return '<a class="text-decoration-none text-reset text-nowrap" href="'.e(request()->fullUrlWithQuery(['sirala' => $alan, 'yon' => $yeni, 'page' => null])).'">'.$ad.$ok.'</a>';
  };
  $lokVar = $durum === 'stokta' && \App\Models\StokKalemi::lokasyonVar();
@endphp
<style>
  .sl-sekme { display:flex; gap:.25rem; border-bottom:1px solid #dfe5f0; margin-bottom:1rem; overflow-x:auto; }
  .sl-sekme a { padding:.6rem 1rem; text-decoration:none; color:#5b6b8c; font-weight:500; border-bottom:3px solid transparent; white-space:nowrap; }
  .sl-sekme a:hover { color:var(--lacivert); background:#eef2fa; }
  .sl-sekme a.aktif { color:var(--lacivert); border-bottom-color:var(--lacivert2); font-weight:700; }
  .sl-sekme .badge { font-weight:600; }
  .sl-kart { border:1px solid #e3e8f2; border-radius:.75rem; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.05); }
  .sl-baslik { font-size:.72rem; letter-spacing:.06em; text-transform:uppercase; color:#6b7a99; font-weight:700; margin-bottom:.5rem; }
  .sl-tablo th { font-size:.74rem; text-transform:uppercase; letter-spacing:.04em; color:#5b6b8c; background:#f4f6fa; white-space:nowrap; }
  .sl-tablo tbody tr { cursor:pointer; }
  .sl-tablo tbody tr:has(.sec:checked) { background:#e8f3ff; }
  .islem-btn { font-weight:600; } .islem-btn.active { box-shadow:0 0 0 .2rem rgba(11,36,71,.25); transform:translateY(-1px); }
  .sl-alt { position:sticky; bottom:0; z-index:1020; margin-top:1rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
  <div>
    <h4 class="mb-1"><i class="bi bi-upc-scan me-2"></i>Stok Listesi (Karekod)</h4>
    <div class="text-muted small">Portal üzerinden yapılan bildirimlerle oluşan ürün bazlı stok. İTS stok sorgusu sunmadığı için İTS'teki tüm kayıtlar burada görünmez.</div>
  </div>
  <a href="{{ request()->fullUrlWithQuery(['excel' => 1, 'page' => null]) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel'e Aktar</a>
</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<nav class="sl-sekme">
  @foreach($sekmeler as $d => [$ad, $ikon])
  <a class="{{ $durum === $d ? 'aktif' : '' }}" href="{{ route('stok.liste', ['durum' => $d]) }}"><i class="bi {{ $ikon }} me-1"></i>{{ $ad }} <span class="badge rounded-pill {{ $durum === $d ? 'text-bg-primary' : 'text-bg-light border' }}">{{ number_format($sayilar[$d] ?? 0, 0, ',', '.') }}</span></a>
  @endforeach
</nav>

<div class="sl-kart p-3 mb-3">
  <form class="row g-2 align-items-end" method="get">
    <input type="hidden" name="durum" value="{{ $durum }}">@if($sirala)<input type="hidden" name="sirala" value="{{ $sirala }}"><input type="hidden" name="yon" value="{{ $yon }}">@endif
    <div class="col-lg-6">
      <label class="form-label small text-muted mb-1">Ara</label>
      <div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input name="ara" value="{{ $ara }}" class="form-control" placeholder="İlaç adı, GTIN, seri no veya parti no" autofocus></div>
    </div>
    @if($lokasyonlar || $lokasyon !== '')
    <div class="col-lg-3 col-md-6">
      <label class="form-label small text-muted mb-1">Lokasyon</label>
      <select name="lokasyon" class="form-select" onchange="this.form.submit()"><option value="">Tüm lokasyonlar</option><option value="-" @selected($lokasyon === '-')>Lokasyonsuz</option>@foreach($lokasyonlar as $l)<option @selected($lokasyon === $l)>{{ $l }}</option>@endforeach</select>
    </div>
    @endif
    <div class="col-auto d-flex gap-2">
      <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filtrele</button>
      @if($ara !== '' || $lokasyon !== '')<a class="btn btn-outline-secondary" href="{{ route('stok.liste', ['durum' => $durum]) }}">Temizle</a>@endif
    </div>
    <div class="col text-end text-muted small align-self-center"><strong class="text-dark">{{ number_format($kalemler->total(), 0, ',', '.') }}</strong> ürün listeleniyor</div>
  </form>
</div>

<form method="post" action="{{ route('stok.toplu') }}" id="toplu" onsubmit="return topluOnay(event)">@csrf
<div class="sl-kart p-3 mb-3">
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="sl-baslik"><span class="badge text-bg-dark me-1">1</span>Bildirim türü</div>
      <input type="hidden" name="tur" id="tur" value="{{ $islemler[0][0] }}">
      <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($islemler as $i => [$kod, $ad, $ikon, $renk])
        <button type="button" class="btn {{ $i ? 'btn-outline-' : 'btn-' }}{{ $renk }} islem-btn {{ $i ? '' : 'active' }}" data-tur="{{ $kod }}" data-ad="{{ $ad }}" data-renk="{{ $renk }}" data-ikon="{{ $ikon }}" aria-pressed="{{ $i ? 'false' : 'true' }}"><i class="bi {{ $i ? $ikon : 'bi-check-circle-fill' }} me-1"></i>{{ $ad }}</button>
        @endforeach
      </div>
      <div id="glnAlan">@include('cari._secici', ['hedef' => 'karsi_gln_input'])<input id="karsi_gln_input" name="karsi_gln" class="form-control form-control-sm" maxlength="13" placeholder="Karşı paydaş GLN (13 hane)"></div>
      <div id="sebepAlan" class="d-none"><select name="sebep" class="form-select form-select-sm"><option value="">Sebep seçin</option>@foreach($sebepler as $k => $ad)<option value="{{ $k }}">{{ $k }} - {{ $ad }}</option>@endforeach</select></div>
    </div>
    <div class="col-lg-4">
      <div class="sl-baslik"><span class="badge text-bg-dark me-1">2</span>Hızlı seçim (SKT'ye göre)</div>
      <div class="d-flex flex-column gap-2">
        @if($sayfaIlaclar->count() > 1)<select id="otoIlac" class="form-select form-select-sm">@foreach($sayfaIlaclar as $ad => $adet)<option value="{{ $ad }}">{{ $ad }} ({{ $adet }} ürün)</option>@endforeach</select>@endif
        <div class="input-group input-group-sm">
          <input type="number" id="otoAdet" min="1" max="100" class="form-control" placeholder="Adet (en fazla 100)">
          <button type="button" class="btn btn-success" id="otoSec"><i class="bi bi-magic me-1"></i>SKT'si yakın olanları seç</button>
        </div>
        <div class="small text-muted" id="otoMesaj">Adede göre otomatik seç (en fazla 100): önce ilacı aratın, adedi yazın. SKT'si en yakın partiden başlanır, süresi geçenler atlanır.</div>
      </div>
    </div>
  </div>
  @if($lokVar)
  <hr class="my-3">
  <div class="sl-baslik"><i class="bi bi-geo-alt me-1"></i>Lokasyon (yalnızca yerel kayıt, İTS'e bildirilmez)</div>
  <div class="d-flex flex-wrap gap-2">
    <input name="lokasyon" list="lokOneri" class="form-control form-control-sm" style="max-width:280px" placeholder="Reyon / raf (örn. Reyon A Raf 3)" maxlength="80">
    <datalist id="lokOneri">@foreach($lokasyonlar as $l)<option value="{{ $l }}">@endforeach</datalist>
    <button class="btn btn-sm btn-outline-primary text-nowrap" formaction="{{ route('stok.lokasyon') }}" data-yerel="1"><i class="bi bi-geo-alt me-1"></i>Lokasyon Ata</button>
    <button class="btn btn-sm btn-warning text-nowrap" formaction="{{ route('stok.lokasyon') }}" name="lokasyon" value="{{ \App\Models\StokKalemi::KARANTINA_ODASI }}" data-yerel="1"><i class="bi bi-shield-exclamation me-1"></i>Karantinaya Al</button>
  </div>
  @endif
</div>

<div class="sl-kart overflow-hidden">
<div class="table-responsive"><table class="table table-hover table-sm sl-tablo align-middle mb-0">
<thead><tr>
  <th style="width:2.5rem" class="ps-3"><input class="form-check-input" type="checkbox" id="hepsi" title="Sayfadakilerin tümünü seç"></th>
  <th>{!! $bas('ilac', 'İlaç') !!}</th><th>Karekod</th><th>Parti</th><th>{!! $bas('skt', 'SKT') !!}</th><th>{!! $bas('lokasyon', 'Lokasyon') !!}</th></tr></thead>
<tbody>
@forelse($kalemler as $k)
@php
  $kalan = $k->skt ? (int) $bugun->diffInDays($k->skt->copy()->startOfDay(), false) : null;
@endphp
<tr data-gtin="{{ $k->gtin }}" data-ad="{{ $adOf($k->gtin) }}" data-skt="{{ $k->skt?->toDateString() }}" data-parti="{{ $k->bn }}">
  <td class="ps-3"><input class="form-check-input sec" type="checkbox" name="secili[]" value="{{ $k->id }}"></td>
  <td><div class="fw-semibold">{{ $adlar[$k->gtin] ?? '—' }}</div><div class="small text-muted font-monospace">{{ $k->gtin }}</div></td>
  <td class="font-monospace small text-break" style="max-width:300px">{{ str_replace("\x1d", '', \App\Http\Controllers\StokController::karekodHam($k)) }}</td>
  <td>{{ $k->bn ?: '—' }}</td>
  <td class="text-nowrap">@if($k->skt){{ $k->skt->format('d.m.Y') }}
    @if($kalan < 0)<span class="badge text-bg-danger ms-1">Süresi geçti</span>@elseif($kalan <= 90)<span class="badge text-bg-warning ms-1">{{ $kalan }} gün</span>@endif
    @else—@endif</td>
  <td>@if($k->lokasyon === $redOdasi)<span class="badge text-bg-danger">{{ $k->lokasyon }}</span>@elseif($k->lokasyon === \App\Models\StokKalemi::KARANTINA_ODASI)<span class="badge text-bg-warning">{{ $k->lokasyon }}</span>@elseif($k->lokasyon)<span class="badge text-bg-light border">{{ $k->lokasyon }}</span>@else<span class="text-muted">—</span>@endif</td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Bu görünümde ürün yok.</td></tr>
@endforelse
</tbody></table></div>
</div>

<div class="sl-alt"><div class="sl-kart p-2 px-3 d-flex flex-wrap align-items-center gap-3 shadow">
  <div><i class="bi bi-check2-square me-1"></i><strong id="sayi">0</strong> ürün seçildi</div>
  <button type="button" class="btn btn-sm btn-link text-decoration-none" id="otoTemizle">Seçimi temizle</button>
  <div class="ms-auto d-flex align-items-center gap-2"><span class="small text-muted d-none d-md-inline">Seçimler yalnızca bu sayfadaki ürünleri kapsar.</span>
    <button class="btn btn-dark px-4" id="uygula"><i class="bi bi-send-check me-1"></i><span id="uygulaAd">Bildir</span></button></div>
</div></div>
</form>
<div class="mt-3">{{ $kalemler->links() }}</div>

<script>
function say(){document.getElementById('sayi').textContent=document.querySelectorAll('.sec:checked').length}
document.getElementById('hepsi').addEventListener('click',function(){document.querySelectorAll('.sec').forEach(c=>c.checked=this.checked);say()});
document.querySelectorAll('.sl-tablo tbody tr[data-gtin]').forEach(r=>r.addEventListener('click',function(e){
 if(e.target.closest('a,input,button'))return;const c=r.querySelector('.sec');c.checked=!c.checked;say()}));
document.querySelectorAll('.sec').forEach(c=>c.addEventListener('change',say));
function islemSec(b){
 document.getElementById('tur').value=b.dataset.tur;
 document.querySelectorAll('.islem-btn').forEach(x=>{const a=x===b,r=x.dataset.renk;
  x.classList.toggle('active',a);x.classList.toggle('btn-'+r,a);x.classList.toggle('btn-outline-'+r,!a);
  x.setAttribute('aria-pressed',a);x.querySelector('i').className='bi me-1 '+(a?'bi-check-circle-fill':x.dataset.ikon)});
 const u=document.getElementById('uygula');u.className='btn px-4 btn-'+b.dataset.renk;document.getElementById('uygulaAd').textContent=b.dataset.ad+' Bildir';
 alanlar()}
document.querySelectorAll('.islem-btn').forEach(b=>b.addEventListener('click',()=>islemSec(b)));
document.getElementById('otoTemizle').addEventListener('click',function(){document.querySelectorAll('.sec').forEach(c=>c.checked=false);document.getElementById('hepsi').checked=false;say();document.getElementById('otoMesaj').textContent='Seçim temizlendi.'});
document.getElementById('otoSec').addEventListener('click',function(){
 const n=parseInt(document.getElementById('otoAdet').value,10),m=document.getElementById('otoMesaj');
 if(!n||n<1){m.textContent='Geçerli bir adet girin.';return}
 if(n>100){document.getElementById('otoAdet').value=100;m.textContent='Tek seferde en fazla 100 ürün seçilebilir; adet 100 olarak ayarlandı, tekrar basın.';return}
 const ilacSel=document.getElementById('otoIlac');
 const bugun=new Date().toISOString().slice(0,10);
 let satirlar=[...document.querySelectorAll('tbody tr[data-gtin]')];
 if(ilacSel)satirlar=satirlar.filter(r=>r.dataset.ad===ilacSel.value);
 satirlar=satirlar.filter(r=>!r.dataset.skt||r.dataset.skt>=bugun);
 satirlar.sort((a,b)=>(a.dataset.skt||'9999').localeCompare(b.dataset.skt||'9999'));
 document.querySelectorAll('.sec').forEach(c=>c.checked=false);
 const secilen=satirlar.slice(0,n);
 secilen.forEach(r=>r.querySelector('.sec').checked=true);
 say();
 const partiler=new Set(secilen.map(r=>r.dataset.parti||'—'));
 m.textContent=secilen.length<n?('Uyarı: yalnızca '+secilen.length+' uygun ürün bulundu ve işaretlendi (istenen '+n+').'):(secilen.length+' ürün işaretlendi ('+[...partiler].join(', ')+' partisinden).');
});
function alanlar(){const t=document.getElementById('tur').value;
 document.getElementById('glnAlan').classList.toggle('d-none',!['satis','iade','devir'].includes(t));
 document.getElementById('sebepAlan').classList.toggle('d-none',!['deaktive','deaktive_iptal'].includes(t))}
function topluOnay(e){const n=document.querySelectorAll('.sec:checked').length;
 if(!n){alert('Önce ürün seçin.');return false}
 if(e&&e.submitter&&e.submitter.dataset.yerel)return true;
 const t=document.querySelector('.islem-btn.active');
 return confirm(n+' ürün için "'+t.dataset.ad+'" bildirimi İTS\'e gönderilecek. Onaylıyor musunuz?')}
alanlar();islemSec(document.querySelector('.islem-btn.active'));
</script>
@endsection
