<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'balance',
    ];
    
    protected $casts = [
        'balance' => 'decimal:2',
    ];
    
    // Relationships
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
    
    // Scopes
    
    public function scopeForTenant($query, $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }
    
    // Methods
    
    public function deposit(float $amount, array $attributes = [])
    {
        $this->balance += $amount;
        $this->save();
        
        return $this->transactions()->create(array_merge([
            'amount' => $amount,
            'type' => 'deposit',
        ], $attributes));
    }
    
    public function withdraw(float $amount, array $attributes = [])
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }
        
        $this->balance -= $amount;
        $this->save();
        
        return $this->transactions()->create(array_merge([
            'amount' => -1 * $amount,
            'type' => 'withdrawal',
        ], $attributes));
    }
    
    public function canAfford(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
