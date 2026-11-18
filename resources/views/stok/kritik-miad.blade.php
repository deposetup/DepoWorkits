@extends('layouts.app')
@section('baslik', 'Kritik / Miad')
@section('icerik')
<div class="card mb-4"><div class="card-body">
<h5 class="mb-3">Eşik Ayarı</h5>
@if(session('ok'))<div class="alert alert-success py-2">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('kritik-miad.kaydet') }}" class="row g-2 align-items-end">@csrf
  <div class="col-12"><label class="form-label">İlaç</label>
    <input name="ilac" value="{{ old('ilac') }}" list="ilacOneri" class="form-control" placeholder="İlaç adını ya da barkodunu yazın; yazdıkça öneriler çıkar" autocomplete="off" required>
    <datalist id="ilacOneri">@foreach($adlar as $g => $ad)<option value="{{ $ad }} ({{ $g }})">@endforeach</datalist>
    <div class="form-text">Listeden seçebilir ya da yalnızca barkodu yazabilirsiniz.</div></div>
  <div class="col-md-3"><label class="form-label">Kritik stok</label><input type="number" name="kritik_stok" min="0" value="0" class="form-control"></div>
  <div class="col-md-3"><label class="form-label">Miad uyarı (gün)</label><input type="number" name="miad_gun" min="1" value="90" class="form-control"></div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Kaydet</button></div>
