<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubSettingResource\Pages;
use App\Filament\Resources\ClubSettingResource\RelationManagers;
use App\Models\ClubSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Facades\Filament;

class ClubSettingResource extends Resource
{
    protected static ?string $model = ClubSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = 'Club Management';
    
    protected static ?string $navigationLabel = 'Custom Settings';
    
    protected static ?int $navigationSort = 20;
    
    // Define the tenant relationship name
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Setting Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('setting_group')
                                    ->label('Setting Group')
                                    ->options([
                                        'branding' => 'Branding',
                                        'pricing' => 'Pricing Rules',
                                        'inventory' => 'Inventory Rules',
                                    ])
                                    ->required(),
                                
                                TextInput::make('setting_key')
                                    ->label('Setting Key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Unique identifier for this setting'),
                            ]),
                        
                        Textarea::make('description')
                            ->label('Description')
                            ->helperText('Optional description for this setting'),
                            
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                            
                        KeyValue::make('setting_value')
                            ->label('Setting Values')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->addable()
                            ->reorderable()
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('setting_key')
                    ->label('Setting Key')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('setting_group')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'branding' => 'info',
                        'pricing' => 'success',
                        'inventory' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('setting_group')
                    ->options([
                        'branding' => 'Branding',
                        'pricing' => 'Pricing Rules',
                        'inventory' => 'Inventory Rules',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubSettings::route('/'),
            'create' => Pages\CreateClubSetting::route('/create'),
            'edit' => Pages\EditClubSetting::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        // Scope to current tenant
        return parent::getEloquentQuery()
            ->when(Filament::getTenant(), fn (Builder $query, $tenant) => 
                $query->where('tenant_id', $tenant->id)
            );
    }
}
