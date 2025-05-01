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

    /** @test
     * @group lead
     */
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

    /** @test
     * @group lead
     */
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

    /** @test
     * @group lead
     */
    public function test_can_set_restriction_period(): void
    {
        Lead::setRestrictionPeriod(6);
        $this->assertEquals(6, Lead::getRestrictionPeriod());
    }

    /** @test
     * @group lead
     */
    public function test_cannot_set_invalid_restriction_period(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Lead::setRestrictionPeriod(0);
    }

    /** @test
     * @group lead
     */
    public function test_find_eligible_stores(): void
    {
        // Criar um lead
        $lead = Lead::factory()->withCoordinates(-23.5505, -46.6333)->create([
            'phone' => '(11) 98765-4321',
        ]);

        // Criar uma loja elegível (dentro do raio e sem restrição)
        $eligibleStore = Store::factory()->create([
            'name' => 'Loja Elegível'
        ]);
        Contract::factory()->forStore($eligibleStore)->create();
        $eligibleLocation = StoreLocation::factory()
            ->forStore($eligibleStore)
            ->withCoordinates(-23.5505, -46.6333)
            ->withCoverageRadius(10)
            ->create();

        // Criar uma loja fora do raio (Rio de Janeiro, ~400km de distância)
        $farStore = Store::factory()->create([
            'name' => 'Loja Distante'
        ]);
        Contract::factory()->forStore($farStore)->create();
        $farLocation = StoreLocation::factory()
            ->forStore($farStore)
            ->withCoordinates(-22.9068, -43.1729) // Rio de Janeiro
            ->withCoverageRadius(10)
            ->create();

        // Criar uma loja sem contrato ativo
        $storeWithoutContract = Store::factory()->create([
            'name' => 'Loja Sem Contrato'
        ]);
        Contract::factory()->forStore($storeWithoutContract)->inactive()->create();
        $withoutContractLocation = StoreLocation::factory()
            ->forStore($storeWithoutContract)
            ->withCoordinates(-23.5505, -46.6333)
            ->withCoverageRadius(10)
            ->create();

        $eligibleStores = $lead->findEligibleStores()->get();

        // Calcular a distância manualmente para debug
        function calcDistance($lat1, $lon1, $lat2, $lon2) {
            $earthRadius = 6371; // km
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $earthRadius * $c;
        }

        $distToEligible = calcDistance(-23.5505, -46.6333, $eligibleLocation->latitude, $eligibleLocation->longitude);
        $distToFar = calcDistance(-23.5505, -46.6333, $farLocation->latitude, $farLocation->longitude);

        // Debug: imprimir lojas para entender o problema
        echo "Loja elegível esperada: {$eligibleStore->id} ({$eligibleStore->name})\n";
        echo "  - Distância calculada: {$distToEligible}km (Raio de cobertura: {$eligibleLocation->coverage_radius}km)\n";
        echo "Loja distante: {$farStore->id} ({$farStore->name})\n";
        echo "  - Distância calculada: {$distToFar}km (Raio de cobertura: {$farLocation->coverage_radius}km)\n";
        echo "Loja sem contrato: {$storeWithoutContract->id} ({$storeWithoutContract->name})\n";
        echo "Lojas encontradas: \n";

        foreach ($eligibleStores as $store) {
            echo "- ID: {$store->id}, Nome: {$store->name}\n";

            $locations = $store->locations()->get();
            foreach ($locations as $location) {
                echo "  - Localização: {$location->name}, Coordenadas: {$location->latitude},{$location->longitude}, Raio: {$location->coverage_radius}\n";
            }

            $contracts = $store->contracts()->get();
            foreach ($contracts as $contract) {
                echo "  - Contrato: {$contract->id}, Ativo: " . ($contract->is_active ? 'Sim' : 'Não') . "\n";
            }
        }

        echo "Total de lojas encontradas: " . $eligibleStores->count() . "\n";

        $this->assertEquals(1, $eligibleStores->count());
        $this->assertTrue($eligibleStores->contains($eligibleStore));
        $this->assertFalse($eligibleStores->contains($farStore));
        $this->assertFalse($eligibleStores->contains($storeWithoutContract));
    }

    /** @test
     * @group lead
     */
    public function test_lead_has_been_sent_to_store(): void
    {
        $lead = Lead::factory()->create();
        $store = Store::factory()->create();
        $contract = Contract::factory()->forStore($store)->create();

        // Simular envio do lead para a loja
        $lead->stores()->attach($store->id, [
            'contract_id' => $contract->id,
            'status' => 'sent',
        ]);

        $this->assertTrue($lead->hasBeenSentToStore($store));
    }

    /** @test
     * @group lead
     */
    public function test_lead_has_been_sent_to_company(): void
    {
        $lead = Lead::factory()->create();
        $company = Company::factory()->create();
        $store = Store::factory()->forCompany($company)->create();
        $contract = Contract::factory()->forStore($store)->create();

        // Simular envio do lead para a loja da empresa
        $lead->stores()->attach($store->id, [
            'contract_id' => $contract->id,
            'status' => 'sent',
        ]);

        $this->assertTrue($lead->hasBeenSentToCompany($company));
    }

    /** @test
     * @group lead
     */
    public function test_lead_has_not_been_sent_to_store_after_restriction_period(): void
    {
        $lead = Lead::factory()->create();
        $store = Store::factory()->create();
        $contract = Contract::factory()->forStore($store)->create();

        // Simular envio do lead para a loja há mais tempo que o período de restrição
        $lead->stores()->attach($store->id, [
            'contract_id' => $contract->id,
            'status' => 'sent',
            'created_at' => now()->subMonths(Lead::getRestrictionPeriod() + 1),
        ]);

        $this->assertFalse($lead->hasBeenSentToStore($store));
    }

    /** @test
     * @group lead
     */
    public function test_can_create_inactive_lead(): void
    {
        $lead = Lead::factory()->inactive()->create();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'is_active' => false,
        ]);
    }
}
