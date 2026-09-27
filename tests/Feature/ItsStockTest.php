<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\ItsStock;
use App\Models\User;
use App\Notifications\DailyStockReportNotification;
use App\Services\ItsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ItsStockTest extends TestCase
{
    use RefreshDatabase;

    protected function makeDepot(): Depot
    {
        return Depot::create([
            'company_title' => 'Gaye İlaç',
            'email' => 'yetkili@gaye.test',
            'gln_number' => '8680001000012',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);
    }

    public function test_command_stores_stock_and_mails_depot(): void
    {
        Notification::fake();
        config(['services.its.stock_endpoint' => '/stock/app/query/']);
        $depot = $this->makeDepot();

        $mock = new MockHandler([
            new Response(200, [], json_encode(['token' => 'fake-token'])),
            new Response(200, [], json_encode(['stockList' => [
                ['gtin' => '08699999999999', 'productName' => 'PAROL 500 MG', 'quantity' => 120],
            ]])),
        ]);
        $this->app->bind(ItsClient::class, fn () => new ItsClient(
            new Client(['handler' => HandlerStack::create($mock)])
        ));

        $this->artisan('its:fetch-stocks')->assertSuccessful();

        $this->assertDatabaseHas('its_stocks', [
            'depot_id' => $depot->id,
            'gtin' => '08699999999999',
            'its_quantity' => 120,
        ]);
        $this->assertSame(now()->subDay()->toDateString(), ItsStock::first()->stock_date->toDateString());
        Notification::assertSentOnDemand(DailyStockReportNotification::class);
    }

    public function test_command_fails_without_stock_endpoint(): void
    {
        Notification::fake();
        config(['services.its.stock_endpoint' => null]);
        $this->makeDepot();

        $this->artisan('its:fetch-stocks')->assertFailed();

        Notification::assertNothingSent();
    }

    public function test_command_is_scheduled_at_six(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'its:fetch-stocks'));

        $this->assertSame('0 6 * * *', $event?->expression);
    }

    public function test_customer_sees_and_exports_own_stock(): void
    {
        $depot = $this->makeDepot();
        $user = User::factory()->create(['role' => 'customer', 'depot_id' => $depot->id]);
        ItsStock::create([
            'depot_id' => $depot->id,
            'stock_date' => now()->toDateString(),
            'gtin' => '08699999999999',
            'product_name' => 'PAROL 500 MG',
            'its_quantity' => 120,
        ]);

        $this->actingAs($user)->get(route('stocks.index'))
            ->assertOk()
            ->assertSee('PAROL 500 MG');

        $csv = $this->actingAs($user)->get(route('stocks.export'))->assertOk()->getContent();
        $this->assertStringContainsString('08699999999999;"PAROL 500 MG";120', $csv);
    }
}
