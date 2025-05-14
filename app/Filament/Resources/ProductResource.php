<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';
    
    // Define the tenant relationship name
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product Information')
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule) {
                                    $tenantIdToCompare = null;
                                    if (function_exists('tenant') && tenant()) {
                                        $tenantIdToCompare = tenant()->id;
                                    } elseif (\Filament\Facades\Filament::getTenant()) {
                                        $tenantIdToCompare = \Filament\Facades\Filament::getTenant()->id;
                                    }
                                    // Ensure that a tenant_id is actually found. If not, this rule might not work as expected
                                    // or it could lead to checking uniqueness where tenant_id is null.
                                    // For robust tenant-scoped uniqueness, $tenantIdToCompare should not be null.
                                    // If $tenantIdToCompare can be null and that's an invalid state for this rule,
                                    // you might need to conditionally apply the rule or throw an exception.
                                    return $rule->where('tenant_id', $tenantIdToCompare);
                                }
                            )
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(function () {
                                // Safely get tenant ID
                                $tenantId = null;
                                
                                if (function_exists('tenant') && tenant()) {
                                    $tenantId = tenant()->id;
                                } elseif (\Filament\Facades\Filament::getTenant()) {
                                    $tenantId = \Filament\Facades\Filament::getTenant()->id;
                                }
                                
                                return ProductCategory::query()
                                    ->when($tenantId, function ($query) use ($tenantId) {
                                        return $query->where('tenant_id', $tenantId);
                                    })
                                    ->where('active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                            
                        Forms\Components\Textarea::make('description')
                            ->columnSpan([
                                'sm' => 1,
                                'md' => 2,
                            ])
                            ->rows(3),
                    ]),
                    
                Section::make('Pricing & Stock')
                    ->columns([
                        'sm' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01),
                            
                        Forms\Components\TextInput::make('weight')
                            ->label('Unit Weight (g)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('g'),
                            
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Current Stock (g)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('g'),
                            
                        Forms\Components\TextInput::make('minimum_stock')
                            ->label('Minimum Stock (g)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('g')
                            ->default(5),
                    ]),
                    
                Section::make('Attributes')
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label('Product is active')
                            ->default(true),
                            
                        Forms\Components\KeyValue::make('attributes')
                            ->label('Product Attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Attribute')
                            ->reorderable()
                            ->columnSpan([
                                'sm' => 1,
                                'md' => 2,
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->money('EUR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock (g)')
                    ->badge()
                    ->color(fn ($record): string => 
                        $record->current_stock <= $record->minimum_stock 
                            ? 'danger' 
                            : ($record->current_stock <= $record->minimum_stock * 1.5 
                                ? 'warning' 
                                : 'success'
                            )
                    )
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                    
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('All Products')
                    ->trueLabel('Active Products')
                    ->falseLabel('Inactive Products'),
                    
                Tables\Filters\TernaryFilter::make('stock_status')
                    ->label('Stock Status')
                    ->placeholder('All Products')
                    ->queries(
                        true: fn (Builder $query) => $query->whereColumn('current_stock', '<=', 'minimum_stock'),
                        false: fn (Builder $query) => $query->whereColumn('current_stock', '>', 'minimum_stock'),
                    )
                    ->trueLabel('Low Stock')
                    ->falseLabel('Sufficient Stock'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggleActive')
                        ->label('Toggle Active Status')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->active = !$record->active;
                                $record->save();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            // Use a query compatible with both SQLite and PostgreSQL
            return static::getModel()::query()
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->count() ?: null;
        } catch (\Exception $e) {
            // Return null if there's an error
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        try {
            // Use a query compatible with both SQLite and PostgreSQL
            return static::getModel()::query()
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->count() ? 'danger' : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
