<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\User;
use App\Services\ItsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_search_pts_packages(): void
    {
        $depot = Depot::create([
            'company_title' => 'Test Ecza Deposu',
            'gln_number' => '8680001000012',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
            'depot_id' => $depot->id,
        ]);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['token' => 'fake-token'])),
            new Response(200, [], json_encode([
                'transferDetails' => [
                    [
                        'sourceGln' => '8680001000012',
                        'destinationGln' => '8680001000022',
                        'transferId' => 100006426,
                        'transferDate' => '2027-03-29T13:32:58.085081Z',
                    ],
                ],
            ])),
        ]);

        $this->app->bind(ItsClient::class, fn () => new ItsClient(
            new Client(['handler' => HandlerStack::create($mock)])
        ));

        $response = $this->actingAs($user)->post(route('pts.search'), [
            'source_gln' => '8680001000012',
            'destination_gln' => '8680001000022',
            'start_date' => '2027-03-01',
            'end_date' => '2027-03-30',
        ]);

        $response->assertOk();
        $response->assertSee('100006426');
    }
}
