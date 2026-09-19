@extends('layouts.app')

@section('title', 'Yeni Depo')

@section('content')
    <h1>Yeni Depo</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('depots.store') }}" style="max-width: 480px;">
        @csrf

        <label for="company_title">Firma Unvanı</label>
        <input type="text" name="company_title" id="company_title" required value="{{ old('company_title') }}">

        <label for="authorized_person">Yetkili Kişi</label>
        <input type="text" name="authorized_person" id="authorized_person" value="{{ old('authorized_person') }}">

        <label for="phone">Telefon</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone') }}">

        <label for="email">E-posta</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}">

        <label for="gln_number">GLN Numarası</label>
        <input type="text" name="gln_number" id="gln_number" required maxlength="20" value="{{ old('gln_number') }}">

        <label for="its_password">İTS Şifresi</label>
        <input type="password" name="its_password" id="its_password" required minlength="6">

        <label for="status">Durum</label>
        <select name="status" id="status" required>
            <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
            <option value="passive" @selected(old('status') === 'passive')>Pasif</option>
        </select>

        <p><button type="submit">Kaydet</button></p>
    </form>
@endsection
