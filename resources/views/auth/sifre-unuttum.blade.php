<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Şifre Talebi - DepoWork ITS</title>
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background: #f4f6fa; min-height: 100vh; display: flex; flex-direction: column; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
  .kutu { width: 100%; max-width: 28rem; background: #fff; border: 1px solid #e3e8f2; border-radius: .9rem; padding: 2rem; box-shadow: 0 8px 24px rgba(11,36,71,.08); }
  .marka { display: flex; align-items: center; gap: .6rem; font-weight: 700; font-size: 1.2rem; color: #0b2447; margin-bottom: 1.25rem; }
  .marka .logo { width: 2.4rem; height: 2.4rem; display: grid; place-items: center; border-radius: .65rem; background: #0b2447; color: #5fd0ff; }
  h2 { font-size: 1.4rem; font-weight: 700; color: #0b2447; }
  .btn-giris { background: #0b2447; border-color: #0b2447; color: #fff; padding: .7rem; font-weight: 600; border-radius: .6rem; }
  .btn-giris:hover { background: #19376d; color: #fff; }
  .alt-bilgi { border-top: 1px solid #e3e8f2; background: #fff; color: #6b7a99; font-size: .8rem; padding: .8rem 1.25rem; display: flex; flex-wrap: wrap; gap: .4rem 1.5rem; justify-content: space-between; }
</style>
</head>
<body>
<main><div class="kutu">
  <div class="marka"><span class="logo"><i class="bi bi-capsule-pill"></i></span>DepoWork ITS</div>
  <h2>Şifre talebi</h2>
  <p class="text-muted">GLN numaranızı ya da kayıtlı e-posta adresinizi yazın. Talebiniz DepoWork ekibine iletilir ve sizinle iletişime geçilir.</p>
  @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <form method="post" action="{{ route('sifre.unuttum.gonder') }}">@csrf
    <div class="mb-3"><label class="form-label fw-semibold" for="kimlik">GLN veya e-posta</label>
      <input id="kimlik" name="kimlik" value="{{ old('kimlik') }}" class="form-control" placeholder="13 haneli GLN ya da e-posta" maxlength="255" required autofocus></div>
    <div class="mb-3"><label class="form-label fw-semibold" for="telefon">Telefon <small class="text-muted fw-normal">(isteğe bağlı)</small></label>
      <input id="telefon" name="telefon" value="{{ old('telefon') }}" class="form-control" maxlength="30" inputmode="tel"></div>
    <div class="mb-3"><label class="form-label fw-semibold" for="mesaj">Not <small class="text-muted fw-normal">(isteğe bağlı)</small></label>
      <textarea id="mesaj" name="mesaj" rows="2" class="form-control" maxlength="500"></textarea></div>
    <div class="alert alert-secondary small py-2"><i class="bi bi-shield-lock me-1"></i>Şifrenizi bu forma <b>yazmayın</b>. Yeni şifre size DepoWork ekibi tarafından iletilir.</div>
    <button class="btn btn-giris w-100"><i class="bi bi-send me-1"></i>Talep Gönder</button>
  </form>
  <div class="text-center mt-3"><a href="{{ route('login') }}" class="small">&larr; Giriş ekranına dön</a></div>
</div></main>
<footer class="alt-bilgi"><span>&copy; {{ now()->year }} {{ config('its.firma') }}</span><span>Sürüm {{ config('its.surum') }}</span></footer>
</body>
</html>
