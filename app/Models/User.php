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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Models\Wallet;

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
        'fob_id',
        'tenant_id',
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
     * Get the owning tenant of this user record (if direct ownership exists).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
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

    /**
     * Get the user's wallet for the current tenant
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
    
    /**
     * Get all wallets across all tenants
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }
    
    /**
     * Get the wallet transactions for the user
     */
    public function walletTransactions()
    {
        return $this->hasManyThrough(
            WalletTransaction::class,
            Wallet::class,
            'user_id',
            'wallet_id'
        );
    }

    /**
     * Get the inventory transactions associated with the user.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'staff_id');
    }
    
    /**
     * Get the inventory adjustments (discrepancies) associated with the user (staff).
     */
    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'staff_id')
                    ->where('type', 'adjustment');
    }

    /**
     * Ensures that the user has a wallet, creating one if it doesn't exist.
     *
     * @return Wallet The user's wallet.
     */
    public function ensureWalletExists(): Wallet
    {
        // Use firstOrCreate on the HasOne relationship.
        // The first array is for matching attributes (usually empty for HasOne as it's keyed by user_id).
        // The second array is for attributes if the wallet needs to be created.
        $wallet = $this->wallet()->firstOrCreate([], [
            'balance' => 0.00, // Default balance as decimal
            'tenant_id' => $this->tenant_id, // Ensure tenant_id is set from the user
        ]);

        // Ensure the relationship is loaded on the current model instance if it was just created
        // and not already loaded.
        if (!$this->relationLoaded('wallet')) {
            $this->setRelation('wallet', $wallet);
        }
        
        return $wallet;
    }
}
