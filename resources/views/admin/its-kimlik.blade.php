@extends('layouts.app')
@section('baslik', 'İTS Kimlik Bilgileri')
@section('icerik')
<h4>İTS Kimlik Bilgileri: {{ $tenant->unvan }}</h4>
<p class="text-muted small">Bu bilgiler yalnızca DepoWork ekibi tarafından girilir ve değiştirilir. Müşteri kullanıcıları göremez ve değiştiremez. Değerler şifreli saklanır, ekranda bir daha gösterilmez, denetim kaydına yazılmaz. Sürücü: <code>{{ config('its.driver') }}</code></p>
<div class="mb-3">Durum: {!! $kayit ? '<span class="badge text-bg-success">Bilgiler kayıtlı</span> <span class="text-muted small">Son güncelleme: '.$kayit->updated_at->format('d.m.Y H:i').'</span>' : '<span class="badge text-bg-warning">Kayıtlı bilgi yok</span>' !!}</div>
<form method="post" action="{{ route('admin.tenants.its.kaydet', $tenant) }}" class="col-md-5 mb-3" autocomplete="off">@csrf
  <div class="mb-2"><label class="form-label">İTS kullanıcı adı (GLN)</label><input name="kullanici_adi" class="form-control" required autocomplete="off"></div>
  <div class="mb-3"><label class="form-label">İTS şifresi</label><div class="input-group"><input type="password" name="sifre" class="form-control" required autocomplete="new-password"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle" title="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
  <button class="btn btn-primary">{{ $kayit ? 'Güncelle' : 'Kaydet' }}</button>
  <a href="{{ route('admin.tenants.index') }}" class="btn btn-link">Müşterilere dön</a>
</form>
<script>
document.querySelectorAll('.goz-btn').forEach(function (b) {
  b.addEventListener('click', function () {
    var i = b.closest('.input-group').querySelector('input'), g = i.type === 'password';
    i.type = g ? 'text' : 'password';
    b.querySelector('i').className = g ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
});
</script>
@endsection
