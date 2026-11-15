# İTS Doküman Özeti

Kaynak: `docs/its/` altındaki Bakanlık PDF'leri (metin çıktıları `.txt` olarak yanında).

## Kullanılacak sürüm
- **REST API Servis Kılavuzu** (dosya adı 28-06-2022, kapak tarihi 31.05.2023) en güncel olandır. 49 servis içerir.
- **05-07-2022 Servis Adres Listesi** canlı adresleri verir. Kılavuzdaki örnekler test adresi `itstest2.saglik.gov.tr` kullanır.
- 27-05-2022 tarihli SOAP "Referans Servisleri Kılavuzu" eskidir. Kullanılmayacak.
- Mimari kararı: `ItsSoapClient` yerine **`ItsRestClient`** yazılacak.

## Ortamlar
| Ortam | Taban adres |
|---|---|
| Test | `https://itstest2.saglik.gov.tr` |
| Canlı | `https://its2.saglik.gov.tr` |

Yol yapısı: `/{modül}/app/{servis}/`. Örnekler: `/token/app/token/`, `/consume/app/consume/`, `/common/app/accept/`, `/reference/app/drug/`, `/pts/app/SendPackage`.

## Kimlik doğrulama
- `POST /token/app/token/` gövdesi `{username, password}` biçimindedir.
- Yanıtta `access_token` (1 saat), `refresh_token` (24 saat) ve `token_type: Bearer` gelir.
- `POST /token/app/refreshtoken/` gövdesi `{token: <refresh_token>}` biçimindedir.
- Tüm servisler `Authorization: Bearer <token>` ister. İstek ve yanıt JSON'dur.
- Tenant başına token önbelleği tutulacak, süre dolmadan yenilenecek.
- Not: ana kılavuz token yanıt alanına `token` der, token kılavuzu `access_token` der. Kodda ikisini de kabul edeceğiz.

## Hata yapısı
`fc` (5 haneli kod) ve `fm` (mesaj). Cevap Kodları ekranı `ITS_Hata_Kodlari.pdf` dosyasından, ya da `/reference/app/errorcode/` servisinden doldurulacak.

## Ekranlar ve servisler
| Ekran | Servis | Önemli alanlar |
|---|---|---|
| İlaç Listesi | `reference/app/drug/` | istek `getAll`; yanıt `gtin, drugName, manufacturerGln, manufacturerName, active, imported` |
| Paydaşlar | `reference/app/stakeholder/` | |
| Ülke seçimi | `reference/app/country/` | |
| Cevap Kodları | `reference/app/errorcode/` | |
| Mal Kabul | `common/app/accept/` | `productList[gtin, sn, bn, xd]` → `notification_id`, ürün başına `uc` |
| Mal İade | `common/app/return/` | |
| Hastane Sarf | `consume/app/consume/` | `dt="D"`, `fr`(GLN), `productList` |
| Sarf İptal | `consume/app/consumecancel/` | |
| Tekil Sarf, İptal, İade, Sorgu | `consume/app/singleconsume*` | |
| Deaktive | `common/app/deactivation/` | `dt="D"`, `frGln`, `ds`, `description`, `document{dd,dn}`, `productList` |
| Deaktivasyon İptal | kılavuz §45 | adres listede yok, kılavuzdan bakılacak |
| Durum Sorgu | `reference/app/check_status/` | yanıtta `Gln1`, `Gln2` ve ürün durumu |
| PTS gönderim | `pts/app/SendPackage` | `receiver`(GLN), `file`(zip, base64) → `transferId`, `md5Checksum` |
| PTS alım | `pts/app/GetPackage` | `transferId` |
| PTS sorgu | `pts/app/search` | |

**Deaktivasyon sebep kodları:** 10 Sistemden Çıkartma, 20 Üretim Fireleri, 30 Geri Çekme, 40 Miat, 50 Revizyon, 60 Sarf.

## Önemli iş kuralları
- PTS ile paket almak mal kabul sayılmaz. XML'deki ürünler için ayrıca Mal Alım bildirimi gerekir.
- Mal Alım başarılı olunca ürünler paydaşın stoğuna girer.
- Parti no (`bn`) en fazla 20 karakterdir. Sıfır ve boşluk gibi dolgu karakteri kullanılmaz.
- SKT bildirim tarihinden önce olamaz. Tarih biçimi XML-Date (`YYYY-MM-DD`).
- `notification_id` her bildirim için saklanacak.
- Hata dönen bildirim yapılmamış sayılır. Düzeltip yeniden göndermek kullanıcıya düşer.

## Taslak kararlar (onaya açık)
- **Stok:** Bakanlık "stok sorgula" servisi sunmuyor. Stok, kendi veritabanımızdaki bildirimlerden (mal kabul, sarf, iade, deaktive) hesaplanacak. Başlangıç stoğu için PTS XML veya Excel içe aktarma gerekecek.
- **Kritik ve Miad Belirle** ekranları İTS'te yok, tamamen yerel özelliktir.
- **Karekod ayrıştırıcı:** `Beseri_Tibbi_Urunler_Barkod_ve_Karekod_Uygulama_Kilavuzu` dosyasına göre yazılacak.
