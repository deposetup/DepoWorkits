@extends('layouts.app')

@section('title', 'Depolar')

@section('content')
    <h1>Depolar</h1>

    @if (auth()->user()->isAdmin())
        <p><a class="button" href="{{ route('depots.create') }}">+ Yeni Depo</a></p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Firma</th>
                <th>GLN</th>
                <th>Durum</th>
                <th>Lisans Bitiş</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($depots as $depot)
                <tr>
                    <td>{{ $depot->company_title }}</td>
                    <td>{{ $depot->gln_number }}</td>
                    <td>{{ $depot->status === 'active' ? 'Aktif' : 'Pasif' }}</td>
                    <td>{{ optional($depot->activeLicense)->ends_at?->format('d.m.Y') ?? '-' }}</td>
                    <td><a href="{{ route('depots.show', $depot) }}">Detay</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Henüz depo yok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (auth()->user()->isAdmin())
        {{ $depots->links() }}
    @endif
@endsection
