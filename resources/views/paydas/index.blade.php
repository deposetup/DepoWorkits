@extends('layouts.app')
@section('baslik', 'Paydaşlar')
@section('icerik')
<h4>Paydaş Listesi</h4>
<div class="alert alert-secondary small">İTS'te kayıtlı paydaşları sorgular ve <b>aktif / pasif</b> durumunu gösterir. Satış ve iade için karşı GLN'in aktif olduğunu buradan doğrulayın. Eczane gibi büyük listelerde sorgu birkaç saniye sürebilir; bir seferde tek tip sorgulanır.</div>
<form method="get" class="row g-2 mb-3">
  <input type="hidden" name="sorgula" value="1">
  <div class="col-md-2"><select name="tip" class="form-select">@foreach($tipler as $k => $ad)<option value="{{ $k }}" @selected($f['tip'] === $k)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="durum" class="form-select">@foreach(['hepsi' => 'Aktif + Pasif', 'aktif' => 'Sadece aktif', 'pasif' => 'Sadece pasif'] as $k => $ad)<option value="{{ $k }}" @selected($f['durum'] === $k)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-2"><input name="plaka" value="{{ $f['plaka'] }}" class="form-control" placeholder="İl plakası (ops.)" type="number" min="1" max="81"></div>
  <div class="col-md-3"><input name="ara" value="{{ $f['ara'] }}" class="form-control" placeholder="Ünvan, GLN veya il ara"></div>
  <div class="col-auto"><button class="btn btn-primary">Sorgula</button></div>
</form>
@if($hata)<div class="alert alert-danger">{{ $hata }}</div>@endif
@if($liste !== null)
<div class="d-flex justify-content-between align-items-center mb-2"><span class="text-muted small">{{ $toplam }} paydaş bulundu @if($toplam > $gosterilen->count())(ilk {{ $gosterilen->count() }} gösteriliyor; Excel'de tümü vardır)@endif</span><a href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a></div>
<table class="table table-sm bg-white align-middle"><thead><tr><th>GLN</th><th>Ünvan</th><th>Tip</th><th>İl / İlçe</th><th>Telefon</th><th>Durum</th></tr></thead><tbody>
@forelse($gosterilen as $p)
<tr><td class="font-monospace">{{ $p['gln'] }}</td><td>{{ $p['companyName'] }}</td><td>{{ $tipler[$p['tip']] ?? $p['tip'] }}</td><td>{{ $p['city'] }} {{ $p['town'] ? '/ '.$p['town'] : '' }}</td><td>{{ $p['phone'] ?: '—' }}</td>
<td>@if($p['active'])<span class="badge text-bg-success">Aktif</span>@else<span class="badge text-bg-secondary">Pasif</span>@endif</td></tr>
@empty<tr><td colspan="6" class="text-muted">Sonuç yok.</td></tr>@endforelse
</tbody></table>
@endif
@endsection
