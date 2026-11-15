@extends('layouts.app')
@section('baslik', 'Şifre Değiştir')
@section('icerik')
<h4>Şifre Değiştir</h4>
<form method="post" action="{{ route('sifre.guncelle') }}" class="col-md-5">@csrf
  <div class="mb-2"><label class="form-label">Mevcut şifre</label><input type="password" name="mevcut" class="form-control" required autocomplete="current-password"></div>
  <div class="mb-2"><label class="form-label">Yeni şifre (en az 10 karakter, harf ve rakam)</label><input type="password" name="password" class="form-control" required autocomplete="new-password"></div>
  <div class="mb-3"><label class="form-label">Yeni şifre (tekrar)</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
  <button class="btn btn-primary">Güncelle</button>
</form>
@endsection
