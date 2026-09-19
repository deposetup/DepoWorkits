@extends('layouts.app')

@section('title', $depot->company_title)

@section('content')
    <h1>{{ $depot->company_title }}</h1>

    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (auth()->user()->isAdmin())
        <form method="POST" action="{{ route('depots.update', $depot) }}" style="max-width: 480px;">
            @csrf
            @method('PUT')

            <label for="company_title">Firma Unvanı</label>
            <input type="text" name="company_title" id="company_title" required
                value="{{ old('company_title', $depot->company_title) }}">

            <label for="authorized_person">Yetkili Kişi</label>
            <input type="text" name="authorized_person" id="authorized_person"
                value="{{ old('authorized_person', $depot->authorized_person) }}">

            <label for="phone">Telefon</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $depot->phone) }}">

            <label for="email">E-posta</label>
            <input type="email" name="email" id="email" value="{{ old('email', $depot->email) }}">

            <label for="gln_number">GLN Numarası</label>
            <input type="text" name="gln_number" id="gln_number" required maxlength="20"
                value="{{ old('gln_number', $depot->gln_number) }}">

            <label for="its_password">İTS Şifresi (değiştirmek istemiyorsan boş bırak)</label>
            <input type="password" name="its_password" id="its_password" minlength="6">

            <label for="status">Durum</label>
            <select name="status" id="status" required>
                <option value="active" @selected($depot->status === 'active')>Aktif</option>
                <option value="passive" @selected($depot->status === 'passive')>Pasif</option>
            </select>

            <p><button type="submit">Güncelle</button></p>
        </form>

        <h2>Değişiklik Talepleri</h2>
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Talep Eden</th>
                    <th>Sebep</th>
                    <th>Durum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($changeRequests as $changeRequest)
                    <tr>
                        <td>{{ $changeRequest->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $changeRequest->requestedBy->name }}</td>
                        <td>{{ $changeRequest->reason }}</td>
                        <td>{{ $changeRequest->status }}</td>
                        <td>
                            @if ($changeRequest->status === 'pending')
                                <form method="POST" action="{{ route('credential-requests.review', $changeRequest) }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="decision" value="approved">
                                    <button type="submit">Onayla</button>
                                </form>
                                <form method="POST" action="{{ route('credential-requests.review', $changeRequest) }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="decision" value="rejected">
                                    <button type="submit" class="secondary">Reddet</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Talep yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <tbody>
                <tr>
                    <th>GLN Numarası</th>
                    <td>{{ $depot->gln_number }}</td>
                </tr>
                <tr>
                    <th>İTS Şifresi</th>
                    <td>••••••••</td>
                </tr>
                <tr>
                    <th>Yetkili Kişi</th>
                    <td>{{ $depot->authorized_person ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Telefon</th>
                    <td>{{ $depot->phone ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Durum</th>
                    <td>{{ $depot->status === 'active' ? 'Aktif' : 'Pasif' }}</td>
                </tr>
            </tbody>
        </table>

        <h2>GLN/Şifre Değişikliği Talep Et</h2>
        <form method="POST" action="{{ route('credential-requests.store') }}" style="max-width: 480px;">
            @csrf
            <label for="reason">Talep Sebebi</label>
            <input type="text" name="reason" id="reason" required maxlength="1000" value="{{ old('reason') }}">
            <p><button type="submit">Talep Gönder</button></p>
        </form>

        <h2>Geçmiş Talepler</h2>
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Sebep</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($changeRequests as $changeRequest)
                    <tr>
                        <td>{{ $changeRequest->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $changeRequest->reason }}</td>
                        <td>{{ $changeRequest->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Talep yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
