<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class TenantRegisterForm extends Component
{
    public Tenant $tenant;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function register()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::registerUser([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ], $this->tenant);

        auth()->login($user);

        // Redirect to tenant dashboard
        return redirect("/admin/tenants/{$this->tenant->slug}");
    }

    public function render()
    {
        return view('livewire.tenant-register-form');
    }
} 