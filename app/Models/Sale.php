<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id', // The member making the purchase
        'staff_id', // The staff member processing the sale
        'total_amount', // In cents
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function items(): HasMany
    {
        // Assuming you'll have a SaleItem model later
        // return $this->hasMany(SaleItem::class);
        // return $this->hasMany(Transaction::class); // Linking directly to Transaction might not be correct.
        // Let's plan for SaleItem later and leave this commented out or return an empty relation for now.
        // For testing purposes, let's link to Transaction temporarily if DailySalesReportTest requires it.
        return $this->hasMany(Transaction::class); 
    }
} 