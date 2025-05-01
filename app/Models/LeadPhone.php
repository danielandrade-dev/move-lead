<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class LeadPhone extends Model
{
    use HasFactory;

    protected $fillable = ['lead_id', 'phone_normalized', 'phone_original'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    // Normaliza o número de telefone (remove tudo exceto números)
    public static function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
