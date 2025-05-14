<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockCheckResource\Pages;
use App\Models\StockCheck;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Enums\StockCheckType;

class StockCheckResource extends Resource
{
    protected static ?string $model = StockCheck::class;

    protected static ?string $tenantOwnershipRelationshipName = null;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 20;

    public static function isScopedToTenant(): bool
    {
        return false; // Rely on our own getEloquentQuery() and Policy for tenant scoping and auth
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stock Check Details')
                    ->schema([
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => tenant()->id),
                        Forms\Components\Hidden::make('staff_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Select::make('type')
                            ->options(StockCheckType::class)
                            ->required()
                            ->default(StockCheckType::CHECK_IN),
                        Forms\Components\Textarea::make('start_notes')
                            ->label('Notes (Start of Check)')
                            ->rows(3),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('check_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_type')
                    ->formatStateUsing(fn (string $state): string => $state === 'check_in' ? 'Check In' : 'Check Out')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'check_in' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => 
                        match ($state) {
                            'pending' => 'gray',
                            'in_progress' => 'blue',
                            'completed' => 'green',
                            default => 'gray',
                        }
                    ),
                Tables\Columns\TextColumn::make('total_discrepancy')
                    ->numeric(3)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
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
                Tables\Filters\SelectFilter::make('check_type')
                    ->options([
                        'check_in' => 'Check In',
                        'check_out' => 'Check Out',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\Filter::make('check_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('continue_check')
                    ->label('Continue Stock Check')
                    ->url(fn (StockCheck $record) => StockCheckResource\Pages\StockCheckItems::getUrl(['record' => $record]))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->button()
                    ->color('primary')
                    ->visible(fn (StockCheck $record) => $record->status !== 'completed'),
                Tables\Actions\Action::make('view_check')
                    ->label('View Stock Check')
                    ->url(fn (StockCheck $record) => StockCheckResource\Pages\StockCheckItems::getUrl(['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->button()
                    ->visible(fn (StockCheck $record) => $record->status === 'completed'),
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
            'index' => Pages\ListStockChecks::route('/'),
            'create' => Pages\CreateStockCheck::route('/create'),
            'edit' => Pages\EditStockCheck::route('/{record}/edit'),
            'stock-check-items' => Pages\StockCheckItems::route('/{record}/items'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBelongsTo(tenant());
    }
} 