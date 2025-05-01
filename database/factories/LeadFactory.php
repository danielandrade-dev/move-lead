<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Segments;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
final class LeadFactory extends Factory
{
    protected $model = Lead::class;

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
            'segment_id' => Segments::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
            'zip_code' => fake()->numerify('#####-###'),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'address' => fake()->streetAddress(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'new',
            'is_active' => true,
        ];
    }

    /**
     * Indica que o lead está inativo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Define um status específico para o lead
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Define um segmento específico para o lead
     */
    public function forSegment(Segments $segment): static
    {
        return $this->state(fn (array $attributes) => [
            'segment_id' => $segment->id,
        ]);
    }

    /**
     * Define coordenadas específicas para o lead
     */
    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
