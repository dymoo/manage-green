<?php

namespace App\Filament\Pages;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointOfSale extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationLabel = 'Point of Sale';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.point-of-sale';
    
    // Form data
    public $member = null;
    public $payment_method = 'cash';
    public $notes = '';
    public $items = [];
    
    // Computed properties
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;
    
    public function mount(): void
    {
        $this->form->fill();
        
        // Initialize with one empty item
        $this->items = [
            [
                'product_id' => null,
                'product_name' => '',
                'price' => 0,
                'quantity' => 0,
                'subtotal' => 0,
            ]
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Details')
                    ->schema([
                        Select::make('member')
                            ->label('Member')
                            ->options(function () {
                                return User::where('tenant_id', tenant()->id)
                                    ->whereHas('roles', function ($query) {
                                        $query->where('name', 'member');
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Card',
                                'other' => 'Other',
                            ])
                            ->default('cash')
                            ->required(),
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->columns(2),
                
                Section::make('Products')
                    ->schema([
                        Repeater::make('items')
                            ->schema([
                                Select::make('product_id')
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
                                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('price', $product->price);
                                                $set('product_name', $product->name);
                                                
                                                // Recalculate subtotal
                                                $quantity = $livewire->items[$livewire->repeaterItemIndices['items']]['quantity'] ?? 0;
                                                if ($quantity > 0) {
                                                    $set('subtotal', round($quantity * $product->price, 2));
                                                }
                                                
                                                $livewire->calculateTotals();
                                            }
                                        }
                                    }),
                                
                                TextInput::make('product_name')
                                    ->label('Product Name')
                                    ->required()
                                    ->disabled(),
                                
                                TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('€')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $get, $livewire) {
                                        $quantity = $get('quantity');
                                        if ($state && $quantity) {
                                            $set('subtotal', round($state * $quantity, 2));
                                        }
                                        
                                        $livewire->calculateTotals();
                                    }),
                                
                                TextInput::make('quantity')
                                    ->label('Quantity (g)')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $get, $livewire) {
                                        $price = $get('price');
                                        if ($state && $price) {
                                            $set('subtotal', round($state * $price, 2));
                                        }
                                        
                                        $livewire->calculateTotals();
                                    }),
                                
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('€')
                                    ->disabled()
                                    ->reactive(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Product')
                            ->required()
                            ->minItems(1)
                            ->itemLabel(function (array $state): ?string {
                                return $state['product_name'] ?? 'New Product';
                            }),
                    ]),
                    
                Section::make('Order Summary')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->prefix('€')
                            ->disabled(),
                            
                        TextInput::make('tax')
                            ->label('Tax')
                            ->prefix('€')
                            ->disabled(),
                            
                        TextInput::make('total')
                            ->label('Total')
                            ->prefix('€')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }
    
    public function calculateTotals(): void
    {
        $subtotal = 0;
        
        foreach ($this->items as $item) {
            if (isset($item['subtotal'])) {
                $subtotal += floatval($item['subtotal']);
            }
        }
        
        $this->subtotal = round($subtotal, 2);
        $this->tax = 0; // You can implement tax calculation if needed
        $this->total = round($subtotal + $this->tax, 2);
    }
    
    public function createOrder(): void
    {
        $this->validate([
            'member' => 'required',
            'payment_method' => 'required',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'tenant_id' => tenant()->id,
                'user_id' => $this->member,
                'staff_id' => Auth::id(),
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'total' => $this->total,
                'payment_method' => $this->payment_method,
                'status' => 'completed',
                'notes' => $this->notes,
                'completed_at' => now(),
            ]);
            
            // Create order items and handle inventory
            foreach ($this->items as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }
                
                // Create order item
                $orderItem = $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
                
                // Update product stock
                $product = Product::find($item['product_id']);
                
                if ($product) {
                    $stockBefore = $product->current_stock;
                    $stockAfter = $stockBefore - $item['quantity'];
                    
                    $product->update([
                        'current_stock' => $stockAfter
                    ]);
                    
                    // Create inventory transaction
                    InventoryTransaction::create([
                        'tenant_id' => tenant()->id,
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'staff_id' => Auth::id(),
                        'quantity' => -$item['quantity'], // Negative for outgoing inventory
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'type' => 'sale',
                        'reference' => "Order #{$order->order_number}",
                    ]);
                }
            }
            
            DB::commit();
            
            // Reset form
            $this->form->fill();
            $this->mount();
            
            // Show success notification
            Notification::make()
                ->title('Order created successfully')
                ->success()
                ->body("Order #{$order->order_number} has been created and inventory updated")
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error creating order')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }
} 