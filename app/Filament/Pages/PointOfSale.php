<?php

namespace App\Filament\Pages;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
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
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

class PointOfSale extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationLabel = 'Point of Sale';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.point-of-sale';
    
    // Form data
    public $member = null;
    public $payment_method = 'wallet';
    public $notes = '';
    public $items = [];
    
    // Computed properties
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;
    
    // Property to hold selected member data or ID
    public ?int $selected_member_id = null;
    public ?User $selected_member = null;
    
    public static function canAccess(array $parameters = []): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        return auth()->user()->hasRole(['admin', 'staff'], $tenant);
    }
    
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
                        Select::make('selected_member_id')
                            ->label('Select Member')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selected_member = User::find($state);
                            })
                            ->placeholder('Search by name or ID...')
                            ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} (ID: {$record->id})")
                            ->getSearchResultsUsing(fn (string $search) => 
                                User::where('name', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%")
                                    ->orWhere('fob_id', 'like', "%{$search}%")
                                    ->forTenant(tenant())
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (User $user) {
                                        return [$user->id => "{$user->name} (ID: {$user->id}, FOB: {$user->fob_id})"];
                                    })
                            ),
                        
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'wallet' => 'Wallet',
                                'cash' => 'Cash',
                                'card' => 'Card',
                                'other' => 'Other',
                            ])
                            ->default('wallet')
                            ->live()
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
            'selected_member_id' => 'required',
            'payment_method' => 'required',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            $member = User::find($this->selected_member_id);
            if (!$member) {
                throw new \Exception("Member not found.");
            }

            $order_total = $this->total; // Calculated earlier

            // Handle wallet payment
            if ($this->payment_method === 'wallet') {
                // Get or create wallet for the member in the current tenant
                $wallet = $member->wallet()->firstOrCreate(
                    [
                        'tenant_id' => tenant()->id,
                        'user_id' => $member->id,
                    ],
                    ['balance' => 0] // Default balance if creating new
                );

                if (!$wallet->canAfford($order_total)) {
                    throw new \Exception("Insufficient wallet balance. Member has {$wallet->balance}, needs {$order_total}.");
                }
            }

            // Create order
            $order = Order::create([
                'tenant_id' => tenant()->id,
                'user_id' => $this->selected_member_id,
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
                
                // Find the product within the current tenant, locked for update
                $product = Product::forTenant(tenant())->lockForUpdate()->find($item['product_id']);
                
                if (!$product) {
                    // Throw an exception or handle the case where the product isn't found for this tenant
                    throw new \Exception("Product with ID {$item['product_id']} not found for this tenant.");
                }

                $required_quantity = $item['quantity'];

                // Check for sufficient stock
                if ($product->current_stock < $required_quantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$product->current_stock}g, Required: {$required_quantity}g");
                }

                // Create order item
                $orderItem = $order->items()->create([
                    'product_id' => $product->id, // Use $product->id to be sure
                    'product_name' => $product->name, // Use $product->name for consistency
                    'price' => $item['price'], // Assuming price is validated/correct from form
                    'quantity' => $required_quantity,
                    'subtotal' => $item['subtotal'], // Assuming subtotal is validated/correct from form
                ]);
                
                // Update product stock
                $stockBefore = $product->current_stock;
                $stockAfter = $stockBefore - $required_quantity;
                
                $product->update([
                    'current_stock' => $stockAfter
                ]);
                
                // Create inventory transaction
                InventoryTransaction::create([
                    'tenant_id' => tenant()->id,
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'staff_id' => Auth::id(),
                    'quantity' => -$required_quantity, // Negative for outgoing inventory
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'type' => 'sale',
                    'reference' => "Order #{$order->order_number}",
                ]);
            }

            // Process wallet payment after order and items are successfully created
            if ($this->payment_method === 'wallet' && isset($wallet)) {
                // The withdraw method on Wallet model now handles creating the WalletTransaction with a specific type
                $wallet->withdraw($order_total, [
                    'type' => 'purchase', // Specify the transaction type
                    'order_id' => $order->id,
                    'staff_id' => Auth::id(),
                    'reference' => "Purchase for Order #{$order->order_number}",
                    'notes' => "Order payment via POS",
                ]);

                // Re-fetch the order to update its payment status if necessary or add wallet transaction details
                // For now, Order status is 'completed' and payment_method is 'wallet'.
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
            // For debugging in tests, you might want to log or dump the exception:
            // logger()->error("POS Create Order Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // throw $e; // Comment out for normal testing
        }
    }
} 