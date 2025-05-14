<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
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
        $balance_before_this_transaction = $this->balance;
        $this->balance += $amount;
        $this->save();
        
        $transaction_data = [
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'amount' => $amount,
            'type' => 'deposit',
            'balance_before' => $balance_before_this_transaction,
            'balance_after' => $this->balance,
        ];

        return $this->transactions()->create(array_merge($transaction_data, $attributes));
    }
    
    public function withdraw(float $amount, array $attributes = [])
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }
        
        $balance_before_this_transaction = $this->balance;
        $this->balance -= $amount;
        $this->save();
        
        $transaction_type = $attributes['type'] ?? 'withdrawal';
        unset($attributes['type']); 

        $transaction_data = [
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'amount' => -1 * $amount, 
            'type' => $transaction_type,
            'balance_before' => $balance_before_this_transaction,
            'balance_after' => $this->balance,
        ];

        return $this->transactions()->create(array_merge($transaction_data, $attributes));
    }
    
    public function canAfford(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
