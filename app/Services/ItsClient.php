<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\ItsNotification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * İTS/İEGM ile gerçek zamanlı iletişimi yöneten servis katmanı.
 *
 * Her istek, ilgili deponun kendi GLN numarası ve şifresi (vekil/proxy
 * mantığıyla) kullanılarak İTS'ye iletilir. Uç nokta adresleri resmi
 * "REST API Servis Adres Listesi" (its.gov.tr, 05-07-2022) kaynaklıdır;
 * her servisin istek/yanıt şeması ve Access Token servisinin kimlik
 * doğrulama akışı ayrı bir doküman/WSDL netleştikçe uygulanacaktır.
 */
class ItsClient
{
    protected Client $http;

    /** Access Token servisi (kimlik doğrulama akışı henüz netleşmedi). */
    protected const TOKEN_ENDPOINT = '/token/app/token/';

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri' => config('services.its.base_url'),
            'timeout' => config('services.its.timeout', 30),
        ]);
    }

    /**
     * Verilen bildirimi ilgili deponun GLN/şifre bilgisiyle İTS'ye gönderir.
     */
    public function send(ItsNotification $notification): array
    {
        $depot = $notification->depot;

        try {
            $response = $this->http->post($this->endpointFor($notification->type), [
                'json' => [
                    'gln' => $depot->gln_number,
                    'password' => $depot->its_password,
                    'payload' => $notification->payload,
                ],
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
     */
    protected function endpointFor(string $type): string
    {
        return match ($type) {
            ItsNotification::TYPE_ALIM => '/common/app/accept/',
            ItsNotification::TYPE_SATIS => '/wholesale/app/dispatch/',
            ItsNotification::TYPE_DEVIR => '/common/app/transfer/',
            ItsNotification::TYPE_ECZANE_SATIS => '/prescription/app/pharmacysale/',
            ItsNotification::TYPE_IHRACAT => '/wholesale/app/export/',
            ItsNotification::TYPE_URETIM => '/product/app/supplynotification/',
            ItsNotification::TYPE_DEAKTIVASYON => '/common/app/deactivation/',
            ItsNotification::TYPE_IPTAL_DEVIR => '/common/app/transfercancel/',
            ItsNotification::TYPE_IPTAL_ECZANE_SATIS => '/prescription/app/pharmacysalecancel/',
            // "İade" servisi, alım/kabul bildirimini geri almak için kullanılır.
            ItsNotification::TYPE_IPTAL_IADE => '/common/app/return/',
            ItsNotification::TYPE_IPTAL_IHRACAT => '/wholesale/app/exportcancel/',
            ItsNotification::TYPE_IPTAL_SATIS => '/wholesale/app/dispatchcancel/',
            // Deaktivasyon iptali için resmi adres listesinde ayrı bir servis yok;
            // İTS dokümantasyonu netleşince doğrulanacak.
            ItsNotification::TYPE_IPTAL_DEAKTIVASYON => '/common/app/deactivation/',
            default => throw new ItsIntegrationException("Bilinmeyen İTS bildirim türü: {$type}"),
        };
    }

    public function checkCredentials(Depot $depot): bool
    {
        // TODO: İTS'nin kimlik doğrulama/health-check uç noktası netleşince uygulanacak.
        return true;
    }
}
