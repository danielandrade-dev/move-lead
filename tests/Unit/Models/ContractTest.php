<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContractTest extends TestCase
{
    use RefreshDatabase;
    /** @test
     * @group contract
     */
    public function test_can_create_contract(): void
    {
        $contract = Contract::factory()->create();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'contractable_type' => $contract->contractable_type,
            'contractable_id' => $contract->contractable_id,
            'lead_price' => $contract->lead_price,
            'leads_contracted' => $contract->leads_contracted,
            'is_active' => true,
        ]);
    }

    /** @test
     * @group contract
     */
    public function test_contract_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $contract = Contract::factory()->forCompany($company)->create();

        $this->assertEquals($company->id, $contract->contractable_id);
        $this->assertEquals(Company::class, $contract->contractable_type);
        $this->assertEquals($company->id, $contract->contractable->id);
    }

    /** @test
     * @group contract
     */
    public function test_contract_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $contract = Contract::factory()->forStore($store)->create();

        $this->assertEquals($store->id, $contract->contractable_id);
        $this->assertEquals(Store::class, $contract->contractable_type);
        $this->assertEquals($store->id, $contract->contractable->id);
    }

    /** @test
     * @group contract
     */
    public function test_contract_with_specific_dates(): void
    {
        $startDate = now();
        $endDate = now()->addYear();

        $contract = Contract::factory()
            ->withDates($startDate, $endDate)
            ->create();

        $this->assertEquals($startDate->format('Y-m-d'), $contract->start_date->format('Y-m-d'));
        $this->assertEquals($endDate->format('Y-m-d'), $contract->end_date->format('Y-m-d'));
    }

    /** @test
     * @group contract
     */
    public function test_contract_for_store_and_company(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();

        $contract = Contract::factory()
            ->forStore($store)
            ->create();

        $this->assertEquals($store->id, $contract->contractable_id);
        $this->assertEquals(Store::class, $contract->contractable_type);
        $this->assertEquals($store->id, $contract->contractable->id);
        $this->assertEquals($company->id, $contract->contractable->company_id);
    }

    /** @test
     * @group contract
     */
    public function test_can_create_inactive_contract(): void
    {
        $contract = Contract::factory()->inactive()->create();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'is_active' => false,
        ]);
    }

    /** @test
     * @group contract
     */
    public function test_scope_active(): void
    {
        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->create();

        // Criar um contrato inativo
        Contract::factory()
            ->inactive()
            ->create();

        $activeContracts = Contract::active()->get();

        $this->assertEquals(1, $activeContracts->count());
        $this->assertTrue($activeContracts->contains($activeContract));
    }

    /** @test
     * @group contract
     */
    public function test_contract_warranty_calculations(): void
    {
        $contract = Contract::factory()
            ->create([
                'leads_contracted' => 100,
                'warranty_percentage' => 30,
                'leads_warranty_used' => 0,
            ]);

        // Deve ter 30 leads disponíveis para garantia (30% de 100)
        $this->assertEquals(30, $contract->available_warranty_leads);

        // Usar 10 leads da garantia
        $contract->leads_warranty_used = 10;
        $contract->save();

        // Deve ter 20 leads restantes para garantia
        $this->assertEquals(20, $contract->available_warranty_leads);

        // Usar todos os leads da garantia
        $contract->leads_warranty_used = 30;
        $contract->save();

        // Não deve ter mais leads disponíveis para garantia
        $this->assertEquals(0, $contract->available_warranty_leads);
        $this->assertTrue($contract->hasReachedWarrantyLimit());
    }

    /** @test
     * @group contract
     */
    public function test_contract_completion(): void
    {
        $contract = Contract::factory()
            ->create([
                'leads_contracted' => 10,
                'leads_delivered' => 0,
            ]);

        // Contrato não deve estar completo
        $this->assertFalse($contract->isComplete());

        // Entregar todos os leads
        $contract->leads_delivered = 10;
        $contract->save();

        // Contrato deve estar completo
        $this->assertTrue($contract->isComplete());
    }
}
