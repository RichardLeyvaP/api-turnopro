<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchProfessionalController;
use App\Http\Controllers\BranchRuleController;
use App\Http\Controllers\BranchRuleProfessionalController;
use App\Http\Controllers\BranchServiceController;
use App\Http\Controllers\BranchStoreController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessTypesController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientProfessionalController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProductStoreController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\BranchServiceProfessionalController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfessionalWorkPlaceController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkplaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/register_client', [UserController::class, 'register_client']);
Route::post('/register_professional', [UserController::class, 'register_professional']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/usuario', [UserController::class, 'index']);

Route::group( ['middleware' => ["auth:sanctum"]], function(){
    Route::get('profile', [UserController::class, 'userProfile']);
    Route::get('logout', [UserController::class, 'logout']);
    Route::post('change_password', [UserController::class, 'change_password']);
    Route::get('qrCode', [UserController::class, 'qrCode']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/professional', [ProfessionalController::class, 'index']);
Route::get('/professional-show', [ProfessionalController::class, 'show']);
Route::post('/professional', [ProfessionalController::class, 'store']);
Route::post('/professional-update', [ProfessionalController::class, 'update']);
Route::post('/professional-destroy', [ProfessionalController::class, 'destroy']);
Route::get('/professionals_branch', [ProfessionalController::class, 'professionals_branch']);
Route::get('/professionals_ganancias', [ProfessionalController::class, 'professionals_ganancias']);
Route::get('/branch_professionals', [ProfessionalController::class, 'branch_professionals']);
Route::get('/professionals_ganancias_branch', [ProfessionalController::class, 'professionals_ganancias_branch']); //Obtener Monto total de un professionals en una branch y un periodo dado
Route::get('/services_professional', [ProfessionalController::class, 'services_professional']);
Route::get('/get-professionals-service', [ProfessionalController::class, 'get_professionals_service']);

Route::get('/client', [ClientController::class, 'index']);
Route::get('/client-show', [ClientController::class, 'show']);
Route::post('/client', [ClientController::class, 'store']);
Route::put('/client', [ClientController::class, 'update']);
Route::post('/client-destroy', [ClientController::class, 'destroy']);
Route::get('/client_attended_date', [ClientController::class, 'client_attended_date']);

Route::get('/business', [BusinessController::class, 'index']);
Route::get('/business-show', [BusinessController::class, 'show']);
Route::post('/business', [BusinessController::class, 'store']);
Route::put('/business', [BusinessController::class, 'update']);
Route::post('/business-destroy', [BusinessController::class, 'destroy']);

Route::get('/business-type', [BusinessTypesController::class, 'index']);
Route::get('/business-type-show', [BusinessTypesController::class, 'show']);
Route::post('/business-type', [BusinessTypesController::class, 'store']);
Route::put('/business-type', [BusinessTypesController::class, 'update']);
Route::post('/business-type-destroy', [BusinessTypesController::class, 'destroy']);

Route::get('/branch', [BranchController::class, 'index']);
Route::get('/branch-show', [BranchController::class, 'show']);
Route::post('/branch', [BranchController::class, 'store']);
Route::put('/branch', [BranchController::class, 'update']);
Route::post('/branch-destroy', [BranchController::class, 'destroy']);
Route::get('/branch_winner', [BranchController::class, 'branch_winner']);//devuelve las ganancias y products mas vendido de una branch 
Route::get('/branches_professional', [BranchController::class, 'branches_professional']);
Route::get('/company_winner', [BranchController::class, 'company_winner']);//devuelve las ganancias y el products mas vendido de la compañia
Route::get('/branch_professionals_winner', [BranchController::class, 'branch_professionals_winner']);//devuelve las ganancias y products mas vendido de una branch

Route::get('/store', [StoreController::class, 'index']);
Route::get('/store-show', [StoreController::class, 'show']);
Route::post('/store', [StoreController::class, 'store']);
Route::put('/store', [StoreController::class, 'update']);
Route::post('/store-destroy', [StoreController::class, 'destroy']);

Route::get('/charge', [ChargeController::class, 'index']);
Route::get('/charge-show', [ChargeController::class, 'show']);
Route::post('/charge', [ChargeController::class, 'store']);
Route::put('/charge', [ChargeController::class, 'update']);
Route::post('/charge-destroy', [ChargeController::class, 'destroy']);

Route::get('/rule', [RuleController::class, 'index']);
Route::get('/rule-show', [RuleController::class, 'show']);
Route::post('/rule', [RuleController::class, 'store']);
Route::put('/rule', [RuleController::class, 'update']);
Route::post('/rule-destroy', [RuleController::class, 'destroy']);

Route::get('/product-category', [ProductCategoryController::class, 'index']);
Route::get('/product-category-show', [ProductCategoryController::class, 'show']);
Route::post('/product-category', [ProductCategoryController::class, 'store']);
Route::put('/product-category', [ProductCategoryController::class, 'update']);
Route::post('/product-category-destroy', [ProductCategoryController::class, 'destroy']);
Route::get('/category_branch', [ProductCategoryController::class, 'category_branch']);

Route::get('/product', [ProductController::class, 'index']);
Route::get('/product-show', [ProductController::class, 'show']);
Route::post('/product', [ProductController::class, 'store']);
Route::put('/product', [ProductController::class, 'update']);
Route::post('/product-destroy', [ProductController::class, 'destroy']);
Route::get('/product_mostSold_date', [ProductController::class, 'product_mostSold_date']);

Route::get('/service', [ServiceController::class, 'index']);
Route::get('/service-show', [ServiceController::class, 'show']);
Route::post('/service', [ServiceController::class, 'store']);
Route::put('/service', [ServiceController::class, 'update']);
Route::post('/service-destroy', [ServiceController::class, 'destroy']);

Route::get('/productstore', [ProductStoreController::class, 'index']);
Route::get('/productstore-show', [ProductStoreController::class, 'show']);
Route::post('/productstore', [ProductStoreController::class, 'store']);
Route::put('/productstore', [ProductStoreController::class, 'update']);
Route::post('/productstore-destroy', [ProductStoreController::class, 'destroy']);
Route::get('/category_products', [ProductStoreController::class, 'category_products']);
Route::post('/move_product_store', [ProductStoreController::class, 'move_product_store']);

Route::get('/branchstore', [BranchStoreController::class, 'index']);
Route::get('/branchstore-show', [BranchStoreController::class, 'show']);
Route::post('/branchstore', [BranchStoreController::class, 'store']);
Route::put('/branchstore', [BranchStoreController::class, 'update']);
Route::post('/branchstore-destroy', [BranchStoreController::class, 'destroy']);

Route::get('/branchservice', [BranchServiceController::class, 'index']);
Route::get('/branchservice-show', [BranchServiceController::class, 'show']);
Route::post('/branchservice', [BranchServiceController::class, 'store']);
Route::put('/branchservice', [BranchServiceController::class, 'update']);
Route::post('/branchservice-destroy', [BranchServiceController::class, 'destroy']);
Route::get('/show-service-idProfessional', [BranchServiceController::class, 'show_service_idProfessional']);//nueva

Route::get('/professionalservice', [BranchServiceProfessionalController::class, 'index']);
Route::get('/professionalservice-show', [BranchServiceProfessionalController::class, 'show']);
Route::post('/professionalservice', [BranchServiceProfessionalController::class, 'store']);
Route::put('/professionalservice', [BranchServiceProfessionalController::class, 'update']);
Route::post('/professionalservice-destroy', [BranchServiceProfessionalController::class, 'destroy']);
Route::get('/professional_services', [BranchServiceProfessionalController::class, 'professional_services']);

Route::get('/clientprofessional', [ClientProfessionalController::class, 'index']);
Route::get('/clientprofessional-show', [ClientProfessionalController::class, 'show']);
Route::post('/clientprofessional', [ClientProfessionalController::class, 'store']);
Route::put('/clientprofessional', [ClientProfessionalController::class, 'update']);
Route::post('/clientprofessional-destroy', [ClientProfessionalController::class, 'destroy']);

Route::get('/car', [CarController::class, 'index']);
Route::get('/car-show', [CarController::class, 'show']);
Route::post('/car', [CarController::class, 'store']);
Route::put('/car', [CarController::class, 'update']);
Route::post('/car-destroy', [CarController::class, 'destroy']);
Route::get('/car_orders', [CarController::class, 'car_orders']);
Route::get('/car_order_delete_professional', [CarController::class, 'car_order_delete_professional']);//dado una branch y un professional devolver las ordenes solicitadas a eliminar en la fecha actual
Route::get('/car_order_delete_branch', [CarController::class, 'car_order_delete_branch']);//dado una branch devolver las ordenes solicitadas a eliminar en la fecha actual
Route::put('/car-give-tips', [CarController::class, 'give_tips']);
Route::get('/reservation_services', [CarController::class, 'reservation_services']);
Route::get('/car_services', [CarController::class, 'car_services']); //dado una reservations devolver los servicios

Route::get('/order', [OrderController::class, 'index']);
Route::get('/order-show', [OrderController::class, 'show']);
Route::post('/order', [OrderController::class, 'store']);
Route::put('/order', [OrderController::class, 'update']);
Route::post('/order-destroy', [OrderController::class, 'destroy']);
Route::get('/sales_periodo_branch', [OrderController::class, 'sales_periodo_branch']);

Route::get('/reservation', [ReservationController::class, 'index']);
Route::get('/reservation-show', [ReservationController::class, 'show']);
Route::post('/reservation', [ReservationController::class, 'store']);
Route::put('/reservation', [ReservationController::class, 'update']);
Route::post('/reservation-destroy', [ReservationController::class, 'destroy']);
Route::get('/reservation_tail', [ReservationController::class, 'reservation_tail']);
Route::get('/professional_reservations', [ReservationController::class, 'professional_reservations']);
Route::get('/professional_reservationDate', [ReservationController::class, 'professional_reservationDate']);
Route::post('/reservation_store', [ReservationController::class, 'reservation_store']);//Hacer una reservation en una fecha dada
Route::get('/client-history', [ReservationController::class, 'client_history']);//Dado una branch y un cliente devolver el historico de este cliente en esta branch

Route::get('/tail', [TailController::class, 'index']);
Route::put('/tail', [TailController::class, 'update']);
Route::get('/tail_up', [TailController::class, 'tail_up']);
Route::get('/cola_branch_data', [TailController::class, 'cola_branch_data']); //dado un branch_id devolver la cola de esa branch
Route::get('/cola_branch_professional', [TailController::class, 'cola_branch_professional']); //dado un branch_id  y un professional_id devolver la cola de esa branch
Route::get('/cola_truncate', [TailController::class, 'cola_truncate']); //vaciar la cola
Route::get('/cola_branch_delete', [TailController::class, 'cola_branch_delete']); //vaciar la cola de una branch_id
Route::get('/tail_attended', [TailController::class, 'tail_attended']); //cambiar estado de cliente, en espera,atendiendo,atendido,rechazado
Route::get('/type_of_service', [TailController::class, 'type_of_service']); //Saber si dentro del cliente q esta atendido uno de los servicios es simutaneu
Route::get('/return_client_status', [TailController::class, 'return_client_status']);//devuelve el estado de la reservacion
Route::get('/cola_branch_capilar', [TailController::class, 'cola_branch_capilar']); //dado un branch_id devolver la cola a atencion capilar de esa branch
Route::get('/cola_branch_tecnico', [TailController::class, 'cola_branch_tecnico']); //dado un branch_id devolver la cola a atencion capilar de un tecnico
Route::get('/set_clock', [TailController::class, 'set_clock']); //dado una id de la reservacion modificar el estado del clock
Route::get('/get_clock', [TailController::class, 'get_clock']); //dado una id de la reservacion devolver el estado del clock
Route::get('/set_timeClock', [TailController::class, 'set_timeClock']); //dado una id de la reservacion guardar el tiempo del reloj y el estado
Route::get('/tail_branch_attended', [TailController::class, 'tail_branch_attended']); //dado una id de la branch Mostrar los clientes que estan siendo atendidos (attended [1:por el barbero, 2:por el tecnico capilar])
Route::get('/reasigned_client', [TailController::class, 'reasigned_client']); //dado una dado una reservatio, un cliente y un professional, reasignar este cliente al professional)

Route::get('/workplace', [WorkplaceController::class, 'index']);
Route::get('/workplace-show', [WorkplaceController::class, 'show']);
Route::post('/workplace', [WorkplaceController::class, 'store']);
Route::put('/workplace', [WorkplaceController::class, 'update']);
Route::post('/workplace-destroy', [WorkplaceController::class, 'destroy']);
Route::get('/branch_workplaces_busy', [WorkplaceController::class, 'branch_workplaces_busy']);//mostrar los pestos de trabajo disponibles al trabajador
Route::get('/branch_workplaces_select', [WorkplaceController::class, 'branch_workplaces_select']);//mostrar los pestos de trabajo disponibles para atender al tecnico

Route::get('/schedule', [ScheduleController::class, 'index']);
Route::get('/schedule-show', [ScheduleController::class, 'show']);
Route::post('/schedule', [ScheduleController::class, 'store']);
Route::put('/schedule', [ScheduleController::class, 'update']);
Route::post('/schedule-destroy', [ScheduleController::class, 'destroy']);
Route::get('/show_schedule_branch', [ScheduleController::class, 'show_schedule_branch']);

Route::get('/branchrule', [BranchRuleController::class, 'index']);
Route::get('/branchrule-show', [BranchRuleController::class, 'show']);
Route::post('/branchrule', [BranchRuleController::class, 'store']);
Route::put('/branchrule', [BranchRuleController::class, 'update']);
Route::post('/branchrule-destroy', [BranchRuleController::class, 'destroy']);
Route::get('/branch_rules', [BranchRuleController::class, 'branch_rules']);//dado una branch, devuelve las rules definidas

Route::get('/branchruleprofessional', [BranchRuleProfessionalController::class, 'index']);
Route::get('/branchruleprofessional-show', [BranchRuleProfessionalController::class, 'show']);
Route::post('/branchruleprofessional', [BranchRuleProfessionalController::class, 'store']);
Route::put('/branchruleprofessional', [BranchRuleProfessionalController::class, 'update']);
Route::post('/branchruleprofessional-destroy', [BranchRuleProfessionalController::class, 'destroy']);
Route::post('/storeByType', [BranchRuleProfessionalController::class, 'storeByType']);//registrar convivencia x el tipo de rule
Route::get('/rules_professional', [BranchRuleProfessionalController::class, 'rules_professional']);//ver el estado de las rules de un professional en una branch de una fecha dada o del dia actual

Route::get('/branchprofessional', [BranchProfessionalController::class, 'index']);
Route::get('/branchprofessional-show', [BranchProfessionalController::class, 'show']);
Route::post('/branchprofessional', [BranchProfessionalController::class, 'store']);
Route::put('/branchprofessional', [BranchProfessionalController::class, 'update']);
Route::post('/branchprofessional-destroy', [BranchProfessionalController::class, 'destroy']);

Route::get('/professionalworkplace', [ProfessionalWorkPlaceController::class, 'index']);
Route::get('/professionalworkplace-show', [ProfessionalWorkPlaceController::class, 'show']);
Route::post('/professionalworkplace', [ProfessionalWorkPlaceController::class, 'store']);
Route::put('/professionalworkplace', [ProfessionalWorkPlaceController::class, 'update']);
Route::post('/professionalworkplace-destroy', [ProfessionalWorkPlaceController::class, 'destroy']);

Route::get('/comment', [CommentController::class, 'index']);
Route::get('/comment-show', [CommentController::class, 'show']);
Route::post('/comment', [CommentController::class, 'store']);
Route::put('/comment', [CommentController::class, 'update']);
Route::post('/comment-destroy', [CommentController::class, 'destroy']);
Route::post('/storeByReservationId', [CommentController::class, 'storeByReservationId']);

Route::get('/send_email', [ReservationController::class, 'send_email']);

Route::get('/images/{foldername}/{filename}', function ($foldername, $filename) {
    $path = storage_path("app/public/{$foldername}/{$filename}");

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = new Response($file, 200);
    $response->header("Content-Type", $type);

    return $response;
})->where(['folder' => 'professionals|clients|comments|products|services|coordinator|product', 'filename' => '.*']);



