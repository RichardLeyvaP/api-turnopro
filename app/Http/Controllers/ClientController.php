<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Car;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index()
    {
        try {

            Log::info("entra a cliente");

            return response()->json(['clients' => Client::with('user')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }

    public function index_autocomplete()
    {
        try {

            Log::info("entra a cliente");
            $clients = Client::with('user')->get()->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name . ' ' . $client->surname . ' ' . $client->second_surname,
                    'client_image' => $client->client_image

                ];
            });
            return response()->json(['clients' => $clients], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }

    public function client_autocomplete()
    {
        try {
            $clients = User::whereHas('client')->orWhereHas('professional')->get()->map(function ($user) {
                $name = '';
                $image = '';
                $id = '';
                if ($user->client) {
                    $name = $user->client->name . ' ' . $user->client->surname . ' ' . $user->client->second_surname;
                    $image = $user->client->client_image;
                    $id = $user->client->id;
                }
                if ($user->professional) {
                    $id = $user->professional->id;
                    $name = $user->professional->name . ' ' . $user->professional->surname . ' ' . $user->professional->second_surname;
                    $image = $user->professional->image_url;
                }
                return [
                    'id' => $id,
                    'name' => $name,
                    'client_image' => $image,
                    'user_id' => $user->id

                ];
            });
            return response()->json(['clients' => $clients], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la professionala"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['client' => Client::with('user')->find($clients_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la professionala"], 500);
        }
    }

    public function client_most_assistance(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date'

            ]);
            $clients = Client::withCount(['cars' => function ($query) use ($data) {
                $query->whereHas('orders.productStore.store.branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })->with(['orders' => function ($query) use ($data) {
                    $query->whereBetween('data', [$data['startDate'], $data['endDate']]);
                }]);
            }])->orderByDesc('cars_count')->limit(10)->get();
            return response()->json(['clients' => $clients], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la professionala"], 500);
        }
    }

    public function client_attended_date(Request $request)
    {
        try {
            $data = $request->validate([
                'Date' => 'required|date'
            ]);
            Log::info('Obtener los cars');
            $branches = Branch::all();
            $result = [];
            $i = 0;
            $total_company = 0;
            foreach ($branches as $branch) {
                $clients = Car::whereHas('orders', function ($query) use ($data, $branch) {
                    $query->whereDate('data', Carbon::parse($data['Date']))
                        ->whereHas('branchServiceProfessional.branchService', function ($query) use ($branch) {
                            $query->where('branch_id', $branch->id);
                        })
                        ->orWhereHas('productStore.store.branches', function ($query) use ($branch) {
                            $query->where('branch_id', $branch->id);
                        });
                })->count();
                $result[$i]['nameBranch'] = $branch->name;
                $result[$i++]['attended'] = $clients;
                $total_company += round($clients, 2);
            } //foreach
            return response()->json([
                'branches' => $result,
                'companyAttended' => $total_company
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "La branch no obtuvo ganancias en este dia"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $clients_data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:clients',
                'phone' => 'required|max:15',
                'user_id' => 'nullable|numeric'
            ]);

            $client = new Client();
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->user_id = $clients_data['user_id'];
            $client->client_image = 'comments/default_profile.jpg';
            $client->save();
            Log::info($client);
          //  $filename = "image/default.png";
            $filename = "comments/default_profile.jpg";
            if ($request->hasFile('client_image')) {
                $filename = $request->file('client_image')->storeAs('clients', $client->id . '.' . $request->file('client_image')->extension(), 'public');
            }
            $client->client_image = $filename;
            $client->save();

            return response()->json(['msg' => 'Cliente insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar al Cliente'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");


            $clients_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15',
                'user_id' => 'required|numeric'
            ]);
            Log::info($request['client_image']);
            $client = Client::find($clients_data['id']);
            if ($client->client_image != $request['client_image']) {
                $destination = public_path("storage\\" . $client->client_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                $client->client_image = $request->file('client_image')->storeAs('clients', $client->id . '.' . $request->file('client_image')->extension(), 'public');
            }
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->user_id = $clients_data['user_id'];
            //$client->client_image = $filename;
            $client->save();

            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el cliente'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {

            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $client = Client::find($clients_data['id']);
            if ($client->client_image != "comments/default_profile.jpg") {
                $destination = public_path("storage\\" . $client->client_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            Client::destroy($clients_data['id']);

            return response()->json(['msg' => 'cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el cliente'], 500);
        }
    }

    public function client_frecuente(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);

            $currentDate = Carbon::now()->format('Y-m-d');
            if ($data['branch_id'] !== null  && strtolower($data['branch_id']) !== 'null') {
                $clientesConMasDeTresReservas = Client::withCount('reservations')->whereHas('reservations', function ($query) use ($currentDate, $data) {
                    $query->whereHas('car.clientProfessional.professional.branches', function ($query) use ($data){
                        $query->where('branch_id', $data['branch_id']);
                    });
                })->has('reservations', '>', 3)->get();
            }else{
                $clientesConMasDeTresReservas = Client::withCount('reservations')->has('reservations', '>', 3)->get();
            }
            /*
            $query->whereDate('data', '=', $currentDate)->whereHas('car.clientProfessional.professional.branches', function ($query) use ($data){
                        $query->where('branch_id', $data['branch_id']);
                    });*/

            $cantidadClientes = $clientesConMasDeTresReservas->count();

            return response()->json($cantidadClientes, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error del servidor"], 500);
        }
    }

    public function clients_frecuence_state(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            Log::info($data);
            if ($data['branch_id'] !== null  && strtolower($data['branch_id']) !== 'null') {
                Log::info('es una branch');
                $clientesConMasDeTresReservas = Client::withCount('reservations')->whereHas('reservations.car.clientProfessional.professional.branches', function ($query) use ($data) {
                    ///$query->whereDate('data', '=', $currentDate)->whereHas('car.clientProfessional.professional.branches', function ($query) use ($data){
                        $query->where('branch_id', $data['branch_id']);
                    ///});
                })->get()->map(function ($query){
                    /*$yearCant = 0;
                    $yearCant = $query->whereHas('reservations', function ($query){
                        $query->whereYear('data', Carbon::now()->format('Y'));
                    })->count();*/
                    if($query->reservations_count >= 12){
                        $frecuence = 'Fiel';
                        Log::info('11111111');                        
                        Log::info($frecuence);
                    }
                    if($query->reservations_count >= 2){
                        $frecuence = 'Frecuente';
                        Log::info('222222222222');  
                        Log::info($frecuence);
                    } 
                    else{
                        $frecuence = 'No Frecuente';
                        Log::info('33333333333');                        
                        Log::info($frecuence);
                    }                    
                    return [
                        'name' => $query->name.' '.$query->surname.' ' .$query->second_surname,
                        'email' =>$query->email,
                        'phone' =>$query->phone,
                        'client_image' =>$query->client_image,
                        'frecuence' =>  $frecuence,
                        'cant_visist' => $query->reservations_count,
                        //'year' => $yearCant
                    ];
                });
            }else{
                $clientesConMasDeTresReservas = Client::withCount('reservations')->get()->map(function ($query){
                    Log::info('bussines');
                    /*$yearCant = 0;
                    $yearCant = $query->whereHas('reservations', function ($query){
                        $query->whereYear('data', Carbon::now()->format('Y'));
                    })->count();*/
                    if($query->reservations_count >= 12){
                        $frecuence = 'Fiel';
                        Log::info('11111111');                        
                        Log::info($frecuence);
                    }
                    if($query->reservations_count >= 2){
                        $frecuence = 'Frecuente';
                        Log::info('222222222222');  
                        Log::info($frecuence);
                    } 
                    else{
                        $frecuence = 'No Frecuente';
                        Log::info('33333333333');                        
                        Log::info($frecuence);
                    }                    
                    return [
                        'name' => $query->name.' '.$query->surname.' ' .$query->second_surname,
                        'email' =>$query->email,
                        'phone' =>$query->phone,
                        'client_image' =>$query->client_image,
                        'frecuence' =>  $frecuence,
                        'cant_visist' => $query->reservations_count,
                        //'year' => $yearCant
                    ];
                });
            }
            

            //$cantidadClientes = $clientesConMasDeTresReservas->count();

            return response()->json($clientesConMasDeTresReservas, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error del servidor"], 500);
        }
    }


    public function client_email_phone(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required'
            ]);
            return response()->json(['client' => Client::Where('email', $request->email)->orwhere('phone', $request->email)->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error interno del sitema"], 500);
        }
    }
}
