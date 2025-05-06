<?php

declare(strict_types=1);

namespace App\Repository\Contracts;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

interface LeadRepositoryInterface
{
    public function findAll(array $filters, int $perPage = 10): Collection;
    public function findById(int $id): Lead;
    public function update(Lead $lead): Lead;
    public function create(Lead $lead): Lead;
}
