<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product'),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('EUR'),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity (g)')
                    ->numeric(decimals: 3),
                    
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('EUR'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No add action needed, items created with order
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }
} 