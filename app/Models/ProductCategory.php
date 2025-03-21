<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $table = 'product_categories';
    
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'active',
        'sort_order',
    ];
    
    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Relationships
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
    
    // Scopes
    
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    public function scopeForTenant($query, $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }
}
