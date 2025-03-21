<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryTransactionResource\Pages;
use App\Models\InventoryTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    
    protected static ?string $navigationLabel = 'Inventory Logs';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 4;
    
    // Define the tenant relationship name
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';
    
    public static function canCreate(): bool
    {
        return false; // Disable manual creation
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->label('Reference')
                    ->disabled(),
                    
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                    
                Forms\Components\Select::make('type')
                    ->options([
                        'sale' => 'Sale',
                        'purchase' => 'Purchase',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                    ])
                    ->disabled(),
                    
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity (g)')
                    ->numeric()
                    ->disabled(),
                    
                Forms\Components\TextInput::make('stock_before')
                    ->label('Stock Before (g)')
                    ->numeric()
                    ->disabled(),
                    
                Forms\Components\TextInput::make('stock_after')
                    ->label('Stock After (g)')
                    ->numeric()
                    ->disabled(),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'danger',
                        'purchase' => 'success',
                        'return' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity (g)')
                    ->numeric(3)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('stock_before')
                    ->label('Before (g)')
                    ->numeric(3),
                    
                Tables\Columns\TextColumn::make('stock_after')
                    ->label('After (g)')
                    ->numeric(3),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'sale' => 'Sale',
                        'purchase' => 'Purchase',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                    ]),
                    
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Product'),
                    
                Tables\Filters\SelectFilter::make('staff_id')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Staff'),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInventoryTransactions::route('/'),
            'view' => Pages\ViewInventoryTransaction::route('/{record}'),
        ];
    }
} 