<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'staff_id',
        'order_number',
        'subtotal',
        'tax',
        'total',
        'payment_method',
        'status',
        'notes',
        'completed_at',
    ];
    
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'completed_at' => 'datetime',
    ];
    
    // Boot method to auto-generate order number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = static::generateOrderNumber($order->tenant_id);
            }
        });
    }
    
    // Generate a unique order number
    public static function generateOrderNumber($tenantId)
    {
        $prefix = 'ORD-';
        $unique = false;
        $orderNumber = '';
        
        while (!$unique) {
            $orderNumber = $prefix . strtoupper(Str::random(8));
            $exists = static::where('tenant_id', $tenantId)
                ->where('order_number', $orderNumber)
                ->exists();
                
            if (!$exists) {
                $unique = true;
            }
        }
        
        return $orderNumber;
    }
    
    // Relationships
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function walletTransaction(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
    
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    
    // Scopes
    
    public function scopeForTenant($query, $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
