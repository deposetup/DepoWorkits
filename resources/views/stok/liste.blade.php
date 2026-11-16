@extends('layouts.app')
@section('baslik', 'Stok Listesi')
@section('icerik')
<h4>Stok Listesi (Karekod)</h4>
<div class="alert alert-secondary small">Liste, bu portal üzerinden yapılan bildirimlerden (Mal Alım/Kabul vb.) oluşan stoktur. İTS stok sorgusu sunmadığı için İTS'teki tüm kayıtlar burada görünmez.</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
  <div class="btn-group">
    @php $sarfVar = auth()->user()->tenant?->sarfYapabilir() ?? true; $devirVar = auth()->user()->tenant?->devirYapabilir() ?? true; @endphp
    @foreach(array_filter(['stokta' => 'Stokta', 'satis' => 'Satıldı (alıcı bekliyor)', 'devir' => 'Devredildi', 'deaktive' => 'Deaktive', 'sarf' => 'Sarf'], fn ($k) => ($sarfVar || $k !== 'sarf') && ($devirVar || $k !== 'devir'), ARRAY_FILTER_USE_KEY) as $d => $ad)
    <a class="btn btn-sm {{ $durum === $d ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('stok.liste', ['durum' => $d]) }}">{{ $ad }}</a>
    @endforeach
  </div>
  <form class="d-flex gap-2" method="get"><input type="hidden" name="durum" value="{{ $durum }}">@if($sirala)<input type="hidden" name="sirala" value="{{ $sirala }}"><input type="hidden" name="yon" value="{{ $yon }}">@endif
    @if($lokasyonlar || $lokasyon !== '')<select name="lokasyon" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Tüm lokasyonlar</option><option value="-" @selected($lokasyon === '-')>Lokasyonsuz</option>@foreach($lokasyonlar as $l)<option @selected($lokasyon === $l)>{{ $l }}</option>@endforeach</select>@endif
    <input name="ara" value="{{ $ara }}" class="form-control form-control-sm" placeholder="İlaç adı, GTIN, seri veya parti"><button class="btn btn-sm btn-outline-secondary">Ara</button></form>
  <span class="text-muted small">{{ $kalemler->total() }} ürün</span>
  <a href="{{ request()->fullUrlWithQuery(['excel' => 1, 'page' => null]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
</div>

