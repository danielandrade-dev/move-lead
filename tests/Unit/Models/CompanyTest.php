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
    /** @test
     * @group company
     */
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

    /** @test
     * @group company
     */
    public function test_company_has_stores_relationship(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();

        $this->assertTrue($company->stores->contains($store));
        $this->assertEquals(1, $company->stores->count());
    }

    /** @test
     * @group company
     */
    public function test_company_has_contracts_relationship(): void
    {
        $company = Company::factory()->create();
        $contract = Contract::factory()->forCompany($company)->create();

        $this->assertTrue($company->contracts->contains($contract));
        $this->assertEquals(1, $company->contracts->count());
    }

    /** @test
     * @group company
     */
    public function test_company_has_active_stores(): void
    {
        $company = Company::factory()->create();

        // Criar uma loja ativa com contrato ativo
        $activeStore = Store::factory()->forCompany($company)->create();
        Contract::factory()->forStore($activeStore)->create();

        // Criar uma loja inativa
        Store::factory()->forCompany($company)->inactive()->create();

        // Criar uma loja ativa sem contrato ativo
        $storeWithoutContract = Store::factory()->forCompany($company)->create();
        Contract::factory()->forStore($storeWithoutContract)->inactive()->create();

        $this->assertEquals(1, $company->activeStores()->count());
        $this->assertTrue($company->activeStores->contains($activeStore));
    }

    /** @test
     * @group company
     */
    public function test_company_has_active_contract(): void
    {
        $company = Company::factory()->create();

        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->forCompany($company)
            ->create();

        $this->assertTrue($company->hasActiveContract());
        $this->assertEquals($activeContract->id, $company->activeContract()->id);
    }

    /** @test
     * @group company
     */
    public function test_company_with_active_and_inactive_contracts(): void
    {
        $company = Company::factory()->create();

        // Criar um contrato ativo
        $activeContract = Contract::factory()
            ->forCompany($company)
            ->create();

        // Criar um contrato para outra empresa
        $otherCompany = Company::factory()->create();
        Contract::factory()
            ->forCompany($otherCompany)
            ->inactive()
            ->create();

        $this->assertTrue($company->hasActiveContract());
        $this->assertEquals(1, $company->contracts()->count());
        $this->assertEquals($activeContract->id, $company->activeContract()->id);
    }

    /** @test
     * @group company
     */
    public function test_company_without_active_contract(): void
    {
        $company = Company::factory()->create();

        // Criar apenas um contrato inativo
        Contract::factory()
            ->forCompany($company)
            ->inactive()
            ->create();

        $this->assertFalse($company->hasActiveContract());
        $this->assertNull($company->activeContract());
    }

    /** @test
     * @group company
     */
    public function test_scope_with_active_contract(): void
    {
        // Criar uma empresa com contrato ativo
        $companyWithActiveContract = Company::factory()->create();
        Contract::factory()
            ->forCompany($companyWithActiveContract)
            ->create();

        // Criar uma empresa sem contrato ativo
        $companyWithoutActiveContract = Company::factory()->create();

        // Criar uma empresa com contrato inativo
        $companyWithInactiveContract = Company::factory()->create();
        Contract::factory()
            ->forCompany($companyWithInactiveContract)
            ->inactive()
            ->create();

        // Criar uma empresa sem contratos
        $companyWithNoContract = Company::factory()->create();

        $companiesWithActiveContract = Company::withActiveContract()->get();

        $this->assertEquals(1, $companiesWithActiveContract->count());
        $this->assertTrue($companiesWithActiveContract->contains($companyWithActiveContract));
        $this->assertFalse($companiesWithActiveContract->contains($companyWithoutActiveContract));
        $this->assertFalse($companiesWithActiveContract->contains($companyWithInactiveContract));
        $this->assertFalse($companiesWithActiveContract->contains($companyWithNoContract));
    }

    /** @test
     * @group company
     */
    public function test_can_create_inactive_company(): void
    {
        $company = Company::factory()->inactive()->create();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => false,
        ]);
    }
}
