@extends('layouts.app')
@section('baslik', 'PTS Sorgu')
@section('icerik')
<h4>Tarih ile PTS Sorgulama</h4>
<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-md-3"><label class="form-label">Başlangıç</label><input type="date" id="baslangic" name="baslangic" value="{{ request('baslangic') }}" class="form-control"></div>
  <div class="col-md-3"><label class="form-label">Bitiş</label><input type="date" id="bitis" name="bitis" value="{{ request('bitis') }}" class="form-control"></div>
  <div class="col-md-3"><label class="form-label">Gönderen GLN (isteğe bağlı)</label><input name="kaynak_gln" value="{{ request('kaynak_gln') }}" maxlength="13" class="form-control" placeholder="13 hane"></div>
  <div class="col-md-3 form-check ms-2"><input type="checkbox" class="form-check-input" name="sadece_alinmayan" value="1" id="sa" @checked(request('sadece_alinmayan'))><label for="sa" class="form-check-label">Sadece alınmayanlar</label></div>
  <div class="col-md-2"><button class="btn btn-primary">Sorgula</button></div>
</form>
@if($hata)<div class="alert alert-danger">{{ $hata }}</div>@endif
<div class="card mb-3"><div class="card-body">
  <h6><i class="bi bi-file-earmark-arrow-up me-1"></i>XML / ZIP dosyası ile içeri al</h6>
  <p class="small text-muted mb-2">İTS'ten bağımsız olarak elinizdeki PTS dosyasını (.xml veya içinde XML olan .zip, en fazla 20 MB) yükleyin. Ürünler okunup paket önizlemesi açılır; doğrulama, Excel ve Mal Alım oradan yapılır. Dosya yüklemek İTS'e bildirim göndermez.</p>
  <form method="post" action="{{ route('pts.yukle') }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">@csrf
    <input type="file" name="dosya" accept=".xml,.zip" class="form-control w-auto" required>
    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Dosyayı Oku</button>
  </form>
</div></div>
@if(is_array($sonuc))
<div class="mb-2 text-end"><a href="{{ request()->fullUrlWithQuery(['excel' => 1]) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a></div>
<table class="table table-sm bg-white"><thead><tr><th>Transfer No</th><th>Gönderen GLN</th><th>Alıcı GLN</th><th>Tarih</th><th>Durum</th><th></th></tr></thead><tbody>
@forelse($sonuc as $r)<tr><td>{{ $r['transferId'] }}</td><td>{{ $r['gonderen'] }}</td><td>{{ $r['alici'] ?? '' }}</td><td>{{ $r['tarih'] ? \Illuminate\Support\Carbon::parse($r['tarih'])->format('d.m.Y H:i') : '' }}</td><td>{{ $r['alindi'] === null ? '—' : ($r['alindi'] ? 'Alındı' : 'Alınmadı') }}</td><td><form method="post" action="{{ route('pts.al') }}">@csrf<input type="hidden" name="transfer_id" value="{{ $r['transferId'] }}"><button class="btn btn-sm btn-outline-primary">Paketi Al</button></form></td></tr>
@empty<tr><td colspan="6" class="text-muted">Kayıt yok.</td></tr>@endforelse</tbody></table>
<p class="small text-muted">Tarih aralığı en fazla 31 gün. "Alındı" bilgisi yalnızca "Sadece alınmayanlar" seçiliyken kesindir. Not: PTS paketi almak mal kabul sayılmaz; ürünler için ayrıca Mal Kabul bildirimi gerekir.</p>
@endif
<script>
// Başlangıç tarihi seçilince bitiş, aynı ayın son günü olur (bu ay ise bugün).
document.getElementById('baslangic').addEventListener('change', function () {
    if (!this.value) return;
    var p = this.value.split('-'), y = +p[0], m = +p[1];
    var son = new Date(y, m, 0), bugun = new Date();
    bugun = new Date(bugun.getFullYear(), bugun.getMonth(), bugun.getDate());
    if (son > bugun && bugun >= new Date(y, m - 1, +p[2])) son = bugun;
    var f = function (n) { return String(n).padStart(2, '0'); };
    document.getElementById('bitis').value = son.getFullYear() + '-' + f(son.getMonth() + 1) + '-' + f(son.getDate());
});
</script>
@endsection
