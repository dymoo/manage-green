<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasTenantPermissions;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasTenantPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the tenants that this user belongs to.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }
    
    /**
     * Get the tenants that the user belongs to.
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    /**
     * Check if the user can access the given tenant.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant->getKey())->exists();
    }
    
    /**
     * Determine if the user can access Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }
        
        // For tenant-specific access, check if user has admin or staff role in current tenant
        if (Filament::getTenant()) {
            return $this->hasRole(['admin', 'staff'], Filament::getTenant());
        }
        
        return false;
    }
}
