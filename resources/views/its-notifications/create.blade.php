@extends('layouts.app')

@section('title', $type === 'alim' ? 'Mal Alım Bildirimi' : 'Mal İade Bildirimi')

@section('content')
    <h1>{{ $type === 'alim' ? 'Mal Alım Bildirimi' : 'Mal İade Bildirimi' }}</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('its-notifications.store') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        @if (auth()->user()->isAdmin())
            <label for="depot_id">Depo</label>
            <select name="depot_id" id="depot_id" required>
                <option value="">Seçiniz</option>
                @foreach ($depots as $depot)
                    <option value="{{ $depot->id }}" @selected(old('depot_id') == $depot->id)>{{ $depot->company_title }}</option>
                @endforeach
            </select>
        @endif

        @if ($type === 'iptal_iade')
            <label for="togln">Karşı Paydaşın GLN Numarası</label>
            <input type="text" name="togln" id="togln" maxlength="20" required value="{{ old('togln') }}">
        @endif

        <h2>Ürünler</h2>
        <table id="product-rows">
            <thead>
                <tr>
                    <th>GTIN</th>
                    <th>Seri No (SN)</th>
                    <th>Parti No (BN)</th>
                    <th>Son Kullanma Tarihi</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="products[0][gtin]" required maxlength="20"></td>
                    <td><input type="text" name="products[0][sn]" required maxlength="20"></td>
                    <td><input type="text" name="products[0][bn]" required maxlength="20"></td>
                    <td><input type="date" name="products[0][xd]" required></td>
                    <td><button type="button" class="secondary" onclick="this.closest('tr').remove()">Sil</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="secondary" id="add-row">+ Ürün Ekle</button>

        <p><button type="submit">Gönder</button></p>
    </form>

    <script>
        (function () {
            var tbody = document.querySelector('#product-rows tbody');
            document.getElementById('add-row').addEventListener('click', function () {
                var index = tbody.querySelectorAll('tr').length;
                var row = document.createElement('tr');
                row.innerHTML =
                    '<td><input type="text" name="products[' + index + '][gtin]" required maxlength="20"></td>' +
                    '<td><input type="text" name="products[' + index + '][sn]" required maxlength="20"></td>' +
                    '<td><input type="text" name="products[' + index + '][bn]" required maxlength="20"></td>' +
                    '<td><input type="date" name="products[' + index + '][xd]" required></td>' +
                    '<td><button type="button" class="secondary" onclick="this.closest(\'tr\').remove()">Sil</button></td>';
                tbody.appendChild(row);
            });
        })();
    </script>
@endsection
