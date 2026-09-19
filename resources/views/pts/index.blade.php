@extends('layouts.app')

@section('title', 'PTS Paket Sorgulama')

@section('content')
    <h1>PTS Paket Sorgulama</h1>

    @if ($error)
        <p class="error">{{ $error }}</p>
    @endif

    <form method="POST" action="{{ route('pts.search') }}">
        @csrf

        @if (auth()->user()->isAdmin())
            <label for="depot_id">Depo</label>
            <select name="depot_id" id="depot_id" required>
                <option value="">Seçiniz</option>
                @foreach ($depots as $depot)
                    <option value="{{ $depot->id }}" @selected(old('depot_id') == $depot->id)>{{ $depot->company_title }}</option>
                @endforeach
            </select>
        @endif

        <label for="source_gln">Gönderen GLN</label>
        <input type="text" name="source_gln" id="source_gln" maxlength="20" required value="{{ old('source_gln') }}">

        <label for="destination_gln">Alıcı GLN</label>
        <input type="text" name="destination_gln" id="destination_gln" maxlength="20" required value="{{ old('destination_gln') }}">

        <label for="start_date">Başlangıç Tarihi</label>
        <input type="date" name="start_date" id="start_date" required value="{{ old('start_date') }}">

        <label for="end_date">Bitiş Tarihi</label>
        <input type="date" name="end_date" id="end_date" required value="{{ old('end_date') }}">

        <p><button type="submit">Sorgula</button></p>
    </form>

    @if (is_array($results))
        <h2>Sonuçlar</h2>
        <table>
            <thead>
                <tr>
                    <th>Gönderen GLN</th>
                    <th>Alıcı GLN</th>
                    <th>Transfer ID</th>
                    <th>Transfer Tarihi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($results as $result)
                    <tr>
                        <td>{{ $result['sourceGln'] ?? '-' }}</td>
                        <td>{{ $result['destinationGln'] ?? '-' }}</td>
                        <td>{{ $result['transferId'] ?? '-' }}</td>
                        <td>{{ $result['transferDate'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Sonuç bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
