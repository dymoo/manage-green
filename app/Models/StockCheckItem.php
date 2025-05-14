<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_check_id',
        'product_id',
        'expected_quantity',
        'actual_quantity',
        'discrepancy',
        'notes',
    ];
    
    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'discrepancy' => 'decimal:3',
    ];
    
    // Relationships
    
    public function stockCheck(): BelongsTo
    {
        return $this->belongsTo(StockCheck::class);
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    // Accessors & Mutators
    
    protected static function booted(): void
    {
        static::creating(function ($stockCheckItem) {
            if ($stockCheckItem->expected_quantity !== null && $stockCheckItem->actual_quantity !== null) {
                $stockCheckItem->discrepancy = $stockCheckItem->actual_quantity - $stockCheckItem->expected_quantity;
            }
        });
        
        static::updating(function ($stockCheckItem) {
            if ($stockCheckItem->expected_quantity !== null && $stockCheckItem->actual_quantity !== null) {
                $stockCheckItem->discrepancy = $stockCheckItem->actual_quantity - $stockCheckItem->expected_quantity;
            }
        });
    }
} 