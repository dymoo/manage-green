<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'setting_key',
        'setting_value',
        'setting_group',
        'description',
        'is_active',
    ];
    
    protected $casts = [
        'setting_value' => 'json',
        'is_active' => 'boolean',
    ];
    
    // Relationships
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // Scopes
    
    public function scopeForTenant($query, $tenant)
    {
        return $query->where('tenant_id', $tenant->id);
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByGroup($query, string $group)
    {
        return $query->where('setting_group', $group);
    }
    
    // Get a setting by key for a tenant
    public static function getSetting(int $tenantId, string $key, $default = null)
    {
        $setting = self::where('tenant_id', $tenantId)
            ->where('setting_key', $key)
            ->where('is_active', true)
            ->first();
            
        return $setting ? $setting->setting_value : $default;
    }
}
