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
 * mantığıyla) kullanılarak İTS'ye iletilir. Gerçek uç nokta, istek/yanıt
 * şeması ve kimlik doğrulama yöntemi (SOAP zarfı ya da REST/JSON) İTS
 * dokümantasyonu netleştikçe burada somutlaştırılacaktır.
 */
class ItsClient
{
    protected Client $http;

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

    protected function endpointFor(string $type): string
    {
        return match ($type) {
            ItsNotification::TYPE_ALIM => '/bildirim/alim',
            ItsNotification::TYPE_SATIS => '/bildirim/satis',
            ItsNotification::TYPE_DEVIR => '/bildirim/devir',
            ItsNotification::TYPE_ECZANE_SATIS => '/bildirim/eczane-satis',
            ItsNotification::TYPE_IHRACAT => '/bildirim/ihracat',
            ItsNotification::TYPE_URETIM => '/bildirim/uretim',
            ItsNotification::TYPE_DEAKTIVASYON => '/deaktivasyon',
            default => '/bildirim/'.str_replace('_', '-', $type),
        };
    }

    public function checkCredentials(Depot $depot): bool
    {
        // TODO: İTS'nin kimlik doğrulama/health-check uç noktası netleşince uygulanacak.
        return true;
    }
}
