<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_lead(): void
    {
        $lead = Lead::factory()->create();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'zip_code' => $lead->zip_code,
            'city' => $lead->city,
            'state' => $lead->state,
            'address' => $lead->address,
            'latitude' => $lead->latitude,
            'longitude' => $lead->longitude,
            'status' => 'new',
            'is_active' => true,
        ]);
    }

    public function test_lead_creates_phone_on_creation(): void
    {
        $lead = Lead::factory()->create([
            'phone' => '(11) 98765-4321',
        ]);

        $this->assertDatabaseHas('lead_phones', [
            'lead_id' => $lead->id,
            'phone_original' => '(11) 98765-4321',
            'phone_normalized' => '11987654321',
        ]);

        $this->assertEquals(1, $lead->phones->count());
    }

    public function test_can_set_restriction_period(): void
    {
        Lead::setRestrictionPeriod(6);
        $this->assertEquals(6, Lead::getRestrictionPeriod());
    }

    public function test_cannot_set_invalid_restriction_period(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Lead::setRestrictionPeriod(0);
    }

    public function test_find_eligible_stores(): void
    {
        // Criar um lead
        $lead = Lead::factory()->withCoordinates(-23.5505, -46.6333)->create([
            'phone' => '(11) 98765-4321',
        ]);

        // Criar uma loja elegível (dentro do raio e sem restrição)
        $eligibleStore = Store::factory()->create();
        Contract::factory()->forStore($eligibleStore)->withStatus('active')->create();
        StoreLocation::factory()
            ->forStore($eligibleStore)
            ->withCoordinates(-23.5505, -46.6333)
            ->withCoverageRadius(10)
            ->create();

        // Criar uma loja fora do raio
        $farStore = Store::factory()->create();
        Contract::factory()->forStore($farStore)->withStatus('active')->create();
        StoreLocation::factory()
            ->forStore($farStore)
            ->withCoordinates(-23.9505, -46.9333) // ~50km de distância
            ->withCoverageRadius(10)
            ->create();

        // Criar uma loja sem contrato ativo
        $storeWithoutContract = Store::factory()->create();
        Contract::factory()->forStore($storeWithoutContract)->withStatus('inactive')->create();
        StoreLocation::factory()
            ->forStore($storeWithoutContract)
            ->withCoordinates(-23.5505, -46.6333)
            ->withCoverageRadius(10)
            ->create();

        $eligibleStores = $lead->findEligibleStores()->get();

        $this->assertEquals(1, $eligibleStores->count());
        $this->assertTrue($eligibleStores->contains($eligibleStore));
        $this->assertFalse($eligibleStores->contains($farStore));
        $this->assertFalse($eligibleStores->contains($storeWithoutContract));
    }

    public function test_lead_has_been_sent_to_store(): void
    {
        $lead = Lead::factory()->create();
        $store = Store::factory()->create();

        // Simular envio do lead para a loja
        $lead->stores()->attach($store->id);

        $this->assertTrue($lead->hasBeenSentToStore($store));
    }

    public function test_lead_has_been_sent_to_company(): void
    {
        $lead = Lead::factory()->create();
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();

        // Simular envio do lead para a loja da empresa
        $lead->stores()->attach($store->id);

        $this->assertTrue($lead->hasBeenSentToCompany($company));
    }

    public function test_lead_has_not_been_sent_to_store_after_restriction_period(): void
    {
        $lead = Lead::factory()->create();
        $store = Store::factory()->create();

        // Simular envio do lead para a loja há mais tempo que o período de restrição
        $lead->stores()->attach($store->id, [
            'created_at' => now()->subMonths(Lead::getRestrictionPeriod() + 1),
        ]);

        $this->assertFalse($lead->hasBeenSentToStore($store));
    }

    public function test_can_create_inactive_lead(): void
    {
        $lead = Lead::factory()->inactive()->create();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'is_active' => false,
        ]);
    }
}
