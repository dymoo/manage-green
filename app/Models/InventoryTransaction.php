<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'order_id',
        'staff_id',
        'quantity',
        'stock_before',
        'stock_after',
        'type',
        'reference',
        'notes',
    ];
    
    protected $casts = [
        'quantity' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
    ];
    
    // Relationships
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
    
    // Scopes
    
    public function scopeForTenant($query, $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }
}
