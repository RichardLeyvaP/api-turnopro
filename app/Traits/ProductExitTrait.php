<?php
namespace App\Traits;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Store;
use App\Services\SendEmailService; // Asegúrate de importar tu servicio correctamente
use Illuminate\Support\Facades\Log;

trait ProductExitTrait
{
    protected $sendEmailService;
      // Inyección del servicio en el constructor
      public function __construct(SendEmailService $sendEmailService)
      {
          $this->sendEmailService = $sendEmailService;
      }

    public function actualizarProductExit($productId, $storeId)
    {
        try {
        $product = Product::findOrFail($productId);
        $store = Store::findOrFail($storeId);

        // Actualizar el campo product_exit utilizando la relación
        $productstoreexist = $store->products()->wherePivot('product_id', $product->id)->first()->pivot;
        Log::info("llamando a actualizarProductExit ($productId, $storeId)");
        Log::info($productstoreexist);
        $branch = $store->branches()->value('branches.id');
        $branches = Branch::find($branch);
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
        /*$professional = Professional::whereHas('branches', function ($query) use ($branch){
            $query->where('branch_id', [$branch]);
          })->whereHas('charge', function ($query) {
            $query->where('name', 'Administrador')
                ->orWhere('name', 'Encargado')
                ->orWhere('name', 'Administrador de Sucursal');
        })/*->whereIn('charge_id', [3,4,5])*//*->get()->pluck('email')->toArray();*/
        // Verificar si el nuevo valor es menor que 5 y registrar un log
        if ($productstoreexist->product_exit < $productstoreexist->stock_depletion) {
            Log::info('Producto agotandose:', ['product' => $product, 'store' => $store, 'product_exit' => $productstoreexist->product_exit, 'Professionals_Emails[]' => $professional,'branches[id]' => $branch]);
            // Puedes agregar aquí cualquier otra acción que necesites realizar
            $professional = ['richardleyvap1991@gmail.com','yasmany891230@gmail.com'];
            foreach ($professional as $email) {
                try {
                    $this->sendEmailService->emailStockDepletion($email, $product, $store, $branches, $productstoreexist->product_exit);
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