<?php
namespace App\Traits;

use App\Models\Product;
use App\Models\Professional;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

trait ProductExitTrait
{
    public function actualizarProductExit($productId, $storeId)
    {
        $product = Product::findOrFail($productId);
        $store = Store::findOrFail($storeId);

        // Actualizar el campo product_exit utilizando la relación
        $productstoreexist = $store->products()->wherePivot('product_id', $product->id)->first()->pivot;
        Log::info("sddsfsdfsdfsdfsdfsdf");
        Log::info($productstoreexist);
        $branch = $store->branches()->value('branches.id');
        $professional = Professional::whereHas('branches', function ($query) use ($branch){
            $query->whereIn('branch_id', [$branch]);
          })->whereIn('charge_id', [1,6])->get()->pluck('email')->toArray();
        // Verificar si el nuevo valor es menor que 5 y registrar un log
        if ($productstoreexist->product_exit < 5) {
            Log::info('Producto con product_exit menor que 5:', ['product' => $product, 'store' => $store, 'product_exit' => $productstoreexist->product_exit, 'Professionals_Emails[]' => $professional,'branches[id]' => $branch]);
            // Puedes agregar aquí cualquier otra acción que necesites realizar
        }
    }
}