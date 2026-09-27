@extends('layouts.app')

@section('title', 'Karekod Sorgulama')

@section('content')
    <h1>Karekod Sorgulama</h1>

    @if ($error)
        <p class="error">{{ $error }}</p>
    @endif

    @if ($errors->any())
        <p class="errors">{{ $errors->first() }}</p>
    @endif

    <div class="karekod-panels">
        <form method="POST" action="{{ route('karekod.search') }}" class="panel">
            @csrf
            <h2>GTIN - SN</h2>
            @include('karekod._depot')
            <input type="text" name="gtin" placeholder="GTIN" maxlength="14" value="{{ old('gtin') }}">
            <input type="text" name="sn" placeholder="SN" maxlength="20" value="{{ old('sn') }}">
            <button type="submit">Sorgula</button>
        </form>

        <form method="POST" action="{{ route('karekod.search') }}" class="panel">
            @csrf
            <h2>Karekod Okuyucu İle Sorgula</h2>
            @include('karekod._depot')
            <p class="hint">İmleci alana getirip karekodu okutun.</p>
            <input type="text" name="karekod" placeholder="Karekod" autofocus autocomplete="off">
        </form>
    </div>

    @if ($result !== null)
        @php
            $code = $result['uc'] ?? null;
            $owner = $result['gln1'] ?? null;
        @endphp

        <h2>Sonuç</h2>
        <table>
            <tbody>
                <tr><th>GTIN</th><td>{{ $product['gtin'] }}</td></tr>
                <tr><th>Sıra No</th><td>{{ $product['sn'] }}</td></tr>
                @isset($product['bn'])
                    <tr><th>Parti No</th><td>{{ $product['bn'] }}</td></tr>
                @endisset
                @isset($product['xd'])
                    <tr><th>Son Kullanma Tarihi</th><td>{{ \Carbon\Carbon::parse($product['xd'])->format('d.m.Y') }}</td></tr>
                @endisset
                <tr>
                    <th>Son Sahibi (GLN)</th>
                    <td>{{ $owner ?: '-' }}@if ($owner && $owner === $depot->gln_number) ({{ $depot->company_title }})@endif</td>
                </tr>
                @if (! empty($result['gln2']))
                    <tr><th>Arada Olduğu GLN</th><td>{{ $result['gln2'] }}</td></tr>
                @endif
                <tr>
                    <th>Durum</th>
                    <td>{{ $code ? $code.' — '.config("its_codes.$code", 'Bilinmeyen kod') : '-' }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <style>
        .karekod-panels { display: flex; gap: 20px; flex-wrap: wrap; }
        .karekod-panels .panel { flex: 1 1 320px; background: #fff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 14px 16px; }
        .karekod-panels h2 { font-size: 14px; margin: 0 0 12px; color: #374151; }
        .karekod-panels input[name="karekod"] { width: 100%; box-sizing: border-box; }
        .karekod-panels .hint { font-size: 13px; color: #1e40af; background: #eff6ff; padding: 8px 10px; border-radius: 4px; }
        .karekod-panels select { margin-bottom: 10px; }
    </style>
@endsection
