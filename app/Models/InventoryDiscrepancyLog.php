<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDiscrepancyLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'log_date',
        'expected_weight_start',
        'actual_weight_start',
        'expected_weight_end',
        'actual_weight_end',
        'discrepancy_weight',
        'notes',
        // Add 'tenant_id' here if not handled automatically
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'log_date' => 'date',
        'expected_weight_start' => 'decimal:3',
        'actual_weight_start' => 'decimal:3',
        'expected_weight_end' => 'decimal:3',
        'actual_weight_end' => 'decimal:3',
        'discrepancy_weight' => 'decimal:3',
    ];

    /**
     * Get the product associated with the log.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class); // Assuming Product model exists
    }

    /**
     * Get the user (staff) who recorded the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // Assuming User model exists
    }

    // Add Tenant relationship if needed:
    // public function tenant(): BelongsTo
    // {
    //     return $this->belongsTo(Tenant::class);
    // }
}
