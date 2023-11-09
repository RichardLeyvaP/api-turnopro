<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchServiceController;
use App\Http\Controllers\BranchStoreController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessTypesController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPersonController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PersonServiceController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProductStoreController;
use App\Http\Controllers\UserController;
use App\Models\Business;
use App\Models\BusinessTypes;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::group( ['middleware' => ["auth:sanctum"]], function(){
    Route::get('profile', [UserController::class, 'userProfile']);
    Route::get('logout', [UserController::class, 'logout']);
    Route::get('qrCode', [UserController::class, 'qrCode']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/people', [PersonController::class, 'index']);
Route::get('/people-show', [PersonController::class, 'show']);
Route::post('/people', [PersonController::class, 'store']);
Route::put('/people', [PersonController::class, 'update']);
Route::post('/people-destroy', [PersonController::class, 'destroy']);

Route::get('/client', [ClientController::class, 'index']);
Route::get('/client-show', [ClientController::class, 'show']);
Route::post('/client', [ClientController::class, 'store']);
Route::put('/client', [ClientController::class, 'update']);
Route::post('/client-destroy', [ClientController::class, 'destroy']);

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
Route::get('/product_branch', [ProductController::class, 'product_branch']);

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

Route::get('/personservice', [PersonServiceController::class, 'index']);
Route::get('/personservice-show', [PersonServiceController::class, 'show']);
Route::post('/personservice', [PersonServiceController::class, 'store']);
Route::put('/personservice', [PersonServiceController::class, 'update']);
Route::post('/personservice-destroy', [PersonServiceController::class, 'destroy']);
Route::get('/person_services', [PersonServiceController::class, 'person_services']);

Route::get('/clientperson', [ClientPersonController::class, 'index']);
Route::get('/clientperson-show', [ClientPersonController::class, 'show']);
Route::post('/clientperson', [ClientPersonController::class, 'store']);
Route::put('/clientperson', [ClientPersonController::class, 'update']);
Route::post('/clientperson-destroy', [ClientPersonController::class, 'destroy']);

Route::get('/car', [CarController::class, 'index']);
Route::get('/car-show', [CarController::class, 'show']);
Route::post('/car', [CarController::class, 'store']);
Route::put('/car', [CarController::class, 'update']);
Route::post('/car-destroy', [CarController::class, 'destroy']);
Route::get('/car_oders', [CarController::class, 'car_oders']);
Route::get('/car_order_delete', [CarController::class, 'car_order_delete']);

Route::get('/order', [OrderController::class, 'index']);
Route::get('/order-show', [OrderController::class, 'show']);
Route::post('/order', [OrderController::class, 'store']);
Route::put('/order', [OrderController::class, 'update']);
Route::post('/order-destroy', [OrderController::class, 'destroy']);