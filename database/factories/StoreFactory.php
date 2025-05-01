<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
final class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company() . ' ' . fake()->companySuffix(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
            'zip_code' => fake()->numerify('#####-###'),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'address' => fake()->streetAddress(),
            'is_active' => true,
        ];
    }

    /**
     * Indica que a loja está inativa
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Define uma empresa específica para a loja
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }
}
