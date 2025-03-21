<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Facades\Filament;

class Tenants extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.pages.tenants';

    protected static ?string $navigationLabel = 'Organization Settings';

    protected static ?string $title = 'Organization Settings';

    protected static ?int $navigationSort = 100;

    protected ?string $heading = 'Organization Settings';
    
    protected static ?string $slug = 'organization-settings';

    // Configure this as a tenant page
    protected static bool $isTenantPage = true;
    
    public static function canAccess(): bool
    {
        // Only allow access to admin users within the tenant
        if (!Filament::getTenant()) {
            return false;
        }
        
        return auth()->user()->hasRole(['admin', 'super_admin'], Filament::getTenant());
    }

    public function table(Table $table): Table
    {
        $currentTenant = Filament::getTenant();
        
        // We're just displaying the current tenant details
        return $table
            ->query(
                Tenant::query()->where('id', $currentTenant->id)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Organization Name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Organization ID')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                // Edit action for current tenant
                EditAction::make()
                    ->label('Update Settings')
                    ->form([
                        TextInput::make('name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255),
                    ]),
            ])
            // Remove header actions as we don't want to create new tenants from here
            ->headerActions([]);
    }
    
    public function getSubNavigation(): array
    {
        return [];
    }
} 