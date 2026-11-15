@extends('layouts.app')
@section('baslik', 'Giriş Kayıtları')
@section('icerik')
<h4>Giriş Kayıtları</h4>
@if(! $kayitlar)<div class="alert alert-warning">Giriş kayıt tablosu henüz oluşturulmadı (migration çalıştırılmalı).</div>@else
<form method="get" class="row g-2 mb-3">
  <div class="col-md-4"><select name="tenant" class="form-select form-select-sm"><option value="">Tüm müşteriler</option>@foreach($tenantlar as $id => $ad)<option value="{{ $id }}" @selected(($f['tenant'] ?? null) == $id)>{{ $ad }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="durum" class="form-select form-select-sm"><option value="">Tümü</option><option value="basarili" @selected(($f['durum'] ?? null) === 'basarili')>Başarılı</option><option value="basarisiz" @selected(($f['durum'] ?? null) === 'basarisiz')>Başarısız</option></select></div>
  <div class="col-md-2"><input type="date" name="tarih" value="{{ $f['tarih'] ?? '' }}" class="form-control form-control-sm"></div>
  <div class="col-md-2"><button class="btn btn-sm btn-primary">Filtrele</button> <a href="{{ route('admin.giris-kayitlari') }}" class="btn btn-sm btn-outline-secondary">Sıfırla</a></div>
</form>
<table class="table table-sm bg-white align-middle">
<thead><tr><th>Giriş Zamanı</th><th>Müşteri</th><th>Kullanıcı</th><th>Yöntem</th><th>Yazılan</th><th>Sonuç</th><th>IP</th><th>Tarayıcı</th></tr></thead><tbody>
@forelse($kayitlar as $k)
<tr class="{{ $k->basarili ? '' : 'table-danger' }}"><td class="text-nowrap">{{ $k->created_at->format('d.m.Y H:i:s') }}</td><td>{{ $k->tenant->unvan ?? '—' }}</td><td>{{ $k->user->name ?? '—' }}</td>
<td>{{ $k->yontem === 'gln' ? 'GLN' : 'E-posta' }}</td><td class="font-monospace small">{{ $k->kimlik }}</td>
<td>{!! $k->basarili ? '<span class="badge text-bg-success">Başarılı</span>' : '<span class="badge text-bg-danger">Başarısız</span>' !!}</td>
<td class="small">{{ $k->ip }}</td><td class="small text-muted" title="{{ $k->tarayici }}">{{ \Illuminate\Support\Str::limit($k->tarayici, 40) }}</td></tr>
@empty<tr><td colspan="8" class="text-muted">Kayıt yok.</td></tr>@endforelse
</tbody></table>
{{ $kayitlar->links('pagination::bootstrap-5') }}
@endif
@endsection
