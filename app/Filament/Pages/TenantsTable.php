<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantsTable extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.pages.tenants';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $title = 'Tenants';

    protected static ?int $navigationSort = 2;
    
    protected static ?string $slug = 'tenants';

    protected static string $routeAlias = 'tenants';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->whereRelation('users', 'user_id', auth()->id())
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
} 