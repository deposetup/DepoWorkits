@extends('layouts.app')
@section('baslik', $baslik)
@section('icerik')
<h4>{{ $baslik }}</h4>
@if($tur === 'deaktive_iptal')<div class="alert alert-info small">Deaktive edilmiş ürünleri geri stoğa alır. Deaktivasyonda kullandığınız <b>sebebi</b> seçin. Kılavuz bu servisi eczaneler için tanımlar; depo hesabınızda İTS reddederse sonuç ekranında İTS'in hata mesajı görünür.</div>@endif
@if($tur === 'devir')<div class="alert alert-info small">Mal Devir yalnızca <b>aynı role sahip</b> paydaşlar arasında yapılabilir (depo → depo). Hata yaptıysanız <b>Mal Devir İptal</b> kullanın. Karşı paydaşın aktif olup olmadığını <b>Sorgular → Paydaşlar</b> ekranından kontrol edebilirsiniz.</div>@endif
@if($tur === 'satis')<div class="alert alert-info small">Satış bildirimi sonrası ürünler alıcıya hemen geçmez; alıcı kendi <b>Mal Alım</b> bildirimini yapana kadar arada bekler. Hata yaptıysanız sonuç ekranındaki <b>Satışı geri al</b> butonunu ya da daha önceki satışlar için <a href="{{ route('islem', 'satis_iptal') }}">Satış İptal</a> ekranını kullanın.</div>@endif
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
@php
  $geriAlinabilir = in_array($tur, ['satis', 'devir'], true);
  $iptalTuru = $tur === 'satis' ? 'satis_iptal' : 'devir_iptal';
  $basarili = collect($sonuc['satirlar'])->where('durum', 'tamam')->pluck('kod')->values();
@endphp
<table class="table table-sm mb-0 align-middle">
@foreach($sonuc['satirlar'] as $s)
<tr class="{{ $s['durum'] === 'tamam' ? 'table-success' : 'table-danger' }}"><td class="font-monospace small">{{ \Illuminate\Support\Str::limit($s['kod'], 60) }}</td><td>{{ $s['durum'] === 'tamam' ? 'Başarılı' : $s['mesaj'] }}</td>
@if($geriAlinabilir)<td class="text-end">@if($s['durum'] === 'tamam')
  <form method="post" action="{{ route('islem.calistir', $iptalTuru) }}" onsubmit="return confirm('Bu ürünün {{ $tur === 'satis' ? 'satışı' : 'devri' }} İTS\'te iptal edilecek. Emin misiniz?')">@csrf<input type="hidden" name="kodlar" value="{{ $s['kod'] }}"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>{{ $tur === 'satis' ? 'Satışı geri al' : 'Devri geri al' }}</button></form>
@endif</td>@endif
</tr>
@endforeach
</table></div>
@if($geriAlinabilir && $basarili->count() > 1)
<form method="post" action="{{ route('islem.calistir', $iptalTuru) }}" class="mt-3" onsubmit="return confirm('Başarılı {{ $basarili->count() }} ürünün {{ $tur === 'satis' ? 'satışı' : 'devri' }} İTS\'te iptal edilecek. Emin misiniz?')">@csrf<input type="hidden" name="kodlar" value="{{ $basarili->implode("\n") }}"><button class="btn btn-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Başarılı {{ $basarili->count() }} ürünün {{ $tur === 'satis' ? 'satışını' : 'devrini' }} geri al</button></form>
@endif
@if($geriAlinabilir && $basarili->isNotEmpty())<div class="small text-muted mt-2">Geri alma yalnızca alıcı henüz Mal Alım bildirimi yapmadıysa mümkündür; aksi hâlde İTS reddeder ve hata mesajı sonuç ekranında görünür.</div>@endif
@if($tur === 'satis' && isset($sonuc))
@if(isset($ptsGonderim) && $ptsGonderim)
<div class="card border-primary mt-3"><div class="card-header bg-primary text-white"><i class="bi bi-send me-1"></i>PTS verisi gönderimi</div>
<div class="card-body">
  <p class="mb-2">Satış bildirimi <b>tamamen başarılı</b> (Bildirim No: {{ $ptsGonderim->bildirim_id ?? '—' }}). Alıcının Mal Alım yapabilmesi için <b>{{ $ptsGonderim->adet }} ürünün</b> PTS verisini <b class="font-monospace">{{ $ptsGonderim->alici_gln }}</b> GLN'li paydaşa göndermek ister misiniz?</p>
  @if(! $ptsAcik)<div class="alert alert-warning small mb-2">PTS paket gönderimi bu ortamda henüz etkinleştirilmedi. Kayıt <a href="{{ route('pts.gonderimler') }}">PTS Gönderimleri</a> ekranında bekletilir; etkinleştirildiğinde oradan gönderebilirsiniz.</div>@endif
  <form method="post" action="{{ route('pts.gonder', $ptsGonderim) }}">@csrf
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="onay" value="1" id="ptsOnay" required><label class="form-check-label" for="ptsOnay">Ürün listesini ve alıcı GLN'ini kontrol ettim; PTS verisinin alıcıya gönderilmesini onaylıyorum.</label></div>
    <button class="btn btn-primary" @disabled(! $ptsAcik)><i class="bi bi-send me-1"></i>PTS verisini gönder</button>
  </form>
  <form method="post" action="{{ route('pts.vazgec', $ptsGonderim) }}" class="d-inline">@csrf<button class="btn btn-link text-muted px-0 mt-2">Şimdi gönderme</button></form>
  <div class="small text-muted mt-1">Satışı geri alırsanız bekleyen PTS kaydından ilgili ürünler otomatik çıkarılır. Gönderim yalnızca bir kez yapılır.</div>
</div></div>
@elseif(collect($sonuc['satirlar'])->contains('durum', 'hata'))
<div class="alert alert-secondary small mt-3">Bazı satırlar hata verdiği için PTS gönderimi sunulmadı. PTS verisi yalnızca bildirimin tamamı başarılı olduğunda hazırlanır; hatalı ürünleri düzeltip tüm listeyi yeniden okutun.</div>
@endif
@endif
@if(isset($geri))<a href="{{ $geri }}" class="btn btn-outline-secondary mt-3">Stok listesine dön</a>@endif
@endisset
@endsection
