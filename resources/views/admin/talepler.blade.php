@extends('layouts.app')
@section('baslik', 'Destek Talepleri')
@section('icerik')
<h4>Destek Talepleri</h4>
<div class="btn-group mb-3">
  @foreach([null => 'Tümü', 'yeni' => 'Yeni', 'islemde' => 'İşlemde', 'kapandi' => 'Kapandı'] as $k => $ad)
  <a class="btn btn-sm {{ $durum === $k ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.talepler', $k ? ['durum' => $k] : []) }}">{{ $ad }}</a>
  @endforeach
</div>
<table class="table table-sm bg-white align-middle">
<thead><tr><th>Tarih</th><th>Müşteri</th><th>Konu</th><th>Mesaj</th><th>Telefon</th><th>Durum</th><th></th></tr></thead><tbody>
@forelse($talepler as $t)
<tr><td class="text-nowrap">{{ $t->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</td><td>{{ $tenantlar[$t->tenant_id] ?? '—' }}</td><td>{{ $konular[$t->konu] ?? $t->konu }}</td>
<td style="white-space:pre-line;max-width:340px">{{ $t->mesaj ?? '—' }}</td><td>{{ $t->telefon ?? '—' }}</td>
<td><span class="badge text-bg-{{ ['yeni' => 'warning', 'islemde' => 'info', 'kapandi' => 'secondary'][$t->durum] ?? 'secondary' }}">{{ ['yeni' => 'Yeni', 'islemde' => 'İşlemde', 'kapandi' => 'Kapandı'][$t->durum] ?? $t->durum }}</span></td>
<td><form method="post" action="{{ route('admin.talepler.durum', $t) }}" class="d-flex gap-1">@csrf
<select name="durum" class="form-select form-select-sm" style="width:110px"><option value="yeni" @selected($t->durum==='yeni')>Yeni</option><option value="islemde" @selected($t->durum==='islemde')>İşlemde</option><option value="kapandi" @selected($t->durum==='kapandi')>Kapandı</option></select>
<button class="btn btn-sm btn-outline-secondary">Kaydet</button></form></td></tr>
@empty<tr><td colspan="7" class="text-muted">Talep yok.</td></tr>@endforelse
</tbody></table>
@endsection
