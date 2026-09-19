<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\ItsNotification;
use App\Models\User;
use App\Services\ItsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItsNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeItsClient(array $tokenBody, array $notificationBody): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode($tokenBody)),
            new Response(200, [], json_encode($notificationBody)),
        ]);

        $this->app->bind(ItsClient::class, fn () => new ItsClient(
            new Client(['handler' => HandlerStack::create($mock)])
        ));
    }

    public function test_index_and_create_pages_render(): void
    {
        $depot = Depot::create([
            'company_title' => 'Test Ecza Deposu',
            'gln_number' => '8680001000044',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['depot_id' => $depot->id]);

        $this->actingAs($admin)->get(route('its-notifications.index'))->assertOk();
        $this->actingAs($admin)->get(route('its-notifications.create', 'alim'))->assertOk();
        $this->actingAs($admin)->get(route('its-notifications.create', 'iptal_iade'))->assertOk();

        $this->actingAs($customer)->get(route('its-notifications.index'))->assertOk();
        $this->actingAs($customer)->get(route('its-notifications.create', 'alim'))->assertOk();

        $this->actingAs($customer)->get(route('its-notifications.create', 'satis'))->assertNotFound();
    }

    public function test_customer_can_submit_mal_alim_notification(): void
    {
        $depot = Depot::create([
            'company_title' => 'Test Ecza Deposu',
            'gln_number' => '8680001000011',
            'its_password' => 'gizli-sifre',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
            'depot_id' => $depot->id,
        ]);

        $this->fakeItsClient(
            ['token' => 'fake-token'],
            ['notificationid' => 2300358, 'productList' => [
                ['gtin' => '08694444444449', 'sn' => 'SN000000001', 'uc' => '00000'],
            ]],
        );

        $response = $this->actingAs($user)->post(route('its-notifications.store'), [
            'type' => ItsNotification::TYPE_ALIM,
            'products' => [
                ['gtin' => '08694444444449', 'sn' => 'SN000000001', 'bn' => 'BNTEST100012', 'xd' => '2027-10-10'],
            ],
        ]);

        $response->assertRedirect(route('its-notifications.index'));

        $this->assertDatabaseHas('its_notifications', [
            'depot_id' => $depot->id,
            'type' => ItsNotification::TYPE_ALIM,
            'status' => 'sent',
            'its_reference' => 2300358,
        ]);
    }

    public function test_customer_notification_is_always_scoped_to_own_depot(): void
    {
        $ownDepot = Depot::create([
            'company_title' => 'Kendi Deposu',
            'gln_number' => '8680001000022',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
            'depot_id' => $ownDepot->id,
        ]);

        $this->fakeItsClient(
            ['token' => 'fake-token'],
            ['notificationid' => 1, 'productList' => []],
        );

        // Customer, depot_id göndermeye çalışsa bile controller kendi deposunu kullanır.
        $this->actingAs($user)->post(route('its-notifications.store'), [
            'type' => ItsNotification::TYPE_ALIM,
            'depot_id' => 9999,
            'products' => [
                ['gtin' => '08694444444449', 'sn' => 'SN000000001', 'bn' => 'BNTEST100012', 'xd' => '2027-10-10'],
            ],
        ]);

        $this->assertDatabaseHas('its_notifications', [
            'depot_id' => $ownDepot->id,
        ]);

        $this->assertDatabaseMissing('its_notifications', [
            'depot_id' => 9999,
        ]);
    }

    public function test_failed_its_request_marks_notification_as_failed(): void
    {
        $depot = Depot::create([
            'company_title' => 'Test Ecza Deposu',
            'gln_number' => '8680001000033',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
            'depot_id' => $depot->id,
        ]);

        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $this->app->bind(ItsClient::class, fn () => new ItsClient(
            new Client(['handler' => HandlerStack::create($mock)])
        ));

        $this->actingAs($user)->post(route('its-notifications.store'), [
            'type' => ItsNotification::TYPE_ALIM,
            'products' => [
                ['gtin' => '08694444444449', 'sn' => 'SN000000001', 'bn' => 'BNTEST100012', 'xd' => '2027-10-10'],
            ],
        ]);

        $this->assertDatabaseHas('its_notifications', [
            'depot_id' => $depot->id,
            'status' => 'failed',
        ]);
    }
}
