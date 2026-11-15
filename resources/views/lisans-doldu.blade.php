@extends('layouts.app')
@section('baslik', 'Lisans Süresi Doldu')
@section('icerik')
<div class="alert alert-danger">
  <h5>Lisans süreniz doldu</h5>
  <p class="mb-0">{{ $tenant->unvan }} için lisans {{ $tenant->lisans_bitis->format('d.m.Y') }} tarihinde sona erdi. İTS işlemleri durduruldu. Yenilemek için bizimle iletişime geçin.</p>
</div>
@endsection