<form method="post" action="{{ route('stok.toplu') }}" id="toplu" onsubmit="return topluOnay(event)">@csrf
<div class="card mb-3"><div class="card-body py-2 row g-2 align-items-center">
  @php
    $islemler = match ($durum) {
      'stokta' => array_filter([
        ['satis', 'Satış', 'bi-cart-check', 'success'],
        $sarfVar ? ['sarf', 'Hastane Sarf', 'bi-hospital', 'info'] : null,
        $devirVar ? ['devir', 'Mal Devir', 'bi-arrow-left-right', 'primary'] : null,
        ['iade', 'Ürün İade', 'bi-arrow-return-left', 'warning'],
        ['deaktive', 'Deaktive', 'bi-slash-circle', 'dark'],
      ]),
      'satis' => [['satis_iptal', 'Satış İptal', 'bi-cart-x', 'danger']],
      'deaktive' => [['deaktive_iptal', 'Deaktivasyon İptal', 'bi-check-circle', 'danger']],
      'devir' => [['devir_iptal', 'Mal Devir İptal', 'bi-x-circle', 'danger']],
      default => [['sarf_iptal', 'Sarf İptal', 'bi-arrow-counterclockwise', 'danger']],
    };
    $islemler = array_values($islemler);
  @endphp
  <div class="col-12"><div class="small text-muted mb-1">Yapılacak bildirimi seçin:</div>
    <input type="hidden" name="tur" id="tur" value="{{ $islemler[0][0] }}">
    <div class="d-flex flex-wrap gap-2" id="islemBtn">
    @foreach($islemler as $i => [$kod, $ad, $ikon, $renk])
      <button type="button" class="btn btn-{{ $renk }} islem-btn {{ $i ? 'opacity-50' : 'active' }}" data-tur="{{ $kod }}" data-ad="{{ $ad }}" data-renk="{{ $renk }}" aria-pressed="{{ $i ? 'false' : 'true' }}"><i class="bi {{ $ikon }} me-1"></i>{{ $ad }}</button>
    @endforeach
    </div></div>
  <div class="col-md-4" id="glnAlan">@include('cari._secici', ['hedef' => 'karsi_gln_input'])<input id="karsi_gln_input" name="karsi_gln" class="form-control form-control-sm" maxlength="13" placeholder="Karşı paydaş GLN (13 hane)"></div>
  <div class="col-md-3 d-none" id="sebepAlan"><select name="sebep" class="form-select form-select-sm"><option value="">Sebep seçin</option>@foreach($sebepler as $k => $ad)<option value="{{ $k }}">{{ $k }} - {{ $ad }}</option>@endforeach</select></div>
  <div class="col-auto"><button class="btn btn-dark" id="uygula"><i class="bi bi-send-check me-1"></i><span id="uygulaAd">Seçilenlere Uygula</span> (<span id="sayi">0</span>)</button></div>
  @if($durum === 'stokta' && \App\Models\StokKalemi::lokasyonVar())
  <div class="col-auto d-flex gap-1"><input name="lokasyon" list="lokOneri" class="form-control form-control-sm" placeholder="Reyon / raf (örn. Reyon A Raf 3)" maxlength="80" form="toplu"><datalist id="lokOneri">@foreach($lokasyonlar as $l)<option value="{{ $l }}">@endforeach</datalist>
    <button class="btn btn-sm btn-outline-primary text-nowrap" formaction="{{ route('stok.lokasyon') }}" data-yerel="1"><i class="bi bi-geo-alt me-1"></i>Lokasyon Ata</button>
    <button class="btn btn-sm btn-warning text-nowrap" formaction="{{ route('stok.lokasyon') }}" name="lokasyon" value="{{ \App\Models\StokKalemi::KARANTINA_ODASI }}" data-yerel="1"><i class="bi bi-shield-exclamation me-1"></i>Karantinaya Al</button></div>
  @endif
  <div class="col-auto small text-muted">Bu sayfadaki ürünler seçilir; başka sayfa için ayrıca uygulayın.</div>
</div></div>

@php
  $sayfaIlaclar = $kalemler->getCollection()->pluck('gtin')->unique()->values();
@endphp
<div class="card mb-2 border-success"><div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
  <i class="bi bi-magic text-success"></i><strong class="small">Adede göre otomatik seç</strong>
  @if($sayfaIlaclar->count() > 1)
  <select id="otoIlac" class="form-select form-select-sm w-auto" style="max-width:320px">@foreach($sayfaIlaclar as $g)<option value="{{ $g }}">{{ $adlar[$g] ?? $g }}</option>@endforeach</select>
  @endif
  <input type="number" id="otoAdet" min="1" max="1000" class="form-control form-control-sm" style="width:110px" placeholder="Adet">
  <button type="button" class="btn btn-sm btn-success" id="otoSec"><i class="bi bi-check2-all me-1"></i>SKT'si en yakın olanlardan seç</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="otoTemizle">Seçimi temizle</button>
  <span class="small text-muted" id="otoMesaj">Önce ilaç adını aratın, adedi yazın; SKT'si en yakın partilerden başlayarak o kadar ürün işaretlenir (SKT'si geçmişler atlanır). Sonra yukarıdan bildirimi seçip uygulayın.</span>
