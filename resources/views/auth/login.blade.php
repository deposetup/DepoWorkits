@extends('layouts.app')

@section('title', 'Giriş Yap')

@section('content')
    <h1>{{ config('app.name') }} — Giriş Yap</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" style="max-width: 320px;">
        @csrf

        <label for="email">E-posta</label>
        <input type="email" name="email" id="email" required autofocus value="{{ old('email') }}">

        <label for="password">Şifre</label>
        <input type="password" name="password" id="password" required>

        <label style="display: flex; align-items: center; gap: 6px; margin-top: 12px;">
            <input type="checkbox" name="remember" value="1" style="margin: 0;">
            Beni hatırla
        </label>

        <p><button type="submit">Giriş Yap</button></p>
    </form>
@endsection
