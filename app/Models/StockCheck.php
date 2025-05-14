<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\StockCheckType;

class StockCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'type',
        'check_in_at',
        'check_out_at',
        'start_notes',
        'end_notes',
        'checked_out_by',
        'total_weight_discrepancy',
        'total_value_discrepancy',
    ];
    
    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'type' => StockCheckType::class,
        'total_weight_discrepancy' => 'decimal:3',
        'total_value_discrepancy' => 'decimal:2',
    ];
    
    // Relationships
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
    
    public function checkoutStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(StockCheckItem::class);
    }
    
    // Scopes
    
    public function scopeForTenant($query, $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }
    
    public function scopeCheckIn($query)
    {
        return $query->where('type', StockCheckType::CheckIn);
    }
    
    public function scopeCheckOut($query)
    {
        return $query->where('type', StockCheckType::CheckOut);
    }
    
    public function scopeToday($query)
    {
        return $query->whereDate('check_in_at', now()->toDateString());
    }
    
    // Removing scopes for pending, in_progress, completed as there is no 'status' column
    // and 'type' enum does not cover these states.
    /*
    public function scopePending($query)
    {
        return $query->where('type', StockCheckType::Pending); // StockCheckType::Pending does not exist
    }
    
    public function scopeInProgress($query)
    {
        return $query->where('type', StockCheckType::InProgress); // StockCheckType::InProgress does not exist
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('type', StockCheckType::Completed); // StockCheckType::Completed does not exist
    }
    */
} 