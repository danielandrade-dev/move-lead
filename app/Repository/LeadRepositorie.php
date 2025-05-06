<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Lead;
use App\Repository\Contracts\LeadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class LeadRepository implements LeadRepositoryInterface
{
    public function findAll(array $filters, int $perPage = 10): Collection
    {
        $query = Lead::query()
            ->when($filters['segment_id'] ?? false, function ($query, $segmentId) {
                $query->where('segment_id', $segmentId);
            })
            ->when($filters['name'] ?? false, function ($query, $name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($filters['phone'] ?? false, function ($query, $phone) {
                $query->where('phone', 'like', '%' . $phone . '%');
            })
            ->when($filters['is_active'] ?? false, function ($query, $isActive) {
                $query->where('is_active', $isActive);
            });

        return $query->paginate($perPage);
    }

    public function findById(int $id): Lead
    {
        return Lead::findOrFail($id);
    }

    public function update(Lead $lead): Lead
    {
        $lead->save();
        return $lead;
    }

    public function create(Lead $lead): Lead
    {
        $lead->save();
        return $lead;
    }
}