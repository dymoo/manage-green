<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryTransactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Transaction Type')
                    ->options([
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'adjustment' => 'Stock Adjustment',
                        'return' => 'Return',
                        'waste' => 'Waste',
                    ])
                    ->required(),
                
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity (g)')
                    ->numeric()
                    ->step(0.001)
                    ->required()
                    ->suffix('g'),
                
                Forms\Components\Select::make('staff_id')
                    ->label('Staff Member')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->required(),
                
                Forms\Components\TextInput::make('reference')
                    ->label('Reference')
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'sale' => 'primary',
                        'adjustment' => 'warning',
                        'return' => 'info',
                        'waste' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->suffix('g')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('stock_before')
                    ->label('Stock Before')
                    ->suffix('g'),
                
                Tables\Columns\TextColumn::make('stock_after')
                    ->label('Stock After')
                    ->suffix('g'),
                
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff Member')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'adjustment' => 'Stock Adjustment',
                        'return' => 'Return',
                        'waste' => 'Waste',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (RelationManager $livewire, array $data): mixed {
                        $product = $livewire->getOwnerRecord();
                        
                        // Calculate the stock before and after
                        $stockBefore = $product->current_stock;
                        $stockChange = $data['quantity'];
                        
                        // Adjust stock change based on transaction type
                        if (in_array($data['type'], ['sale', 'waste'])) {
                            $stockChange = -abs($stockChange);
                        } else {
                            $stockChange = abs($stockChange);
                        }
                        
                        $stockAfter = $stockBefore + $stockChange;
                        
                        // Create transaction record
                        $transaction = $livewire->getRelationship()->create([
                            ...$data,
                            'tenant_id' => tenant()->id,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                        ]);
                        
                        // Update product stock
                        $product->current_stock = $stockAfter;
                        $product->save();
                        
                        return $transaction;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
} 