</div></div>
<table class="table table-sm bg-white align-middle"><thead><tr>
  <th><input type="checkbox" id="hepsi" onclick="document.querySelectorAll('.sec').forEach(c=>c.checked=this.checked);say()"></th>
  @php
  $bas = function ($alan, $ad) use ($sirala, $yon) {
    $yeni = $sirala === $alan && $yon === 'asc' ? 'desc' : 'asc';
    $ok = $sirala === $alan ? ($yon === 'asc' ? ' <i class="bi bi-sort-up"></i>' : ' <i class="bi bi-sort-down"></i>') : ' <i class="bi bi-arrow-down-up text-muted small"></i>';
    return '<a class="text-decoration-none text-reset" href="'.e(request()->fullUrlWithQuery(['sirala' => $alan, 'yon' => $yeni, 'page' => null])).'">'.$ad.$ok.'</a>';
  };
@endphp
  <th>{!! $bas('ilac', 'İlaç') !!}</th><th>Karekod</th><th>Parti</th><th>{!! $bas('skt', 'SKT') !!}</th><th>{!! $bas('lokasyon', 'Lokasyon') !!}</th></tr></thead><tbody>
@forelse($kalemler as $k)
<tr data-gtin="{{ $k->gtin }}" data-skt="{{ $k->skt?->toDateString() }}"><td><input type="checkbox" class="sec" name="secili[]" value="{{ $k->id }}" onclick="say()"></td>
<td>{{ $adlar[$k->gtin] ?? '—' }}</td>
<td class="font-monospace small">{{ str_replace("\x1d", '', \App\Http\Controllers\StokController::karekodHam($k)) }}</td>
<td>{{ $k->bn ?: '—' }}</td><td>{{ $k->skt?->format('d.m.Y') ?? '—' }}</td><td>@if($k->lokasyon === $redOdasi)<span class="badge text-bg-danger">{{ $k->lokasyon }}</span>@else{{ $k->lokasyon ?: '—' }}@endif</td></tr>
@empty<tr><td colspan="6" class="text-muted">Bu durumda ürün yok.</td></tr>@endforelse
</tbody></table>
</form>
{{ $kalemler->links() }}
<script>
function say(){document.getElementById('sayi').textContent=document.querySelectorAll('.sec:checked').length}
document.querySelectorAll('.islem-btn').forEach(b=>b.addEventListener('click',function(){
 document.getElementById('tur').value=b.dataset.tur;
 document.querySelectorAll('.islem-btn').forEach(x=>{const a=x===b;x.classList.toggle('active',a);x.classList.toggle('opacity-50',!a);x.setAttribute('aria-pressed',a)});
 const u=document.getElementById('uygula');u.className='btn btn-'+b.dataset.renk;document.getElementById('uygulaAd').textContent=b.dataset.ad+' Bildir';
 alanlar()}));
document.getElementById('otoTemizle').addEventListener('click',function(){document.querySelectorAll('.sec').forEach(c=>c.checked=false);say();document.getElementById('otoMesaj').textContent='Seçim temizlendi.'});
document.getElementById('otoSec').addEventListener('click',function(){
 const n=parseInt(document.getElementById('otoAdet').value,10),m=document.getElementById('otoMesaj');
 if(!n||n<1){m.textContent='Geçerli bir adet girin.';return}
 const ilacSel=document.getElementById('otoIlac');
 const bugun=new Date().toISOString().slice(0,10);
 let satirlar=[...document.querySelectorAll('tbody tr[data-gtin]')];
 if(ilacSel)satirlar=satirlar.filter(r=>r.dataset.gtin===ilacSel.value);
 satirlar=satirlar.filter(r=>!r.dataset.skt||r.dataset.skt>=bugun);
 satirlar.sort((a,b)=>(a.dataset.skt||'9999').localeCompare(b.dataset.skt||'9999'));
 document.querySelectorAll('.sec').forEach(c=>c.checked=false);
 const secilen=satirlar.slice(0,n);
 secilen.forEach(r=>r.querySelector('.sec').checked=true);
 say();
 const partiler=new Set(secilen.map(r=>r.children[3].textContent.trim()));
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
alanlar();
(function(){const b=document.querySelector('.islem-btn.active');if(b){const u=document.getElementById('uygula');u.className='btn btn-'+b.dataset.renk;document.getElementById('uygulaAd').textContent=b.dataset.ad+' Bildir'}})();
</script>
@endsection