</form>
</div></div>
<style>
  .yazdir-alan, .yazdir-baslik, .yazdir-alt { display: none; }
  @media print {
    @page { size: A4; margin: 12mm 12mm 16mm; }
    body * { visibility: hidden !important; }
    body[data-yazdir="gecen"] #yazdirGecen, body[data-yazdir="miad"] #yazdirMiad { display: block; position: absolute; left: 0; top: 0; width: 100%; }
    body[data-yazdir="gecen"] #yazdirGecen *, body[data-yazdir="miad"] #yazdirMiad * { visibility: visible !important; }
    .yazdir-baslik { display: block; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
    .yazdir-baslik .musteri { font-size: 15px; font-weight: 700; }
    .yazdir-baslik .detay { font-size: 11px; color: #333; }
    .yazdir-baslik .oda { font-size: 13px; font-weight: 600; margin-top: 6px; }
    .yazdir-alt { display: block; position: fixed; left: 0; right: 0; bottom: 0; text-align: center; font-size: 10px; color: #555; border-top: 1px solid #999; padding-top: 4px; }
    .yazdir-alan table { table-layout: fixed; width: 100%; font-size: 11px; border-collapse: collapse; }
    .yazdir-alan th, .yazdir-alan td { border: 1px solid #999 !important; padding: 4px 6px !important; vertical-align: top; word-wrap: break-word; }
    .yazdir-alan thead { display: table-header-group; }
    .yazdir-alan tr { break-inside: avoid; }
    .yazdir-alan .col-gtin { width: 16%; } .yazdir-alan .col-ilac { width: 32%; } .yazdir-alan .col-recete { width: 12%; } .yazdir-alan .col-adet { width: 8%; } .yazdir-alan .col-parti { width: 32%; }
    .yazdir-alan .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
<script>function yazdirBolum(b){document.body.dataset.yazdir=b;window.print();}window.addEventListener('afterprint',function(){delete document.body.dataset.yazdir;});</script>
<div class="row g-4">
<div class="col-lg-6"><h5>Kritik Stok</h5>
<table class="table table-sm bg-white"><thead><tr><th>İlaç</th><th class="text-end">Stok</th><th class="text-end">Eşik</th></tr></thead><tbody>
@forelse($kritik as $k)<tr class="table-warning"><td>{{ $k['ad'] }}<div class="small text-muted font-monospace">{{ $k['gtin'] }}</div></td><td class="text-end">{{ $k['adet'] }}</td><td class="text-end">{{ $k['esik'] }}</td></tr>
@empty<tr><td colspan="3" class="text-muted">Kritik seviyede ilaç yok.</td></tr>@endforelse</tbody></table></div>
<div class="col-lg-6">
<div class="d-flex justify-content-between align-items-center gap-2"><h5 class="text-danger mb-0">Süresi Geçenler <span class="badge text-bg-danger">{{ number_format($gecenAdet, 0, ',', '.') }} adet</span></h5>
<button type="button" class="btn btn-sm btn-outline-dark" onclick="yazdirBolum('gecen')" @disabled(! count($gecen))><i class="bi bi-printer me-1"></i>Yazdır</button></div>
<p class="small text-muted mb-1">SKT'si geçmiş, stokta görünen ürünler Red Odası'na alınır. <a href="{{ route('stok.red-odasi') }}">Red Odası'nı aç</a></p>
<table class="table table-sm bg-white"><thead><tr><th>İlaç</th><th>Reçete</th><th>Parti</th><th>SKT</th><th class="text-end">Adet</th><th class="text-end">Geçen Gün</th></tr></thead><tbody>
@forelse(array_slice($gecen, 0, 200) as $m)<tr class="table-danger"><td>{{ $m['ad'] ?? '—' }}<div class="small text-muted font-monospace">{{ $m['gtin'] }}</div></td><td>{!! \App\Models\IlacRecete::rozet($receteler[$m['gtin']] ?? null, true) ?: '—' !!}</td><td class="font-monospace small">{{ $m['bn'] ?: '—' }}</td><td>{{ $m['skt']->format('d.m.Y') }}</td><td class="text-end">{{ $m['adet'] }}</td><td class="text-end">{{ abs($m['kalan']) }}</td></tr>
@empty<tr><td colspan="6" class="text-muted">Süresi geçmiş ürün yok.</td></tr>@endforelse</tbody></table>
@if(count($gecen) > 200)<div class="small text-muted mb-3">İlk 200 parti gösteriliyor ({{ count($gecen) }} parti). Tamamı için <a href="{{ route('stok.red-odasi') }}">Red Odası</a> ya da Excel çıktısı.</div>@endif
<div class="d-flex justify-content-between align-items-center gap-2 mt-3"><h5 class="mb-0">Miadı Yaklaşan <span class="badge text-bg-warning">{{ number_format($yaklasanAdet, 0, ',', '.') }} adet</span></h5>
<button type="button" class="btn btn-sm btn-outline-dark" onclick="yazdirBolum('miad')" @disabled(! count($miad))><i class="bi bi-printer me-1"></i>Yazdır</button></div>
<table class="table table-sm bg-white"><thead><tr><th>İlaç</th><th>Reçete</th><th>Parti</th><th>SKT</th><th class="text-end">Adet</th><th class="text-end">Kalan Gün</th></tr></thead><tbody>
@forelse(array_slice($miad, 0, 200) as $m)<tr class="table-warning"><td>{{ $m['ad'] ?? '—' }}<div class="small text-muted font-monospace">{{ $m['gtin'] }}</div></td><td>{!! \App\Models\IlacRecete::rozet($receteler[$m['gtin']] ?? null, true) ?: '—' !!}</td><td class="font-monospace small">{{ $m['bn'] ?: '—' }}</td><td>{{ $m['skt']->format('d.m.Y') }}</td><td class="text-end">{{ $m['adet'] }}</td><td class="text-end">{{ $m['kalan'] }}</td></tr>
@empty<tr><td colspan="6" class="text-muted">Miadı yaklaşan ürün yok.</td></tr>@endforelse</tbody></table>
@if(count($miad) > 200)<div class="small text-muted">İlk 200 parti gösteriliyor ({{ count($miad) }} parti).</div>@endif
</div>
</div>

@php $t = auth()->user()->tenant; @endphp
@foreach([['id' => 'yazdirGecen', 'baslik' => 'Süresi Geçenler', 'liste' => $gecen, 'tip' => 'gecen'], ['id' => 'yazdirMiad', 'baslik' => 'Miadı Yaklaşanlar', 'liste' => $miad, 'tip' => 'miad']] as $b)
@php $urunler = collect($b['liste'])->groupBy('gtin'); @endphp
<div id="{{ $b['id'] }}" class="yazdir-alan">
<div class="yazdir-baslik">
  <div class="musteri">{{ $t?->unvan }}</div>
  <div class="detay">@if($t?->gln)GLN: {{ $t->gln }}@endif @if($t?->il || $t?->ilce) · {{ collect([$t->ilce, $t->il])->filter()->implode(' / ') }}@endif @if($t?->telefon) · Tel: {{ $t->telefon }}@endif</div>
  <div class="oda">{{ $b['baslik'] }} · {{ now()->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</div>
</div>
<div class="d-flex gap-3 mb-2 small"><span>Ürün çeşidi: <strong>{{ $urunler->count() }}</strong></span><span>Toplam adet: <strong>{{ $urunler->sum(fn ($l) => $l->sum('adet')) }}</strong></span></div>
<table class="table table-sm bg-white align-middle">
<thead><tr><th class="col-gtin">GTIN</th><th class="col-ilac">İlaç</th><th class="col-recete">Reçete</th><th class="col-adet text-end">Adet</th><th class="col-parti">Parti / SKT / {{ $b['tip'] === 'gecen' ? 'Geçen Gün' : 'Kalan Gün' }}</th></tr></thead><tbody>
@foreach($urunler as $gtin => $l)
<tr><td class="font-monospace">{{ $gtin }}</td><td>{{ $l->first()['ad'] ?? '—' }}</td><td>{!! \App\Models\IlacRecete::rozet($receteler[$gtin] ?? null, true) ?: '—' !!}</td><td class="text-end fw-semibold">{{ $l->sum('adet') }}</td>
<td class="small">@foreach($l as $m)<div><span class="font-monospace">{{ $m['bn'] ?: '—' }}</span> · {{ $m['skt']->format('d.m.Y') }} · {{ abs($m['kalan']) }} gün {{ $b['tip'] === 'gecen' ? 'geçti' : 'kaldı' }}</div>@endforeach</td></tr>
@endforeach
</tbody></table>
<div class="yazdir-alt">Bu çıktı its.depowork.com.tr aracılığı ile hazırlanmıştır.</div>
</div>
@endforeach
@endsection
