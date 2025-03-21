<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasTenantPermissions;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
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
        // For super admins, return all tenants
        if ($this->hasRole('super_admin')) {
            return Tenant::all();
        }
        
        return $this->tenants;
    }

    /**
     * Check if the user can access the given tenant.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // Super admins can access any tenant
        if ($this->hasRole('super_admin')) {
            return true;
        }
        
        return $this->tenants()->whereKey($tenant->getKey())->exists();
    }
    
    /**
     * Get the default tenant for the user.
     */
    public function getDefaultTenant(Panel $panel): ?Model
    {
        // For super admins, prioritize the "manage-green" tenant if it exists
        if ($this->hasRole('super_admin')) {
            $defaultTenant = Tenant::where('slug', 'manage-green')->first();
            
            if ($defaultTenant && $this->canAccessTenant($defaultTenant)) {
                return $defaultTenant;
            }
        }
        
        // Otherwise, return the first tenant they have access to
        return $this->tenants()->first();
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
    
    /**
     * Create a new user and assign it to a tenant with the default 'user' role.
     *
     * @param array $attributes User attributes
     * @param Tenant|null $tenant Tenant to assign the user to
     * @return self
     */
    public static function registerUser(array $attributes, ?Tenant $tenant = null): self
    {
        // Create the user
        $user = static::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);
        
        // If a tenant is provided, assign the user to it with the 'user' role
        if ($tenant) {
            $user->tenants()->attach($tenant);
            
            // Get or create the 'user' role for this tenant
            $role = Role::firstOrCreate(
                ['name' => 'user', 'tenant_id' => $tenant->id],
                ['guard_name' => 'web']
            );
            
            // Assign the role to the user
            $user->assignRole($role);
        }
        
        return $user;
    }
}
