<?php

namespace Tests\Feature;

use App\Services\ItsClient;
use Tests\TestCase;

class ItsClientConfigTest extends TestCase
{
    public function test_timeout_from_env_string_is_passed_as_number(): void
    {
        // .env değerleri string gelir; Guzzle "timeout" için int|float bekler.
        config(['services.its.timeout' => '30', 'services.its.base_url' => 'https://its2.saglik.gov.tr']);

        $client = new ItsClient;
        $http = (fn () => $this->http)->call($client);

        $this->assertSame(30.0, $http->getConfig('timeout'));
    }
}
