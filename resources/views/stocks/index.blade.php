@extends('layouts.app')

@section('title', 'İTS Stok')

@section('content')
    <h1>İTS Stok</h1>

    @if (auth()->user()->isAdmin())
        <form method="GET" action="{{ route('stocks.index') }}">
            <select name="depot" onchange="this.form.submit()">
                <option value="">Depo seçiniz</option>
                @foreach ($depots as $item)
                    <option value="{{ $item->id }}" @selected($depot?->id === $item->id)>{{ $item->company_title }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if ($depot)
        @if ($stocks->isNotEmpty())
            <p>
                {{ $stocks->first()->stock_date->format('d.m.Y') }} gün sonu stoğu (İTS/KDS verisi bir gün geriden gelir)
                &middot; <a href="{{ route('stocks.export', ['depot' => $depot->id]) }}">Excel indir</a>
            </p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>GTIN</th>
                    <th>Ürün Adı</th>
                    <th>İTS Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr>
                        <td>{{ $stock->gtin }}</td>
                        <td>{{ $stock->product_name ?? '-' }}</td>
                        <td>{{ $stock->its_quantity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Henüz stok verisi yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
