@extends('layouts.app')
@section('baslik', $baslik)
@section('icerik')
<h4>{{ $baslik }}</h4>
@if($tur === 'deaktive_iptal')<div class="alert alert-info small">Deaktive edilmiş ürünleri geri stoğa alır. Deaktivasyonda kullandığınız <b>sebebi</b> seçin. Kılavuz bu servisi eczaneler için tanımlar; depo hesabınızda İTS reddederse sonuç ekranında İTS'in hata mesajı görünür.</div>@endif
@if($tur === 'devir')<div class="alert alert-info small">Mal Devir yalnızca <b>aynı role sahip</b> paydaşlar arasında yapılabilir (depo → depo). Hata yaptıysanız <b>Mal Devir İptal</b> kullanın. Karşı paydaşın aktif olup olmadığını <b>Sorgular → Paydaşlar</b> ekranından kontrol edebilirsiniz.</div>@endif
@if($tur === 'satis')<div class="alert alert-info small">Satış bildirimi sonrası ürünler alıcıya hemen geçmez; alıcı kendi <b>Mal Alım</b> bildirimini yapana kadar arada bekler. Hata yaptıysanız <b>Satış İptal</b> kullanın.</div>@endif
<form method="post" action="{{ route('islem.calistir', $tur) }}" class="mb-4">@csrf
  <label class="form-label">Karekodlar (her satıra bir ürün; okuyucu ile okutun)</label>
  <textarea name="kodlar" rows="8" class="form-control font-monospace" autofocus>{{ old('kodlar') }}</textarea>
  @if(in_array($tur, ['iade', 'satis', 'devir']))
  <div class="mt-2 col-md-4"><input name="karsi_gln" value="{{ old('karsi_gln') }}" class="form-control" placeholder="{{ $tur === 'satis' ? 'Satış yapılan paydaşın GLN\'i (13 hane)' : ($tur === 'devir' ? 'Devir yapılan paydaşın GLN\'i (13 hane)' : 'İade edilen paydaşın GLN\'i (13 hane)') }}" maxlength="13" required></div>
  @endif
  @if(in_array($tur, ['deaktive', 'deaktive_iptal']))
  <div class="row g-2 mt-2">
    <div class="col-md-4"><select name="sebep" class="form-select" required>
      <option value="">Sebep seçin</option>
      @foreach($sebepler as $k => $ad)<option value="{{ $k }}">{{ $k }} - {{ $ad }}</option>@endforeach
    </select></div>
    <div class="col-md-8"><input name="aciklama" class="form-control" placeholder="Açıklama (isteğe bağlı)" maxlength="255"></div>
  </div>
  @endif
  <button class="btn btn-primary mt-3">Gönder</button>
</form>
@isset($sonuc)
<div class="card"><div class="card-header">Sonuç @if($sonuc['bildirim'])<small class="text-muted">Bildirim No: {{ $sonuc['bildirim'] }}</small>@endif</div>
<table class="table table-sm mb-0">
@foreach($sonuc['satirlar'] as $s)
<tr class="{{ $s['durum'] === 'tamam' ? 'table-success' : 'table-danger' }}"><td class="font-monospace small">{{ \Illuminate\Support\Str::limit($s['kod'], 60) }}</td><td>{{ $s['durum'] === 'tamam' ? 'Başarılı' : $s['mesaj'] }}</td></tr>
@endforeach
</table></div>
@if(isset($geri))<a href="{{ $geri }}" class="btn btn-outline-secondary mt-3">Stok listesine dön</a>@endif
@endisset
@endsection
