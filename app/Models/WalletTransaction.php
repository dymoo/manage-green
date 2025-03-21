<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'order_id',
        'amount',
        'type',
        'payment_method',
        'staff_id',
        'reference',
        'notes',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
    ];
    
    // Relationships
    
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
    
    // Helpers
    
    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }
    
    public function isWithdrawal(): bool
    {
        return $this->type === 'withdrawal';
    }
    
    public function isPurchase(): bool
    {
        return $this->type === 'purchase';
    }
}
