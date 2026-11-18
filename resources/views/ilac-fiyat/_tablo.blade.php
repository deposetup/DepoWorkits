@php $para = fn ($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.'); $isk = fn ($v) => $v === null ? '—' : '%'.rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ','); @endphp
<div class="table-responsive"><table class="table table-sm table-hover bg-white align-middle small">
<thead class="table-light"><tr><th>GTIN</th><th>İlaç Adı</th><th>Üretici</th><th>Durum</th><th>Geçerlilik</th><th>Değişiklik</th><th class="text-end">ISF</th><th class="text-end">DSF</th><th class="text-end">PSF (KDV Hariç)</th><th class="text-end">PSF</th><th class="text-end">Kurum İsk.</th><th class="text-end">Özel İsk.</th></tr></thead><tbody>
@forelse($sonuclar as $f)
<tr class="{{ $f->aktif ? '' : 'text-muted' }}"><td class="font-monospace">{{ $f->gtin }}</td><td class="fw-semibold">{{ $f->ad ?? '—' }}</td><td>{{ $f->uretici ?? '—' }}</td>
<td class="text-nowrap">@if($f->aktif)<span class="badge text-bg-success">Aktif</span>@else<span class="badge text-bg-secondary">Pasif</span>@endif @if($f->ithal)<span class="badge text-bg-info">İthal</span>@endif</td>
<td class="text-nowrap">{{ $f->gecerlilik_tarihi?->format('d.m.Y') ?? '—' }}</td><td class="text-nowrap">{{ $f->degisiklik_tarihi?->format('d.m.Y') ?? '—' }}</td>
<td class="text-end">{{ $para($f->isf) }}</td><td class="text-end">{{ $para($f->dsf) }}</td><td class="text-end">{{ $para($f->psf_kdvsiz) }}</td><td class="text-end fw-semibold">{{ $para($f->psf) }}</td><td class="text-end">{{ $isk($f->kurum_isk) }}</td><td class="text-end">{{ $isk($f->ozel_isk) }}</td></tr>
@empty<tr><td colspan="12" class="text-muted">Eşleşen ilaç bulunamadı.</td></tr>@endforelse
</tbody></table></div>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><div class="small text-muted">{{ number_format($sonuclar->total(), 0, ',', '.') }} kayıt bulundu · sayfa başına {{ $sonuclar->perPage() }} satır</div>{{ $sonuclar->links() }}</div>
