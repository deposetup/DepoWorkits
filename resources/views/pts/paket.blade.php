@extends('layouts.app')
@section('baslik', 'PTS Paketi')
@section('icerik')
<a href="{{ route('pts') }}" class="small">&larr; PTS Sorgu</a>
<h4 class="mt-2">PTS Paketi <small class="text-muted">{{ $paket->transfer_id }}</small></h4>
<div class="mb-3 small">
  @php $cariAdlari = \App\Models\Cari::adlar(auth()->user()->tenant_id); @endphp
  Gönderen: {{ $paket->kaynak_gln ?? '—' }}@if($ad = ($cariAdlari[$paket->kaynak_gln ?? ''] ?? null)) <span class="badge text-bg-primary"><i class="bi bi-person-check"></i> Cari: {{ $ad }}</span>@elseif($paket->kaynak_gln) <a href="{{ route('paydaslar') }}" class="small">(cariye ekle)</a>@endif · Belge No: {{ $paket->belge_no ?? '—' }} ·
  Bütünlük (MD5): {!! is_null($paket->md5_uyumlu) ? '<span class="text-muted">kontrol edilemedi</span>' : ($paket->md5_uyumlu ? '<span class="text-success">uyumlu</span>' : '<span class="text-danger">UYUMSUZ</span>') !!} ·
  Durum: <span class="badge text-bg-{{ $paket->durum === 'mal_alindi' ? 'success' : 'secondary' }}">{{ $paket->durum === 'mal_alindi' ? 'Mal alındı' : ($paket->durum === 'sifirlandi' ? 'Havuzdan çıkarıldı (sıfırlandı)' : 'İndirildi (mal kabul yapılmadı)') }}</span>
</div>
<div class="alert alert-warning small">Paketi indirmek mal kabul sayılmaz. Ürünler stoğa ancak aşağıdaki <b>Mal Alım</b> bildirimi başarılı olunca girer.</div>
@foreach($hatalar as $h)<div class="alert alert-danger">{{ $h }}</div>@endforeach

@php($ilr = $paket->ilerleme)
@if(count($urunler))
@if($paket->durum !== 'mal_alindi' || ($ilr && $ilr['hatali'] > 0))
<div id="malAlimKutu" class="card mb-3 border-primary"><div class="card-body">
  <div id="malAlimBtnSatir">
    <button type="button" id="malAlimBtn" class="btn btn-primary" data-toplam="{{ count($urunler) }}">
      @if($ilr && ! $ilr['bitti'] && $ilr['islenen'] > 0)Devam Et ({{ $ilr['islenen'] }} / {{ $ilr['toplam'] }} işlendi)@else Mal Alım Yap ({{ count($urunler) }} ürün)@endif
    </button>
  </div>
  <div id="malAlimIlerleme" class="d-none mt-2">
    <div class="progress" style="height:24px"><div id="malAlimBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%">0%</div></div>
    <div id="malAlimMetin" class="small mt-1"></div>
  </div>
  <div id="malAlimHata" class="alert alert-danger d-none mt-2 mb-0"></div>
