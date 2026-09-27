@extends('layouts.app')

@section('title', 'Karekod Sorgulama')

@section('content')
    <div class="kq-header">
        <div>
            <h1>Karekod Sorgulama</h1>
            <p>Ürünün İTS'deki durumunu ve son sahibini sorgulayın.</p>
        </div>
    </div>

    @if ($error)
        <p class="error">{{ $error }}</p>
    @endif

    @if ($errors->any())
        <p class="errors">{{ $errors->first() }}</p>
    @endif

    {{-- Tek form: karekod doluysa o kullanılır, boşsa GTIN + SN. --}}
    <form method="POST" action="{{ route('karekod.search') }}" class="kq-form">
        @csrf

        @if (auth()->user()->isAdmin())
            <div class="kq-depot">
                <label for="depot_id">Depo</label>
                <select name="depot_id" id="depot_id" required>
                    <option value="">Depo seçiniz</option>
                    @foreach ($depots as $item)
                        <option value="{{ $item->id }}" @selected(old('depot_id', $depot?->id) == $item->id)>{{ $item->company_title }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="kq-panels">
            <section class="kq-card">
                <header>
                    <span class="kq-step">1</span>
                    <h2>GTIN - SN ile Sorgula</h2>
                </header>
                <div class="kq-fields">
                    <div>
                        <label for="gtin">GTIN (Barkod)</label>
                        <input type="text" name="gtin" id="gtin" inputmode="numeric" maxlength="14" placeholder="08699..." value="{{ old('gtin') }}">
                    </div>
                    <div>
                        <label for="sn">Sıra No (SN)</label>
                        <input type="text" name="sn" id="sn" maxlength="20" value="{{ old('sn') }}">
                    </div>
                    <button type="submit">Sorgula</button>
                </div>
            </section>

            <section class="kq-card">
                <header>
                    <span class="kq-step">2</span>
                    <h2>Karekod Okuyucu ile Sorgula</h2>
                </header>
                <label for="karekod">Karekod</label>
                <input type="text" name="karekod" id="karekod" class="kq-scan" placeholder="İmleç buradayken karekodu okutun" autofocus autocomplete="off">
                <p class="kq-hint">Okuyucu okutma sonrası sorguyu otomatik gönderir.</p>
            </section>
        </div>
    </form>

    @if ($result !== null)
        @php
            // İTS kodu sayı olarak da dönebilir; "00000" gibi 5 haneli yazıya çevir.
            $code = isset($result['uc']) ? str_pad((string) $result['uc'], 5, '0', STR_PAD_LEFT) : null;
            $owner = $result['gln1'] ?? null;
            $isOurs = $owner && $owner === $depot->gln_number;
            $tone = match (true) {
                in_array($code, ['00000', '60014', '10210', '10223'], true) => 'ok',
                in_array($code, ['10201', '10202', '10203', '10205'], true) => 'bad',
                default => 'warn',
            };
        @endphp

        <section class="kq-result kq-{{ $tone }}">
            <header>
                <div>
                    <span class="kq-label">Sorgu Sonucu</span>
                    <strong>{{ $product['gtin'] }} / {{ $product['sn'] }}</strong>
                </div>
                <span class="kq-badge">{{ $code ?? '-' }}</span>
            </header>

            <p class="kq-status">{{ $code ? config("its_codes.$code", 'Bilinmeyen kod') : 'İTS durum bilgisi dönmedi.' }}</p>

            <dl class="kq-grid">
                <div>
                    <dt>GTIN</dt>
                    <dd>{{ $product['gtin'] }}</dd>
                </div>
                <div>
                    <dt>Sıra No</dt>
                    <dd>{{ $product['sn'] }}</dd>
                </div>
                <div>
                    <dt>Parti No</dt>
                    <dd>{{ $product['bn'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt>Son Kullanma Tarihi</dt>
                    <dd>{{ isset($product['xd']) ? \Carbon\Carbon::parse($product['xd'])->format('d.m.Y') : '-' }}</dd>
                </div>
                <div>
                    <dt>Son Sahibi (GLN)</dt>
                    <dd>
                        {{ $owner ?: '-' }}
                        @if ($isOurs)
                            <span class="kq-pill">{{ $depot->company_title }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Arada Olduğu GLN</dt>
                    <dd>{{ ($result['gln2'] ?? '') ?: '-' }}</dd>
                </div>
            </dl>
        </section>
    @endif

    <style>
        .kq-header h1 { margin: 0 0 4px; font-size: 22px; }
        .kq-header p { margin: 0 0 20px; color: #6b7280; font-size: 14px; }

        .kq-form label { display: block; margin: 0 0 6px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }
        .kq-form input, .kq-form select { width: 100%; box-sizing: border-box; height: 38px; padding: 0 10px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }
        .kq-form input:focus, .kq-form select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
        .kq-form button { height: 38px; padding: 0 20px; border-radius: 6px; font-weight: 600; }

        .kq-depot { max-width: 360px; margin-bottom: 16px; }

        .kq-panels { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
        .kq-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px 20px; }
        .kq-card header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
        .kq-card h2 { margin: 0; font-size: 15px; font-weight: 600; color: #1f2933; }
        .kq-step { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #eff6ff; color: #2563eb; font-size: 12px; font-weight: 700; }

        .kq-fields { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end; }
        .kq-scan { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .kq-hint { margin: 8px 0 0; font-size: 12px; color: #6b7280; }

        .kq-result { margin-top: 24px; background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid var(--kq-tone); border-radius: 8px; padding: 18px 20px; }
        .kq-ok { --kq-tone: #059669; --kq-tone-bg: #ecfdf5; }
        .kq-warn { --kq-tone: #d97706; --kq-tone-bg: #fffbeb; }
        .kq-bad { --kq-tone: #dc2626; --kq-tone-bg: #fef2f2; }
        .kq-result header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .kq-label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }
        .kq-result strong { font-size: 16px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .kq-badge { padding: 4px 10px; border-radius: 999px; background: var(--kq-tone-bg); color: var(--kq-tone); font-size: 13px; font-weight: 700; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .kq-status { margin: 12px 0 16px; padding: 10px 12px; border-radius: 6px; background: var(--kq-tone-bg); color: #1f2933; font-size: 14px; }

        .kq-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px 24px; margin: 0; }
        .kq-grid div { border-top: 1px solid #f1f5f9; padding-top: 10px; }
        .kq-grid dt { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .kq-grid dd { margin: 0; font-size: 14px; font-weight: 500; }
        .kq-pill { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 600; }

        @media (max-width: 560px) {
            .kq-fields { grid-template-columns: 1fr; }
        }
    </style>
@endsection
