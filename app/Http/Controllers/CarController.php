<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $car = Car::join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'people.*', 'cars.*']);
            return response()->json(['cars' => $car], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar carro");
        Log::info($request);
        try {
            $data = $request->validate([
                'client_person_id' => 'required|numeric',
                'amount' => 'nullable|numeric',
                'pay' => 'boolean',
                'active' => 'boolean',
            ]);
            $car = new Car();
            $car->client_person_id = $data['client_person_id'];
            $car->amount = $data['amount'];
            $car->pay = $data['pay'];
            $car->active = $data['active'];
            $car->save();

            return response()->json(['msg' => 'Carro insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al insertar el carro'], 500);
        }
    }

    public function car_oders(Request $request)
    {
        try {
            $data = $request->validate([
               'id' => 'required|numeric'
           ]);
           $products = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('product_store', 'product_store.id', '=', 'orders.product_store_id')->join('products', 'products.id', '=', 'product_store.product_id')->where('cars.id', $data['id'])->get(['products.*','orders.*']);

           $services = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('branch_service_person', 'branch_service_person.id', '=', 'orders.branch_service_person_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('cars.id', $data['id'])->get(['services.*','orders.*']);

           return response()->json(['productscar' => $products, 'servicescar' => $services], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar ls ordenes"], 500);
       }
    }

   /* public function car_order_delete(Request $request)
    {
        try {
            $data = $request->validate([
               'id' => 'required|numeric'
           ]);
           $car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_person', 'branch_service_person.id', '=', 'orders.branch_service_person_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->where('cars.id', $data['id'])->where('request_delete', true)->orderBy('updated_at', 'desc')->get(['clients.name as nombreClients', 'clients.surname', 'clients.second_surname','people.name as nombreProfesional', 'people.surname as surnameProfesional',  'people.second_surname as second_surnameProfesional','products.name as nameProduct', 'services.name as nameService','orders.id','orders.is_product','orders.id','orders.updated_at']);

           return response()->json(['carOrderDelete' => $car], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar ls ordenes"], 500);
       }
    }*/
    public function car_order_delete(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
    
            $car = Order::join('cars', 'cars.id', '=', 'orders.car_id')
                ->join('client_person', 'client_person.id', '=', 'cars.client_person_id')
                ->join('clients', 'clients.id', '=', 'client_person.client_id')
                ->join('people', 'people.id', '=', 'client_person.person_id')
                ->leftJoin('product_store', 'product_store.id', '=', 'orders.product_store_id')
                ->leftJoin('products', 'products.id', '=', 'product_store.product_id')
                ->leftJoin('branch_service_person', 'branch_service_person.id', '=', 'orders.branch_service_person_id')
                ->leftJoin('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')
                ->leftJoin('services', 'services.id', '=', 'branch_service.service_id')
                ->select([
                    DB::raw('CONCAT(people.name, " ", people.surname) AS nameProfessional'),
                    DB::raw('CONCAT(clients.name, " ", clients.surname) AS nameClient'),
                    'products.name as nameProduct',
                    'services.name as nameService',
                    'orders.is_product',
                    'orders.id',
                    'orders.updated_at'
                ])
                ->where('cars.id', $data['id'])
                ->where('request_delete', true)
                ->orderBy('updated_at', 'desc')
                ->get();
    
            return response()->json(['carOrderDelete' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las ordenes"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $car = Car::join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->where('cars.id', $data['id'])->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'people.*', 'cars.*']);
            return response()->json(['car' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

   public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            $car = Car::find($data['id']);
            $car->pay = true;
            $car->save();
            return response()->json(['msg' => 'Carro actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el carro'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar");
        try{
        $data = $request->validate([
            'id' => 'required|numeric'
        ]);

            $car = Car::find($data['id']);
            $car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }
}
