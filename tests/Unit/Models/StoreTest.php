<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Lead;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** @test
     * @group store
     */
    public function test_can_create_store(): void
    {
        $store = Store::factory()->create();

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => $store->name,
            'document' => $store->document,
            'email' => $store->email,
            'phone' => $store->phone,
            'zip_code' => $store->zip_code,
            'city' => $store->city,
            'state' => $store->state,
            'address' => $store->address,
            'is_active' => true,
        ]);
    }

    /** @test
     * @group store
     */
    public function test_store_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();

        $this->assertEquals($company->id, $store->company->id);
    }

    /** @test
     * @group store
     */
    public function test_store_has_locations(): void
    {
        $store = Store::factory()->create();
        $location = StoreLocation::factory()->forStore($store)->create();

        $this->assertTrue($store->locations->contains($location));
        $this->assertEquals(1, $store->locations->count());
    }

    /** @test
     * @group store
     */
    public function test_store_has_users(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['store_id' => $store->id]);

        $this->assertTrue($store->users->contains($user));
        $this->assertEquals(1, $store->users->count());
    }

    /** @test
     * @group store
     */
    public function test_store_has_contracts(): void
    {
        $store = Store::factory()->create();
        $contract = Contract::factory()->forStore($store)->create();

        $this->assertTrue($store->contracts->contains($contract));
        $this->assertEquals(1, $store->contracts->count());
    }

    /** @test
     * @group store
     */
    public function test_store_has_active_contract(): void
    {
        $store = Store::factory()->create();

        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->forStore($store)
            ->create();

        $this->assertTrue($store->hasActiveContract());
        $this->assertEquals($activeContract->id, $store->activeContract()->id);
    }

    /** @test
     * @group store
     */
    public function test_store_with_active_and_inactive_contracts(): void
    {
        $store = Store::factory()->create();

        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->forStore($store)
            ->create();

        // Criar um contrato para outra loja
        $otherStore = Store::factory()->create();
        Contract::factory()
            ->forStore($otherStore)
            ->inactive()
            ->create();

        $this->assertTrue($store->hasActiveContract());
        $this->assertEquals(1, $store->contracts()->count());
        $this->assertEquals($activeContract->id, $store->activeContract()->id);
    }

    /** @test
     * @group store
     */
    public function test_store_without_active_contract(): void
    {
        $store = Store::factory()->create();

        // Criar apenas um contrato inativo
        Contract::factory()
            ->forStore($store)
            ->inactive()
            ->create();

        $this->assertFalse($store->hasActiveContract());
        $this->assertNull($store->activeContract());
    }

    /** @test
     * @group store
     */
    public function test_find_eligible_leads(): void
    {
        $store = Store::factory()->create();
        Contract::factory()->forStore($store)->create();

        // Criar uma localização para a loja
        StoreLocation::factory()
            ->forStore($store)
            ->withCoordinates(-23.5505, -46.6333)
            ->withCoverageRadius(10)
            ->state(['is_active' => true])
            ->create();

        // Criar um lead elegível (dentro do raio)
        $eligibleLead = Lead::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->withStatus('new')
            ->create();

        // Criar um lead fora do raio
        $farLead = Lead::factory()
            ->withCoordinates(-23.9505, -46.9333)
            ->withStatus('new')
            ->create();

        // Criar um lead dentro do raio mas já processado
        $processedLead = Lead::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->withStatus('sent')
            ->create();

        $eligibleLeads = $store->findEligibleLeads()->get();

        $this->assertEquals(1, $eligibleLeads->count());
        $this->assertTrue($eligibleLeads->contains($eligibleLead));
        $this->assertFalse($eligibleLeads->contains($farLead));
        $this->assertFalse($eligibleLeads->contains($processedLead));
    }

    /** @test
     * @group store
     */
    public function test_store_main_location(): void
    {
        $store = Store::factory()->create();

        // Criar uma localização principal
        $mainLocation = StoreLocation::factory()
            ->forStore($store)
            ->main()
            ->create();

        // Criar uma localização secundária
        StoreLocation::factory()
            ->forStore($store)
            ->create();

        $this->assertEquals($mainLocation->id, $store->mainLocation()->id);
    }

    /** @test
     * @group store
     */
    public function test_scope_with_active_contract(): void
    {
        // Criar uma loja com contrato ativo
        $storeWithActiveContract = Store::factory()->create();
        Contract::factory()
            ->forStore($storeWithActiveContract)
            ->create();

        // Criar uma loja sem contrato ativo
        $storeWithoutActiveContract = Store::factory()->create();

        // Criar outra loja com contrato inativo
        $storeWithInactiveContract = Store::factory()->create();
        Contract::factory()
            ->forStore($storeWithInactiveContract)
            ->inactive()
            ->create();

        // Criar uma loja sem contratos
        Store::factory()->create();

        $storesWithActiveContract = Store::withActiveContract()->get();

        $this->assertEquals(1, $storesWithActiveContract->count());
        $this->assertTrue($storesWithActiveContract->contains($storeWithActiveContract));
        $this->assertFalse($storesWithActiveContract->contains($storeWithoutActiveContract));
        $this->assertFalse($storesWithActiveContract->contains($storeWithInactiveContract));
    }

    /** @test
     * @group store
     */
    public function test_can_create_inactive_store(): void
    {
        $store = Store::factory()->inactive()->create();

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'is_active' => false,
        ]);
    }
}
