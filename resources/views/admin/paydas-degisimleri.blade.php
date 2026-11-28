@extends('layouts.app')
@section('baslik', 'Paydaş Değişimleri')
@section('icerik')
<h4>Paydaş Değişimleri <small class="text-muted">Depolar</small></h4>
@if(! $kayitlar)<div class="alert alert-warning">İzleme tabloları henüz oluşturulmadı (migration çalıştırılmalı).</div>@else
<div class="alert alert-secondary small">İTS depo listesi hafta içi her gün <b>12:00 ve 17:00</b>'de karşılaştırılır. Şu an izlenen depo: <b>{{ number_format($izlenen, 0, ',', '.') }}</b>. Son kontrol: <b>{{ $sonKontrol ? \Illuminate\Support\Carbon::parse($sonKontrol)->timezone('Europe/Istanbul')->format('d.m.Y H:i') : 'henüz yapılmadı' }}</b>.
Bildirim adresi: <b>{{ $alici }}</b>.
<form method="post" action="{{ route('admin.paydas-degisimleri.test-mail') }}" class="d-inline ms-2">@csrf<button class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-envelope me-1"></i>Test maili gönder</button></form>
<a href="{{ route('admin.mail-ayarlari') }}" class="btn btn-sm btn-outline-primary py-0 ms-1"><i class="bi bi-gear me-1"></i>Mail Ayarları</a>
@unless($mailAyarli)<br><span class="text-danger">E-posta ayarı yapılmadı: değişiklikler yalnızca bu sayfada görünür, e-posta ayarlanınca bekleyenler tek postada gider.</span>@endunless</div>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3"><select name="olay" class="form-select form-select-sm"><option value="">Tüm olaylar</option>@foreach($olaylar as $k => [$ad])<option value="{{ $k }}" @selected(($f['olay'] ?? null) === $k)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-4"><input name="ara" value="{{ $f['ara'] ?? '' }}" class="form-control form-control-sm" placeholder="Depo adı veya GLN"></div>
  <div class="col-md-3"><button class="btn btn-sm btn-primary">Filtrele</button> <a href="{{ route('admin.paydas-degisimleri') }}" class="btn btn-sm btn-outline-secondary">Sıfırla</a></div>
</form>
<table class="table table-sm bg-white align-middle">
<thead><tr><th>Tespit</th><th>Depo</th><th>GLN</th><th>İl</th><th>Olay</th><th>E-posta</th></tr></thead><tbody>
@forelse($kayitlar as $d)
<tr><td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($d->created_at)->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</td><td>{{ $d->unvan }}</td><td class="font-monospace">{{ $d->gln }}</td><td>{{ $d->il ?: '—' }}</td>
<td><span class="badge text-bg-{{ $olaylar[$d->olay][1] ?? 'secondary' }}">{{ $olaylar[$d->olay][0] ?? $d->olay }}</span></td>
<td class="small">{{ $d->bildirildi_at ? 'Gönderildi' : 'Bekliyor' }}</td></tr>
@empty<tr><td colspan="6" class="text-muted">Henüz değişiklik tespit edilmedi.</td></tr>@endforelse
</tbody></table>
{{ $kayitlar->links('pagination::bootstrap-5') }}
@endif
@endsection
