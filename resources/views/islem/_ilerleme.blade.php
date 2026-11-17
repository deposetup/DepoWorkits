{{-- Tüm bildirim ekranları için yüzdeli ilerleme paneli. JS: topluBildirim({...}) --}}
<div id="bildirimDurum" class="card mb-3 border-primary d-none"><div class="card-body">
  <div class="d-flex flex-wrap align-items-end gap-2 mb-2">
    <div><div class="small text-muted" id="bdBaslik">Bildirim durumu</div><div class="fs-2 fw-bold lh-1" id="bdYuzde">%0</div></div>
    <span id="bdOzet" class="small text-muted ms-auto"></span>
  </div>
  <div class="progress mb-2" style="height:26px"><div id="bdBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%">0%</div></div>
  <div id="bdGruplar" class="small"></div>
  <div id="bdHata" class="mt-2"></div>
  <div id="bdSon" class="mt-3 d-none d-flex flex-wrap gap-2">
    <form id="bdSonuc" method="post" action="">@csrf<input type="hidden" name="toplu_id" id="bdTopluId"><input type="hidden" name="karsi_gln" id="bdGln"><input type="hidden" name="geri" id="bdGeri">
      <button class="btn btn-primary"><i class="bi bi-card-checklist me-1"></i>Sonuç ekranını aç</button></form>
    <form id="bdExcel" method="post" action="{{ route('islem.sonuc-excel') }}" class="d-none">@csrf<input type="hidden" name="veri" id="bdVeri"><input type="hidden" name="baslik" id="bdExBaslik" value="">
      <button class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel'e aktar</button></form>
  </div>
</div></div>
<script>
const esc=t=>String(t??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
/** opts: {adetler:[grup boyutları], istek:(i,topluId)=>Promise<Response>, ad, tur, sonucUrl, karsiGln, geri, btn} */
async function topluBildirim(o){
 const topluId=(self.crypto&&crypto.randomUUID)?crypto.randomUUID():('t'+Date.now()+Math.random().toString(36).slice(2,10)),
  kutu=document.getElementById('bildirimDurum'),bar=document.getElementById('bdBar'),yz=document.getElementById('bdYuzde'),liste=document.getElementById('bdGruplar'),
  ozet=document.getElementById('bdOzet'),hata=document.getElementById('bdHata'),son=document.getElementById('bdSon'),xl=document.getElementById('bdExcel'),
  toplam=o.adetler.reduce((a,b)=>a+b,0),baslik=document.getElementById('bdBaslik');
 kutu.classList.remove('d-none');son.classList.add('d-none');xl.classList.add('d-none');hata.innerHTML='';if(o.btn)o.btn.disabled=true;
 baslik.textContent=o.ad+' bildirimi İTS\'e gönderiliyor…';yz.textContent='%0';
 bar.className='progress-bar progress-bar-striped progress-bar-animated';bar.style.width='0%';bar.textContent='0%';ozet.textContent='';
 liste.innerHTML=o.adetler.length>1?o.adetler.map((n,i)=>'<div class="d-flex gap-2 py-1 border-bottom"><span class="text-muted">Grup '+(i+1)+'/'+o.adetler.length+'</span><span>'+n+' ürün</span><span class="ms-auto text-muted" id="bdd'+i+'">Bekliyor</span></div>').join(''):'<div id="bdd0" class="text-muted">Gönderiliyor…</div>';
 kutu.scrollIntoView({behavior:'smooth',block:'center'});
 window.onbeforeunload=()=>'Bildirim sürüyor, sayfadan ayrılmayın.';
 let tamam=0,hatali=0,islenen=0,durdu=false;const tum=[];
 for(let i=0;i<o.adetler.length;i++){
  const d=document.getElementById('bdd'+i);d.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Gönderiliyor…';d.className=(o.adetler.length>1?'ms-auto ':'')+'text-primary';
  try{
   const r=await o.istek(i,topluId);
   const j=await r.json().catch(()=>({message:'Sunucudan geçersiz yanıt ('+r.status+').'}));
   if(!r.ok)throw new Error(j.message||('Sunucu hatası (HTTP '+r.status+')'));
   const t=j.satirlar.filter(x=>x.durum==='tamam').length,h=j.satirlar.length-t;
   tamam+=t;hatali+=h;tum.push(...j.satirlar);
   d.innerHTML=(h?'<i class="bi bi-exclamation-triangle-fill text-warning"></i> ':'<i class="bi bi-check-circle-fill text-success"></i> ')+'Başarılı '+t+(h?' · Hatalı '+h:'')+(j.bildirim?' · Bildirim No '+esc(j.bildirim):'');
   d.className=(o.adetler.length>1?'ms-auto ':'')+(h?'text-warning':'text-success');
  }catch(err){
   hatali+=o.adetler[i];durdu=true;d.innerHTML='<i class="bi bi-x-circle-fill text-danger"></i> Hata: '+esc(err.message);d.className=(o.adetler.length>1?'ms-auto ':'')+'text-danger';
   for(let k=i+1;k<o.adetler.length;k++)document.getElementById('bdd'+k).textContent='Gönderilmedi';
   hata.innerHTML='<div class="alert alert-danger mb-0">'+(o.adetler.length>1?'Grup '+(i+1)+' gönderilemedi: ':'Gönderilemedi: ')+esc(err.message)+'. Sonraki gruplar gönderilmedi. Önceki gruplar İTS\'e iletilmiştir.</div>';
   break;
  }
  islenen+=o.adetler[i];const y=Math.floor(islenen*100/toplam);bar.style.width=y+'%';bar.textContent=y+'%';yz.textContent='%'+y;
  ozet.textContent=islenen+' / '+toplam+' ürün işlendi · Başarılı: '+tamam+' · Hatalı: '+hatali;
 }
 window.onbeforeunload=null;if(o.btn)o.btn.disabled=false;
 bar.classList.remove('progress-bar-animated','progress-bar-striped');bar.classList.add(durdu||hatali?'bg-warning':'bg-success');
 baslik.textContent=durdu?o.ad+' bildirimi yarıda kaldı':o.ad+' bildirimi tamamlandı';
 ozet.textContent=islenen+' / '+toplam+' ürün işlendi · Başarılı: '+tamam+' · Hatalı: '+hatali;
 const hl=tum.filter(x=>x.durum!=='tamam');
 if(hl.length&&!durdu)hata.innerHTML='<div class="table-responsive"><table class="table table-sm table-danger mb-0"><tbody>'+hl.slice(0,200).map(x=>'<tr><td class="font-monospace small">'+esc(x.kod)+'</td><td>'+esc(x.mesaj)+'</td></tr>').join('')+'</tbody></table></div>'+(hl.length>200?'<div class="small text-muted">İlk 200 hata gösteriliyor; tümü için Excel\'e aktarın.</div>':'');
 if(tum.length){
  const f=document.getElementById('bdSonuc');f.action=o.sonucUrl;document.getElementById('bdTopluId').value=topluId;
  document.getElementById('bdGln').value=o.karsiGln||'';document.getElementById('bdGeri').value=o.geri||'';
  document.getElementById('bdVeri').value=JSON.stringify(tum);document.getElementById('bdExBaslik').value=o.ad;xl.classList.remove('d-none');son.classList.remove('d-none');
 }
}
</script>
