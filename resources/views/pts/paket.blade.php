@extends('layouts.app')
@section('baslik', 'PTS Paketi')
@section('icerik')
<a href="{{ route('pts') }}" class="small">&larr; PTS Sorgu</a>
<h4 class="mt-2">PTS Paketi <small class="text-muted">{{ $paket->transfer_id }}</small></h4>
<div class="mb-3 small">
  @php $cariAdlari = \App\Models\Cari::adlar(auth()->user()->tenant_id); @endphp
  Gönderen: {{ $paket->kaynak_gln ?? '—' }}@if($ad = ($cariAdlari[$paket->kaynak_gln ?? ''] ?? null)) <span class="badge text-bg-primary"><i class="bi bi-person-check"></i> Cari: {{ $ad }}</span>@elseif($paket->kaynak_gln) <a href="{{ route('paydaslar') }}" class="small">(cariye ekle)</a>@endif · Belge No: {{ $paket->belge_no ?? '—' }} ·
  Bütünlük (MD5): {!! is_null($paket->md5_uyumlu) ? '<span class="text-muted">kontrol edilemedi</span>' : ($paket->md5_uyumlu ? '<span class="text-success">uyumlu</span>' : '<span class="text-danger">UYUMSUZ</span>') !!} ·
  Durum: <span class="badge text-bg-{{ $paket->durum === 'mal_alindi' ? 'success' : 'secondary' }}">{{ $paket->durum === 'mal_alindi' ? 'Mal alındı' : ($paket->durum === 'sifirlandi' ? 'Havuzdan çıkarıldı (sıfırlandı)' : 'İndirildi (mal kabul yapılmadı)') }}</span>
</div>
<div class="alert alert-warning small">Paketi indirmek mal kabul sayılmaz. Ürünler stoğa ancak aşağıdaki <b>Mal Alım</b> bildirimi başarılı olunca girer.</div>
@foreach($hatalar as $h)<div class="alert alert-danger">{{ $h }}</div>@endforeach

@if(count($urunler))
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">{{ count($urunler) }} ürün bulundu</h6><a href="{{ route('pts.excel', $paket) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a></div>
@php($grup = collect($urunler)->groupBy('gtin'))
<table class="table table-sm bg-white"><thead><tr><th>GTIN</th><th>Parti</th><th>SKT</th><th class="text-end">Adet</th></tr></thead><tbody>
@foreach($grup as $gtin => $l)
@foreach($l->groupBy(fn ($u) => ($u['bn'] ?? '').'|'.($u['xd'] ?? '')) as $alt)
<tr><td class="font-monospace">{{ $gtin }}</td><td>{{ $alt[0]['bn'] ?? '—' }}</td><td>{{ $alt[0]['xd'] ?? '—' }}</td><td class="text-end">{{ count($alt) }}</td></tr>
@endforeach
@endforeach
</tbody></table>
<form method="post" action="{{ route('pts.dogrula', $paket) }}" class="mb-2">@csrf<button class="btn btn-outline-secondary btn-sm">Ürünleri İTS'te Doğrula (salt-okunur)</button></form>
@if($paket->durum !== 'mal_alindi' && ! $sonuc)
<form method="post" action="{{ route('pts.mal-alim', $paket) }}" onsubmit="return confirm('Bu paketteki {{ count($urunler) }} ürün için Mal Alım bildirimi yapılacak. Onaylıyor musunuz?')">@csrf
  <button class="btn btn-primary">Mal Alım Yap ({{ count($urunler) }} ürün)</button>
</form>
@endif
@elseif(! $hatalar)
<div class="alert alert-info">Pakette GTIN ve seri numarası taşıyan ürün bulunamadı. XML yapısı beklediğimizden farklı olabilir; aşağıdaki etiket özetini destek ekibine/geliştiriciye iletin.</div>
@endif

@if($sonuc)
<div class="card mt-3"><div class="card-header">Mal Alım sonucu @if($sonuc['bildirim'])<small class="text-muted">Bildirim No: {{ $sonuc['bildirim'] }}</small>@endif</div>
<table class="table table-sm mb-0">
@foreach(array_slice($sonuc['satirlar'], 0, 200) as $s)
<tr class="{{ $s['durum'] === 'tamam' ? 'table-success' : 'table-danger' }}"><td class="font-monospace small">{{ $s['kod'] }}</td><td>{{ $s['durum'] === 'tamam' ? 'Başarılı' : $s['mesaj'] }}</td></tr>
@endforeach
</table>@if(count($sonuc['satirlar']) > 200)<div class="p-2 small text-muted">İlk 200 satır gösteriliyor.</div>@endif</div>
@endif

@if(! count($urunler) && count($yapi))
<h6 class="mt-3">XML yapı özeti (etiket: sayı)</h6>
<p class="small font-monospace">@foreach($yapi as $k => $n){{ $k }}: {{ $n }}@if(! $loop->last) · @endif @endforeach</p>
@endif
@endsection
