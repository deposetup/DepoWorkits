@extends('layouts.app')
@section('baslik', 'Stok İçe Aktar')
@section('icerik')
<h4>Stok İçe Aktar (İTS Doğrulamalı)</h4>
<div class="alert alert-info small">
  İTS, depo stok listesini servis olarak sunmuyor. Bu ekran aday ürünleri İTS'te tek tek sorgular; <b>son sahibi sizin GLN'iniz olan ve arada olmayan</b> ürünleri stoğunuza alır. Alınan ürünler <b>Stok Listesi (Karekod)</b> ekranında görünür ve satış/sarf yapılabilir. Bu işlem İTS'e hiçbir bildirim göndermez (yalnızca sorgu).
</div>
@if(session('hata'))<div class="alert alert-danger">{{ session('hata') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@isset($csv)
<div class="alert alert-success"><b>Dosya içe aktarıldı.</b> {{ number_format($csv['okunan'], 0, ',', '.') }} satır okundu: <b>{{ number_format($csv['eklenen'], 0, ',', '.') }}</b> yeni ürün stoğa eklendi, {{ number_format($csv['guncellenen'], 0, ',', '.') }} ürün zaten kayıtlıydı (güncellendi)@if($csv['atlanan_durum']), {{ $csv['atlanan_durum'] }} ürün satış/sarf gibi başka durumda olduğu için atlandı @endif @if($csv['gecersiz']), <span class="text-danger">{{ $csv['gecersiz'] }} geçersiz satır</span>@endif.
@if(! empty($csv['silinen'])) <b>{{ number_format($csv['silinen'], 0, ',', '.') }}</b> eski stok kaydı dosyada olmadığı için stoktan silindi (güncel stok = dosya).@endif @if(! empty($csv['silme_atlandi'])) <span class="text-danger">Dosyada geçersiz satır olduğu (ya da geçerli satır olmadığı) için eski kayıtlar silinmedi.</span>@endif
@foreach($csv['ornek_hatalar'] as $h)<div class="small text-danger">{{ $h }}</div>@endforeach
<div class="mt-2"><a href="{{ route('stok.barkod') }}" class="btn btn-sm btn-success">Barkod Bazlı Stok</a> <a href="{{ route('stok.liste') }}" class="btn btn-sm btn-outline-success">Stok Listesi</a></div></div>
@endisset
<div id="csvPanel" class="card mb-3 border-primary d-none"><div class="card-body">
  <div class="d-flex align-items-end gap-2 mb-2"><div><div class="small text-muted" id="csvBaslik">Hazırlanıyor…</div><div class="fs-2 fw-bold lh-1" id="csvYuzde">%0</div></div><span class="small text-muted ms-auto" id="csvOzet"></span></div>
  <div class="small text-muted mb-1" id="csvEtiketA">1) Hazırlık</div>
  <div class="progress mb-2" style="height:20px"><div id="csvYukBar" class="progress-bar" style="width:0%">0%</div></div>
  <div class="small text-muted mb-1">2) Stoğa aktarma</div>
  <div class="progress mb-2" style="height:20px"><div id="csvAktBar" class="progress-bar bg-success" style="width:0%">0%</div></div>
  <div id="csvSon"></div>
</div></div>
<div class="card mb-3"><div class="card-body">
  <h6><i class="bi bi-filetype-csv me-1"></i>İTS portalından indirilen stok dosyası (CSV / ZIP)</h6>
  <p class="small text-muted mb-2">İTS portalından kendi GLN'inizle indirdiğiniz stok dosyasını (<code>GTIN;SN;BN;EXP_DATE</code>) yükleyin. Dosya İTS'in kendi kaydı olduğu için ürünler tek tek sorgulanmadan stoğunuza alınır ve <b>dosyada olmayan eski stok kayıtları silinir (güncel stok = yüklenen dosya)</b>; İTS'e bildirim gönderilmez. Dosya adında GLN varsa sizin GLN'inizle aynı olmalıdır. Büyük dosyalar için .zip yükleyebilirsiniz (en fazla 50 MB). Satış/sarf gibi başka durumdaki ürünlere dokunulmaz (değiştirilmez), SKT'si geçenler otomatik Red Odası'na alınır.</p>
  <form method="post" action="{{ route('stok.ice-aktar.csv') }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center" id="csvForm">@csrf
    <input type="file" name="dosya" accept=".csv,.txt,.zip" class="form-control w-auto" required>
    <button class="btn btn-primary" id="csvBtn"><i class="bi bi-upload me-1"></i>Dosyayı Stoğa Aktar</button></form>
