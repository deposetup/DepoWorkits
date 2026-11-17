@extends('layouts.app')
@section('baslik', $oda)
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <h4 class="mb-0"><i class="bi {{ $karantina ? 'bi-shield-exclamation' : 'bi-archive' }} me-1"></i>{{ $oda }}</h4>
  <a class="btn btn-success" href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
</div>
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
<div class="d-flex gap-3 mb-2 small text-muted"><span>Ürün çeşidi: <strong>{{ $urunler->count() }}</strong></span><span>Toplam adet: <strong>{{ $toplam }}</strong></span></div>
<div class="table-responsive"><table class="table table-sm bg-white align-middle">
<thead><tr><th>GTIN</th><th>İlaç</th><th class="text-end">Adet</th><th>Parti / SKT / Adet</th><th></th></tr></thead><tbody>
@forelse($urunler as $u)
<tr><td class="font-monospace">{{ $u->gtin }}</td><td>{{ $u->ad ?? '—' }}</td><td class="text-end fw-semibold">{{ $u->adet }}</td>
<td class="small">@foreach($u->partiler as $p)<div><span class="font-monospace">{{ $p->bn ?: '—' }}</span> · {{ $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '—' }} · <b>{{ $p->adet }}</b> adet</div>@endforeach</td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('stok.liste', ['ara' => $u->gtin, 'lokasyon' => $oda]) }}"><i class="bi bi-upc-scan me-1"></i>Karekodlar</a></td></tr>
@empty<tr><td colspan="5" class="text-muted">{{ $karantina ? 'Karantina Odası boş.' : 'Red Odası boş: SKT\'si geçmiş ürün yok.' }}</td></tr>@endforelse
</tbody></table></div>
@endsection
