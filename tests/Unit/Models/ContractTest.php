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

    public function test_can_create_contract(): void
    {
        $contract = Contract::factory()->create();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'company_id' => $contract->company_id,
            'store_id' => $contract->store_id,
            'monthly_value' => $contract->monthly_value,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_contract_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $contract = Contract::factory()->forCompany($company)->create();

        $this->assertEquals($company->id, $contract->company->id);
    }

    public function test_contract_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $contract = Contract::factory()->forStore($store)->create();

        $this->assertEquals($store->id, $contract->store->id);
    }

    public function test_contract_with_specific_dates(): void
    {
        $startDate = now();
        $endDate = now()->addYear();

        $contract = Contract::factory()
            ->withDates($startDate, $endDate)
            ->create();

        $this->assertEquals($startDate->format('Y-m-d H:i:s'), $contract->start_date->format('Y-m-d H:i:s'));
        $this->assertEquals($endDate->format('Y-m-d H:i:s'), $contract->end_date->format('Y-m-d H:i:s'));
    }

    public function test_contract_with_specific_status(): void
    {
        $contract = Contract::factory()
            ->withStatus('pending')
            ->create();

        $this->assertEquals('pending', $contract->status);
    }

    public function test_contract_for_store_and_company(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();

        $contract = Contract::factory()
            ->forStore($store)
            ->create();

        $this->assertEquals($store->id, $contract->store_id);
        $this->assertEquals($company->id, $contract->company_id);
    }

    public function test_can_create_inactive_contract(): void
    {
        $contract = Contract::factory()->inactive()->create();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'is_active' => false,
        ]);
    }

    public function test_scope_active(): void
    {
        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->withStatus('active')
            ->create();

        // Criar um contrato inativo
        Contract::factory()
            ->withStatus('inactive')
            ->create();

        // Criar um contrato pendente
        Contract::factory()
            ->withStatus('pending')
            ->create();

        $activeContracts = Contract::active()->get();

        $this->assertEquals(1, $activeContracts->count());
        $this->assertTrue($activeContracts->contains($activeContract));
    }
}
