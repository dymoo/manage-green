<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Wallet creation can be part of a specific state or handled by User model events
            // if a wallet should always be created for every user.
            // For now, let's assume User::ensureWalletExists() or similar will be called when needed.
        });
    }

    /**
     * Indicate that the user is a member of a specific tenant.
     */
    public function member(?Tenant $tenant = null): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            $tenantIdToSet = $tenant?->id;
            if (!$tenantIdToSet && isset($attributes['tenant_id'])) {
                $tenantIdToSet = $attributes['tenant_id'];
            }
            if (!$tenantIdToSet) {
                $tenantIdToSet = Tenant::factory()->create()->id;
            }
            return [
                'tenant_id' => $tenantIdToSet,
            ];
        })->afterCreating(function (User $user) use ($tenant) {
            $resolvedTenant = $tenant ?? Tenant::find($user->tenant_id);
            if ($resolvedTenant) {
                $user->tenants()->syncWithoutDetaching([$resolvedTenant->id]);
                $role = Role::firstOrCreate(['name' => 'member', 'tenant_id' => $resolvedTenant->id], ['guard_name' => 'web']);
                $user->assignRole($role);
                $user->ensureWalletExists(); // Ensure wallet is created for members
            }
        });
    }

    /**
     * Indicate that the user is an admin of a specific tenant.
     */
    public function admin(?Tenant $tenant = null): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            $tenantIdToSet = $tenant?->id;
            if (!$tenantIdToSet && isset($attributes['tenant_id'])) {
                $tenantIdToSet = $attributes['tenant_id'];
            }
            if (!$tenantIdToSet) {
                $tenantIdToSet = Tenant::factory()->create()->id;
            }
            return [
                'tenant_id' => $tenantIdToSet,
            ];
        })->afterCreating(function (User $user) use ($tenant) {
            $resolvedTenant = $tenant ?? Tenant::find($user->tenant_id);
            if ($resolvedTenant) {
                $user->tenants()->syncWithoutDetaching([$resolvedTenant->id]);
                $role = Role::firstOrCreate(['name' => 'admin', 'tenant_id' => $resolvedTenant->id], ['guard_name' => 'web']);
                $user->assignRole($role);
                $user->ensureWalletExists(); // Admins might also have wallets
            }
        });
    }

    /**
     * Indicate that the user is staff of a specific tenant.
     */
    public function staff(?Tenant $tenant = null): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            $tenantIdToSet = $tenant?->id;
            if (!$tenantIdToSet && isset($attributes['tenant_id'])) {
                $tenantIdToSet = $attributes['tenant_id'];
            }
            if (!$tenantIdToSet) {
                $tenantIdToSet = Tenant::factory()->create()->id;
            }
            return [
                'tenant_id' => $tenantIdToSet,
            ];
        })->afterCreating(function (User $user) use ($tenant) {
            $resolvedTenant = $tenant ?? Tenant::find($user->tenant_id);
            if ($resolvedTenant) {
                $user->tenants()->syncWithoutDetaching([$resolvedTenant->id]);
                $role = Role::firstOrCreate(['name' => 'staff', 'tenant_id' => $resolvedTenant->id], ['guard_name' => 'web']);
                $user->assignRole($role);
                $user->ensureWalletExists(); // Staff might also have wallets
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
