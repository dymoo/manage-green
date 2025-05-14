<?php

namespace App\Filament\Resources\StockCheckResource\Pages;

use App\Filament\Resources\StockCheckResource;
use App\Models\Product;
use App\Models\StockCheck;
use App\Models\StockCheckItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockCheckItems extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = StockCheckResource::class;
    
    protected static ?string $title = 'Stock Check Items';
    
    protected static string $view = 'filament.resources.stock-check-resource.pages.stock-check-items';
    
    public ?StockCheck $record = null;
    
    public ?array $data = [];
    
    public function getTableQuery(): Builder
    {
        return StockCheckItem::query()->where('stock_check_id', $this->record->id);
    }
    
    public function mount(StockCheck $record): void
    {
        $this->record = $record;
        $this->data = $record->toArray();
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.current_stock')
                    ->label('Current System Stock')
                    ->numeric(decimalPlaces: 3),
                TextColumn::make('expected_quantity')
                    ->label('Expected Stock')
                    ->numeric(decimalPlaces: 3),
                TextColumn::make('actual_quantity')
                    ->label('Actual Stock')
                    ->numeric(decimalPlaces: 3),
                TextColumn::make('discrepancy')
                    ->numeric(decimalPlaces: 3)
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                TextColumn::make('notes')
                    ->limit(30),
            ])
            ->filters([
                // Add filters if needed
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('add_product')
                    ->label('Add Product')
                    ->form([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(function () {
                                // Get products that don't already have items in this stock check
                                $existingProductIds = StockCheckItem::where('stock_check_id', $this->record->id)
                                    ->pluck('product_id');
                                
                                return Product::where('tenant_id', tenant()->id)
                                    ->whereNotIn('id', $existingProductIds)
                                    ->where('active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                        TextInput::make('expected_quantity')
                            ->label('Expected Quantity')
                            ->numeric()
                            ->default(function ($get) {
                                $productId = $get('product_id');
                                if (!$productId) return null;
                                
                                $product = Product::find($productId);
                                return $product ? $product->current_stock : null;
                            })
                            ->required(),
                        TextInput::make('actual_quantity')
                            ->label('Actual Quantity')
                            ->numeric()
                            ->required(),
                        Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->action(function (array $data): void {
                        $product = Product::find($data['product_id']);
                        
                        // Create the stock check item
                        StockCheckItem::create([
                            'stock_check_id' => $this->record->id,
                            'product_id' => $data['product_id'],
                            'expected_quantity' => $data['expected_quantity'],
                            'actual_quantity' => $data['actual_quantity'],
                            'discrepancy' => $data['actual_quantity'] - $data['expected_quantity'],
                            'notes' => $data['notes'],
                        ]);
                        
                        Notification::make()
                            ->title("Product {$product->name} added to stock check")
                            ->success()
                            ->send();
                            
                        $this->refreshTable();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('expected_quantity')
                            ->label('Expected Quantity')
                            ->numeric()
                            ->disabled()
                            ->required(),
                        TextInput::make('actual_quantity')
                            ->label('Actual Counted Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->rule(static function ($get, $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $expected = $record->expected_quantity; 
                                    if (is_numeric($value) && is_numeric($expected) && (float)$value > (float)$expected) {
                                        $fail('Actual quantity cannot be greater than the expected quantity for this item.');
                                    }
                                };
                            }),
                        Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->mutateRecordDataUsing(function (array $data): array {
                        // Re-calculate discrepancy
                        $data['discrepancy'] = $data['actual_quantity'] - $data['expected_quantity'];
                        return $data;
                    })
                    ->disabled($this->record->check_out_at !== null),
                DeleteAction::make()
                    ->disabled($this->record->check_out_at !== null),
            ])
            ->bulkActions([
                // Add bulk actions if needed
            ])
            ->emptyStateHeading('No items in stock check')
            ->emptyStateDescription('Add products to this stock check to verify inventory levels.')
            ->emptyStateActions([
                \Filament\Tables\Actions\Action::make('add_all_products')
                    ->label('Add All Active Products')
                    ->color('gray')
                    ->icon('heroicon-o-plus')
                    ->action(function () {
                        // Get all active products not already in this stock check
                        $existingProductIds = StockCheckItem::where('stock_check_id', $this->record->id)
                            ->pluck('product_id');
                        
                        $products = Product::where('tenant_id', tenant()->id)
                            ->whereNotIn('id', $existingProductIds)
                            ->where('active', true)
                            ->get();
                            
                        // Begin a transaction to ensure all items are added
                        DB::beginTransaction();
                        
                        try {
                            foreach ($products as $product) {
                                StockCheckItem::create([
                                    'stock_check_id' => $this->record->id,
                                    'product_id' => $product->id,
                                    'expected_quantity' => $product->current_stock,
                                    'actual_quantity' => null, // Staff will need to enter this
                                    'discrepancy' => null,
                                    'notes' => null,
                                ]);
                            }
                            
                            DB::commit();
                            
                            Notification::make()
                                ->title(count($products) . ' products added to stock check')
                                ->success()
                                ->send();
                                
                            $this->refreshTable();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Failed to add products')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->disabled($this->record->check_out_at !== null),
            ]);
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('back_to_list')
                ->label('Back to List')
                ->url(fn () => StockCheckResource::getUrl('index'))
                ->color('secondary'),
                
            Action::make('complete_stock_check')
                ->label('Complete Stock Check')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function () {
                    // Check if all products have actual quantities
                    $incompleteItems = StockCheckItem::where('stock_check_id', $this->record->id)
                        ->whereNull('actual_quantity')
                        ->count();
                        
                    if ($incompleteItems > 0) {
                        Notification::make()
                            ->title('Cannot complete stock check')
                            ->body("There are {$incompleteItems} items that don't have actual quantities. Please fill them in before completing.")
                            ->danger()
                            ->send();
                            
                        return;
                    }
                    
                    // Calculate total weight discrepancy by summing the 'discrepancy' from items.
                    // This assumes 'expected_quantity' and 'actual_quantity' on items are in the same unit (e.g., grams).
                    $totalWeightDiscrepancy = StockCheckItem::where('stock_check_id', $this->record->id)->sum('discrepancy');
                    
                    $updateData = [
                        'total_weight_discrepancy' => $totalWeightDiscrepancy,
                        // Add total_value_discrepancy calculation if needed
                    ];

                    // If this is a check-out, also set check_out_at and checked_out_by
                    if ($this->record->type === \App\Enums\StockCheckType::CHECK_OUT) {
                        $updateData['check_out_at'] = now();
                        $updateData['checked_out_by'] = auth()->id();
                    }
                                        
                    $this->record->update($updateData);
                    
                    // If this is a check-out, update product quantities to match the actual counted values
                    if ($this->record->type === \App\Enums\StockCheckType::CHECK_OUT) {
                        $items = StockCheckItem::with('product')
                            ->where('stock_check_id', $this->record->id)
                            ->get();
                            
                        foreach ($items as $item) {
                            if ($item->product) { // Ensure product exists
                                // Update product stock to match actual quantity
                                $item->product->update([
                                    'current_stock' => $item->actual_quantity,
                                ]);
                                
                                // Create inventory transaction to record this change
                                $item->product->inventoryTransactions()->create([
                                    'tenant_id' => tenant()->id,
                                    'staff_id' => $this->record->staff_id, // staff_id is the initiator of the check
                                    'quantity' => $item->discrepancy, // This is the change amount
                                    'stock_before' => $item->expected_quantity,
                                    'stock_after' => $item->actual_quantity,
                                    'type' => 'adjustment',
                                    'reference' => 'Stock check #' . $this->record->id,
                                    'notes' => $item->notes,
                                ]);
                            }
                        }
                    }
                    
                    Notification::make()
                        ->title('Stock check completed')
                        ->success()
                        ->send();
                        
                    redirect(StockCheckResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->check_out_at === null), // Only visible if not yet checked out
                
            Action::make('view_details')
                ->label('View Details')
                ->url(fn () => StockCheckResource::getUrl('edit', ['record' => $this->record]))
                ->color('gray'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Add any necessary widgets
        ];
    }
} 