<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contractable_type',
        'contractable_id',
        'start_date',
        'end_date',
        'lead_price',
        'leads_contracted',
        'leads_delivered',
        'leads_returned',
        'leads_warranty_used',
        'warranty_percentage',
        'is_active',
        'completed_at',
        'auto_close_at'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'completed_at',
        'auto_close_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lead_price' => 'decimal:2',
        'warranty_percentage' => 'integer'
    ];

    public function contractable()
    {
        return $this->morphTo();
    }

    public function leadStores()
    {
        return $this->hasMany(LeadStore::class);
    }

    public function getAvailableWarrantyLeadsAttribute()
    {
        $maxWarrantyLeads = ceil($this->leads_contracted * ($this->warranty_percentage / 100));
        return max(0, $maxWarrantyLeads - $this->leads_warranty_used);
    }

    public function hasReachedWarrantyLimit(): bool
    {
        return $this->available_warranty_leads <= 0;
    }

    public function isComplete(): bool
    {
        return $this->leads_delivered >= $this->leads_contracted;
    }

    public function incrementDeliveredLeads()
    {
        DB::transaction(function () {
            $this->leads_delivered++;

            if ($this->isComplete()) {
                if ($this->hasReachedWarrantyLimit()) {
                    $this->completeContract();
                } else {
                    $this->scheduleAutoClose();
                }
            }

            $this->save();
        });
    }

    protected function scheduleAutoClose()
    {
        $this->auto_close_at = now()->addDays(7);
        $this->save();
    }

    public function completeContract()
    {
        $this->is_active = false;
        $this->completed_at = now();
        $this->auto_close_at = null;
        $this->save();
    }

    public function processLeadReturn(Lead $lead)
    {
        if (!$this->hasReachedWarrantyLimit()) {
            DB::transaction(function () {
                $this->leads_returned++;
                $this->leads_warranty_used++;

                if ($this->hasReachedWarrantyLimit() && $this->isComplete()) {
                    $this->completeContract();
                }

                $this->save();
            });

            return true;
        }

        return false;
    }

    public function getRemainingLeadsAttribute()
    {
        return max(0, $this->leads_contracted - $this->leads_delivered);
    }

    public function getWarrantyUsagePercentageAttribute()
    {
        if ($this->leads_contracted === 0) return 0;
        return ($this->leads_warranty_used / $this->leads_contracted) * 100;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePendingAutoClose($query)
    {
        return $query->where('is_active', true)
                    ->whereNotNull('auto_close_at')
                    ->where('auto_close_at', '<=', now());
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (!isset($contract->warranty_percentage)) {
                $contract->warranty_percentage = 30;
            }
        });
    }
}