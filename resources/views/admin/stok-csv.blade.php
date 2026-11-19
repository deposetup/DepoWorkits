@extends('layouts.app')
@section('baslik', 'Stok CSV Aktar')
@section('icerik')
<a href="{{ route('admin.tenants.index') }}" class="small">&larr; Müşteriler</a>
<h4 class="mt-2">{{ $tenant->unvan }} <small class="text-muted">Stok dosyası aktar (GLN {{ $tenant->gln }})</small></h4>
<div class="alert alert-info small">İTS portalından bu müşterinin GLN'iyle indirilen stok dosyasını (<code>GTIN;SN;BN;EXP_DATE</code>, .csv ya da .zip) müşterinin stoğuna alır. Dosya adındaki GLN müşterininkiyle uyuşmuyorsa aktarım reddedilir. İTS'e bildirim gönderilmez. Şu an bu müşterinin stoğunda <b>{{ number_format($mevcut, 0, ',', '.') }}</b> ürün var.</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($csv)
<div class="alert alert-success"><b>Dosya içe aktarıldı.</b> {{ number_format($csv['okunan'], 0, ',', '.') }} satır okundu: <b>{{ number_format($csv['eklenen'], 0, ',', '.') }}</b> yeni ürün eklendi, {{ number_format($csv['guncellenen'], 0, ',', '.') }} ürün zaten kayıtlıydı{{ '' }}@if($csv['atlanan_durum']), {{ $csv['atlanan_durum'] }} ürün başka durumda olduğu için atlandı @endif @if($csv['gecersiz']), <span class="text-danger">{{ $csv['gecersiz'] }} geçersiz satır</span>@endif.@if(! empty($csv['silinen'])) <b>{{ number_format($csv['silinen'], 0, ',', '.') }}</b> eski kayıt dosyada olmadığı için silindi.@endif
@foreach($csv['ornek_hatalar'] as $h)<div class="small text-danger">{{ $h }}</div>@endforeach</div>
@endif
<form method="post" action="{{ route('admin.stok-csv.yukle', $tenant) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Aktarılıyor, bekleyin…'">@csrf
  <input type="file" name="dosya" accept=".csv,.txt,.zip" class="form-control w-auto" required>
  <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Stoğa Aktar</button>
</form>
@endsection
