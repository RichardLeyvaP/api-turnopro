<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Car;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index()
    {
        try {

            Log::info("entra a cliente");

            $clients = Client::with('user')
            ->addSelect('*', DB::raw("CONCAT(name, ' ', surname, ' ', second_surname) AS fullName"))
            ->get();
            return response()->json(['clients' => $clients], 200, [], JSON_NUMERIC_CHECK);
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
                if ($client =$user->client) { 
                    $name = $client->name . ' ' . $client->surname . ' ' . $client->second_surname;
                    $image = $client->client_image;
                    $id = $client->id;
                    $reservations = Reservation::whereHas('car', function ($query) use ($client) {
                        $query->where('pay', 1)->whereHas('clientProfessional', function ($query) use ($client){
                            $query->where('client_id', $client->id);
                        });
                    })->orderByDesc('data')->limit(12)->get();
                    if ($reservations->isEmpty())
                    {
                        $details = [
                        'professionalName' => "Ninguno",
                        'imageLook' => 'comments/default_profile.jpg',
                        'image_url' => '',
                        'cantVisit' => 0,
                        'endLook' => 'No hay comentarios',
                        'lastVisit' => 'No ha sido atendido',
                        'frecuencia' => "No Frecuente"
                    ];
                }
                else{
                $countReservations = $reservations->count();
            if ($countReservations >= 12) {
                $currentYear = Carbon::now()->year;
    
                $fiel = $reservations->filter(function ($reservation) use ($currentYear) {
                    return Carbon::parse($reservation->data)->year == $currentYear;
                })->count();
                if ($fiel >= 12) {
                    $frecuencia = "Fiel";
                }
            } elseif ($countReservations >= 3) {
                $frecuencia = "Frecuente";
            } else {
                $frecuencia = "No Frecuente";
            }

            $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })->orderByDesc('data')->orderByDesc('updated_at')->first();

            $reservation = $reservations->first();
            $professional = $reservation->car->clientProfessional->professional;
            $details = [
                'professionalName' => $professional->name . ' ' . $professional->surname,
                'image_url' => $professional->image_url ? $professional->image_url : 'professionals/default_profile.jpg',
                'imageLook' => $comment ? ($comment->client_look ? $comment->client_look : 'comments/default_profile.jpg') : 'comments/default_profile.jpg',
                'cantVisit' => $reservations->count(),
                'endLook' => $comment ? $comment->look : null,
                'lastVisit' => $reservation->data,
                'frecuencia' => $frecuencia,
            ];
                    }
                }
                if ($professional = $user->professional) {
                    $id = $professional->id;
                    $name = $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname;
                    $image = $professional->image_url;
                    $details = [];
                }
                return [
                    'id' => $id,
                    'name' => $name,
                    'client_image' => $image,
                    'user_id' => $user->id,
                    'details' => $details

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
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:clients',
                'phone' => 'required|max:15',
                //'user_id' => 'nullable|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->email)
            ]);
            $client = new Client();
            $client->name = $request->name;
            $client->surname = $request->surname;
            $client->second_surname = $request->second_surname;
            $client->email = $request->email;
            $client->phone = $request->phone;
            $client->user_id = $user->id;
            //$client->client_image = 'comments/default.jpg';
            $client->save();
            Log::info($client);
          //  $filename = "image/default.png";
            $filename = "clients/default.jpg";
            if ($request->hasFile('client_image')) {
                $filename = $request->file('client_image')->storeAs('clients', $client->id . '.' . $request->file('client_image')->extension(), 'public');
            }
            $client->client_image = $filename;
            $client->save();

            return response()->json(['msg' => 'Cliente insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error al insertar al Cliente'], 500);
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
                //'user_id' => 'required|numeric'
            ]);
            Log::info($request['client_image']);
            $client = Client::find($clients_data['id']);
            if ($request->hasFile('client_image'))
            if ($client->client_image != 'clients/default.jpg') {
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
            //$client->user_id = $clients_data['user_id'];
            //$client->client_image = $filename;
            $client->save();

            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error al actualizar el cliente'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {

            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $client = Client::find($clients_data['id']);
            if ($client->client_image != "comments/default.jpg") {
                $destination = public_path("storage\\" . $client->client_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            Client::destroy($clients_data['id']);
            User::destroy($client->user_id);

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

            $currentDate = Carbon::now();
            if ($data['branch_id'] !=0) {
                $clientesConMasDeTresReservas = Client::whereHas('reservations', function ($query) use ($currentDate, $data) {
                    $query->where('branch_id', $data['branch_id'])
                          ->whereDate('data', $currentDate);
                })
                ->whereHas('reservations', function ($query) {
                    $query->groupBy('client_id')
                          ->havingRaw('COUNT(*) > 3');
                })
                ->get();
                /*$clientesConMasDeTresReservas = Client::withCount(['reservations'=> function ($query) use ($currentDate, $data) {
                        $query->where('branch_id', $data['branch_id'])->whereDate('data', $currentDate);
                }])->has('reservations', '>', 3)->get();*/
            }else{
                $clientesConMasDeTresReservas = Client::whereHas('reservations', function ($query) use ($currentDate, $data) {
                    $query->whereDate('data', $currentDate);
                })
                ->whereHas('reservations', function ($query) {
                    $query->groupBy('client_id')
                          ->havingRaw('COUNT(*) > 3');
                })
                ->get();
                /*$clientesConMasDeTresReservas = Client::withCount(['reservations'=> function ($query) use ($currentDate){
                    $query->whereDate('data', $currentDate);
                }])->has('reservations', '>', 3)->get();*/
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
            if ($data['branch_id'] !=0) {
                Log::info('es una branch');
                $clientesConMasDeTresReservas = Client::withCount(['reservations' => function ($query) use ($data) {
                    ///$query->whereDate('data', '=', $currentDate)->whereHas('car.clientProfessional.professional.branches', function ($query) use ($data){
                        $query->where('branch_id', $data['branch_id']);
                    ///});
                }])->get()->map(function ($query){
                    /*$yearCant = 0;
                    $yearCant = $query->whereHas('reservations', function ($query){
                        $query->whereYear('data', Carbon::now()->format('Y'));
                    })->count();*/
                    if($query->reservations_count >= 12){
                        $frecuence = 'Fiel';
                        Log::info('Es Fiel');                        
                        Log::info($frecuence);
                    }
                    if($query->reservations_count >= 2){
                        $frecuence = 'Frecuente';
                        Log::info('Es Frecuente');  
                        Log::info($frecuence);
                    } 
                    else{
                        $frecuence = 'No Frecuente';
                        Log::info('No es frecuente');                        
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
                        Log::info('Es fiel');                        
                        Log::info($frecuence);
                    }
                    if($query->reservations_count >= 2){
                        $frecuence = 'Frecuente';
                        Log::info('Es frecuente');  
                        Log::info($frecuence);
                    } 
                    else{
                        $frecuence = 'No Frecuente';
                        Log::info('No es frecuente');                        
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

    public function clients_frecuence_periodo(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable',
                'startDate' => 'nullable',
                'endDate' => 'nullable'
            ]);
            Log::info($data);
                Log::info('es una branch');
                
                $clientesConMasDeTresReservas = Client::withCount(['reservations' => function ($query) use ($data) {
                    ///$query->whereDate('data', '=', $currentDate)->whereHas('car.clientProfessional.professional.branches', function ($query) use ($data){
                        $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']);
                    ///});
                }])->get()->map(function ($query) use ($data){
                    $reservation = Reservation::where('branch_id', $data['branch_id'])->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate'])->whereHas('car.clientProfessional', function ($reservation) use ($query){
                        $reservation->where('client_id', $query->id);
                    })->orderByDesc('data')->first();
                    /*$yearCant = 0;
                    $yearCant = $query->whereHas('reservations', function ($query){
                        $query->whereYear('data', Carbon::now()->format('Y'));
                    })->count();*/
                    if($query->reservations_count >= 12){
                        $frecuence = 'Fiel';
                        Log::info('Es Fiel');                        
                        Log::info($frecuence);
                    }
                    if($query->reservations_count >= 2 && $query->reservations_count < 12){
                        $frecuence = 'Frecuente';
                        Log::info('Es Frecuente');  
                        Log::info($frecuence);
                    } 
                    else if($query->reservations_count < 2){
                        $frecuence = 'No Frecuente';
                        Log::info('No es frecuente');                        
                        Log::info($frecuence);
                    }                    
                    return [
                        'name' => $query->name.' '.$query->surname.' ' .$query->second_surname,
                        'email' =>$query->email,
                        'phone' =>$query->phone,
                        'client_image' =>$query->client_image,
                        'frecuence' =>  $frecuence,
                        'cant_visist' => $query->reservations_count,
                        'data' => $reservation ? $reservation->data : 'No ha sido atendido'
                    ];
                })->values();
            
            

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
