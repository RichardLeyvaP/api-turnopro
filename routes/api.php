<?php

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
use App\Http\Controllers\CardGiftController;
use App\Http\Controllers\CardGiftUserController;
use App\Http\Controllers\ChargePermissionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfessionalWorkPlaceController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SendMailController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkplaceController;
use App\Models\ChargePermission;
use App\Models\CourseStudent;
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
Route::get('qrCode', [UserController::class, 'qrCode']);

Route::group( ['middleware' => ["auth:sanctum"]], function(){
    Route::get('profile', [UserController::class, 'userProfile']);
    Route::get('logout', [UserController::class, 'logout']);
    Route::post('change_password', [UserController::class, 'change_password']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/professional', [ProfessionalController::class, 'index']);
Route::get('/professional-show', [ProfessionalController::class, 'show']);
Route::get('/professional-show-autocomplete', [ProfessionalController::class, 'show_autocomplete']);
Route::post('/professional', [ProfessionalController::class, 'store']);
Route::post('/professional-update', [ProfessionalController::class, 'update']);
Route::post('/professional-destroy', [ProfessionalController::class, 'destroy']);
Route::get('/professionals_branch', [ProfessionalController::class, 'professionals_branch']);
Route::get('/professionals_ganancias', [ProfessionalController::class, 'professionals_ganancias']);
Route::get('/branch_professionals', [ProfessionalController::class, 'branch_professionals']);
Route::get('/professionals_ganancias_branch', [ProfessionalController::class, 'professionals_ganancias_branch']); //Obtener Monto total de un professionals en una branch y un periodo dado
Route::get('/services_professional', [ProfessionalController::class, 'services_professional']);
Route::get('/get-professionals-service', [ProfessionalController::class, 'get_professionals_service']);
Route::get('/professional-reservations-time', [ProfessionalController::class, 'professional_reservations_time']); // dado un professional una branch y una fecha devuelve los horarios reservados de ese professional
Route::get('/professional-state', [ProfessionalController::class, 'professionals_state']); // dado una branch devuelve los professional disponibles
Route::get('/update-state', [ProfessionalController::class, 'update_state']); // dado una un id actualiza el state del professional
Route::get('/branch-professionals-service', [ProfessionalController::class, 'branch_professionals_service']); // los professionales de una branch que realizan x servicios

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


Route::get('/business', [BusinessController::class, 'index']);
Route::get('/business-show', [BusinessController::class, 'show']);
Route::post('/business', [BusinessController::class, 'store']);
Route::put('/business', [BusinessController::class, 'update']);
Route::post('/business-destroy', [BusinessController::class, 'destroy']);
Route::get('/business-winner', [BusinessController::class, 'business_winner']);//Ganancias por negocioscompany_close_car



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
Route::get('/company_winner', [BranchController::class, 'company_winner']);//devuelve las ganancias y el products mas vendido de la compañia
Route::get('/branch_professionals_winner', [BranchController::class, 'branch_professionals_winner']);//devuelve las ganancias y products mas vendido de una branch
Route::get('/show-business', [BranchController::class, 'show_business']);
Route::get('/company-close-car', [BranchController::class, 'company_close_cars']);//Cierre de caja de la compañia por branch

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
Route::get('/category_branch', [ProductCategoryController::class, 'category_branch']); //devolver las categorías de lso productos que existen en una branch

Route::get('/product', [ProductController::class, 'index']);
Route::get('/product-show', [ProductController::class, 'show']);
Route::post('/product', [ProductController::class, 'store']);
Route::post('/product-update', [ProductController::class, 'update']);
Route::post('/product-destroy', [ProductController::class, 'destroy']);
Route::get('/product_mostSold_date', [ProductController::class, 'product_mostSold_date']);
Route::get('/but-product', [ProductController::class, 'but_product']);//optener los productos mas vendidos

Route::get('/service', [ServiceController::class, 'index']);
Route::get('/service-show', [ServiceController::class, 'show']);
Route::post('/service', [ServiceController::class, 'store']);
Route::post('/service-update', [ServiceController::class, 'update']);
Route::post('/service-destroy', [ServiceController::class, 'destroy']);

Route::get('/productstore', [ProductStoreController::class, 'index']);
Route::get('/productstore-show', [ProductStoreController::class, 'show']);
Route::post('/productstore', [ProductStoreController::class, 'store']);
Route::put('/productstore', [ProductStoreController::class, 'update']);
Route::post('/productstore-destroy', [ProductStoreController::class, 'destroy']);
Route::get('/category_products', [ProductStoreController::class, 'category_products']);//dada una branch y una categiría devolver los pructos
Route::post('/move-product-store', [ProductStoreController::class, 'move_product_store']);

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
Route::get('/branch-cars', [CarController::class, 'branch_cars']);//devuelve los cars de una branch en la fecha actual
Route::get('/car_order_delete_professional', [CarController::class, 'car_order_delete_professional']);//dado una branch y un professional devolver las ordenes solicitadas a eliminar en la fecha actual
Route::get('/car_order_delete_branch', [CarController::class, 'car_order_delete_branch']);//dado una branch devolver las ordenes solicitadas a eliminar en la fecha actual
Route::put('/car-give-tips', [CarController::class, 'give_tips']);
Route::get('/reservation_services', [CarController::class, 'reservation_services']);
Route::get('/car_services', [CarController::class, 'car_services']); //dado una reservations devolver los servicios
Route::get('/cars-winner-day', [CarController::class, 'cars_sum_amount']);//Dado un business devolver las ganancial del dia
Route::get('/cars-winner-week', [CarController::class, 'cars_sum_amount_week']);//Dado un business devolver las ganancial de la semana
Route::get('/cars-winner-mounth', [CarController::class, 'cars_sum_amount_mounth']);//Dado un business devolver las ganancial de la semana

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
Route::get('/update-confirmation', [ReservationController::class, 'update_confirmation']);//Dado una branch y actualizar la confirmation
Route::get('/branch-reservations', [ReservationController::class, 'branch_reservations']);//Dado una branch devolver las reservations del dia
Route::get('/reservations-count', [ReservationController::class, 'reservations_count']);//Dado un business devolver las reservations del diareservations_count_week
Route::get('/reservations-count-week', [ReservationController::class, 'reservations_count_week']);//Dado un business devolver las reservations por dias de la semana actual

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
Route::get('/branch-professionals', [BranchProfessionalController::class, 'branch_professionals']);//dado una branch devuelve los professionales que trabajan en ella

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

Route::get('/notification', [NotificationController::class, 'index']);
Route::get('/notification-show', [NotificationController::class, 'show']);
Route::get('/notification-professional', [NotificationController::class, 'professional_show']); //dada una branch y un profesional mostrar las notificaciones de este prfessional
Route::post('/notification', [NotificationController::class, 'store']);
Route::put('/notification', [NotificationController::class, 'update']);
Route::post('/notification-destroy', [NotificationController::class, 'destroy']);

Route::get('/payment', [PaymentController::class, 'index']);
Route::get('/payment-show', [PaymentController::class, 'show']);
Route::post('/payment', [PaymentController::class, 'store']);
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
Route::put('/enrollment', [EnrollmentController::class, 'update']);
Route::post('/enrollment-destroy', [EnrollmentController::class, 'destroy']);

Route::get('/student', [StudentController::class, 'index']);
Route::get('/student-show', [StudentController::class, 'show']);
Route::post('/student', [StudentController::class, 'store']);
Route::post('/student-update', [StudentController::class, 'update']);
Route::post('/student-destroy', [StudentController::class, 'destroy']);

Route::get('/course', [CourseController::class, 'index']);
Route::get('/course-show', [CourseController::class, 'show']);
Route::post('/course', [CourseController::class, 'store']);
Route::post('/course-update', [CourseController::class, 'update']);
Route::post('/course-destroy', [CourseController::class, 'destroy']);

Route::get('/course-student', [CourseStudentController::class, 'index']);
Route::get('/course-student-show', [CourseStudentController::class, 'show']);
Route::post('/course-student', [CourseStudentController::class, 'store']);
Route::post('/course-student-update', [CourseStudentController::class, 'update']);
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

Route::get('/send_email', [ReservationController::class, 'send_email']);
Route::get('/sendMessage', [SendMailController::class, 'sendMessage']);

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
})->where(['folder' => 'professionals|clients|comments|products|services|branches|image|pdfs|licenc', 'filename' => '.*']);



