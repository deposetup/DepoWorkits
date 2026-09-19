# İTS REST API Referansı

Kaynak: "İlaç Takip Sistemi RESTAPI Servisleri Kullanım Kılavuzu" (İTS, sürüm 1.0,
15.03.2022, its.gov.tr) ve resmi "REST API Servis Adres Listesi" (05-07-2022).
Ek kaynak: https://its.gov.tr/its/entegrasyon (İTS entegrasyon portalı, ihtiyaç
halinde başvurulacak genel referans sayfası).

Production base URL: `https://its2.saglik.gov.tr` (kılavuzdaki örnekler test
ortamı `itstest2.saglik.gov.tr` adresini kullanır).

## Access Token

- `POST /token/app/token/` — JSON, auth gerektirmez.
- İstek: `{"username": "<GLN>", "password": "<İTS şifresi>"}`
- Cevap: `{"token": "<JWT>"}`
- Sonraki tüm servis çağrılarında `Authorization: Bearer <token>` header'ı
  kullanılır. Token süresi (TTL) kılavuzda belirtilmemiştir.

## Hata (Fault) Mesajı

Tüm servislerde ortak: `{"fc": "<5 haneli kod>", "fm": "<açıklama>"}`.
Kod listesi için ayrı servis: `GET/POST /reference/app/errorcode/`.

## Ortak Alanlar

- `gtin` — Küresel Ticari Ürün Numarası
- `sn` — Ürünün sıra numarası (karekod ile eşleşmeli, max 20 char)
- `bn` — Parti numarası (karekod ile eşleşmeli, max 20 char, `0`/boşluk dolgu yasak)
- `xd` — Son kullanma tarihi (XML-Date, bildirim tarihinden ileri olamaz)
- Cevapta bildirim numarası — açıklama tablosunda `notification_id` yazsa da,
  tüm örnek yanıtlarda tutarlı şekilde `notificationid` (alt çizgisiz)
  kullanılmış; gerçek API doğrulanınca netleştirilmeli
- Cevapta her ürün için `uc` — 5 haneli onay/hata kodu

## Bildirim Türleri

| Tür (model sabiti) | Adres | dt | Ek alanlar (istek) |
|---|---|---|---|
| `TYPE_URETIM` | `/product/app/supplynotification/` | `M` (üretici) / `I` (ithalatçı) | `mi` (GLN), `pt` (PP/BP/FP), `gtin`, `xd`, `bn`, `md` (üretim tarihi), `snlist` (tek ürün/parti, `productList` YOK) |
| `TYPE_IHRACAT` | `/wholesale/app/export/` | `X` | `fr` (GLN), `rt` (alıcı verisi, 100 char, zorunlu), `document`, `productList` |
| `TYPE_IPTAL_IHRACAT` | `/wholesale/app/exportcancel/` | `C` | `fr`, `productList` |
| `TYPE_SATIS` | `/wholesale/app/dispatch/` | — | `togln` (karşı GLN), `productList` |
| `TYPE_IPTAL_SATIS` | `/wholesale/app/dispatchcancel/` | — | `productList` (togln yok) |
| `TYPE_ECZANE_SATIS` | `/prescription/app/pharmacysale/` | `S` | `fr`, `to`, `document{dd,dn,dr,cp,eid,rkn}`, `productList` |
| `TYPE_IPTAL_ECZANE_SATIS` | `/prescription/app/pharmacysalecancel/` | `C` | `fr`, `to`, `document{DD,DN,DR,CP,EID,RKN}`, `productList` |
| `TYPE_ALIM` (Mal Alım/Kabul) | `/common/app/accept/` | — | sadece `productList` |
| `TYPE_IPTAL_IADE` (Mal İade — alımı geri almak için kullanılır) | `/common/app/return/` | — | `togln`, `productList` |
| `TYPE_DEVIR` | `/common/app/transfer/` | — | `togln`, `productList` |
| `TYPE_IPTAL_DEVIR` | `/common/app/transfercancel/` | — | `productList` (togln yok) |
| `TYPE_DEAKTIVASYON` | `/common/app/deactivation/` | `D` | `frGln`, `ds` (2 haneli sebep kodu, bkz. aşağı), `description`, `document{dd,dn}`, `productList` |
| `TYPE_IPTAL_DEAKTIVASYON` | `/common/app/deactivationcancel/` | `C` | `frGln`, `ds`, `description`, `document{dd,dn}`, `productList` (sadece eczaneler kullanabilir) |

`productList` elemanları genelde `{gtin, sn, bn, xd}` şeklindedir (servise göre
alt küme değişebilir — yukarıdaki tabloya bakın).

### Deaktivasyon Sebep Kodları (`ds`)

| Kod | Açıklama |
|---|---|
| 10 | Sistemden Çıkartma |
| 20 | Üretim Fireleri Sebebiyle |
| 30 | Geri Çekme Sebebiyle |
| 40 | Miat Sebebiyle |
| 50 | Revizyon Sebebiyle |
| 60 | Sarf Sebebiyle |

## Diğer Servisler (model sabitlerinde karşılığı yok, ileride gerekebilir)

- Hastane Sarf: `POST /consume/app/consume/` — `dt:"D"`, `fr`, `productList`
- Durum Sorgulama: `POST /reference/app/check_status/` — `productList`, cevapta `responseObjectList` (`gln1`, `gln2`, `gtin`, `sn`, `uc`)
- Ürün Doğrulama: `POST /reference/app/verification/`
- PTS Paket Sorgulama: `POST /pts/app/search` — `sourceGln`, `destinationGln`, `startDate`, `endDate`

## Not

`ItsClient` (bkz. `app/Services/ItsClient.php`), bu tabloyu doğrudan
uygulamaz — sadece token alıp doğru adrese `Authorization: Bearer` header'ı
ile iletir. `ItsNotification->payload`, kaydı oluşturan taraf tarafından bu
tabloya uygun şekilde önceden hazırlanmış olmalıdır (henüz bu oluşturma
katmanı/formu yazılmadı).
