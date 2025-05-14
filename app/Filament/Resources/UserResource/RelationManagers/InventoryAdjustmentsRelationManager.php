<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryAdjustmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryAdjustments';

    public function form(Form $form): Form
    {
        // Adjustments are typically read-only in this context
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('created_at') // Or another suitable attribute
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Discrepancy (g)')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')), // Positive discrepancy might be surplus, negative is loss
                Tables\Columns\TextColumn::make('stock_before')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stock_after')
                    ->label('Stock After Adjustment')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference') // Link to Stock Check? 
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                // Add date filters if needed
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(), // Disable creation from here
            ])
            ->actions([
                // Tables\Actions\EditAction::make(), // Disable editing from here
                // Tables\Actions\DeleteAction::make(), // Disable deletion from here
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
