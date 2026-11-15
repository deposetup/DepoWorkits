@extends('layouts.app')
@section('baslik', 'Giriş')
@section('icerik')
<div class="row justify-content-center"><div class="col-md-4">
  <div class="card shadow-sm"><div class="card-body">
    <h5 class="card-title mb-3">Giriş Yap</h5>
    <form method="post" action="/giris">@csrf
      <div class="mb-3"><label class="form-label">E-posta</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="mb-3"><label class="form-label">Şifre</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary w-100">Giriş</button>
    </form>
  </div></div>
</div></div>
@endsection
