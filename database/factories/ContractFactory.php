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
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_value' => fake()->randomFloat(2, 100, 10000),
            'status' => 'active',
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
     * Define um status específico para o contrato
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Define uma empresa específica para o contrato
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Define uma loja específica para o contrato
     */
    public function forStore(Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $store->id,
            'company_id' => $store->company_id,
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