</div></div>
<script>
(function () {
  const btn = document.getElementById('malAlimBtn'); if (!btn) return;
  const url = @json(route('pts.mal-alim', $paket)), token = @json(csrf_token());
  const bar = document.getElementById('malAlimBar'), metin = document.getElementById('malAlimMetin'),
        kutu = document.getElementById('malAlimIlerleme'), hata = document.getElementById('malAlimHata');
  let devam = @json((bool) ($ilr && ! $ilr['bitti'] && $ilr['islenen'] > 0));
  let calisiyor = false;
  function goster(d) {
    const yuzde = d.toplam ? Math.floor(d.islenen * 100 / d.toplam) : 100;
    bar.style.width = yuzde + '%'; bar.textContent = yuzde + '%';
    metin.textContent = d.islenen + ' / ' + d.toplam + ' ürün işlendi · Başarılı: ' + d.basarili + ' · Hatalı: ' + d.hatali;
  }
  async function adim(basla) {
    const r = await fetch(url, {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json'}, body: JSON.stringify({basla: basla ? 1 : 0})});
    const d = await r.json().catch(() => ({mesaj: 'Sunucudan geçersiz yanıt (' + r.status + ').'}));
    if (!r.ok) { const e = new Error(d.hata || d.mesaj || d.message || ('Sunucu hatası (HTTP ' + r.status + ')')); e.durum = d; throw e; }
    return d;
  }
  btn.addEventListener('click', async function () {
    if (calisiyor) return;
    if (!devam && !confirm('Bu paketteki ' + btn.dataset.toplam + ' ürün için Mal Alım bildirimi yapılacak. Onaylıyor musunuz?')) return;
    calisiyor = true; btn.disabled = true; hata.classList.add('d-none'); kutu.classList.remove('d-none');
    window.onbeforeunload = () => 'Mal Alım sürüyor, sayfadan ayrılmayın.';
    let basla = !devam, d;
    try {
      do { d = await adim(basla); basla = false; goster(d); } while (!d.bitti);
      window.onbeforeunload = null; location.reload();
    } catch (e) {
      window.onbeforeunload = null; calisiyor = false; btn.disabled = false;
      btn.textContent = 'Kaldığı yerden devam et';
      hata.textContent = 'Hata: ' + e.message + ' — "Kaldığı yerden devam et" ile tekrar deneyebilirsiniz.'; hata.classList.remove('d-none');
      devam = true;
    }
  });
})();
</script>
@endif

@if($ilr && $ilr['bitti'])
<div class="card mt-3"><div class="card-header">Mal Alım sonucu @if($ilr['bildirim'])<small class="text-muted">Son Bildirim No: {{ $ilr['bildirim'] }}</small>@endif
  <span class="ms-2 text-success">Başarılı: {{ $ilr['basarili'] }}</span> <span class="ms-2 text-danger">Hatalı: {{ $ilr['hatali'] }}</span></div>
@if($ilr['hatalar'])<table class="table table-sm mb-0">
@foreach($ilr['hatalar'] as $s)<tr class="table-danger"><td class="font-monospace small">{{ $s['kod'] }}</td><td>{{ $s['mesaj'] }}</td></tr>@endforeach
</table>@if($ilr['hatali'] > count($ilr['hatalar']))<div class="p-2 small text-muted">İlk {{ count($ilr['hatalar']) }} hata gösteriliyor.</div>@endif @endif</div>
@endif


<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">{{ count($urunler) }} ürün bulundu</h6><a href="{{ route('pts.excel', $paket) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a></div>
@php($grup = collect($urunler)->groupBy('gtin'))
<table class="table table-sm bg-white"><thead><tr><th>GTIN</th><th>Parti</th><th>SKT</th><th class="text-end">Adet</th></tr></thead><tbody>
@foreach($grup as $gtin => $l)
@foreach($l->groupBy(fn ($u) => ($u['bn'] ?? '').'|'.($u['xd'] ?? '')) as $alt)
<tr><td class="font-monospace">{{ $gtin }}</td><td>{{ $alt[0]['bn'] ?? '—' }}</td><td>{{ $alt[0]['xd'] ?? '—' }}</td><td class="text-end">{{ count($alt) }}</td></tr>
@endforeach
@endforeach
</tbody></table>
<form method="post" action="{{ route('pts.dogrula', $paket) }}" class="mb-2">@csrf<button class="btn btn-outline-secondary btn-sm">Ürünleri İTS'te Doğrula (salt-okunur)</button></form>
@elseif(! $hatalar)
<div class="alert alert-info">Pakette GTIN ve seri numarası taşıyan ürün bulunamadı. XML yapısı beklediğimizden farklı olabilir; aşağıdaki etiket özetini destek ekibine/geliştiriciye iletin.</div>
@endif

@if(! count($urunler) && count($yapi))
<h6 class="mt-3">XML yapı özeti (etiket: sayı)</h6>
<p class="small font-monospace">@foreach($yapi as $k => $n){{ $k }}: {{ $n }}@if(! $loop->last) · @endif @endforeach</p>
@endif
@endsection