</div></div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-archive me-1"></i>Aldığım PTS paketlerinden</h6>
    <p class="small text-muted">PTS Sorgu ekranından "Paketi Al" ile indirdiğiniz {{ $paketSayisi }} paketteki tüm ürünler İTS'te doğrulanır. Geçmiş stoğunuzu kapsamak için PTS Sorgu'dan önceki tarih aralıklarını (31 gün sınırı) da paket olarak alın.</p>
    <form method="post" action="{{ route('stok.ice-aktar.calistir') }}" id="ptsForm">@csrf<input type="hidden" name="kaynak" value="pts">
      <button class="btn btn-primary" @disabled(! $paketSayisi)><i class="bi bi-cloud-download me-1"></i>Paketleri Tara ve Stoğa Al</button></form>
  </div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-body">
    <h6><i class="bi bi-upc-scan me-1"></i>Karekod listesinden</h6>
    <form method="post" action="{{ route('stok.ice-aktar.calistir') }}" id="kodForm">@csrf<input type="hidden" name="kaynak" value="kodlar">
      <textarea name="kodlar" rows="5" class="form-control font-monospace mb-2" placeholder="Her satıra bir karekod (okuyucu ile okutun)">{{ old('kodlar') }}</textarea>
      <button class="btn btn-primary"><i class="bi bi-shield-check me-1"></i>Doğrula ve Stoğa Al</button></form>
  </div></div></div>
</div>
@if($sonuc)
<div class="card mt-4"><div class="card-header fw-semibold">Sonuç</div><div class="card-body">
  <p class="mb-1"><span class="badge text-bg-success">{{ $sonuc['alinan'] }}</span> ürün stoğa alındı · <span class="badge text-bg-secondary">{{ $sonuc['zatenVar'] }}</span> zaten stokta · <span class="badge text-bg-warning">{{ count($sonuc['digerleri']) }}</span> alınmadı · {{ $sonuc['sorgulanan'] }} ürün sorgulandı</p>
  @foreach($sonuc['hatalar'] as $h)<div class="text-danger small">{{ $h }}</div>@endforeach
  @if($sonuc['digerleri'])
  <table class="table table-sm mt-3 mb-0"><thead><tr><th>GTIN</th><th>Seri</th><th>Neden</th><th>Son sahibi (GLN)</th></tr></thead><tbody>
  @foreach(array_slice($sonuc['digerleri'], 0, 300) as $d)<tr><td class="font-monospace">{{ $d['gtin'] }}</td><td class="font-monospace">{{ $d['sn'] }}</td><td>{{ $d['neden'] }}</td><td class="font-monospace">{{ $d['gln1'] ?: '—' }}</td></tr>@endforeach
  </tbody></table>
  @endif
