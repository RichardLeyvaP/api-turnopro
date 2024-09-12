<?php
namespace App\Traits;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Store;
use App\Services\SendEmailService; // Asegúrate de importar tu servicio correctamente
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait ProductExitTrait
{
    /*protected $sendEmailService;
      // Inyección del servicio en el constructor
      public function __construct(SendEmailService $sendEmailService)
      {
          $this->sendEmailService = $sendEmailService;
      }*/

    public function actualizarProductExit($productstore, $branch)
    {
        try {
             // Obtener el servicio desde el contenedor de Laravel
             $sendEmailService = App::make(SendEmailService::class);
            $product = Product::findOrFail($productstore->product_id);
            $store = Store::findOrFail($productstore->store_id);

        // Actualizar el campo product_exit utilizando la relación
        //$productstoreexist = $store->products()->wherePivot('product_id', $product->id)->first()->pivot;
        Log::info("llamando a actualizarProductExit");
        //Log::info($productstoreexist);
        //$branch = $productstore->stores()->values('branch_id');
        if ($branch == 0) {
            $branches = [];
            $professional = Professional::WhereHas('charge', function ($query) {
                $query->where('name', 'Administrador');
            })
            ->get()->pluck('email')->toArray();
        }else {
            $branches = Branch::findOrFail($branch);
            $professional = Professional::whereHas('branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch);
            })
            ->whereHas('charge', function ($query) {
                $query->Where('name', 'Encargado')
                      ->orWhere('name', 'Administrador de Sucursal');
            })
            ->orWhereHas('charge', function ($query) {
                $query->where('name', 'Administrador');
            })
            ->get()->pluck('email')->toArray();
        }        
        Log::info('Producto agotandose Almacen-Producto :', ['productStore' => $productstore]);
        Log::info('Comparacion de existencai con stock :',[$productstore->product_exit <= $productstore->stock_depletion]);
        // Verificar si el nuevo valor es menor que 5 y registrar un log
        if ($productstore->product_exit <= $productstore->stock_depletion) {
            Log::info('Producto agotandose Almacen-Producto Existencia :', ['existencia' => $productstore->product_exit]);
            Log::info('Producto agotandose Product:', ['product' => $product]);
            Log::info('Producto agotandose Almacen :', ['store' => $store]);
            Log::info('Producto agotandose Branches :', ['branch' => $branches]);
            // Puedes agregar aquí cualquier otra acción que necesites realizar
            $professional = ['yasmaasASasny891230@gmail.com'];
            foreach ($professional as $email) {
                try {
                    $sendEmailService->emailStockDepletion($email, $product, $store, $branches, $productstore->product_exit);
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }
        }
        } catch (\Exception $e) {
            // Capturar cualquier error que ocurra durante el proceso
            Log::error('Error en la función actualizarProductExit: ' . $e->getMessage());
        }
    }
}