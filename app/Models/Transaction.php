<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'staff_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        // 'transactable_id', // Add if using morphs
        // 'transactable_type', // Add if using morphs
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // The user whose balance is affected
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id'); // The staff member who processed it
    }
    
    // Optional: Polymorphic relationship to the source (Sale, TopUp, etc.)
    // public function transactable(): MorphTo
    // {
    //     return $this->morphTo();
    // }
} 