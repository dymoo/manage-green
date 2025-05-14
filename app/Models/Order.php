<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

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
    
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($currentTenant = Filament::getTenant()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $currentTenant->getKey());
            }
        });

        static::creating(function ($order) {
            if (!$order->tenant_id && ($currentTenant = Filament::getTenant())) {
                $order->tenant_id = $currentTenant->getKey();
            }
            
            if (!$order->order_number) {
                $tenantIdForOrderNumber = $order->tenant_id ?: (Filament::getTenant() ? Filament::getTenant()->getKey() : null);
                if ($tenantIdForOrderNumber) {
                    $order->order_number = static::generateOrderNumber($tenantIdForOrderNumber);
                } else {
                    throw new \InvalidArgumentException('Cannot generate order number: Tenant ID could not be determined.');
                }
            }
        });
    }
    
    public static function generateOrderNumber($tenantId)
    {
        if (!$tenantId) {
            throw new \InvalidArgumentException('Tenant ID is required to generate an order number.');
        }
        $prefix = 'ORD-';
        $unique = false;
        $orderNumber = '';
        
        while (!$unique) {
            $orderNumber = $prefix . strtoupper(Str::random(8));
            $exists = static::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('order_number', $orderNumber)
                ->exists();
                
            if (!$exists) {
                $unique = true;
            }
        }
        
        return $orderNumber;
    }
    
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
    
    public function scopeForTenant($query, $tenant)
    {
        $tenantId = $tenant instanceof Model ? $tenant->getKey() : $tenant;
        return $query->where($this->getTable() . '.tenant_id', $tenantId);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
