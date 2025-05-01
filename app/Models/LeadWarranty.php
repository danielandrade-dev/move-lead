<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class LeadWarranty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_store_id',
        'new_lead_id',
        'status',
        'return_reason',
        'analysis_notes',
        'analyzed_by',
        'analyzed_at',
        'replaced_at'
    ];

    protected $dates = ['analyzed_at', 'replaced_at'];

    // Processa a aprovação da garantia
    public function approve(User $analyst, ?string $notes = null)
    {
        DB::transaction(function () use ($analyst, $notes) {
            $contract = $this->leadStore->contract;

            if (!$contract->hasReachedWarrantyLimit()) {
                $this->update([
                    'status' => 'waiting_replacement',
                    'analysis_notes' => $notes,
                    'analyzed_by' => $analyst->id,
                    'analyzed_at' => now()
                ]);

                $contract->processLeadReturn($this->leadStore->lead);
            } else {
                throw new \Exception(__('Warranty limit reached for this contract'));
            }
        });
    }

    // Quando um novo lead é atribuído como substituição
    public function assignReplacementLead(Lead $newLead)
    {
        DB::transaction(function () use ($newLead) {
            // Cria novo LeadStore para o lead de garantia
            LeadStore::create([
                'lead_id' => $newLead->id,
                'store_id' => $this->leadStore->store_id,
                'contract_id' => $this->leadStore->contract_id,
                'status' => 'sent',
                'sent_at' => now(),
                'is_warranty' => true
            ]);

            $this->update([
                'new_lead_id' => $newLead->id,
                'status' => 'replaced',
                'replaced_at' => now()
            ]);
        });
    }

    public function leadStore()
    {
        return $this->belongsTo(LeadStore::class);
    }

    public function newLead()
    {
        return $this->belongsTo(Lead::class, 'new_lead_id');
    }

    public function analyst()
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }
}
