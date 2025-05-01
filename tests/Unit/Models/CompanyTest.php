<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company(): void
    {
        $company = Company::factory()->create();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => $company->name,
            'document' => $company->document,
            'email' => $company->email,
            'phone' => $company->phone,
            'zip_code' => $company->zip_code,
            'city' => $company->city,
            'state' => $company->state,
            'address' => $company->address,
            'is_active' => true,
        ]);
    }

    public function test_company_has_stores_relationship(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();

        $this->assertTrue($company->stores->contains($store));
        $this->assertEquals(1, $company->stores->count());
    }

    public function test_company_has_contracts_relationship(): void
    {
        $company = Company::factory()->create();
        $contract = Contract::factory()->forCompany($company)->create();

        $this->assertTrue($company->contracts->contains($contract));
        $this->assertEquals(1, $company->contracts->count());
    }

    public function test_company_has_active_stores(): void
    {
        $company = Company::factory()->create();

        // Criar uma loja ativa com contrato ativo
        $activeStore = Store::factory()->forCompany($company)->create();
        Contract::factory()->forStore($activeStore)->create([
            'status' => 'active',
        ]);

        // Criar uma loja inativa
        Store::factory()->forCompany($company)->inactive()->create();

        // Criar uma loja ativa sem contrato ativo
        $storeWithoutContract = Store::factory()->forCompany($company)->create();
        Contract::factory()->forStore($storeWithoutContract)->create([
            'status' => 'inactive',
        ]);

        $this->assertEquals(1, $company->activeStores()->count());
        $this->assertTrue($company->activeStores->contains($activeStore));
    }

    public function test_company_has_active_contract(): void
    {
        $company = Company::factory()->create();

        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->forCompany($company)
            ->withStatus('active')
            ->create();

        // Criar um contrato inativo
        Contract::factory()
            ->forCompany($company)
            ->withStatus('inactive')
            ->create();

        $this->assertTrue($company->hasActiveContract());
        $this->assertEquals($activeContract->id, $company->activeContract()->id);
    }

    public function test_company_without_active_contract(): void
    {
        $company = Company::factory()->create();

        // Criar apenas contratos inativos
        Contract::factory()
            ->forCompany($company)
            ->withStatus('inactive')
            ->count(2)
            ->create();

        $this->assertFalse($company->hasActiveContract());
        $this->assertNull($company->activeContract());
    }

    public function test_scope_with_active_contract(): void
    {
        // Criar uma empresa com contrato ativo
        $companyWithActiveContract = Company::factory()->create();
        Contract::factory()
            ->forCompany($companyWithActiveContract)
            ->withStatus('active')
            ->create();

        // Criar uma empresa sem contrato ativo
        $companyWithoutActiveContract = Company::factory()->create();
        Contract::factory()
            ->forCompany($companyWithoutActiveContract)
            ->withStatus('inactive')
            ->create();

        // Criar uma empresa sem contratos
        Company::factory()->create();

        $companiesWithActiveContract = Company::withActiveContract()->get();

        $this->assertEquals(1, $companiesWithActiveContract->count());
        $this->assertTrue($companiesWithActiveContract->contains($companyWithActiveContract));
    }

    public function test_can_create_inactive_company(): void
    {
        $company = Company::factory()->inactive()->create();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => false,
        ]);
    }
}
