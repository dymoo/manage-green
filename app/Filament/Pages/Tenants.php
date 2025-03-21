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

class Tenants extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.pages.tenants';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $title = 'Tenants';

    protected static ?int $navigationSort = -1;

    protected ?string $heading = 'Manage Tenants';
    
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
            ])
            ->actions([
                EditAction::make()
                    ->form([
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
                            ->unique(ignoreRecord: true),
                    ]),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form([
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
                            ->unique(),
                    ])
                    ->using(function (array $data): Tenant {
                        $tenant = Tenant::create($data);
                        $tenant->users()->attach(auth()->user());
                        
                        return $tenant;
                    }),
            ]);
    }
} 