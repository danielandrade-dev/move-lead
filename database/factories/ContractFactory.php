<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
final class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+2 years');

        return [
            'contractable_type' => Company::class,
            'contractable_id' => Company::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'lead_price' => fake()->randomFloat(2, 100, 10000),
            'leads_contracted' => fake()->numberBetween(10, 100),
            'leads_delivered' => 0,
            'leads_returned' => 0,
            'leads_warranty_used' => 0,
            'warranty_percentage' => 30,
            'is_active' => true,
        ];
    }

    /**
     * Indica que o contrato está inativo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Define uma empresa específica para o contrato
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'contractable_type' => Company::class,
            'contractable_id' => $company->id,
        ]);
    }

    /**
     * Define uma loja específica para o contrato
     */
    public function forStore(Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'contractable_type' => Store::class,
            'contractable_id' => $store->id,
        ]);
    }

    /**
     * Define datas específicas para o contrato
     */
    public function withDates(\DateTime $startDate, \DateTime $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
