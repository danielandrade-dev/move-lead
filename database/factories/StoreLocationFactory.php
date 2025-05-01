<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreLocation>
 */
final class StoreLocationFactory extends Factory
{
    protected $model = StoreLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Coordenadas aproximadas do Brasil
        $latitude = fake()->latitude(-33.7683, 5.2717);
        $longitude = fake()->longitude(-73.9872, -34.7299);

        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(3, true),
            'coverage_radius' => fake()->randomFloat(2, 1, 100),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_main' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indica que a localização está inativa
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Define como localização principal
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }

    /**
     * Define uma loja específica para a localização
     */
    public function forStore(Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $store->id,
        ]);
    }

    /**
     * Define coordenadas específicas para a localização
     */
    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /**
     * Define um raio de cobertura específico
     */
    public function withCoverageRadius(float $radius): static
    {
        return $this->state(fn (array $attributes) => [
            'coverage_radius' => $radius,
        ]);
    }
}
