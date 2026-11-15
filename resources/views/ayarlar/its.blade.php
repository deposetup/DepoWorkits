@extends('layouts.app')
@section('baslik', 'İTS Bağlantısı')
@section('icerik')
<h4>İTS Bağlantısı</h4>
<div class="row g-3">
  <div class="col-lg-5"><div class="card h-100"><div class="card-body">
    <div class="d-flex align-items-center gap-2 mb-2"><span class="ikon-yuvarlak"><i class="bi bi-plug"></i></span><h6 class="mb-0">Bağlantı Durumu</h6></div>
    <p class="text-muted small">İTS kullanıcı adı ve şifreniz DepoWork ekibi tarafından tanımlanır ve şifreli saklanır; bu ekrandan görüntülenemez veya değiştirilemez. Sürücü: <code>{{ $surucu }}</code>{{ $surucu === 'mock' ? ' (gerçek İTS\'e bağlanmaz, deneme verisi kullanılır)' : '' }}</p>
    <div class="mb-3">Kimlik durumu: {!! $kayitli ? '<span class="badge text-bg-success">Tanımlı</span>' : '<span class="badge text-bg-warning">Tanımlı değil, sağdaki formla bize yazın</span>' !!}
      @if($guncelleme)<div class="text-muted small mt-1">Son güncelleme: {{ $guncelleme->format('d.m.Y H:i') }}</div>@endif</div>
    @if($kayitli)<form method="post" action="{{ route('ayarlar.its.test') }}">@csrf<button class="btn btn-primary"><i class="bi bi-plug me-1"></i>Bağlantıyı Sına</button></form>@endif
  </div></div></div>

  <div class="col-lg-7"><div class="card h-100 talep-kart"><div class="card-body p-4">
    <div class="d-flex align-items-center gap-3 mb-3">
      <span class="ikon-yuvarlak buyuk"><i class="bi bi-headset"></i></span>
      <div><h5 class="mb-0">Bize Yazın</h5><div class="text-muted small">İTS bilgilerinizde değişiklik mi gerekiyor? Talebinizi iletin, DepoWork ekibi sizinle iletişime geçsin.</div></div>
    </div>
    <div class="alert alert-warning py-2 small d-flex gap-2"><i class="bi bi-shield-lock mt-1"></i><span><strong>Güvenliğiniz için:</strong> İTS şifrenizi bu forma veya e-postaya <u>yazmayın</u>. Yeni şifreyi sizinle telefonda doğrulayarak birlikte tanımlarız.</span></div>
    <form method="post" action="{{ route('ayarlar.its.talep') }}" novalidate>@csrf
      <div class="mb-3">
        <label class="form-label fw-semibold">Talep konusu</label>
        <div class="d-grid gap-2">
          @foreach($konular as $k => $ad)
          <label class="konu-secenek">
            <input type="radio" name="konu" value="{{ $k }}" class="form-check-input" @checked(old('konu', $onKonu) === $k)>
            <span>{{ $ad }}</span>
          </label>
          @endforeach
        </div>
        @error('konu')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="mesaj">Mesajınız <span class="text-muted fw-normal">(isteğe bağlı)</span></label>
        <textarea id="mesaj" name="mesaj" rows="4" maxlength="1000" class="form-control @error('mesaj') is-invalid @enderror" placeholder="Örn. İTS şifremi yeniledim, sistemde de güncellenmesini rica ediyorum.">{{ old('mesaj') }}</textarea>
        @error('mesaj')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text text-end"><span id="sayac">0</span>/1000</div>
      </div>
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="telefon">Size ulaşabileceğimiz telefon</label>
          <div class="input-group"><span class="input-group-text"><i class="bi bi-telephone"></i></span>
          <input id="telefon" name="telefon" value="{{ old('telefon', $tenant->telefon) }}" class="form-control @error('telefon') is-invalid @enderror" placeholder="05xx xxx xx xx" maxlength="30" inputmode="tel">
          @error('telefon')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="col-md-6 text-md-end"><button class="btn btn-primary btn-lg px-4"><i class="bi bi-send me-2"></i>Talebi Gönder</button></div>
      </div>
    </form>
    @if($talepler->isNotEmpty())
    <hr class="my-4">
    <div class="small text-muted mb-2">Son talepleriniz</div>
    @foreach($talepler as $x)
      <div class="d-flex justify-content-between align-items-center small py-1 border-bottom">
        <span><i class="bi bi-chat-left-text me-1 text-muted"></i>{{ $konular[$x->konu] ?? $x->konu }}</span>
        <span class="text-nowrap"><span class="text-muted me-2">{{ $x->created_at->format('d.m.Y H:i') }}</span><span class="badge text-bg-{{ ['yeni' => 'secondary', 'islemde' => 'info', 'kapandi' => 'success'][$x->durum] ?? 'secondary' }}">{{ ['yeni' => 'İletildi', 'islemde' => 'İşlemde', 'kapandi' => 'Tamamlandı'][$x->durum] ?? $x->durum }}</span></span>
      </div>
    @endforeach
    @endif
  </div></div></div>
</div>
<style>
  .ikon-yuvarlak { width: 2.2rem; height: 2.2rem; display: grid; place-items: center; border-radius: .6rem; background: #e8f0ff; color: #19376d; }
  .ikon-yuvarlak.buyuk { width: 3rem; height: 3rem; font-size: 1.4rem; background: linear-gradient(135deg, #0b2447, #19376d); color: #5fd0ff; }
  .talep-kart { border: 0; border-top: 4px solid #5fd0ff; }
  .konu-secenek { display: flex; gap: .7rem; align-items: center; padding: .65rem .9rem; border: 1px solid #dfe5f2; border-radius: .6rem; cursor: pointer; transition: border-color .15s, background .15s; margin: 0; }
  .konu-secenek:hover { border-color: #5fd0ff; background: #f6fbff; }
  .konu-secenek:has(input:checked) { border-color: #19376d; background: #eef3ff; font-weight: 600; }
</style>
<script>
  (function () { var m = document.getElementById('mesaj'), c = document.getElementById('sayac'); if (!m) return; var g = function () { c.textContent = m.value.length; }; m.addEventListener('input', g); g(); })();
</script>
@endsection
