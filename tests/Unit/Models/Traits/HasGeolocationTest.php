<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Traits;

use App\Models\StoreLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HasGeolocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_distance_to(): void
    {
        $location = StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333) // São Paulo
            ->create();

        // Testar distância para o mesmo ponto (deve ser 0)
        $distance = $location->getDistanceTo(-23.5505, -46.6333);
        $this->assertEquals(0, round($distance, 2));

        // Testar distância para o Rio de Janeiro (aproximadamente 360km)
        $distance = $location->getDistanceTo(-22.9068, -43.1729);
        $this->assertGreaterThan(350, $distance);
        $this->assertLessThan(370, $distance);
    }

    public function test_scope_within_radius(): void
    {
        // Criar uma localização em São Paulo
        StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->create();

        // Criar uma localização no Rio de Janeiro
        StoreLocation::factory()
            ->withCoordinates(-22.9068, -43.1729)
            ->create();

        // Buscar localizações dentro de um raio de 50km de São Paulo
        $nearbyLocations = StoreLocation::withinRadius(-23.5505, -46.6333, 50)->get();
        $this->assertEquals(1, $nearbyLocations->count());

        // Buscar localizações dentro de um raio de 500km de São Paulo (deve incluir Rio)
        $nearbyLocations = StoreLocation::withinRadius(-23.5505, -46.6333, 500)->get();
        $this->assertEquals(2, $nearbyLocations->count());
    }

    public function test_scope_order_by_distance(): void
    {
        // Criar uma localização em São Paulo
        $locationSP = StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->create();

        // Criar uma localização no Rio de Janeiro
        $locationRJ = StoreLocation::factory()
            ->withCoordinates(-22.9068, -43.1729)
            ->create();

        // Ordenar por distância a partir de Campinas
        $orderedLocations = StoreLocation::orderByDistance(-22.9099, -47.0626)->get();
        $this->assertEquals($locationSP->id, $orderedLocations->first()->id);
        $this->assertEquals($locationRJ->id, $orderedLocations->last()->id);

        // Ordenar por distância a partir do Rio de Janeiro
        $orderedLocations = StoreLocation::orderByDistance(-22.9068, -43.1729)->get();
        $this->assertEquals($locationRJ->id, $orderedLocations->first()->id);
        $this->assertEquals($locationSP->id, $orderedLocations->last()->id);
    }

    public function test_get_coordinates(): void
    {
        $location = StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->create();

        $coordinates = $location->getCoordinates();

        $this->assertEquals([
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ], $coordinates);
    }

    public function test_updates_geom_on_save(): void
    {
        $location = StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->create();

        // Atualizar coordenadas
        $location->latitude = -22.9068;
        $location->longitude = -43.1729;
        $location->save();

        // Verificar se a distância para o novo ponto é 0
        $this->assertEquals(0, round($location->getDistanceTo(-22.9068, -43.1729), 2));
    }
}
