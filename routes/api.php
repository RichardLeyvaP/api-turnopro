<?php

use App\Http\Controllers\AssociatedController;
use App\Http\Controllers\AssociateBranchController;
use App\Http\Controllers\BoxCloseController;
use App\Http\Controllers\BoxController;
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
use App\Http\Controllers\CardGiftController;
use App\Http\Controllers\CardGiftUserController;
use App\Http\Controllers\ChargePermissionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\CourseProfessionalController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfessionalPaymentController;
use App\Http\Controllers\ProfessionalWorkPlaceController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\ClientSurveyController;
use App\Http\Controllers\TailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkplaceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EnrollmentStoreController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ProductSaleController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\RestdayController;
use App\Http\Controllers\TraceController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\OperationTipController;
use App\Http\Controllers\CashierSaleController;
use App\Models\ChargePermission;
use App\Models\CourseStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
//agregando el import de websocket
use App\Http\Controllers\TestingEventController;

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
Route::get('/testing-websocket', [TestingEventController::class, 'testingEvent']);
Route::get('/fetch-data', [TestingEventController::class, 'fetchData']);

Route::post('/register_client', [UserController::class, 'register_client']);
Route::post('/register_professional', [UserController::class, 'register_professional']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/login-phone', [UserController::class, 'login_phone']); //para l APK
Route::get('/login-phone-get-branch', [UserController::class, 'login_phone_get_branch']);//login para la apk
Route::get('/usuario', [UserController::class, 'index']);
Route::get('qrCode', [UserController::class, 'qrCode']);
Route::get('qrCode-otros', [UserController::class, 'qrCodeOtros']);
Route::get('reactive-password', [UserController::class, 'reactive_password']);
Route::get('change_password', [UserController::class, 'change_password']);

Route::group( ['middleware' => ["auth:sanctum"]], function(){
    Route::get('profile', [UserController::class, 'userProfile']);
    Route::get('logout', [UserController::class, 'logout']);
});
Route::get('/time', function () {
    //return now(); // Devuelve la hora actual.
    return Carbon::parse(now())->timezone('America/Santiago')->format('Y-m-d H:i:s');

});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/professional', [ProfessionalController::class, 'index']);
Route::get('/professional-show', [ProfessionalController::class, 'show']);
Route::get('/professional-show-apk', [ProfessionalController::class, 'show_apk']);
Route::get('/professional-show-autocomplete', [ProfessionalController::class, 'show_autocomplete']);
Route::get('/professional-show-autocomplete-Notin', [ProfessionalController::class, 'show_autocomplete_Notin']);
Route::get('/professional-show-autocomplete-branch', [ProfessionalController::class, 'show_autocomplete_branch']);//mostrar solo los professionals de una branch dada
Route::post('/professional', [ProfessionalController::class, 'store']);
Route::post('/professional-update', [ProfessionalController::class, 'update']);
Route::post('/professional-destroy', [ProfessionalController::class, 'destroy']);
Route::get('/professionals_branch', [ProfessionalController::class, 'professionals_branch']);
Route::get('/professionals_ganancias', [ProfessionalController::class, 'professionals_ganancias']);
Route::get('/branch_professionals', [ProfessionalController::class, 'branch_professionals']);
Route::get('/branch_professionals_web', [ProfessionalController::class, 'branch_professionals_web']); //devolver los professionales de una branch
Route::get('/branch_professionals_cashier', [ProfessionalController::class, 'branch_professionals_cashier']); //devolver los cajeros (a) de una branch
Route::get('/professionals_ganancias_branch', [ProfessionalController::class, 'professionals_ganancias_branch']); //Obtener Monto total de un professionals en una branch y un periodo dado
Route::get('/services_professional', [ProfessionalController::class, 'services_professional']);
Route::get('/get-professionals-service', [ProfessionalController::class, 'get_professionals_service']);
Route::get('/professional-reservations-time', [ProfessionalController::class, 'professional_reservations_time']); // dado un professional una branch y una fecha devuelve los horarios reservados de ese professional
Route::get('/professional-state', [ProfessionalController::class, 'professionals_state']); // dado una branch devuelve los professional disponibles
Route::get('/update-state', [ProfessionalController::class, 'update_state']); // dado una un id actualiza el state del professional
Route::get('/branch-professionals-service', [ProfessionalController::class, 'branch_professionals_service1']); // los professionales de una branch que realizan x servicios
Route::get('/branch-professionals-service-new', [ProfessionalController::class, 'branch_professionals_serviceNew']); // los professionales de una branch que realizan x servicios
Route::get('/verify-tec-prof', [ProfessionalController::class, 'verifi_tec_profe']); // dado una email devolver el nombre y el typo de cargo

Route::get('/client', [ClientController::class, 'index']);
Route::get('/client-index-autocomplete', [ClientController::class, 'index_autocomplete']);
Route::get('/client-show', [ClientController::class, 'show']);
Route::post('/client', [ClientController::class, 'store']);
Route::post('/client-update', [ClientController::class, 'update']);
Route::post('/client-destroy', [ClientController::class, 'destroy']);
Route::get('/client_attended_date', [ClientController::class, 'client_attended_date']);
Route::get('/client_attended_date', [ClientController::class, 'client_attended_date']);
Route::get('/client-most-assistance', [ClientController::class, 'client_most_assistance']);
Route::get('/client-autocomplete', [ClientController::class, 'client_autocomplete']);
Route::get('/client-frecuente', [ClientController::class, 'client_frecuente']);///dado una business devolver la cantidad de visitas a por branch
Route::get('/client-email-phone', [ClientController::class, 'client_email_phone']);///dado un numero de telefono o un email devolver si es client
Route::get('/clients-frecuence-state', [ClientController::class, 'clients_frecuence_state']);///dado una business o branch devolver la cantidad de visitas a por clientes
Route::get('/clients-frecuence-periodo', [ClientController::class, 'clients_frecuence_periodo']);///dado una business o branch devolver la cantidad de visitas a por clientes


Route::get('/business', [BusinessController::class, 'index']);
Route::get('/business-show', [BusinessController::class, 'show']);
Route::post('/business', [BusinessController::class, 'store']);
Route::put('/business', [BusinessController::class, 'update']);
Route::post('/business-destroy', [BusinessController::class, 'destroy']);
Route::get('/business-winner', [BusinessController::class, 'business_winner']);//Ganancias por negocioscompany_close_car
Route::get('/business-branch-academy', [BusinessController::class, 'business_branch_academy']);//Ganancias por negocioscompany_close_car

Route::get('/business-type', [BusinessTypesController::class, 'index']);
Route::get('/business-type-show', [BusinessTypesController::class, 'show']);
Route::post('/business-type', [BusinessTypesController::class, 'store']);
Route::put('/business-type', [BusinessTypesController::class, 'update']);
Route::post('/business-type-destroy', [BusinessTypesController::class, 'destroy']);

Route::get('/branch', [BranchController::class, 'index']);
Route::get('/branch-show', [BranchController::class, 'show']);
Route::post('/branch', [BranchController::class, 'store']);
Route::post('/branch-update', [BranchController::class, 'update']);
Route::post('/branch-destroy', [BranchController::class, 'destroy']);
Route::get('/branch_winner', [BranchController::class, 'branch_winner']);//devuelve las ganancias y products mas vendido de una branch 
Route::get('/branch_winner_icon', [BranchController::class, 'branch_winner_icon']);//devuelve las ganancias y products mas vendido de una branch 
Route::get('/branches_professional', [BranchController::class, 'branches_professional']);
Route::get('/company_winner', [BranchController::class, 'company_winner']);//devuelve las ganancias por sucursales
Route::get('/branch_professionals_winner', [BranchController::class, 'branch_professionals_winner']);//devuelve las ganancias y products mas vendido de una branch
Route::get('/show-business', [BranchController::class, 'show_business']);
Route::get('/company-close-car', [BranchController::class, 'company_close_cars']);//Cierre de caja de la compañia por branch

Route::get('/store', [StoreController::class, 'index']);
Route::get('/store-show-notin', [StoreController::class, 'show_NotIn']);
Route::get('/store-show', [StoreController::class, 'show']);
Route::get('/store-show-branch', [StoreController::class, 'show_branch']);
Route::get('/store-academy-show', [StoreController::class, 'store_academy_show']);
Route::post('/store', [StoreController::class, 'store']);
Route::put('/store', [StoreController::class, 'update']);
Route::post('/store-destroy', [StoreController::class, 'destroy']);

Route::get('/charge', [ChargeController::class, 'index']);
Route::get('/charge-web', [ChargeController::class, 'index_web']);
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
Route::get('/category_branch', [ProductCategoryController::class, 'category_branch']); //devolver las categorías de lso productos que existen en una branch
Route::get('/category-products-branch', [ProductCategoryController::class, 'category_products_branch']); //devolver las categorías con los productos que existen en una branch

Route::get('/product', [ProductController::class, 'index']);
Route::get('/product-show', [ProductController::class, 'show']);
Route::post('/product', [ProductController::class, 'store']);
Route::post('/product-update', [ProductController::class, 'update']);
Route::post('/product-destroy', [ProductController::class, 'destroy']);
Route::get('/product_mostSold_date', [ProductController::class, 'product_mostSold_date']);
Route::get('/but-product', [ProductController::class, 'but_product']);//optener los productos mas vendidos
Route::get('/product-mostSold', [ProductController::class, 'product_mostSold']);//optener los productos mas vendidos 
Route::get('/product-mostSold-periodo', [ProductController::class, 'product_mostSold_periodo']);//optener los productos mas vendidos en un período dado
Route::get('/product-stock', [ProductController::class, 'product_stock']);//optener los productos cuya existencia es menor a una definida

Route::get('/service', [ServiceController::class, 'index']);
Route::get('/service-show', [ServiceController::class, 'show']);
Route::post('/service', [ServiceController::class, 'store']);
Route::post('/service-update', [ServiceController::class, 'update']);
Route::post('/service-destroy', [ServiceController::class, 'destroy']);
Route::get('/branch-service-show', [ServiceController::class, 'branch_service_show']);//dada una branch devolver los servicios que realiza esta branch

Route::get('/productstore', [ProductStoreController::class, 'index']);
Route::get('/productstore-show', [ProductStoreController::class, 'show']);
Route::get('/show-stores-products', [ProductStoreController::class, 'showStoresProducts']); //devolver los products y los stores
Route::get('/productstore-academy-show', [ProductStoreController::class, 'academy_show']);//devuelve los productos por almacenes de una academia
Route::get('/products-academy-show', [ProductStoreController::class, 'products_academy_show']);//devuelve los productos por almacenes de una academia para autocomplete
Route::get('/productstore-show-web', [ProductStoreController::class, 'product_show_web']);//dada una branch devuelve los productos de los almacenes que hay en el
Route::get('/productstore-show-academy-web', [ProductStoreController::class, 'product_show_academy_web']);//dada una branch devuelve los productos de los almacenes que hay en el
Route::post('/productstore', [ProductStoreController::class, 'store']);
Route::put('/productstore', [ProductStoreController::class, 'update']);
Route::post('/productstore-destroy', [ProductStoreController::class, 'destroy']);
Route::get('/category_products', [ProductStoreController::class, 'category_products']);//dada una branch y una categiría devolver los pructos
Route::post('/move-product-store', [ProductStoreController::class, 'move_product_store']);
Route::get('/move-products', [ProductStoreController::class, 'movement_products']);//devolver los movientos de productos de una branch dada en eun año dado

Route::get('/branchstore', [BranchStoreController::class, 'index']);
Route::get('/branchstore-show', [BranchStoreController::class, 'show']);
Route::get('/branchstore-show-notInt', [BranchStoreController::class, 'show_notIn']);
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
Route::post('/professionalservice-meta', [BranchServiceProfessionalController::class, 'update_meta']);
Route::put('/professionalservice', [BranchServiceProfessionalController::class, 'update']);
Route::post('/professionalservice-destroy', [BranchServiceProfessionalController::class, 'destroy']);
Route::get('/professional_services', [BranchServiceProfessionalController::class, 'professional_services']);
Route::get('/store_professional_service', [BranchServiceProfessionalController::class, 'store_professional_service']);
Route::get('/branch-service-professionals', [BranchServiceProfessionalController::class, 'branch_service_professionals']);//devolver los professionales que realizan un servicio en una sucursal
Route::get('/services-professional-branch', [BranchServiceProfessionalController::class, 'services_professional_branch']);//devolver los servicios que realiza un professional en un abranch
Route::get('/services-professional-branch-web', [BranchServiceProfessionalController::class, 'services_professional_branch_web']);//devolver los servicios que realiza un professional en un abranch
//Route::get('/services-professional-branch-free', [BranchServiceProfessionalController::class, 'services_professional_branch_free']);//devolver los servicios que no realiza un professional en un abranch
Route::get('/professionals-branch-service', [BranchServiceProfessionalController::class, 'professionals_branch_service']);//devolver los professionales que no realizan un servicio en una sucursal

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
Route::post('/car-denegada', [CarController::class, 'destroy_denegada']);
Route::post('/car-destroy-solicitud', [CarController::class, 'destroy_solicitud']);
Route::get('/car_orders', [CarController::class, 'car_orders']);
Route::get('/branch-cars', [CarController::class, 'branch_cars']);//devuelve los cars de una branch en la fecha actual
Route::get('/branch-cars-delete', [CarController::class, 'branch_cars_delete']);//devuelve los cars de una branch en la fecha actual solicitados a eliminar
Route::get('/car_order_delete_professional', [CarController::class, 'car_order_delete_professional']);//dado una branch y un professional devolver las ordenes solicitadas a eliminar en la fecha actual
Route::get('/car_order_delete_branch', [CarController::class, 'car_order_delete_branch']);//dado una branch devolver las ordenes solicitadas a eliminar en la fecha actual
Route::put('/car-give-tips', [CarController::class, 'give_tips']);
Route::get('/reservation_services', [CarController::class, 'reservation_services']);
Route::get('/car_services', [CarController::class, 'car_services']); //dado una reservations devolver los servicios
Route::get('/car_services2', [CarController::class, 'car_services2']); //dado una reservations devolver los servicios y detalles del cliente
Route::get('/cars-winner-day', [CarController::class, 'cars_sum_amount']);//Dado un business devolver las ganancial del dia
Route::get('/cars-winner-week', [CarController::class, 'cars_sum_amount_week']);//Dado un business devolver las ganancial de la semana
Route::get('/cars-winner-mounth', [CarController::class, 'cars_sum_amount_mounth']);//Dado un business devolver las ganancial del mes
Route::get('/car-products-services', [CarController::class, 'car_products_services']);//Dado un business devolver el producto y el servicio mas vendido
Route::get('/professional-car', [CarController::class, 'professional_car']);//Ganancias del professionals en una branch por dias
Route::get('/tecnico-car', [CarController::class, 'tecnico_car']);//Ganancias del tecninco en una branch por dias
Route::get('/professional-car-date', [CarController::class, 'professional_car_date']);//Detalles del carro
Route::get('/tecnico-car-date', [CarController::class, 'tecnico_car_date']);//Detalles del carro 
Route::get('/professional-car-notpay', [CarController::class, 'professional_car_notpay']);//Detalles del carro

Route::get('/order', [OrderController::class, 'index']);
Route::get('/order-delete-show', [OrderController::class, 'order_delete_show']);
Route::get('/order-show', [OrderController::class, 'show']);
Route::post('/order', [OrderController::class, 'store']);
Route::post('/store-products', [OrderController::class, 'store_products']);
Route::post('/order-web', [OrderController::class, 'store_web']); //para registrar en traces la operacion que realiza
Route::put('/order', [OrderController::class, 'update']);
Route::put('/order2', [OrderController::class, 'update2']);
Route::put('/order-web', [OrderController::class, 'update_web']); //para registrar en traces la operacion que realiza
Route::post('/order-destroy', [OrderController::class, 'destroy']);
Route::post('/order-destroy-web', [OrderController::class, 'destroy_web']);//para registrar en traces la operacion que realiza
Route::post('/order-denegar', [OrderController::class, 'order_denegar']);//Deniega la solicitud de eliminación de la cajera
Route::post('/order-destroy-solicitud', [OrderController::class, 'destroy_solicitud']);//para registrar en traces la operacion que realiza
Route::get('/sales_periodo_branch', [OrderController::class, 'sales_periodo_branch']);

Route::get('/reservation', [ReservationController::class, 'index']);
Route::get('/reservation-send-mail', [ReservationController::class, 'reservation_send_mail']);//para enviar correos a las reservas de 2 dias en adelante comunicando que debn confirmar en 24hr
Route::get('/reservation-show', [ReservationController::class, 'show']);
Route::post('/reservation', [ReservationController::class, 'store']);
Route::put('/reservation', [ReservationController::class, 'update']);
Route::post('/reservation-destroy', [ReservationController::class, 'destroy']);
Route::get('/reservation_tail', [ReservationController::class, 'reservation_tail']);
Route::get('/professional_reservations', [ReservationController::class, 'professional_reservations']);
Route::get('/professional_reservationDate', [ReservationController::class, 'professional_reservationDate']);
Route::post('/reservation_store', [ReservationController::class, 'reservation_store']);//Hacer una reservation en una fecha dada
Route::get('/client-history', [ReservationController::class, 'client_history']);//Dado una branch y un cliente devolver el historico de este cliente en esta branch
Route::get('/update-confirmation', [ReservationController::class, 'update_confirmation']);//Dado una branch y actualizar la confirmation
Route::get('/reservation-notconfirm', [ReservationController::class, 'reserve_noconfirm']);//Reservas no confirmadas, eliminarlas, tarea programada
Route::get('/branch-reservations', [ReservationController::class, 'branch_reservations']);//Dado una branch devolver las reservations del dia
Route::get('/reservations-count', [ReservationController::class, 'reservations_count']);//Dado un business devolver las reservations del dia reservations_count_week
Route::get('/reservations-count-week', [ReservationController::class, 'reservations_count_week']);//Dado un business devolver las reservations por dias de la semana actual

Route::get('/tail', [TailController::class, 'index']);
Route::put('/tail', [TailController::class, 'update']);
Route::get('/tail_up', [TailController::class, 'tail_up']);
Route::get('/cola_branch_data', [TailController::class, 'cola_branch_data']); //dado un branch_id devolver la cola de esa branch
Route::get('/cola_branch_data2', [TailController::class, 'cola_branch_data2']); //dado un branch_id devolver la cola de esa branch que estan en attended 3 movil
Route::get('/cola_branch_professional', [TailController::class, 'cola_branch_professional']); //dado un branch_id  y un professional_id devolver la cola de esa branch
Route::get('/cola_branch_professional_new', [TailController::class, 'cola_branch_professional_new']); //dado un branch_id  y un professional_id devolver la cola de esa branch y los servicios por carros
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
Route::get('/branch-show', [WorkplaceController::class, 'branch_show']);
Route::post('/workplace', [WorkplaceController::class, 'store']);
Route::put('/workplace', [WorkplaceController::class, 'update']);
Route::post('/workplace-destroy', [WorkplaceController::class, 'destroy']);
Route::get('/branch_workplaces_busy', [WorkplaceController::class, 'branch_workplaces_busy']);//mostrar los pestos de trabajo disponibles al trabajador
Route::get('/branch_workplaces_select', [WorkplaceController::class, 'branch_workplaces_select']);//mostrar los pestos de trabajo disponibles para atender al tecnico
Route::get('/update-state-prof-workplace', [WorkplaceController::class, 'update_state_prof']);//pasar el puesto de trabajo del professional a disponible
Route::get('/update-state-tec-workplace', [WorkplaceController::class, 'update_state_tec']);//pasar el puesto de trabajo del tecnico a disponible
Route::get('/workplace-reset', [WorkplaceController::class, 'resetWorkplaces']);//pasar los puestos de trabajo a 0

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
Route::get('/branch-rules-noIn', [BranchRuleController::class, 'branch_rules_noIn']);//dado una branch, devuelve las rules definidas

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
Route::put('/request_location_professional', [BranchProfessionalController::class, 'update_state']); //dato un professional pasarlo a location state 2 y sacar del puesto de trabajo
Route::put('/branchprofessional', [BranchProfessionalController::class, 'update']);
Route::post('/branchprofessional-destroy', [BranchProfessionalController::class, 'destroy']);
Route::get('/branch-professionals', [BranchProfessionalController::class, 'branch_professionals']);//dado una branch devuelve los professionales que trabajan en ella
Route::get('/branch-professionals-barber', [BranchProfessionalController::class, 'branch_professionals_barber']);//dado una branch devuelve los professionales que trabajan en ella que son barberos
Route::get('/branch-professionals-barber-totem', [BranchProfessionalController::class, 'branch_professionals_barber_totem']);//dado una branch devuelve los professionales que trabajan en ella que son barberos
Route::get('/branch-professionals-barber-tecnico', [BranchProfessionalController::class, 'branch_professionals_barber_tecnico']);//dado una branch devuelve los professionales que trabajan en ella que son barberos
Route::get('/branch_colacion', [BranchProfessionalController::class, 'branch_colacion']); //dado una id de la branch Mostrar los professionales que estan en colacion state 2
Route::get('/branch_colacion3', [BranchProfessionalController::class, 'branch_colacion3']); //dado una id de la branch Mostrar los professionales que estan en colacion state 3
Route::get('/branch_colacion4', [BranchProfessionalController::class, 'branch_colacion4']); //dado una id de la branch Mostrar los professionales que estan en colacion state 4


Route::get('/professionalworkplace', [ProfessionalWorkPlaceController::class, 'index']);
Route::get('/professionalworkplace-show', [ProfessionalWorkPlaceController::class, 'show']);
Route::post('/professionalworkplace', [ProfessionalWorkPlaceController::class, 'store']);
Route::put('/professionalworkplace', [ProfessionalWorkPlaceController::class, 'update']);
Route::post('/professionalworkplace-destroy', [ProfessionalWorkPlaceController::class, 'destroy']);
Route::get('/workplace-show-professional', [ProfessionalWorkPlaceController::class, 'workplace_show_professional']);//dato un professional devolver el puesto de trabajo en el que esta en el dia en una branch
Route::get('/workplace-show-professional2', [ProfessionalWorkPlaceController::class, 'workplace_show_professional2']);//dato un professional devolver el puesto de trabajo en el que esta en el dia en una branch
Route::get('/workplace-professional-day', [ProfessionalWorkPlaceController::class, 'workplace_professional_day']);//dato un professional devolver el puesto de trabajo en el que esta en el dia en una branch, y la hora en que entro

Route::get('/comment', [CommentController::class, 'index']);
Route::get('/comment-show', [CommentController::class, 'show']);
Route::post('/comment', [CommentController::class, 'store']);
Route::put('/comment', [CommentController::class, 'update']);
Route::post('/comment-destroy', [CommentController::class, 'destroy']);
Route::post('/storeByReservationId', [CommentController::class, 'storeByReservationId']);

Route::get('/notification', [NotificationController::class, 'index']);
Route::get('/notification-show', [NotificationController::class, 'show']);
Route::get('/notification-professional', [NotificationController::class, 'professional_show']); //dada una branch y un profesional mostrar las notificaciones de este prfessional
Route::get('/notification-professional-web', [NotificationController::class, 'professional_show_web']); //dada una branch y un profesional mostrar las notificaciones de este prfessional
Route::post('/notification', [NotificationController::class, 'store']);
Route::post('/notification2', [NotificationController::class, 'store2']);
Route::put('/notification', [NotificationController::class, 'update']);
Route::put('/notification2', [NotificationController::class, 'update2']);
Route::put('/notification3', [NotificationController::class, 'update3']);
Route::put('/notification-charge', [NotificationController::class, 'update_charge']);
Route::post('/notification-destroy', [NotificationController::class, 'destroy']);

Route::get('/payment', [PaymentController::class, 'index']);
Route::get('/payment-show', [PaymentController::class, 'show']);
Route::post('/payment', [PaymentController::class, 'store']);
Route::post('/payment-product-sales', [PaymentController::class, 'product_sales']);//venta de productos
Route::put('/payment', [PaymentController::class, 'update']);
Route::post('/payment-destroy', [PaymentController::class, 'destroy']);

Route::get('/box', [BoxController::class, 'index']);
Route::get('/box-show', [BoxController::class, 'show']);
Route::post('/box', [BoxController::class, 'store']);
Route::put('/box', [BoxController::class, 'update']);
Route::post('/box-destroy', [BoxController::class, 'destroy']);

Route::get('/closebox', [BoxCloseController::class, 'index']);
Route::get('/closebox-show', [BoxCloseController::class, 'show']);
Route::post('/closebox', [BoxCloseController::class, 'store']);
Route::put('/closebox', [BoxCloseController::class, 'update']);
Route::post('/closebox-destroy', [BoxCloseController::class, 'destroy']);

Route::get('/card-gift', [CardGiftController::class, 'index']);
Route::get('/card-gift-show', [CardGiftController::class, 'show']);
Route::post('/card-gift', [CardGiftController::class, 'store']);
Route::post('/card-gift-update', [CardGiftController::class, 'update']);
Route::post('/card-gift-destroy', [CardGiftController::class, 'destroy']);
//Route::get('/show-value', [CardGiftController::class, 'show_value']);

Route::get('/card-gift-user', [CardGiftUserController::class, 'index']);
Route::get('/card-gift-user-show', [CardGiftUserController::class, 'show']);
Route::post('/card-gift-user', [CardGiftUserController::class, 'store']);
Route::post('/card-gift-user-update', [CardGiftUserController::class, 'update']);
Route::post('/card-gift-user-destroy', [CardGiftUserController::class, 'destroy']);
Route::get('/card-gift-user-show-value', [CardGiftUserController::class, 'show_value']);//dado un codigo mostrsr el valor existente

Route::get('/enrollment', [EnrollmentController::class, 'index']);
Route::get('/enrollment-show', [EnrollmentController::class, 'show']);
Route::post('/enrollment', [EnrollmentController::class, 'store']);
Route::post('/enrollment-updated', [EnrollmentController::class, 'update']);
Route::post('/enrollment-destroy', [EnrollmentController::class, 'destroy']);

Route::get('/student', [StudentController::class, 'index']);
Route::get('/student-show', [StudentController::class, 'show']);
Route::get('/student-code', [StudentController::class, 'student_code']); //dado un codigo devolver un resumen del students
Route::post('/student', [StudentController::class, 'store']);
Route::post('/student-update', [StudentController::class, 'update']);
Route::post('/student-destroy', [StudentController::class, 'destroy']);

Route::get('/course', [CourseController::class, 'index']);
Route::get('/course-show', [CourseController::class, 'show']);
Route::post('/course', [CourseController::class, 'store']);
Route::post('/course-update', [CourseController::class, 'update']);
Route::post('/course-destroy', [CourseController::class, 'destroy']);
Route::get('/calculate-course-earnings', [CourseController::class, 'calculateCourseEarnings']);//devolver los ingresos por cursos
Route::get('/calculate-course-earnings-enrollment', [CourseController::class, 'calculateCourseEarningsEnrollment']);//devolver los ingresos por cursos de una academia dada

Route::get('/course-student', [CourseStudentController::class, 'index']);
Route::get('/course-student-show', [CourseStudentController::class, 'show']);
Route::get('/course-student-product-show', [CourseStudentController::class, 'course_students_product_show']);
Route::post('/course-student', [CourseStudentController::class, 'store']);
Route::post('/course-student-landing', [CourseStudentController::class, 'store_landing']);
Route::post('/course-student-update', [CourseStudentController::class, 'update']);
Route::post('/course-student-update2', [CourseStudentController::class, 'update2']);
Route::post('/course-student-destroy', [CourseStudentController::class, 'destroy']);

Route::get('/record', [RecordController::class, 'index']);
Route::get('/record-show', [RecordController::class, 'show']);
Route::post('/record', [RecordController::class, 'store']);
Route::put('/record', [RecordController::class, 'update']);
Route::post('/record-destroy', [RecordController::class, 'destroy']);
Route::get('/arriving-late-branch-date', [RecordController::class, 'arriving_late_branch_date']);
Route::get('/arriving-late-branch-month', [RecordController::class, 'arriving_late_branch_month']);
Route::get('/arriving-late-branch-periodo', [RecordController::class, 'arriving_late_branch_periodo']);
Route::get('/arriving-late-professional-date', [RecordController::class, 'arriving_late_professional_date']);
Route::get('/arriving-late-professional-periodo', [RecordController::class, 'arriving_late_professional_periodo']);
Route::get('/arriving-late-professional-month', [RecordController::class, 'arriving_late_professional_month']);
Route::get('/arriving-branch-date', [RecordController::class, 'arriving_branch_date']);
Route::get('/arriving-branch-month', [RecordController::class, 'arriving_branch_month']);
Route::get('/arriving-branch-periodo', [RecordController::class, 'arriving_branch_periodo']);

Route::get('/permission', [PermissionController::class, 'index']);
Route::get('/permission-show', [PermissionController::class, 'show']);
Route::post('/permission', [PermissionController::class, 'store']);
Route::put('/permission', [PermissionController::class, 'update']);
Route::post('/permission-destroy', [PermissionController::class, 'destroy']);

Route::get('/charge-permission', [ChargePermissionController::class, 'index']);
Route::get('/charge-permission-show', [ChargePermissionController::class, 'show']);
Route::post('/charge-permission', [ChargePermissionController::class, 'store']);
Route::put('/charge-permission', [ChargePermissionController::class, 'update']);
Route::post('/charge-permission-destroy', [ChargePermissionController::class, 'destroy']);
Route::get('/charge-permission-NOTIN', [ChargePermissionController::class, 'show_charge_NoIN']);//devolver para asignar los permisos que no posee el cargo

Route::get('/associated', [AssociatedController::class, 'index']);
Route::get('/associated-show', [AssociatedController::class, 'show']);
Route::post('/associated', [AssociatedController::class, 'store']);
Route::put('/associated', [AssociatedController::class, 'update']);
Route::post('/associated-destroy', [AssociatedController::class, 'destroy']);

Route::get('/expense', [ExpenseController::class, 'index']);
Route::get('/expense-show', [ExpenseController::class, 'show']);
Route::post('/expense', [ExpenseController::class, 'store']);
Route::put('/expense', [ExpenseController::class, 'update']);
Route::post('/expense-destroy', [ExpenseController::class, 'destroy']);

Route::get('/revenue', [RevenueController::class, 'index']);
Route::get('/revenue-show', [RevenueController::class, 'show']);
Route::post('/revenue', [RevenueController::class, 'store']);
Route::put('/revenue', [RevenueController::class, 'update']);
Route::post('/revenue-destroy', [RevenueController::class, 'destroy']);

Route::get('/finance', [FinanceController::class, 'index']);
Route::get('/finance-show', [FinanceController::class, 'show']);
Route::get('/finance-combined-data', [FinanceController::class, 'combinedData']);
Route::post('/finance', [FinanceController::class, 'store']);
Route::post('/finance-updated', [FinanceController::class, 'update']);
Route::post('/finance-destroy', [FinanceController::class, 'destroy']);
Route::get('/revenue-expense-analysis', [FinanceController::class, 'revenue_expense_analysis']);//devolver las finanzas del año
Route::get('/revenue-expense-details', [FinanceController::class, 'revenue_expense_details']);//devolver las finanzas detalladas del año
Route::get('/details-operations', [FinanceController::class, 'details_operations']);//devolver las finanzas por detalle de operacion de un año
Route::get('/details-operations-month', [FinanceController::class, 'details_operations_month']);//devolver las finanzas por detalle de operacion de un mes

Route::get('/traces-branch-day', [TraceController::class, 'traces_branch_day']);
Route::get('/traces-branch-month', [TraceController::class, 'traces_branch_month']);
Route::get('/traces-branch-periodo', [TraceController::class, 'traces_branch_periodo']);

Route::get('/vacation', [VacationController::class, 'index']);
Route::get('/vacation-show', [VacationController::class, 'show']);
Route::post('/vacation', [VacationController::class, 'store']);
Route::put('/vacation', [VacationController::class, 'update']);
Route::post('/vacation-destroy', [VacationController::class, 'destroy']);

Route::get('/enrollmentstore', [EnrollmentStoreController::class, 'index']);
Route::get('/enrollmentstore-show', [EnrollmentStoreController::class, 'show']);
Route::get('/enrollmentstore-show-notIn', [EnrollmentStoreController::class, 'show_notIn']);
Route::post('/enrollmentstore', [EnrollmentStoreController::class, 'store']);
Route::put('/enrollmentstore', [EnrollmentStoreController::class, 'update']);
Route::post('/enrollmentstore-destroy', [EnrollmentStoreController::class, 'destroy']);

Route::get('/productsale', [ProductSaleController::class, 'index']);
Route::get('/productsale-show', [ProductSaleController::class, 'show']);
Route::post('/productsale', [ProductSaleController::class, 'store']);
Route::put('/productsale', [ProductSaleController::class, 'update']);
Route::post('/productsale-destroy', [ProductSaleController::class, 'destroy']);

Route::get('/professional-payment-show', [ProfessionalPaymentController::class, 'show']);
Route::get('/professional-payment-show-apk', [ProfessionalPaymentController::class, 'show_apk']);
Route::get('/professional-payment-periodo', [ProfessionalPaymentController::class, 'show_periodo']);
Route::post('/professional-payment', [ProfessionalPaymentController::class, 'store']);
Route::post('/professional-payment-cashier', [ProfessionalPaymentController::class, 'store_cashier']);
Route::post('/professional-payment-destroy', [ProfessionalPaymentController::class, 'destroy']);
Route::get('/branch-payment-show', [ProfessionalPaymentController::class, 'branch_payment_show']);//devolver de una branch los pagos realizado a los professionals
Route::get('/professional-win-year', [ProfessionalPaymentController::class, 'professional_win_year']);//devolver las ganancias de un professional en un año dado

Route::get('/operation-tip', [OperationTipController::class, 'show']);
Route::post('/operation-tip', [OperationTipController::class, 'store']);
Route::post('/operation-tip-destroy', [OperationTipController::class, 'destroy']);
Route::get('/operation-tip-show', [OperationTipController::class, 'operation_tip_show']);//devolver de una branch los pagos realizado a los cajeros
Route::get('/operation-tip-periodo', [OperationTipController::class, 'operation_tip_periodo']);//devolver de una branch los pagos realizado a los cajeros en un periodo dado
Route::get('/cashier-car-notpay', [OperationTipController::class, 'cashier_car_notpay']);//Detalles del carro y cajero(a)s

Route::get('/professional-branch-notif-queque', [AssistantController::class, 'professional_branch_notif_queque']);//dado un professional devolver la cola del dia y las notificaciones

Route::get('/survey', [SurveyController::class, 'index']);
Route::get('/survey-show', [SurveyController::class, 'show']);
Route::post('/survey', [SurveyController::class, 'store']);
Route::put('/survey', [SurveyController::class, 'update']);
Route::post('/survey-destroy', [SurveyController::class, 'destroy']);

Route::post('/client-survey', [ClientSurveyController::class, 'store']);
Route::get('/surveyCounts', [ClientSurveyController::class, 'surveyCounts']);

Route::post('/associate-branch', [AssociateBranchController::class, 'store']);
Route::post('/associate-branch-destroy', [AssociateBranchController::class, 'destroy']);
Route::get('/associate-branch', [AssociateBranchController::class, 'show']);

Route::post('/course-professional', [CourseProfessionalController::class, 'store']);
Route::post('/course-professional-destroy', [CourseProfessionalController::class, 'destroy']);
Route::get('/course-professional', [CourseProfessionalController::class, 'show']);
Route::get('/course-professional-show-Notin', [CourseProfessionalController::class, 'show_Notin']);//devolver los professionales que no han sido asociados al curso

Route::get('/restday-show', [RestdayController::class, 'show']);
Route::put('/restday', [RestdayController::class, 'update']);

Route::get('/cashiersale', [CashierSaleController::class, 'index']);
Route::get('/cashiersale-show', [CashierSaleController::class, 'show']);
Route::post('/cashiersale', [CashierSaleController::class, 'store']);
Route::put('/cashiersale', [CashierSaleController::class, 'update']);
Route::post('/cashiersale-destroy', [CashierSaleController::class, 'destroy']);

Route::get('/retention', [RetentionController::class, 'index']);
Route::post('/retention', [RetentionController::class, 'store']);


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
})->where(['folder' => 'professionals|clients|comments|products|services|branches|image|pdfs|licenc|enrollments|students|comments|image', 'filename' => '.*']);



