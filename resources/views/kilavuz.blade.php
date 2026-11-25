@extends('layouts.app')
@section('baslik', 'Kullanım Kılavuzu')
@section('icerik')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h4 class="mb-0"><i class="bi bi-book me-2"></i>Kullanım Kılavuzu</h4>
  <button type="button" class="btn btn-sm btn-outline-secondary d-print-none" onclick="window.print()"><i class="bi bi-printer me-1"></i>Yazdır</button>
</div>
<p class="text-muted">DepoWork İTS, İlaç Takip Sistemi (İTS) bildirimlerinizi tek ekrandan yapmanızı ve stoğunuzu izlemenizi sağlar. Aşağıdaki başlıklara tıklayarak ilgili bölümü açabilirsiniz.</p>

<div class="card mb-3"><div class="card-body">
  <h6 class="mb-2"><i class="bi bi-signpost-split me-1"></i>Hızlı Başlangıç</h6>
  <ol class="mb-0">
    <li><b>Giriş yapın</b> ve ilk girişte KVKK aydınlatma metnini onaylayın.</li>
    <li><b>İTS Bağlantısı</b> ekranında kimlik durumunun <span class="badge text-bg-success">Tanımlı</span> olduğunu ve <i>Bağlantıyı Sına</i> sonucunun başarılı olduğunu kontrol edin.</li>
    <li>Gelen ürünleri <b>PTS Sorgu</b> ile paket olarak alın veya <b>Mal Kabul</b> ekranında karekod okutun.</li>
    <li>Satış, iade, sarf gibi işlemleri <b>Satış &amp; Çıkış</b> menüsünden yapın; stoğunuzu <b>Stok</b> menüsünden izleyin.</li>
  </ol>
</div></div>

