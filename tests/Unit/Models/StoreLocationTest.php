<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StoreLocationTest extends TestCase
{
    use RefreshDatabase;

    /** @test
     * @group storelocation
     */
    public function test_can_create_store_location(): void
    {
        $location = StoreLocation::factory()->create();

        $this->assertDatabaseHas('store_locations', [
            'id' => $location->id,
            'store_id' => $location->store_id,
            'name' => $location->name,
            'coverage_radius' => $location->coverage_radius,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'is_main' => false,
            'is_active' => true,
        ]);
    }

    /** @test
     * @group storelocation
     */
    public function test_store_location_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $location = StoreLocation::factory()->forStore($store)->create();

        $this->assertEquals($store->id, $location->store->id);
    }

    /** @test
     * @group storelocation
     */
    public function test_store_location_with_specific_coordinates(): void
    {
        $latitude = -23.5505;
        $longitude = -46.6333;

        $location = StoreLocation::factory()
            ->withCoordinates($latitude, $longitude)
            ->create();

        $this->assertEquals($latitude, $location->latitude);
        $this->assertEquals($longitude, $location->longitude);
    }

    /** @test
     * @group storelocation
     */
    public function test_store_location_with_specific_coverage_radius(): void
    {
        $radius = 15;

        $location = StoreLocation::factory()
            ->withCoverageRadius($radius)
            ->create();

        $this->assertEquals($radius, $location->coverage_radius);
    }

    /** @test
     * @group storelocation
     */
    public function test_can_create_main_location(): void
    {
        $location = StoreLocation::factory()
            ->main()
            ->create();

        $this->assertTrue($location->is_main);
    }

    /** @test
     * @group storelocation
     */
    public function test_can_create_inactive_location(): void
    {
        $location = StoreLocation::factory()
            ->inactive()
            ->create();

        $this->assertFalse($location->is_active);
    }

    /** @test
     * @group storelocation
     */
    public function test_get_distance_to_point(): void
    {
        $location = StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333) // São Paulo
            ->create();

        $distance = $location->getDistanceTo(-23.5505, -46.6333); // Mesmo ponto
        $this->assertEquals(0, round($distance, 2));

        $distance = $location->getDistanceTo(-22.9068, -43.1729); // Rio de Janeiro
        $this->assertGreaterThan(350, $distance); // Aproximadamente 360km
        $this->assertLessThan(370, $distance);
    }

    /** @test
     * @group storelocation
     */
    public function test_scope_within_radius(): void
    {
        // Criar uma localização em São Paulo
        StoreLocation::factory()
            ->withCoordinates(-23.5505, -46.6333)
            ->withCoverageRadius(10)
            ->create();

        // Criar uma localização no Rio de Janeiro
        StoreLocation::factory()
            ->withCoordinates(-22.9068, -43.1729)
            ->withCoverageRadius(10)
            ->create();

        // Buscar localizações dentro de um raio de 50km de São Paulo
        $nearbyLocations = StoreLocation::withinRadius(-23.5505, -46.6333, 50)->get();

        $this->assertEquals(1, $nearbyLocations->count());
    }

    /** @test
     * @group storelocation
     */
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
    }

    /** @test
     * @group storelocation
     */
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
}
