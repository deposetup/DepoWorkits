@extends('layouts.app')
@php $yeni = ! $cari->exists; @endphp
@section('baslik', $yeni ? 'Yeni Cari' : 'Cari Düzelt')
@section('icerik')
<h4>{{ $yeni ? 'Yeni Cari' : 'Cari Düzelt' }}</h4>
@if($errors->any())<div class="alert alert-danger small">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="post" action="{{ $yeni ? route('cari.olustur') : route('cari.guncelle', $cari) }}" class="bg-white border rounded p-3" style="max-width:720px">
@csrf
<div class="row g-3">
<div class="col-md-5"><label class="form-label">GLN @if($yeni)*@endif</label>
<input name="gln" value="{{ old('gln', $cari->gln) }}" class="form-control font-monospace" maxlength="13" inputmode="numeric" pattern="[0-9]{13}" {{ $yeni ? 'required' : 'readonly' }}>
@unless($yeni)<div class="form-text">GLN değiştirilemez; yanlışsa cariyi silip yeniden ekleyin.</div>@endunless</div>
<div class="col-md-7"><label class="form-label">Cari Adı *</label><input name="unvan" value="{{ old('unvan', $cari->unvan) }}" class="form-control" maxlength="255" required></div>
<div class="col-md-4"><label class="form-label">Tip</label><select name="tip" class="form-select"><option value="">—</option>@foreach($tipler as $k => $ad)<option value="{{ $k }}" @selected(old('tip', $cari->tip) === (string) $k)>{{ $ad }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">İl</label><input name="il" value="{{ old('il', $cari->il) }}" class="form-control" maxlength="60"></div>
<div class="col-md-4"><label class="form-label">İlçe</label><input name="ilce" value="{{ old('ilce', $cari->ilce) }}" class="form-control" maxlength="60"></div>
<div class="col-md-6"><label class="form-label">E-posta</label><input type="email" name="eposta" value="{{ old('eposta', $cari->eposta) }}" class="form-control" maxlength="255"></div>
<div class="col-md-6"><label class="form-label">Telefon</label><input name="telefon" value="{{ old('telefon', $cari->telefon) }}" class="form-control" maxlength="30"></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Kaydet</button> <a href="{{ route('cari.index') }}" class="btn btn-outline-secondary">Vazgeç</a></div>
</form>
@endsection
