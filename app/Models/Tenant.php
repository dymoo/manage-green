<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;
use App\Models\Product;

class Tenant extends BaseTenant implements HasCurrentTenantLabel
{
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'primary_color',
        'secondary_color',
        'domain',
        'use_custom_domain',
        'currency',
        'timezone',
        'address',
        'city',
        'country',
        'postal_code',
        'phone',
        'email',
        'vat_number',
        'registration_number',
        'enable_wallet',
        'enable_inventory',
        'enable_pos',
        'enable_member_portal',
    ];
    
    protected $casts = [
        'use_custom_domain' => 'boolean',
        'enable_wallet' => 'boolean',
        'enable_inventory' => 'boolean',
        'enable_pos' => 'boolean',
        'enable_member_portal' => 'boolean',
    ];

    public function getCurrentTenantLabel(): string
    {
        return $this->name;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    
    public function settings(): HasMany
    {
        return $this->hasMany(ClubSetting::class);
    }
    
    /**
     * Get the products associated with this tenant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the stock checks associated with this tenant.
     */
    public function stockChecks(): HasMany
    {
        return $this->hasMany(StockCheck::class);
    }
    
    /**
     * Get a specific club setting by key
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()
            ->where('setting_key', $key)
            ->where('is_active', true)
            ->first();
            
        return $setting ? $setting->setting_value : $default;
    }
    
    /**
     * Get all settings for a specific group
     */
    public function getSettingsByGroup(string $group)
    {
        return $this->settings()
            ->where('setting_group', $group)
            ->where('is_active', true)
            ->get()
            ->pluck('setting_value', 'setting_key');
    }
    
    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    /**
     * Get the full URL for the tenant's domain
     */
    public function getDomainUrl(): string
    {
        if ($this->use_custom_domain && $this->domain) {
            return 'https://' . $this->domain;
        }
        
        return 'https://' . $this->slug . '.' . config('app.domain');
    }
}
