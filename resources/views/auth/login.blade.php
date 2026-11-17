<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Giriş - DepoWork ITS</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  @font-face { font-family: 'Orbit-B PL'; src: url('{{ asset('fonts/OrbitB-PL.ttf') }}') format('truetype'); font-display: swap; }
  .orbit { font-family: 'Orbit-B PL', system-ui, sans-serif; font-weight: 400; letter-spacing: .04em; }
  :root { --lacivert: #0b2447; --lacivert2: #19376d; --vurgu: #5fd0ff; }
  html, body { height: 100%; }
  body { background: #f4f6fa; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  .sayfa { min-height: 100vh; display: flex; flex-direction: column; }
  .bolme { flex: 1; display: flex; }
  .sol { flex: 1.1; position: relative; overflow: hidden; color: #fff; padding: 3rem 3.5rem; display: none; flex-direction: column; justify-content: space-between;
         background: radial-gradient(circle at 15% 10%, rgba(95,208,255,.22), transparent 45%), linear-gradient(160deg, var(--lacivert), #0d2b57 55%, var(--lacivert2)); }
  .sol::after { content: ""; position: absolute; width: 520px; height: 520px; right: -180px; bottom: -200px; border-radius: 50%; border: 70px solid rgba(255,255,255,.04); }
  @media (min-width: 992px) { .sol { display: flex; } }
  .marka { display: flex; align-items: center; gap: .85rem; font-size: 2.1rem; line-height: 1; }
  .marka .logo { width: 3.4rem; height: 3.4rem; padding: .45rem; border-radius: 50%; background: rgba(95,208,255,.14); border: 1px solid rgba(95,208,255,.35); color: #fff; box-shadow: 0 0 0 .5rem rgba(95,208,255,.06); }
  .marka .logo svg, .mobil-marka .logo svg { animation: don 7s linear infinite; }
  @keyframes don { to { transform: rotate(360deg); } }
  @media (prefers-reduced-motion: reduce) { .marka .logo svg, .mobil-marka .logo svg { animation: none; } }
  .marka .nokta { color: var(--vurgu); }
  .sol > div:not(.marka) { margin: auto 0; }
  .sol h1 { font-size: 2.1rem; font-weight: 700; line-height: 1.25; max-width: 30rem; }
  .sol p.alt { color: #b9c8e6; max-width: 30rem; }
  .ozellik { list-style: none; padding: 0; margin: 1.75rem 0 0; display: grid; gap: .9rem; position: relative; z-index: 1; }
  .ozellik li { display: flex; gap: .85rem; align-items: flex-start; color: #dbe5f8; }
  .ozellik i { color: var(--vurgu); font-size: 1.1rem; margin-top: .1rem; }
  .ozellik b { color: #fff; display: block; font-weight: 600; }
  .sol small { color: #8196c4; position: relative; z-index: 1; }
  .sag { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1.25rem; }
  .kutu { width: 100%; max-width: 25rem; }
  .mobil-marka { display: flex; align-items: center; gap: .6rem; font-size: 1.6rem; line-height: 1; color: var(--lacivert); margin-bottom: 1.75rem; }
  .mobil-marka .logo { width: 2.6rem; height: 2.6rem; padding: .35rem; border-radius: 50%; background: var(--lacivert); color: #fff; }
  .mobil-marka .nokta { color: #2a8fd0; }
  .bolme { position: relative; }
  .kurumsal { position: absolute; top: 1.25rem; right: 1.75rem; z-index: 2; display: block; }
  .kurumsal img { height: 3.4rem; width: auto; display: block; }
  @media (max-width: 575px) { .kurumsal { position: static; padding: 1rem 1.25rem 0; text-align: right; } .bolme { flex-direction: column; } .kurumsal img { height: 2.7rem; display: inline-block; } }
  @media (min-width: 992px) { .mobil-marka { display: none; } }
  .kutu h2 { font-weight: 700; color: var(--lacivert); font-size: 1.6rem; margin-bottom: .25rem; }
  .kutu .aciklama { color: #6b7a99; margin-bottom: 1.75rem; }
  .input-group-text { background: #fff; color: #6b7a99; }
  .form-control { padding: .7rem .85rem; }
  .form-control:focus { border-color: var(--lacivert2); box-shadow: 0 0 0 .2rem rgba(25,55,109,.15); }
  .btn-giris { background: var(--lacivert); border-color: var(--lacivert); color: #fff; padding: .75rem; font-weight: 600; border-radius: .6rem; }
  .btn-giris:hover { background: var(--lacivert2); border-color: var(--lacivert2); color: #fff; }
  .guven { margin-top: 1.5rem; padding: .85rem 1rem; border-radius: .6rem; background: #eaf0fb; color: #3b4d73; font-size: .82rem; display: flex; gap: .6rem; }
  .guven i { color: var(--lacivert2); }
  .alt-bilgi { border-top: 1px solid #e3e8f2; background: #fff; color: #6b7a99; font-size: .8rem; padding: .8rem 1.25rem; display: flex; flex-wrap: wrap; gap: .4rem 1.5rem; justify-content: space-between; }
</style>
</head>
<body>
<div class="sayfa">
  <div class="bolme">
    <a class="kurumsal" href="https://deposetup.com.tr" target="_blank" rel="noopener" title="Depo Setup Yazılım ve Danışmanlık Hizmetleri Ltd. Şti."><img src="{{ asset('img/deposetup-logo.svg') }}" alt="Depo Setup"></a>
    <section class="sol" aria-hidden="true">
      <div class="marka"><span class="logo"><svg viewBox="0 0 48 48" width="100%" height="100%" aria-hidden="true"><g transform="rotate(-45 24 24)"><rect x="5" y="14" width="38" height="20" rx="10" fill="none" stroke="currentColor" stroke-width="2.6"/><path d="M24 14h9a10 10 0 0 1 0 20h-9z" fill="var(--vurgu)"/><path d="M24 14v20" stroke="currentColor" stroke-width="2.6"/></g></svg></span><span class="orbit">DepoWork<span class="nokta"> ITS</span></span></div>
      <div style="position:relative;z-index:1">
        <h1>İTS bildirimlerinizi tek ekrandan, güvenle yönetin.</h1>
        <p class="alt mt-3">Mal kabul, satış, devir, sarf ve deaktivasyon bildirimleriniz ile PTS paketleriniz tek bir kurumsal portalda.</p>
        <ul class="ozellik">
          <li><i class="bi bi-upc-scan"></i><span><b>Karekod ile hızlı işlem</b>Okutun, bildirin, stoğunuz otomatik güncellensin.</span></li>
          <li><i class="bi bi-diagram-3"></i><span><b>PTS entegrasyonu</b>Gelen paketleri alın, satış sonrası PTS verisini onayınızla gönderin.</span></li>
          <li><i class="bi bi-boxes"></i><span><b>Stok ve miad takibi</b>GTIN ve parti bazlı stok, kritik seviye ve miad uyarıları.</span></li>
          <li><i class="bi bi-shield-lock"></i><span><b>Güvenli erişim</b>Müşteriye özel veri ayrımı ve işlem kayıtları.</span></li>
        </ul>
      </div>
    </section>
    <section class="sag">
      <div class="kutu">
        <div class="mobil-marka"><span class="logo"><svg viewBox="0 0 48 48" width="100%" height="100%" aria-hidden="true"><g transform="rotate(-45 24 24)"><rect x="5" y="14" width="38" height="20" rx="10" fill="none" stroke="currentColor" stroke-width="2.6"/><path d="M24 14h9a10 10 0 0 1 0 20h-9z" fill="var(--vurgu)"/><path d="M24 14v20" stroke="currentColor" stroke-width="2.6"/></g></svg></span><span class="orbit">DepoWork<span class="nokta"> ITS</span></span></div>
        <h2>Hoş geldiniz</h2>
        <p class="aciklama">Hesabınızla giriş yaparak devam edin.</p>
        <form method="post" action="/giris" autocomplete="on">@csrf
          <div class="mb-3">
            <label class="form-label fw-semibold" for="email">E-posta veya GLN</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input id="email" type="text" name="email" inputmode="email" placeholder="E-posta adresi ya da 13 haneli GLN" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" autofocus required>
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold" for="sifre">Şifre</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input id="sifre" type="password" name="password" class="form-control" autocomplete="current-password" required>
              <button type="button" class="btn btn-outline-secondary" id="goster" aria-label="Şifreyi göster/gizle"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button class="btn btn-giris w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Giriş Yap</button>
        </form>
        <div class="text-center mt-3"><a href="{{ route('sifre.unuttum') }}" class="small">Şifremi unuttum</a></div>
        <div class="guven"><i class="bi bi-info-circle"></i><span>E-posta adresiniz ya da GLN numaranızla giriş yapabilirsiniz.</span></div>
      </div>
    </section>
  </div>
  <footer class="alt-bilgi">
    <span>&copy; {{ now()->year }} {{ config('its.firma') }}</span>
    <span class="text-end">Sürüm {{ config('its.surum') }}</span>
  </footer>
</div>
<script>
document.getElementById('goster').addEventListener('click', function () {
  var i = document.getElementById('sifre'), g = i.type === 'password';
  i.type = g ? 'text' : 'password';
  this.querySelector('i').className = g ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
</body>
</html>
