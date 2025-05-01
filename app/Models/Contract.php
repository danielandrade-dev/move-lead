<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class Contract extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Atributos que são permitidos para atribuição em massa
     */
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
        'auto_close_at',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected $dates = [
        'start_date',
        'end_date',
        'completed_at',
        'auto_close_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lead_price' => 'decimal:2',
        'leads_contracted' => 'integer',
        'leads_delivered' => 'integer',
        'leads_returned' => 'integer',
        'leads_warranty_used' => 'integer',
        'warranty_percentage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    public function incrementDeliveredLeads(): void
    {
        DB::transaction(function (): void {
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

    public function completeContract(): void
    {
        $this->is_active = false;
        $this->completed_at = now();
        $this->auto_close_at = null;
        $this->save();
    }

    public function processLeadReturn(Lead $lead)
    {
        if ( ! $this->hasReachedWarrantyLimit()) {
            DB::transaction(function (): void {
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
        if (0 === $this->leads_contracted) {
            return 0;
        }
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

    /**
     * Boot function from Laravel
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($contract): void {
            if ( ! isset($contract->warranty_percentage)) {
                $contract->warranty_percentage = 30;
            }
        });

        static::saving(function ($contract): void {
            if ($contract->start_date > $contract->end_date) {
                throw new InvalidArgumentException('A data de início deve ser anterior à data de término');
            }

            if (null !== $contract->leads_contracted && $contract->leads_contracted < 1) {
                throw new InvalidArgumentException('O número de leads contratados deve ser maior que zero');
            }

            if (null !== $contract->lead_price && $contract->lead_price < 0) {
                throw new InvalidArgumentException('O preço dos leads não pode ser negativo');
            }
        });
    }

    protected function scheduleAutoClose(): void
    {
        $this->auto_close_at = now()->addDays(7);
        $this->save();
    }
}
