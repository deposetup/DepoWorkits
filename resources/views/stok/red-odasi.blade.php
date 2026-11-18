@extends('layouts.app')
@section('baslik', $oda)
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h4 class="mb-0"><i class="bi {{ $karantina ? 'bi-shield-exclamation' : 'bi-archive' }} me-1"></i>{{ $oda }}</h4>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer me-1"></i>Yazdır</button>
    <a class="btn btn-success" href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
  </div>
</div>
<style>
  .yazdir-baslik, .yazdir-alt { display: none; }
  #yazdirAlan table { table-layout: fixed; width: 100%; }
  @media print {
    @page { size: A4; margin: 12mm 12mm 16mm; }
    body * { visibility: hidden !important; }
    #yazdirAlan, #yazdirAlan * { visibility: visible !important; }
    #yazdirAlan { position: absolute; left: 0; top: 0; width: 100%; }
    .yazdirma { display: none !important; }
    .yazdir-baslik { display: block; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
    .yazdir-baslik .musteri { font-size: 15px; font-weight: 700; }
    .yazdir-baslik .detay { font-size: 11px; color: #333; }
    .yazdir-baslik .oda { font-size: 13px; font-weight: 600; margin-top: 6px; }
    .yazdir-alt { display: block; position: fixed; left: 0; right: 0; bottom: 0; text-align: center; font-size: 10px; color: #555; border-top: 1px solid #999; padding-top: 4px; }
    #yazdirAlan table { font-size: 11px; border-collapse: collapse; }
    #yazdirAlan th, #yazdirAlan td { border: 1px solid #999 !important; padding: 4px 6px !important; vertical-align: top; word-wrap: break-word; }
    #yazdirAlan thead { display: table-header-group; }
    #yazdirAlan tr { break-inside: avoid; }
    #yazdirAlan .col-gtin { width: 17%; } #yazdirAlan .col-adet { width: 9%; } #yazdirAlan .col-ilac { width: 42%; } #yazdirAlan .col-parti { width: 32%; }
  }
</style>
@if($karantina)
<div class="alert alert-warning small">Karantinaya aldığınız ürünler burada listelenir. Ürün eklemek için <a href="{{ route('stok.liste') }}">Stok Listesi</a>'nde ürünleri seçip <b>Karantinaya Al</b> düğmesine basın. Bu işlem yalnızca yerel kayıttır, İTS'e bildirilmez. Karantinadan çıkarmak için ürüne başka bir lokasyon atayın.</div>
@else
<div class="alert alert-info small">Son kullanma tarihi geçmiş, stokta görünen ürünler otomatik olarak <b>Red Odası</b> lokasyonuna alınır. Fiziksel olarak da bu ürünleri ayırın. Ürünleri İTS'ten düşmek için <a href="{{ route('islem', 'deaktive') }}">Deaktive</a> (sebep: Miat) ekranını kullanın.</div>
@endif
@if(! $karantina && $hazir)
@include('islem._ilerleme')
<div class="card mb-3 {{ count($miatIds) ? 'border-danger' : '' }}"><div class="card-body d-flex flex-wrap align-items-center gap-3">
  <div class="flex-grow-1">
    <div class="fw-semibold"><i class="bi bi-slash-circle me-1"></i>Miadı dolan ürünleri İTS'e bildir (Deaktive · Miat)</div>
    <div class="small text-muted">SKT'si geçmiş <b>{{ number_format(count($miatIds), 0, ',', '.') }}</b> ürün otomatik seçilir ve <b>40 - Miat</b> sebebiyle deaktive bildirimi gönderilir. İlerlemeyi yüzde olarak izlersiniz.@if(count($miatIds) >= 5000) Tek seferde en fazla 5.000 ürün işlenir; bittikten sonra tekrar basın.@endif</div>
  </div>
  <button type="button" id="miatBtn" class="btn btn-danger" @disabled(! count($miatIds))><i class="bi bi-send-check me-1"></i>Miadı Dolanları Bildir ({{ number_format(count($miatIds), 0, ',', '.') }})</button>
</div></div>
<script>
document.getElementById('miatBtn').addEventListener('click',function(){
 const ids=@json($miatIds),GRUP=50,gruplar=[];for(let i=0;i<ids.length;i+=GRUP)gruplar.push(ids.slice(i,i+GRUP));
 if(!ids.length)return;
 if(!confirm(ids.length+' ürün için "Deaktive (40 - Miat)" bildirimi İTS\'e gönderilecek ('+gruplar.length+' grup halinde). Bu işlem İTS\'te ürünleri stoktan düşer. Onaylıyor musunuz?'))return;
 topluBildirim({adetler:gruplar.map(g=>g.length),ad:'Deaktive (Miat)',btn:this,sonucUrl:@json(route('islem.sonuc', 'deaktive')),geri:'stok',
  istek:(i,id)=>{const fd=new FormData();fd.set('_token',document.querySelector('#bdSonuc input[name=_token]').value);fd.set('tur','deaktive');fd.set('sebep','40');fd.set('aciklama','Miadı dolan ürün');fd.set('toplu_id',id);
   gruplar[i].forEach(x=>fd.append('secili[]',x));return fetch(@json(route('stok.toplu')),{method:'POST',headers:{'Accept':'application/json'},body:fd})}});
});
</script>
@endif
@unless($hazir)<div class="alert alert-warning">Lokasyon özelliği henüz etkinleştirilmedi (veritabanı güncellemesi bekleniyor).</div>@endunless
@php $t = auth()->user()->tenant; @endphp
<div id="yazdirAlan">
<div class="yazdir-baslik">
  <div class="musteri">{{ $t?->unvan }}</div>
  <div class="detay">@if($t?->gln)GLN: {{ $t->gln }}@endif @if($t?->il || $t?->ilce) · {{ collect([$t->ilce, $t->il])->filter()->implode(' / ') }}@endif @if($t?->telefon) · Tel: {{ $t->telefon }}@endif</div>
  <div class="oda">{{ $oda }} · {{ now()->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</div>
</div>
<div class="d-flex gap-3 mb-2 small text-muted"><span>Ürün çeşidi: <strong>{{ $urunler->count() }}</strong></span><span>Toplam adet: <strong>{{ $toplam }}</strong></span></div>
<div class="table-responsive"><table class="table table-sm bg-white align-middle">
<thead><tr><th class="col-gtin">GTIN</th><th class="col-ilac">İlaç</th><th class="col-adet text-end">Adet</th><th class="col-parti">Parti / SKT<span class="yazdirma"> / Adet</span></th><th class="yazdirma"></th></tr></thead><tbody>
@forelse($urunler as $u)
<tr><td class="font-monospace">{{ $u->gtin }}</td><td>{{ $u->ad ?? '—' }}</td><td class="text-end fw-semibold">{{ $u->adet }}</td>
<td class="small">@foreach($u->partiler as $p)<div><span class="font-monospace">{{ $p->bn ?: '—' }}</span> · {{ $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '—' }}<span class="yazdirma"> · <b>{{ $p->adet }}</b> adet</span></div>@endforeach</td>
<td class="text-end yazdirma"><a class="btn btn-sm btn-outline-primary" href="{{ route('stok.liste', ['ara' => $u->gtin, 'lokasyon' => $oda]) }}"><i class="bi bi-upc-scan me-1"></i>Karekodlar</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">{{ $karantina ? 'Karantina Odası boş.' : 'Red Odası boş: SKT\'si geçmiş ürün yok.' }}</td></tr>@endforelse
</tbody></table></div>
<div class="yazdir-alt">Bu çıktı its.depowork.com.tr aracılığı ile hazırlanmıştır.</div>
</div>
@endsection
