<?php

namespace Tests\Feature;

use App\Models\CredentialChangeRequest;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_depot(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('depots.store'), [
            'company_title' => 'Yeni Ecza Deposu',
            'gln_number' => '8680001000099',
            'its_password' => 'gizli-sifre',
            'status' => 'active',
        ]);

        $depot = Depot::firstWhere('gln_number', '8680001000099');

        $response->assertRedirect(route('depots.show', $depot));
        $this->assertNotNull($depot);
        $this->assertSame('Yeni Ecza Deposu', $depot->company_title);
        $this->assertSame('gizli-sifre', $depot->its_password);
    }

    public function test_customer_cannot_create_a_depot(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post(route('depots.store'), [
            'company_title' => 'Yeni Ecza Deposu',
            'gln_number' => '8680001000099',
            'its_password' => 'gizli-sifre',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_customer_cannot_view_another_depots_page(): void
    {
        $ownDepot = Depot::create([
            'company_title' => 'Kendi Deposu',
            'gln_number' => '8680001000011',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $otherDepot = Depot::create([
            'company_title' => 'Başka Depo',
            'gln_number' => '8680001000022',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $customer = User::factory()->create(['depot_id' => $ownDepot->id]);

        $this->actingAs($customer)->get(route('depots.show', $otherDepot))->assertForbidden();
        $this->actingAs($customer)->get(route('depots.show', $ownDepot))->assertOk();
    }

    public function test_customer_can_submit_and_admin_can_approve_credential_change_request(): void
    {
        $depot = Depot::create([
            'company_title' => 'Ecza Deposu',
            'gln_number' => '8680001000033',
            'its_password' => 'sifre',
            'status' => 'active',
        ]);

        $customer = User::factory()->create(['depot_id' => $depot->id]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($customer)->post(route('credential-requests.store'), [
            'reason' => 'GLN numarası değişti.',
        ])->assertRedirect();

        $changeRequest = CredentialChangeRequest::firstWhere('depot_id', $depot->id);
        $this->assertSame('pending', $changeRequest->status);

        $this->actingAs($admin)->post(route('credential-requests.review', $changeRequest), [
            'decision' => 'approved',
        ])->assertRedirect();

        $this->assertSame('approved', $changeRequest->fresh()->status);
        $this->assertSame($admin->id, $changeRequest->fresh()->reviewed_by);
    }

    public function test_depots_create_route_does_not_collide_with_show_route(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('depots.create'))->assertOk();
    }
}
