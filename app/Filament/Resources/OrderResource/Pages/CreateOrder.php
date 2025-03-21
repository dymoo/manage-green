<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Calculate totals from items
            $subtotal = 0;
            
            // Get items data before unsetting them from the data array
            $items = $data['items'] ?? [];
            unset($data['items']);
            
            // Calculate subtotal from items
            foreach ($items as $item) {
                $subtotal += $item['subtotal'];
            }
            
            // Set calculated values
            $data['subtotal'] = $subtotal;
            $data['tax'] = 0; // Tax can be calculated if needed
            $data['total'] = $subtotal;
            $data['staff_id'] = Auth::id();
            $data['tenant_id'] = tenant()->id;
            
            // Create order
            $record = static::getModel()::create($data);
            
            // Create order items and handle inventory
            foreach ($items as $item) {
                // Create order item
                $orderItem = $record->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
                
                // Update product stock
                $product = Product::find($item['product_id']);
                $stockBefore = $product->current_stock;
                $stockAfter = $stockBefore - $item['quantity'];
                
                $product->update([
                    'current_stock' => $stockAfter
                ]);
                
                // Create inventory transaction
                InventoryTransaction::create([
                    'tenant_id' => tenant()->id,
                    'product_id' => $product->id,
                    'order_id' => $record->id,
                    'staff_id' => Auth::id(),
                    'quantity' => -$item['quantity'], // Negative for outgoing inventory
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'type' => 'sale',
                    'reference' => "Order #{$record->order_number}",
                ]);
            }
            
            return $record;
        });
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Order created successfully';
    }
}