</div></div>
@endif
<script>
(function(){
 const panel=document.getElementById('csvPanel'),yb=document.getElementById('csvYukBar'),ab=document.getElementById('csvAktBar'),yz=document.getElementById('csvYuzde'),
  bs=document.getElementById('csvBaslik'),oz=document.getElementById('csvOzet'),son=document.getElementById('csvSon'),etA=document.getElementById('csvEtiketA');
 const csrf=document.querySelector('#csvForm input[name=_token]').value,nf=n=>Number(n).toLocaleString('tr-TR');
 const esc=t=>String(t??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
 const set=(b,y)=>{y=Math.max(0,Math.min(100,y));b.style.width=y+'%';b.textContent=y+'%'};
 const dur=b=>b.classList.remove('progress-bar-animated','progress-bar-striped'),can=b=>b.classList.add('progress-bar-striped','progress-bar-animated');
 let acik=false,butonlar=[...document.querySelectorAll('#csvForm button,#ptsForm button,#kodForm button')];
 function baslat(etiket){acik=true;butonlar.forEach(b=>b.disabled=true);panel.classList.remove('d-none');son.innerHTML='';etA.textContent=etiket;
  yb.className='progress-bar';ab.className='progress-bar bg-success';set(yb,0);set(ab,0);yz.textContent='%0';oz.textContent='';
  window.onbeforeunload=()=>'İçe aktarma sürüyor, sayfadan ayrılmayın.';panel.scrollIntoView({behavior:'smooth',block:'center'})}
 function bitir(){acik=false;window.onbeforeunload=null;butonlar.forEach(b=>b.disabled=false);dur(yb);dur(ab)}
 function hata(m,devam){bs.textContent='İçe aktarma durdu';bitir();
  son.innerHTML='<div class="alert alert-danger mb-0">'+esc(m)+(devam?'<div class="mt-2"><button type="button" class="btn btn-sm btn-danger" id="csvDevam">Kaldığı yerden devam et</button></div>':'')+'</div>';
  if(devam)document.getElementById('csvDevam').addEventListener('click',devam)}
 async function post(url,gov){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(gov)});
  const d=await r.json().catch(()=>({message:'Sunucudan geçersiz yanıt ('+r.status+').'}));
  if(!r.ok)throw new Error(d.message||(d.errors&&Object.values(d.errors)[0][0])||('Sunucu hatası (HTTP '+r.status+')'));return d}

 /* ---- CSV / ZIP dosyası ---- */
 function yukle(fd){return new Promise((res,rej)=>{const x=new XMLHttpRequest();x.open('POST',@json(route('stok.ice-aktar.csv.hazirla')));
  x.setRequestHeader('Accept','application/json');x.setRequestHeader('X-CSRF-TOKEN',csrf);
  x.upload.onprogress=e=>{if(e.lengthComputable){const y=Math.floor(e.loaded*100/e.total);set(yb,y);yz.textContent='%'+Math.floor(y*0.3);oz.textContent=nf(Math.round(e.loaded/1024))+' / '+nf(Math.round(e.total/1024))+' KB yüklendi'}};
  x.upload.onload=()=>{bs.textContent='Dosya sunucuda hazırlanıyor…'};
  x.onload=()=>{let j={};try{j=JSON.parse(x.responseText)}catch(_){}
   if(x.status>=200&&x.status<300)res(j);else rej(new Error(j.message||(j.errors&&Object.values(j.errors)[0][0])||('Yükleme başarısız (HTTP '+x.status+'). Dosya sunucu sınırını aşıyor olabilir.')))};
  x.onerror=()=>rej(new Error('Bağlantı hatası, dosya yüklenemedi.'));x.send(fd)})}
 document.getElementById('csvForm').addEventListener('submit',async function(e){
  e.preventDefault();const form=this;if(!form.dosya.files.length||acik)return;
  baslat('1) Dosya yükleme');can(yb);bs.textContent='Dosya yükleniyor…';
  try{
   const h=await yukle(new FormData(form));dur(yb);set(yb,100);can(ab);bs.textContent='Stoğa aktarılıyor… ('+nf(h.toplam)+' satır)';
   const dongu=async()=>{try{baslat0();let d;do{d=await post(@json(route('stok.ice-aktar.csv.adim')),{token:h.token});
     const y=d.toplam?Math.floor(d.okunan*100/d.toplam):100;set(ab,y);yz.textContent='%'+(30+Math.floor(y*0.7));
     oz.textContent=nf(d.okunan)+' / '+nf(d.toplam)+' satır işlendi'+(d.gecersiz?' · Geçersiz: '+nf(d.gecersiz):'')}while(!d.bitti);
    set(ab,100);yz.textContent='%100';bitir();bs.textContent='İçe aktarma tamamlandı';const c=d.sonuc;
    son.innerHTML='<div class="alert alert-success mb-0"><b>Dosya içe aktarıldı.</b> '+nf(c.okunan)+' satır okundu: <b>'+nf(c.eklenen)+'</b> yeni ürün stoğa eklendi, '+nf(c.guncellenen)+' ürün zaten kayıtlıydı (güncellendi)'
     +(c.atlanan_durum?', '+nf(c.atlanan_durum)+' ürün başka durumda olduğu için atlandı':'')+(c.gecersiz?', '+nf(c.gecersiz)+' satır geçersiz':'')+'.'
     +(c.silinen?' <b>'+nf(c.silinen)+'</b> eski stok kaydı dosyada olmadığı için stoktan silindi (güncel stok = dosya).':'')+(c.silme_atlandi?' <span class="text-danger">Dosyada geçersiz satır olduğu (ya da geçerli satır olmadığı) için eski kayıtlar silinmedi.</span>':'')
     +(c.ornek_hatalar||[]).map(x=>'<div class="small text-danger">'+esc(x)+'</div>').join('')+linkler()+'</div>'}
    catch(err){hata(err.message,dongu)}};
   function baslat0(){acik=true;butonlar.forEach(b=>b.disabled=true);son.innerHTML='';can(ab);window.onbeforeunload=()=>'İçe aktarma sürüyor, sayfadan ayrılmayın.'}
   await dongu();
  }catch(err){hata(err.message)}
 });
 function linkler(){return '<div class="mt-2"><a href="'+@json(route('stok.barkod'))+'" class="btn btn-sm btn-success">Barkod Bazlı Stok</a> <a href="'+@json(route('stok.liste'))+'" class="btn btn-sm btn-outline-success">Stok Listesi</a></div>'}

 /* ---- İTS doğrulamalı: PTS paketleri / karekod listesi ---- */
 async function dogrula(gov,etiket){
  baslat(etiket);bs.textContent='Hazırlanıyor…';can(yb);let h;
  try{h=await post(@json(route('stok.ice-aktar.dogrula.baslat')),gov)}catch(err){return hata(err.message)}
  const guncelle=d=>{
   const a=d.a.toplam?Math.floor(d.a.islenen*100/d.a.toplam):100,b=d.b.toplam?Math.floor(d.b.islenen*100/d.b.toplam):(d.asama==='sorgu'?100:0);
   set(yb,a);set(ab,b);if(a>=100)dur(yb);yz.textContent='%'+Math.floor(a*0.3+b*0.7);
   bs.textContent=d.asama==='paket'?'PTS paketleri okunuyor… ('+d.a.islenen+' / '+d.a.toplam+' paket)':'İTS\'te doğrulanıyor ve stoğa alınıyor…';
   oz.textContent=d.asama==='paket'?nf(d.aday)+' aday ürün bulundu':nf(d.b.islenen)+' / '+nf(d.b.toplam)+' ürün sorgulandı · Stoğa alınan: '+nf(d.alinan)+' · Zaten stokta: '+nf(d.zatenVar)+' · Alınmayan: '+nf(d.digerSay)};
  guncelle(h);can(ab);
  const dongu=async()=>{try{acik=true;butonlar.forEach(b=>b.disabled=true);son.innerHTML='';window.onbeforeunload=()=>'İçe aktarma sürüyor, sayfadan ayrılmayın.';let d=h;
   while(!d.bitti){d=await post(@json(route('stok.ice-aktar.dogrula.adim')),{token:h.token});guncelle(d);if(d.bitti)break}
   set(yb,100);set(ab,100);yz.textContent='%100';bitir();bs.textContent='İçe aktarma tamamlandı';const c=d.sonuc;
   son.innerHTML='<div class="alert alert-success mb-0"><span class="badge text-bg-success">'+nf(c.alinan)+'</span> ürün stoğa alındı · <span class="badge text-bg-secondary">'+nf(c.zatenVar)+'</span> zaten stokta · <span class="badge text-bg-warning">'+nf(c.digerSay)+'</span> alınmadı · '+nf(c.sorgulanan)+' ürün İTS\'te sorgulandı'
    +(c.hatalar||[]).map(x=>'<div class="small text-danger">'+esc(x)+'</div>').join('')
    +(c.digerleri.length?'<div class="table-responsive mt-2"><table class="table table-sm mb-0"><thead><tr><th>GTIN</th><th>Seri</th><th>Neden</th><th>Son sahibi (GLN)</th></tr></thead><tbody>'
      +c.digerleri.map(x=>'<tr><td class="font-monospace">'+esc(x.gtin)+'</td><td class="font-monospace">'+esc(x.sn)+'</td><td>'+esc(x.neden)+'</td><td class="font-monospace">'+esc(x.gln1||'—')+'</td></tr>').join('')+'</tbody></table></div>'
      +(c.digerSay>c.digerleri.length?'<div class="small text-muted">İlk '+c.digerleri.length+' kayıt gösteriliyor.</div>':''):'')
    +linkler()+'</div>'}
   catch(err){hata(err.message,dongu)}};
  await dongu()}
 document.getElementById('ptsForm').addEventListener('submit',e=>{e.preventDefault();if(acik)return;
  if(!confirm('Paketlerdeki ürünler İTS\'te sorgulanacak. Devam edilsin mi?'))return;dogrula({kaynak:'pts'},'1) PTS paketlerini okuma')});
 document.getElementById('kodForm').addEventListener('submit',e=>{e.preventDefault();if(acik)return;
  const k=e.target.kodlar.value.trim();if(!k){alert('Önce karekod girin.');return}dogrula({kaynak:'kodlar',kodlar:k},'1) Karekodları hazırlama')});
})();
</script>
@endsection
