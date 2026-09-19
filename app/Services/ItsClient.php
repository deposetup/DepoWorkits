<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\ItsNotification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * İTS/İEGM ile gerçek zamanlı iletişimi yöneten servis katmanı.
 *
 * Kaynak: "İlaç Takip Sistemi RESTAPI Servisleri Kullanım Kılavuzu" (İTS,
 * sürüm 1.0, its.gov.tr) ve resmi "REST API Servis Adres Listesi" (05-07-2022).
 *
 * Akış: her istekten önce depoya ait GLN/İTS şifresi ile Access Token servisi
 * (bkz. TOKEN_ENDPOINT) çağrılır; dönen token, asıl bildirim isteğinde
 * "Authorization: Bearer <token>" header'ı olarak kullanılır — GLN/şifre
 * bildirim gövdesine (payload) hiçbir zaman dahil edilmez.
 *
 * Her bildirim türünün gövde alanları farklıdır (bkz. kılavuz ilgili bölüm);
 * bu nedenle $notification->payload, kaydı oluşturan taraf (ör. bir form
 * request/servis) tarafından türe uygun şekilde önceden hazırlanmış olmalıdır.
 * ItsClient, bu gövdeyi olduğu gibi ilgili uç noktaya iletmekten sorumludur.
 */
class ItsClient
{
    protected Client $http;

    /** Access Token servisi. */
    protected const TOKEN_ENDPOINT = '/token/app/token/';

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri' => config('services.its.base_url'),
            'timeout' => config('services.its.timeout', 30),
        ]);
    }

    /**
     * Depoya ait GLN/İTS şifresi ile Access Token servisinden Bearer token alır.
     *
     * @see Kılavuz böl. 2 "ACCESS TOKEN ALIMI VE KULLANIM AKIŞI"
     */
    protected function getAccessToken(Depot $depot): string
    {
        try {
            $response = $this->http->post(self::TOKEN_ENDPOINT, [
                'json' => [
                    'username' => $depot->gln_number,
                    'password' => $depot->its_password,
                ],
            ]);

            $token = json_decode((string) $response->getBody(), true)['token'] ?? null;

            if (! $token) {
                throw new ItsIntegrationException("İTS token yanıtı geçersiz (depot #{$depot->id}).");
            }

            return $token;
        } catch (GuzzleException $e) {
            throw new ItsIntegrationException(
                "İTS token alınamadı (depot #{$depot->id}): ".$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Verilen bildirimi ilgili deponun kimliğiyle İTS'ye gönderir.
     *
     * $notification->payload, hedef servisin beklediği alanlarla
     * (dt/fr/to/togln/productList vb., türe göre değişir) önceden
     * doldurulmuş olmalıdır.
     */
    public function send(ItsNotification $notification): array
    {
        $depot = $notification->depot;

        try {
            $token = $this->getAccessToken($depot);

            $response = $this->http->post($this->endpointFor($notification->type), [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'json' => $notification->payload,
            ]);

            return json_decode((string) $response->getBody(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new ItsIntegrationException(
                "İTS isteği başarısız (depot #{$depot->id}, tür: {$notification->type}): ".$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Bildirim türünü resmi İTS REST servis adresine eşler.
     *
     * @see https://its.gov.tr/Content/pdf/05-07-2022_RESTAPI%20SERVIS%20ADRES%20L%C4%B0STES%C4%B0.pdf
     * @see Kılavuz, ilgili bildirim bölümleri (3-49)
     */
    protected function endpointFor(string $type): string
    {
        return match ($type) {
            ItsNotification::TYPE_URETIM => '/product/app/supplynotification/',
            ItsNotification::TYPE_IHRACAT => '/wholesale/app/export/',
            ItsNotification::TYPE_IPTAL_IHRACAT => '/wholesale/app/exportcancel/',
            ItsNotification::TYPE_SATIS => '/wholesale/app/dispatch/',
            ItsNotification::TYPE_IPTAL_SATIS => '/wholesale/app/dispatchcancel/',
            ItsNotification::TYPE_ECZANE_SATIS => '/prescription/app/pharmacysale/',
            ItsNotification::TYPE_IPTAL_ECZANE_SATIS => '/prescription/app/pharmacysalecancel/',
            ItsNotification::TYPE_ALIM => '/common/app/accept/',
            // "İade" servisi, alım/kabul bildirimini geri almak için kullanılır (Kılavuz böl. 12).
            ItsNotification::TYPE_IPTAL_IADE => '/common/app/return/',
            ItsNotification::TYPE_DEVIR => '/common/app/transfer/',
            ItsNotification::TYPE_IPTAL_DEVIR => '/common/app/transfercancel/',
            ItsNotification::TYPE_DEAKTIVASYON => '/common/app/deactivation/',
            ItsNotification::TYPE_IPTAL_DEAKTIVASYON => '/common/app/deactivationcancel/',
            default => throw new ItsIntegrationException("Bilinmeyen İTS bildirim türü: {$type}"),
        };
    }

    /**
     * Depoya ait GLN/İTS şifresinin geçerli olup olmadığını, Access Token
     * servisinden token alınabilip alınamadığına bakarak doğrular.
     */
    public function checkCredentials(Depot $depot): bool
    {
        try {
            return (bool) $this->getAccessToken($depot);
        } catch (ItsIntegrationException) {
            return false;
        }
    }
}
