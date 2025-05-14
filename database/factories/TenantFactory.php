<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company(); // Use unique() to avoid slug collisions in tests
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_path' => null,
            'primary_color' => '#059669', // Default emerald-600
            'secondary_color' => null,
            'domain' => null,
            'use_custom_domain' => false,
            'currency' => 'EUR',
            'timezone' => 'Europe/Amsterdam',
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => fake()->countryCode(),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'vat_number' => null,
            'registration_number' => null,
            'enable_wallet' => true,
            'enable_inventory' => true,
            'enable_pos' => true,
            'enable_member_portal' => false,
        ];
    }
} 