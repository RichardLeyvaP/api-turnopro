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
        $excludedEmails = [
            'evylabrada@gmail.com',
            'Evelyn@klint.cl',
            'Deylert@klint.cl',
            'deylert89@gmail.com',
            'yasmany891230@gmail.com',
            'evelyn@klint.cl',
            'administracion@klint.cl'
        ];

        $professional = collect($professional)
        ->filter(function ($email) use ($excludedEmails) {
            // Validar que sea un email válido y no esté en la lista de excluidos
            return filter_var($email, FILTER_VALIDATE_EMAIL) && 
                !in_array($email, $excludedEmails);
        })
        ->unique() // Eliminar duplicados
        ->values() // Reindexar keys
        ->all();   // Convertir a array
        // Verificar si el nuevo valor es menor que 5 y registrar un log
        if ($productstore->product_exit <= $productstore->stock_depletion) {
            foreach ($professional as $email) {
                try {
                    $sendEmailService->emailStockDepletion($email, $product, $store, $branches, $productstore->product_exit);
                } catch (\Swift_TransportException $e) {
                    
                } catch (\Exception $e) {
                    
                }
            }
        }
        } catch (\Exception $e) {
            // Capturar cualquier error que ocurra durante el proceso
           
        }
    }
}