<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;
use Illuminate\Support\Str;

class RegisterTenant extends BaseRegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register tenant';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, $set) {
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Tenant::class),
            ]);
    }

    protected function handleRegistration(array $data): Tenant
    {
        $tenant = Tenant::create($data);

        $tenant->users()->attach(auth()->user());

        return $tenant;
    }
} 