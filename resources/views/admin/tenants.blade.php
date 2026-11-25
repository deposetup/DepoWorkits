@extends('layouts.app')
@section('baslik', 'Müşteriler')
@section('icerik')
<h4>Müşteriler</h4>
<table class="table table-sm bg-white align-middle">
  <thead><tr><th>Ünvan</th><th>GLN</th><th>Lisans</th><th>Durum</th><th></th></tr></thead>
  <tbody>
  @foreach($tenants as $t)
    @php $kalan = $t->kalanGun(); @endphp
    <tr>
      <td>{{ $t->unvan }}</td><td>{{ $t->gln }}</td>
      <td class="text-nowrap">{{ $t->lisans_bitis->format('d.m.Y') }} <span class="badge {{ $kalan > 30 ? 'text-bg-light border' : ($kalan > 0 ? 'text-bg-warning' : 'text-bg-danger') }}">{{ $kalan }} gün</span></td>
      <td>{!! $t->lisansAktifMi() ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-danger">Pasif/Süresi doldu</span>' !!}</td>
      <td class="text-nowrap">
        <form method="post" action="{{ route('admin.tenants.musteri-gozu', $t) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success" title="Bu müşteriyi, kendi ekranı gibi salt okunur görüntüle"><i class="bi bi-eye me-1"></i>Müşteri Gözü</button></form>
        <a href="{{ route('admin.tenants.duzelt', $t) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square me-1"></i>Düzelt</a>
        <div class="dropdown d-inline">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Diğer</button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:260px">
            <li class="px-3 py-2">
              <div class="small text-muted mb-1">Lisans: kalan gün sayısını belirle</div>
              <form method="post" action="{{ route('admin.tenants.kalan', $t) }}" class="d-flex gap-1" onsubmit="return confirm('{{ addslashes($t->unvan) }} için kalan gün sayısı değiştirilecek. Emin misiniz?')">@csrf
                <input type="number" name="kalan" value="{{ $kalan }}" min="0" max="3650" class="form-control form-control-sm" style="width:90px" title="Kalan gün sayısı"><button class="btn btn-sm btn-outline-success">Ayarla</button></form>
              <div class="d-flex gap-1 mt-2">
                @foreach([30 => '+30 gün', 365 => '+1 yıl'] as $g => $ad)
                <form method="post" action="{{ route('admin.tenants.uzat', $t) }}">@csrf<input type="hidden" name="gun" value="{{ $g }}"><button class="btn btn-sm btn-outline-primary" title="Mevcut bitişe {{ $g }} gün ekler">{{ $ad }}</button></form>
                @endforeach
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('admin.kullanici.index', $t) }}"><i class="bi bi-people me-2"></i>Kullanıcılar</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.tenants.its', $t) }}"><i class="bi bi-key me-2"></i>İTS Bilgileri</a></li>
            <li><form method="post" action="{{ route('admin.tenants.pts-sifirla', $t) }}" onsubmit="return confirm('{{ addslashes($t->unvan) }} müşterisinin PTS Havuzu (Arada Kalanlar) sıfırlanacak. Bekleyen paketler havuzdan çıkarılır, ürünler stoğa alınmaz. Emin misiniz?')">@csrf<button class="dropdown-item" title="Deneme PTS paketlerini ana sayfadaki PTS Havuzu özetinden çıkarır"><i class="bi bi-eraser me-2"></i>PTS Havuzunu Sıfırla</button></form></li>
            <li><form method="post" action="{{ route('admin.tenants.aktif', $t) }}">@csrf<button class="dropdown-item"><i class="bi bi-power me-2"></i>{{ $t->aktif ? 'Pasifleştir' : 'Aktifleştir' }}</button></form></li>
          </ul>
        </div>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
<h5 class="mt-4">Yeni Müşteri</h5>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('admin.tenants.store') }}" class="row g-2">@csrf
  <div class="col-md-2"><input id="gln" name="gln" class="form-control" placeholder="GLN (13 hane)" maxlength="13" inputmode="numeric" autocomplete="off" autofocus required></div>
  <div class="col-md-4"><input id="unvan" name="unvan" class="form-control" placeholder="Ünvan (otomatik: Her Kelimenin Baş Harfi Büyük)" required></div>
  <div class="col-md-3"><select id="tur" name="tur" class="form-select">@foreach(\App\Models\Tenant::TURLER as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-2"><input type="date" name="lisans_baslangic" value="{{ now()->toDateString() }}" class="form-control" required></div>
  <div class="col-md-2"><input type="date" name="lisans_bitis" value="{{ now()->addYear()->toDateString() }}" class="form-control" required></div>
  <div class="col-md-2"><input id="il" name="il" class="form-control" placeholder="İl" maxlength="60"></div>
  <div class="col-md-2"><input id="ilce" name="ilce" class="form-control" placeholder="İlçe" maxlength="60"></div>
  <div class="col-md-4"><input id="eposta" type="email" name="eposta" class="form-control" placeholder="E-posta (isteğe bağlı)"></div>
  <div class="col-md-2"><input id="telefon" name="telefon" class="form-control" placeholder="Telefon" maxlength="30"></div>
  <div class="col-12"><div class="fw-semibold small mt-2">İTS portal kullanıcı bilgileri <span class="text-muted fw-normal">(isteğe bağlı; sonradan Düzelt ekranından da girilebilir. Kullanıcı hesabı oluşturmaz.)</span></div></div>
  <div class="col-md-3"><input name="its_kullanici_adi" class="form-control" placeholder="İTS kullanıcı adı (genelde GLN)" autocomplete="off" maxlength="100"></div>
  <div class="col-md-3"><div class="input-group"><input type="password" name="its_sifre" class="form-control" placeholder="İTS şifresi" autocomplete="new-password" maxlength="200"><button type="button" class="btn btn-outline-secondary goz-btn" tabindex="-1" aria-label="Şifreyi göster/gizle" title="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button></div></div>
  <div class="col-12"><div class="form-text">GLN'i yazınca ünvan, tür, il, ilçe, e-posta ve telefon İTS paydaş listesinden otomatik gelir; dilerseniz düzeltebilirsiniz.</div>@include('admin._gln-sorgu')</div>
  <div class="col-12"><button class="btn btn-primary">Ekle</button></div>
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
