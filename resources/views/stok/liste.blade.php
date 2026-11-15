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
  <form class="d-flex gap-2" method="get"><input type="hidden" name="durum" value="{{ $durum }}">
    @if($lokasyonlar || $lokasyon !== '')<select name="lokasyon" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Tüm lokasyonlar</option><option value="-" @selected($lokasyon === '-')>Lokasyonsuz</option>@foreach($lokasyonlar as $l)<option @selected($lokasyon === $l)>{{ $l }}</option>@endforeach</select>@endif
    <input name="ara" value="{{ $ara }}" class="form-control form-control-sm" placeholder="İlaç adı, GTIN, seri veya parti"><button class="btn btn-sm btn-outline-secondary">Ara</button></form>
  <span class="text-muted small">{{ $kalemler->total() }} ürün</span>
  <a href="{{ request()->fullUrlWithQuery(['excel' => 1, 'page' => null]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
</div>

<form method="post" action="{{ route('stok.toplu') }}" id="toplu" onsubmit="return topluOnay(event)">@csrf
<div class="card mb-3"><div class="card-body py-2 row g-2 align-items-center">
  <div class="col-auto"><select name="tur" id="tur" class="form-select form-select-sm" onchange="alanlar()">
    @if($durum === 'stokta')
      <option value="satis">Satış</option>@if($sarfVar)<option value="sarf">Hastane Sarf</option>@endif @if($devirVar)<option value="devir">Mal Devir</option>@endif<option value="iade">Ürün İade</option><option value="deaktive">Deaktive</option>
    @elseif($durum === 'satis')<option value="satis_iptal">Satış İptal</option>
    @elseif($durum === 'deaktive')<option value="deaktive_iptal">Deaktivasyon İptal</option>
    @elseif($durum === 'devir')<option value="devir_iptal">Mal Devir İptal</option>
    @else<option value="sarf_iptal">Sarf İptal</option>@endif
  </select></div>
  <div class="col-md-4" id="glnAlan">@include('cari._secici', ['hedef' => 'karsi_gln_input'])<input id="karsi_gln_input" name="karsi_gln" class="form-control form-control-sm" maxlength="13" placeholder="Karşı paydaş GLN (13 hane)"></div>
  <div class="col-md-3 d-none" id="sebepAlan"><select name="sebep" class="form-select form-select-sm"><option value="">Sebep seçin</option>@foreach($sebepler as $k => $ad)<option value="{{ $k }}">{{ $k }} - {{ $ad }}</option>@endforeach</select></div>
  <div class="col-auto"><button class="btn btn-sm btn-danger">Seçilenlere Uygula (<span id="sayi">0</span>)</button></div>
  @if($durum === 'stokta' && \App\Models\StokKalemi::lokasyonVar())
  <div class="col-auto d-flex gap-1"><input name="lokasyon" list="lokOneri" class="form-control form-control-sm" placeholder="Reyon / raf (örn. Reyon A Raf 3)" maxlength="80" form="toplu"><datalist id="lokOneri">@foreach($lokasyonlar as $l)<option value="{{ $l }}">@endforeach</datalist>
    <button class="btn btn-sm btn-outline-primary text-nowrap" formaction="{{ route('stok.lokasyon') }}" data-yerel="1"><i class="bi bi-geo-alt me-1"></i>Lokasyon Ata</button></div>
  @endif
  <div class="col-auto small text-muted">Bu sayfadaki ürünler seçilir; başka sayfa için ayrıca uygulayın.</div>
</div></div>

<table class="table table-sm bg-white align-middle"><thead><tr>
  <th><input type="checkbox" id="hepsi" onclick="document.querySelectorAll('.sec').forEach(c=>c.checked=this.checked);say()"></th>
  <th>İlaç</th><th>Karekod</th><th>Parti</th><th>SKT</th><th>Lokasyon</th></tr></thead><tbody>
@forelse($kalemler as $k)
<tr><td><input type="checkbox" class="sec" name="secili[]" value="{{ $k->id }}" onclick="say()"></td>
<td>{{ $adlar[$k->gtin] ?? '—' }}</td>
<td class="font-monospace small">{{ \App\Http\Controllers\StokController::karekod($k) }}</td>
<td>{{ $k->bn ?: '—' }}</td><td>{{ $k->skt?->format('d.m.Y') ?? '—' }}</td><td>@if($k->lokasyon === $redOdasi)<span class="badge text-bg-danger">{{ $k->lokasyon }}</span>@else{{ $k->lokasyon ?: '—' }}@endif</td></tr>
@empty<tr><td colspan="6" class="text-muted">Bu durumda ürün yok.</td></tr>@endforelse
</tbody></table>
</form>
{{ $kalemler->links() }}
<script>
function say(){document.getElementById('sayi').textContent=document.querySelectorAll('.sec:checked').length}
function alanlar(){const t=document.getElementById('tur').value;
 document.getElementById('glnAlan').classList.toggle('d-none',!['satis','iade','devir'].includes(t));
 document.getElementById('sebepAlan').classList.toggle('d-none',!['deaktive','deaktive_iptal'].includes(t))}
function topluOnay(e){const n=document.querySelectorAll('.sec:checked').length;
 if(!n){alert('Önce ürün seçin.');return false}
 if(e&&e.submitter&&e.submitter.dataset.yerel)return true;
 const t=document.getElementById('tur');
 return confirm(n+' ürün için "'+t.options[t.selectedIndex].text+'" bildirimi İTS\'e gönderilecek. Onaylıyor musunuz?')}
alanlar();
</script>
@endsection
