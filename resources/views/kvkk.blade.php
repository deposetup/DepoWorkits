@extends('layouts.app')
@section('baslik', 'KVKK Aydınlatma Metni')
@section('icerik')
<h4>KVKK Aydınlatma Metni</h4>
<div class="card mb-3"><div class="card-body small">
  <p>DepoWork ITS, İlaç Takip Sistemi bildirimlerini yönetmeniz için kullanıcı adı, e-posta, giriş kayıtları (IP ve zaman) ve ürün hareketi verilerinizi işler. İTS kullanıcı bilgileriniz şifreli saklanır ve yalnızca Bakanlık İTS servislerine bildirim yapmak için kullanılır.</p>
  <p>Verileriniz hizmetin sunulması, güvenliğin sağlanması ve yasal yükümlülükler amacıyla işlenir; yasal zorunluluk dışında üçüncü kişilerle paylaşılmaz. Haklarınız için destek kanalımızla iletişime geçebilirsiniz.</p>
  <p class="text-muted mb-0">Not: Bu metin taslaktır; yayına almadan önce şirketinizin hukuk danışmanı tarafından onaylanmış metinle değiştirilmelidir.</p>
</div></div>
<form method="post" action="{{ route('kvkk.onay') }}">@csrf<button class="btn btn-warning">ONAYLIYORUM</button></form>
@endsection
