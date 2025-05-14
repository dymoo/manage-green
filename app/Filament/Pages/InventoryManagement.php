<?php

namespace App\Filament\Pages;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryManagement extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    
    protected static ?string $navigationLabel = 'Inventory Management';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.pages.inventory-management';
    
    // Form data
    public $check_in = [
        'product_id' => null,
        'quantity' => null,
        'notes' => '',
    ];
    
    public $check_out = [
        'product_id' => null,
        'quantity' => null,
        'notes' => '',
    ];
    
    public $adjustment = [
        'product_id' => null,
        'actual_quantity' => null,
        'notes' => '',
        'discrepancy' => null,
        'discrepancy_percent' => null,
    ];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Inventory')
                    ->tabs([
                        Tab::make('Check In')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->schema([
                                Section::make()
                                    ->description('Add new inventory to your stock')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('check_in.product_id')
                                                    ->label('Product')
                                                    ->options(function () {
                                                        return Product::forTenant(tenant())
                                                            ->active()
                                                            ->get()
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                
                                                TextInput::make('check_in.quantity')
                                                    ->label('Quantity to Add (g)')
                                                    ->numeric()
                                                    ->minValue(0.001)
                                                    ->step(0.001)
                                                    ->suffix('g')
                                                    ->required(),
                                            ]),
                                            
                                        Textarea::make('check_in.notes')
                                            ->label('Notes')
                                            ->placeholder('Supplier, batch number, purchase details, etc.')
                                            ->rows(2),
                                    ])
                                    ->columnSpan(2),
                            ]),
                            
                        Tab::make('Check Out')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->schema([
                                Section::make()
                                    ->description('Remove inventory from your stock (for non-sales purposes)')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('check_out.product_id')
                                                    ->label('Product')
                                                    ->options(function () {
                                                        return Product::forTenant(tenant())
                                                            ->active()
                                                            ->get()
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                
                                                TextInput::make('check_out.quantity')
                                                    ->label('Quantity to Remove (g)')
                                                    ->numeric()
                                                    ->minValue(0.001)
                                                    ->step(0.001)
                                                    ->suffix('g')
                                                    ->required(),
                                            ]),
                                            
                                        Textarea::make('check_out.notes')
                                            ->label('Notes')
                                            ->placeholder('Reason for removal, destination, etc.')
                                            ->rows(2),
                                    ])
                                    ->columnSpan(2),
                            ]),
                            
                        Tab::make('Stock Adjustment')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Section::make()
                                    ->description('Adjust inventory based on actual physical count')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('adjustment.product_id')
                                                    ->label('Product')
                                                    ->options(function () {
                                                        return Product::forTenant(tenant())
                                                            ->active()
                                                            ->get()
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $product = Product::find($state);
                                                            $set('adjustment.system_quantity', $product ? $product->current_stock : null);
                                                        } else {
                                                            $set('adjustment.system_quantity', null);
                                                        }
                                                    }),
                                                
                                                TextInput::make('adjustment.system_quantity')
                                                    ->label('System Quantity (g)')
                                                    ->suffix('g')
                                                    ->disabled(),
                                                
                                                TextInput::make('adjustment.actual_quantity')
                                                    ->label('Actual Quantity (g)')
                                                    ->helperText('Enter the exact weight measured during physical count')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(0.001)
                                                    ->suffix('g')
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        $systemQty = $get('adjustment.system_quantity');
                                                        
                                                        if ($state !== null && $systemQty !== null) {
                                                            $discrepancy = $state - $systemQty;
                                                            $set('adjustment.discrepancy', round($discrepancy, 3));
                                                            
                                                            if ($systemQty > 0) {
                                                                $discrepancyPercent = ($discrepancy / $systemQty) * 100;
                                                                $set('adjustment.discrepancy_percent', round($discrepancyPercent, 2));
                                                            }
                                                        }
                                                    }),
                                                
                                                TextInput::make('adjustment.discrepancy')
                                                    ->label('Discrepancy (g)')
                                                    ->suffix('g')
                                                    ->disabled()
                                                    ->numeric(),
                                                
                                                TextInput::make('adjustment.discrepancy_percent')
                                                    ->label('Discrepancy (%)')
                                                    ->suffix('%')
                                                    ->disabled()
                                                    ->numeric(),
                                            ]),
                                        
                                        Textarea::make('adjustment.notes')
                                            ->label('Notes')
                                            ->placeholder('Explanation for any discrepancies')
                                            ->rows(2)
                                            ->required(),
                                    ])
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->columnSpan(2),
            ]);
    }
    
    public function checkIn(): void
    {
        $this->validate([
            'check_in.product_id' => 'required|exists:products,id',
            'check_in.quantity' => 'required|numeric|min:0.001',
        ]);
        
        DB::beginTransaction();
        
        try {
            $productId = $this->check_in['product_id'];
            $quantity = $this->check_in['quantity'];
            $notes = $this->check_in['notes'];
            
            $product = Product::findOrFail($productId);
            $stockBefore = $product->current_stock;
            $stockAfter = $stockBefore + $quantity;
            
            // Update product stock
            $product->current_stock = $stockAfter;
            $product->save();
            
            // Create inventory transaction record
            InventoryTransaction::create([
                'tenant_id' => tenant()->id,
                'product_id' => $productId,
                'staff_id' => Auth::id(),
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'type' => 'purchase',
                'reference' => 'Check-in ' . now()->format('Y-m-d H:i'),
                'notes' => $notes,
            ]);
            
            DB::commit();
            
            Notification::make()
                ->title('Inventory added successfully')
                ->success()
                ->send();
                
            $this->reset('check_in');
            $this->form->fill();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error adding inventory')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function checkOut(): void
    {
        $this->validate([
            'check_out.product_id' => 'required|exists:products,id',
            'check_out.quantity' => 'required|numeric|min:0.001',
        ]);
        
        DB::beginTransaction();
        
        try {
            $productId = $this->check_out['product_id'];
            $quantity = $this->check_out['quantity'];
            $notes = $this->check_out['notes'];
            
            $product = Product::findOrFail($productId);
            
            // Check if there's enough stock
            if ($product->current_stock < $quantity) {
                throw new \Exception('Not enough stock available. Current stock: ' . $product->current_stock . 'g');
            }
            
            $stockBefore = $product->current_stock;
            $stockAfter = $stockBefore - $quantity;
            
            // Update product stock
            $product->current_stock = $stockAfter;
            $product->save();
            
            // Create inventory transaction record
            InventoryTransaction::create([
                'tenant_id' => tenant()->id,
                'product_id' => $productId,
                'staff_id' => Auth::id(),
                'quantity' => -$quantity, // Negative for removal
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'type' => 'adjustment',
                'reference' => 'Check-out ' . now()->format('Y-m-d H:i'),
                'notes' => $notes,
            ]);
            
            DB::commit();
            
            Notification::make()
                ->title('Inventory removed successfully')
                ->success()
                ->send();
                
            $this->reset('check_out');
            $this->form->fill();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error removing inventory')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function adjustStock(): void
    {
        $this->validate([
            'adjustment.product_id' => 'required|exists:products,id',
            'adjustment.actual_quantity' => 'required|numeric|min:0',
            'adjustment.notes' => 'required|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $productId = $this->adjustment['product_id'];
            $actualQuantity = $this->adjustment['actual_quantity'];
            $notes = $this->adjustment['notes'];
            
            $product = Product::findOrFail($productId);
            $stockBefore = $product->current_stock;
            $discrepancy = $actualQuantity - $stockBefore;
            
            // Update product stock to match actual count
            $product->current_stock = $actualQuantity;
            $product->save();
            
            // Create inventory transaction record
            InventoryTransaction::create([
                'tenant_id' => tenant()->id,
                'product_id' => $productId,
                'staff_id' => Auth::id(),
                'quantity' => $discrepancy,
                'stock_before' => $stockBefore,
                'stock_after' => $actualQuantity,
                'type' => 'adjustment',
                'reference' => 'Stock Adjustment ' . now()->format('Y-m-d H:i'),
                'notes' => "Adjusted from {$stockBefore}g to {$actualQuantity}g. Discrepancy: {$discrepancy}g. " . $notes,
            ]);
            
            DB::commit();
            
            Notification::make()
                ->title('Stock adjustment completed')
                ->success()
                ->send();
                
            $this->reset('adjustment');
            $this->form->fill();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error adjusting stock')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
} 