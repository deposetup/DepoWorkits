@extends('layouts.app')

@section('title', 'İTS Bildirimleri')

@section('content')
    <h1>İTS Bildirimleri</h1>

    <p>
        <a class="button" href="{{ route('its-notifications.create', 'alim') }}">+ Mal Alım Bildirimi</a>
        <a class="button secondary" href="{{ route('its-notifications.create', 'iptal_iade') }}">+ Mal İade Bildirimi</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                @if (auth()->user()->isAdmin())
                    <th>Depo</th>
                @endif
                <th>Tür</th>
                <th>Durum</th>
                <th>İTS Referansı</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($notifications as $notification)
                <tr>
                    <td>{{ $notification->created_at->format('d.m.Y H:i') }}</td>
                    @if (auth()->user()->isAdmin())
                        <td>{{ $notification->depot->company_title }}</td>
                    @endif
                    <td>{{ $notification->typeLabel() }}</td>
                    <td>{{ $notification->status }}</td>
                    <td>{{ $notification->its_reference ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Henüz bildirim yok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $notifications->links() }}
@endsection