<div class="accordion" id="kilavuz">

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#k1"><i class="bi bi-box-arrow-in-right me-2"></i>1. Giriş, Şifre ve Hesap</button></h2>
  <div id="k1" class="accordion-collapse collapse show" data-bs-parent="#kilavuz"><div class="accordion-body">
    <ul>
      <li><b>Giriş:</b> Size verilen kullanıcı adı ve şifre ile giriş yapılır. Çok sayıda hatalı denemede kısa süreli engel uygulanır.</li>
      <li><b>Şifremi unuttum:</b> Giriş ekranındaki bağlantıdan talep oluşturun; DepoWork ekibi sizinle iletişime geçer.</li>
      <li><b>Şifre Değiştir:</b> Menünün altındaki bu bağlantıdan kendi şifrenizi güncelleyebilirsiniz.</li>
      <li><b>KVKK onayı:</b> İlk girişte aydınlatma metnini onaylamadan işlem yapılamaz.</li>
      <li><b>Lisans:</b> Ana Sayfa'nın sağ üstünde kalan lisans süreniz görünür. Süre bittiğinde raporlar (stok, analiz vb.) salt okunur açık kalır; İTS bildirimleri için lisansın yenilenmesi gerekir. Yenileme için <b>0850 908 00 42</b> numarasını arayın.</li>
      <li><b>Çıkış:</b> Ortak bilgisayarlarda işiniz bitince menünün altındaki <i>Çıkış Yap</i> düğmesini kullanın.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k2"><i class="bi bi-house-door me-2"></i>2. Ana Sayfa</button></h2>
  <div id="k2" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <p>Ana Sayfa, günlük özetlerinizi ve sık kullanılan ekranlara kısayolları gösterir.</p>
    <ul>
      <li><b>Özetleri düzenle:</b> Kartların sırasını oklarla değiştirebilir, göz simgesiyle gizleyebilirsiniz. <i>Varsayılana dön</i> ile ilk haline getirirsiniz.</li>
      <li>Soldaki menü, sayfa içinde istediğiniz yere gitmenizi sağlar. Telefonda sol üstteki <i class="bi bi-list"></i> düğmesiyle açılır.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k3"><i class="bi bi-plug me-2"></i>3. İTS Bağlantısı</button></h2>
  <div id="k3" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <ul>
      <li>İTS kullanıcı adı ve şifreniz DepoWork ekibi tarafından tanımlanır, şifreli saklanır ve ekranda gösterilmez.</li>
      <li><b>Bağlantıyı Sına</b> ile İTS'e erişimin çalıştığını doğrulayın. Hata alırsanız İTS şifreniz değişmiş olabilir.</li>
      <li><b>Bize Yazın:</b> Şifre yenileme veya bağlantı sorunlarında talep oluşturun. <u>İTS şifrenizi forma yazmayın</u>; yeni şifre telefonda birlikte tanımlanır.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k4"><i class="bi bi-box-arrow-in-down me-2"></i>4. Alım: PTS Sorgu, Mal Kabul, Manuel Ekle</button></h2>
  <div id="k4" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <h6>PTS Sorgu</h6>
    <ol>
      <li>Başlangıç ve bitiş tarihini seçip <i>Sorgula</i>'ya basın (en fazla 31 günlük aralık). İsterseniz gönderen GLN veya <i>Sadece alınmayanlar</i> ile daraltın.</li>
      <li>Listeden paketin yanındaki <i>Paketi Al</i> düğmesine basın; paket içeriği açılır.</li>
      <li>Paket ekranında ürünleri doğrulayabilir, Excel'e aktarabilir ve <b>Mal Alım</b> bildirimini yapabilirsiniz.</li>
      <li>Elinizde XML veya ZIP biçiminde bir PTS dosyası varsa <i>Dosyayı Oku</i> ile yükleyebilirsiniz. Dosya yüklemek İTS'e bildirim göndermez.</li>
    </ol>
    <h6>Mal Kabul</h6>
    <p>Gelen ürünlerin karekodlarını okuyucu ile her satıra bir ürün gelecek şekilde okutun ve <i>Gönder</i>'e basın. İsterseniz reyon/raf bilgisini yazın; okutulan tüm ürünlere atanır. Son kullanma tarihi geçmiş ürünler otomatik olarak <b>Red Odası</b>'na alınır.</p>
    <h6>Manuel Ekle</h6>
    <p>PTS verisi olmayan, İTS'te size atanmış ama paketi bulunmayan ürünler içindir. Önizlemeyi kontrol edip onaylayınca İTS'e Mal Alım bildirimi gönderilir. Tek seferde en fazla 1.000 ürün işlenir.</p>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k5"><i class="bi bi-box-arrow-up me-2"></i>5. Satış &amp; Çıkış İşlemleri</button></h2>
  <div id="k5" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <p>Hepsinde yöntem aynıdır: karekodları her satıra bir ürün olacak şekilde okutun, gerekli alanları doldurun, <i>Gönder</i>'e basıp onaylayın. Çok sayıda ürün otomatik olarak gruplar halinde gönderilir ve ilerleme gösterilir. İşlem bitince ayrıntılı sonuç ekranı açılır; başarısız satırlar en üstte listelenir ve <i>Excel</i> ile indirilebilir.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
      <thead><tr><th>İşlem</th><th>Ne zaman kullanılır?</th><th>Gerekli bilgi</th></tr></thead>
      <tbody>
        <tr><td>Satış</td><td>Ürünü bir paydaşa satarken. Alıcı kendi Mal Alım bildirimini yapana kadar ürün arada bekler.</td><td>Alıcı GLN (13 hane)</td></tr>
        <tr><td>Satış İptal</td><td>Hatalı veya geri dönen satışı iptal etmek için.</td><td>—</td></tr>
        <tr><td>Mal Devir / Devir İptal</td><td>Aynı role sahip paydaşlar arasında devir (örn. depo → depo).</td><td>Karşı GLN</td></tr>
        <tr><td>İhracat / İhracat İptal</td><td>Yalnızca <b>ecza deposu, üretici, ihracatçı ve ihracata yetkili firma</b> türündeki kurumlar görür. Ürünü yurt dışına çıkarırken İTS'e ihracat bildirimi yapar; hatalı bildirimi iptalle geri alır, ürün tekrar stoğa döner.</td><td>İhracat: alıcı bilgisi (zorunlu, en çok 100 karakter)</td></tr>
        <tr><td>Hastane Sarf / Sarf İptal</td><td>Hastanelerde ürünün kullanımının bildirilmesi ve iptali.</td><td>—</td></tr>
        <tr><td>Ürün İade</td><td>Ürünü tedarikçiye iade ederken.</td><td>İade edilen paydaşın GLN'i</td></tr>
        <tr><td>Deaktive / Deaktivasyon İptal</td><td>Ürünü sistemden çıkarma (fire, geri çekme vb.) veya bu işlemi geri alma.</td><td>Sebep (zorunlu), açıklama</td></tr>
      </tbody>
    </table></div>
    <ul>
      <li>Kurumunuzun türüne göre (eczane, depo, hastane) bazı işlemler menüde görünmez; bu normaldir.</li>
      <li>GLN'i elle yazmak yerine <b>Cari Kartlar</b>'a kayıtlı paydaşlarınızı listeden seçebilirsiniz.</li>
      <li><b>PTS Gönderimleri:</b> Satış başarıyla bildirildikten sonra alıcıya PTS paketi göndermek için kullanılır. PTS göndermek Satış bildiriminin yerine geçmez; Satış ayrıca yapılmalıdır.</li>
      <li>Satış, devir veya ihracat sonrası sonuç ekranındaki geri alma düğmesiyle hatalı bildirimi hemen geri alabilirsiniz. Daha eski ihracatlar için <b>İhracat İptal</b> ekranını ya da <b>Stok Listesi → İhraç Edildi</b> sekmesini kullanın.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k6"><i class="bi bi-boxes me-2"></i>6. Stok</button></h2>
  <div id="k6" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <ul>
      <li><b>Stok Özeti:</b> Toplam durumun genel görünümü.</li>
      <li><b>Barkod Bazlı Stok:</b> Her barkod (GTIN) için tek satırda toplam adet; partiler ve son kullanma tarihleri ayrıntıda görünür. Excel'e aktarılabilir.</li>
      <li><b>Stok Listesi (Karekod):</b> Ürün bazında karekodlu stok. Ürünleri seçerek toplu işlem (satış, iade vb.) başlatabilir veya <i>adede göre otomatik seç</i> ile en yakın SKT'li partilerden başlayarak seçim yapabilirsiniz. Liste yalnızca bu portaldan yapılan bildirimlerle oluşan stoğu gösterir; İTS'teki tüm kayıtları kapsamaz.</li>
      <li><b>Stok İçe Aktar (İTS Doğrulamalı):</b> Mevcut stoğunuzu portala taşımak için İTS portalından indirdiğiniz stok dosyasını (CSV) yükleyin veya PTS'ten aldığınız paketleri kullanın; ürünler İTS'te doğrulanarak içeri alınır.</li>
      <li><b>Kritik / Miad:</b> Son kullanma tarihi yaklaşan ve geçen ürünler. Uyarı eşiklerini (gün) buradan ayarlayabilirsiniz. SKT'si geçenler Red Odası'na alınır.</li>
      <li><b>Karekod Oluşturucu:</b> Etiketi silinmiş veya okunmayan ürün için GTIN, SKT, parti ve seri bilgisinden GS1 DataMatrix karekod üretir ve yazdırır.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k7"><i class="bi bi-grid-3x3-gap me-2"></i>7. Reyon - Raf, Red ve Karantina Odası</button></h2>
  <div id="k7" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <ul>
      <li><b>Reyon - Raf Özeti:</b> Ürünlerin lokasyonlara göre dağılımı. Lokasyonu Mal Kabul sırasında veya Stok Listesi'nden sonradan atayabilirsiniz.</li>
      <li><b>Red Odası:</b> Son kullanma tarihi geçmiş ürünlerin toplandığı yer.</li>
      <li><b>Karantina Odası:</b> Satışa verilmemesi gereken, ayrı tutulan ürünler.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k8"><i class="bi bi-patch-question me-2"></i>8. Sorgular, Cari Kartlar ve Analiz</button></h2>
  <div id="k8" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <ul>
      <li><b>Durum Sorgu:</b> Bir ürünün İTS'te kayıtlı olup olmadığını, son kullanma tarihini ve son sahibini gösterir. Veri değiştirmez, risksizdir.</li>
      <li><b>Paydaşlar:</b> İTS paydaş listesinde arama yapın, aktif/pasif durumu görün ve <i>Cari Ekle</i> ile cari kartlarınıza ekleyin. Satış ve devirden önce karşı tarafın aktif olduğunu buradan kontrol edin.</li>
      <li><b>İlaç Fiyat Listesi:</b> İlaç adı veya barkodla fiyat arayın; sayfa başına 25 kayıt gösterilir ve sayfalar arasında gezebilirsiniz. (Test aşamasındadır.)</li>
      <li><b>Cari Kartlar:</b> Sık çalıştığınız alıcı ve tedarikçileri kaydedin; işlem ekranlarında GLN yazmak yerine listeden seçin. Cariden çıkarmak geçmiş işlemleri etkilemez.</li>
      <li><b>Analiz:</b> Son 30 günde yaptığınız bildirimlerin tarih, tür, ürün ve başarı/hata durumunu gösterir.</li>
    </ul>
  </div></div></div>

  <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#k9"><i class="bi bi-wrench-adjustable me-2"></i>9. Sık Karşılaşılan Durumlar</button></h2>
  <div id="k9" class="accordion-collapse collapse" data-bs-parent="#kilavuz"><div class="accordion-body">
    <dl class="mb-0">
      <dt>İşlemde bazı ürünler başarısız göründü.</dt>
      <dd>Sonuç ekranında her satırın yanında İTS'in verdiği hata mesajı yazar (örn. ürün sizde değil, durumu uygun değil). Hatalı satırları düzeltip yalnızca onları tekrar gönderin.</dd>
      <dt>Menüde bir işlem göremiyorum.</dt>
      <dd>Kurum türünüze göre (eczane, depo, hastane) yetkisiz işlemler gizlenir.</dd>
      <dt>"İTS bağlantısı kurulamadı" hatası alıyorum.</dt>
      <dd>İTS Bağlantısı ekranında <i>Bağlantıyı Sına</i>'yı deneyin. Sürerse <i>Bize Yazın</i> ile talep açın; İTS şifrenizi yazmayın.</dd>
      <dt>Lisans süresi bitti.</dt>
      <dd>Raporları görüntülemeye devam edebilirsiniz, ancak bildirim yapmak için yenileme gerekir.</dd>
      <dt>Karekod okuyucu satırları birleştiriyor.</dt>
      <dd>Okuyucunuzun her okumadan sonra Enter göndermesi gerekir; her karekod ayrı bir satırda olmalıdır.</dd>
    </dl>
  </div></div></div>
</div>

<div class="card mt-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
  <div><strong>Yardıma mı ihtiyacınız var?</strong><div class="text-muted small">Depo Setup Yazılım ve Danışmanlık Ltd. Şti.</div></div>
  <a href="tel:+908509080042" class="btn btn-primary"><i class="bi bi-telephone me-1"></i>0850 908 00 42</a>
</div></div>
@endsection
