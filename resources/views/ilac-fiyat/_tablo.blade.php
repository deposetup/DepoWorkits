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
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
  <div class="small text-muted">{{ number_format($sonuclar->total(), 0, ',', '.') }} kayıt bulundu · {{ $sonuclar->firstItem() ?? 0 }}–{{ $sonuclar->lastItem() ?? 0 }} arası gösteriliyor · sayfa başına {{ $sonuclar->perPage() }} satır</div>
  @if($sonuclar->hasPages())
  <nav class="d-flex align-items-center gap-2" aria-label="Sayfalar">
    <a class="btn btn-sm btn-outline-secondary {{ $sonuclar->onFirstPage() ? 'disabled' : '' }}" href="{{ $sonuclar->url(1) }}" @if($sonuclar->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>« İlk</a>
    <a class="btn btn-sm btn-outline-primary {{ $sonuclar->onFirstPage() ? 'disabled' : '' }}" href="{{ $sonuclar->previousPageUrl() ?? '#' }}" @if($sonuclar->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>‹ Önceki</a>
    <span class="small px-2">Sayfa <b>{{ $sonuclar->currentPage() }}</b> / {{ $sonuclar->lastPage() }}</span>
    <a class="btn btn-sm btn-outline-primary {{ $sonuclar->hasMorePages() ? '' : 'disabled' }}" href="{{ $sonuclar->nextPageUrl() ?? '#' }}" @unless($sonuclar->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>Sonraki ›</a>
    <a class="btn btn-sm btn-outline-secondary {{ $sonuclar->hasMorePages() ? '' : 'disabled' }}" href="{{ $sonuclar->url($sonuclar->lastPage()) }}" @unless($sonuclar->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>Son »</a>
  </nav>
  @endif
</div>
