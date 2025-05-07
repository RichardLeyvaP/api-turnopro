<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($product) {
            if ($product->isForceDeleting()) {
                // Eliminación permanente
                ProductStore::where('product_id', $product->id)
                    ->forceDelete();
            } else {
                // Eliminación lógica
                ProductStore::where('product_id', $product->id)
                    ->delete();
            }
        });

        /*static::restoring(function ($service) {
            // Restauración en cascada
            BranchService::withTrashed()
                ->where('service_id', $service->id)
                ->restore();
        });*/
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function stores(){
        return $this->belongsToMany(Store::class, 'product_store')->withPivot('product_quantity','product_exit','number_notification', 'stock_depletion','id')->withTimestamps();
    }

    public function orders(){
        return $this->hasManyThrough(Order::class, ProductStore::class);
    }

    public function productSales(){
        return $this->hasManyThrough(ProductSale::class, ProductStore::class);
    }

    public function cashiersales(){
        return $this->hasManyThrough(CashierSale::class, ProductStore::class);
    }

    public function productStores(){
        return $this->hasMany(ProductStore::class);
    }

    public function workerPurchases()
    {
        return $this->hasMany(WorkerPurchase::class);
    }
    
    protected $casts = [
        'purchase_price' => 'double',
        'sale_price' => 'double'
    ];
}
