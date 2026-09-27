<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\User;
use App\Services\ItsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarekodSorgulamaTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_queries_by_karekod_and_sees_owner_and_status(): void
    {
        $depot = Depot::create([
            'company_title' => 'Gaye İlaç',
            'gln_number' => '8680001000011',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['role' => 'customer', 'depot_id' => $depot->id]);

        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['token' => 'fake-token'])),
            new Response(200, [], json_encode(['responseObjectList' => [
                ['gln1' => '8680001000011', 'gln2' => '', 'gtin' => '08699828760047', 'sn' => '459725419', 'uc' => '60014'],
            ]])),
        ]));
        $stack->push(Middleware::history($history));
        $this->app->bind(ItsClient::class, fn () => new ItsClient(new Client(['handler' => $stack])));

        $this->actingAs($user)->get(route('karekod.index'))->assertOk()->assertSee('Karekod Okuyucu');

        $this->actingAs($user)
            ->post(route('karekod.search'), ['karekod' => "010869982876004721459725419\x1D172701071020210107"])
            ->assertOk()
            ->assertSee('8680001000011')
            ->assertSee('<span class="kq-pill">Gaye İlaç</span>', false)
            ->assertSee('Ürün üzerinize kayıtlıdır.');

        $sent = json_decode((string) $history[1]['request']->getBody(), true);
        $this->assertSame('/reference/app/check_status/', $history[1]['request']->getUri()->getPath());
        $this->assertSame(['gtin' => '08699828760047', 'sn' => '459725419', 'xd' => '2027-01-07', 'bn' => '20210107'], $sent['productList'][0]);
    }
}
