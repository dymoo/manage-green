<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'subtotal',
        'product_snapshot',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'subtotal' => 'decimal:2',
        'product_snapshot' => 'json',
    ];
    
    // Relationships
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    // Boot method to auto-calculate subtotal
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            if (!$item->subtotal) {
                $item->subtotal = $item->price * $item->quantity;
            }
            
            if (!$item->product_snapshot && $item->product_id) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->product_snapshot = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'sku' => $product->sku,
                        'category' => $product->category ? $product->category->name : null,
                        'attributes' => $product->attributes,
                    ];
                }
            }
        });
    }
}
