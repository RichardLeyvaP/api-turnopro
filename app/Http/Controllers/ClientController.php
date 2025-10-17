<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Car;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function index()
    {
        try {
            $now = Carbon::now();
            $clients = Client::with('user')
            ->addSelect('*', DB::raw("CONCAT(name, ' ', surname, ' ', second_surname) AS fullName"))
            ->get();
            $dates = $clients->map(function ($client) use ($now){
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'client_image' => $client->client_image.'?$'.$now,
                    'user_id' => $client->user_id
                ];
            });
            return response()->json(['clients' => $dates], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }
   
    public function client_branch(Request $request)
    {
        try {

            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
            $now = Carbon::now();
            $dates = [];
            $clients = Client::whereHas('clientProfessionals.cars.reservations', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->orWhereDoesntHave('clientProfessionals.cars.reservations')
            ->distinct()
            ->get()->unique('id');
            $dates = $clients->map(function ($client) use ($now) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'client_image' => $client->client_image . '?$' . $now,
                    'user_id' => $client->user_id
                ];
            });

            return response()->json(['clients' => $dates], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }

    public function index_autocomplete()
    {
        try {
            $clients = Client::with('user')->get()->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'client_image' => $client->client_image

                ];
            });
            return response()->json(['clients' => $clients], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }
    
    public function client_reservation(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $clients = [];
            $concatenatedServices = '';
            $reservations = Reservation::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('confirmation', 1)->orderBy('start_time')->with([
        'car.clientProfessional.client',
        'car.clientProfessional.professional' => function ($query) {
            $query->withTrashed(); // Incluye los profesionales eliminados lógicamente
        },
        'car.orders.branchServiceProfessional.branchService.service'
              ])->get();
            foreach ($reservations as $reservation) {
                $client = $reservation['car']['clientProfessional']['client'];
                $professional = $reservation['car']['clientProfessional']['professional'];
                // Asegurarse de que $servicesOrders sea una colección
                $servicesOrders = $reservation['car']['orders'] ? collect($reservation['car']['orders'])->where('is_product', 0) : collect();
            
                $concatenatedServices = '';
                $serviceNames = [];

                foreach ($servicesOrders as $servicesOrder) {
                    $serviceName = $servicesOrder->branchServiceProfessional->branchService->service->name ?? '';
                    if ($serviceName) {
                        $serviceNames[] = $serviceName;
                        // Concatenar el nombre del servicio a la variable
                        //$concatenatedServices .= $serviceName . ', ';
                    }
                }
                $concatenatedServices = implode(', ', $serviceNames);
                    
                $clients[] = [
                    'id' => $reservation['id'],
                    'name' => $client['name'],
                    'email' => $client['email'],
                    'client_image' => $client['client_image'],
                    'professionalName' => $professional['name'] ? $professional['name'] : 'Eliminado',
                    'start_time' => $reservation['start_time'],
                    'final_hour' => $reservation['final_hour'],
                    'services' => $concatenatedServices
                ];
                
                // Eliminar la coma final y el espacio
                //$concatenatedServices = rtrim($concatenatedServices, ', ');
            }
            return response()->json(['clients' => $clients], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getmessage()."Error al mostrar los clientes"], 500);
        }
    }
    
    public function client_autocomplete1(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $clients = User::where(function ($query) use ($data) {
                $query->whereHas('client.clientProfessionals.cars.reservation', function ($subQuery) use ($data) {
                    $subQuery->where('branch_id', $data['branch_id']);
                });
            })->orWhereDoesntHave('client.clientProfessionals.cars.reservations')
            ->distinct('id')->get()->map(function ($user) {
                $name = '';
                $image = '';
                $id = '';
                $frecuencia = "No Frecuente";
                $details = [];
                if ($client = $user->client) {
                    $name = $client->name;
                    $image = $client->client_image;
                    $id = $client->id;
                    $reservations = Reservation::whereHas('car', function ($query) use ($client) {
                        $query->where('pay', 1)->whereHas('clientProfessional', function ($query) use ($client) {
                            $query->where('client_id', $client->id);
                        });
                    })->orderByDesc('data')->limit(12)->get();
                    if ($reservations->isEmpty()) {
                        $details = [
                            'professionalName' => "Ninguno",
                            'imageLook' => 'comments/default_profile.jpg',
                            'image_url' => '',
                            'cantVisit' => 0,
                            'endLook' => 'No hay comentarios',
                            'lastVisit' => 'No ha sido atendido',
                            'frecuencia' => "No Frecuente"
                        ];
                    } else {
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
                        // Aquí es donde hacemos el cambio
                        $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
                        $details = [
                            'professionalName' => $professional ? $professional->name : '',
                            'image_url' => $professional ? $professional->image_url : 'professionals/default_profile.jpg',
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
                    $name = $professional->name;
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
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:clients',
                'phone' => 'required|max:15',
                //'user_id' => 'nullable|numeric'
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors();
    
            // Verifica si el error es por el campo 'email'
                if ($errors->has('email')){
                    return response()->json([
                        'msg' => $validator->errors()->all()
                    ], 401);
                }else{
                    return response()->json([
                        'msg' => $validator->errors()->all()
                    ], 400);
                }
            }
            if($request->user_id){
                $user = User::find($request->user_id);
            }else{
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->email)
                ]);
            }
            $client = new Client();
            $client->name = $request->name;
            $client->email = $request->email;
            $client->phone = $request->phone;
            $client->user_id = $user->id;
            $client->save();
            $filename = "clients/default_profile.jpg";
            if ($request->hasFile('client_image')) {
                $filename = $request->file('client_image')->storeAs('clients', $client->id . '.' . $request->file('client_image')->extension(), 'public');
            }
            $client->client_image = $filename;
            $client->save();
            DB::commit();
            return response()->json(['msg' => 'Cliente insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al insertar al Cliente'], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $clients_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15|string',
            ]);
            $client = Client::find($clients_data['id']);
            if ($request->hasFile('client_image'))
            if ($client->client_image != 'clients/default_profile.jpg') {
                $destination = public_path("storage\\" . $client->client_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                $client->client_image = $request->file('client_image')->storeAs('clients', $client->id . '.' . $request->file('client_image')->extension(), 'public');
            }
            $client->name = $clients_data['name'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->save();

            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al actualizar el cliente'], 500);
        }
    }
    
    public function destroy(Request $request)
    {
        try {

            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            // Obtener el usuario autenticado
            $professUser= Auth::user();        
            if ($professUser->professional->charge->name != "Administrador") {
                return response()->json(['msg' => 'cliente no eliminado no es administrador'], 200);
            }
            $client = Client::find($clients_data['id']);
            if ($client->client_image != "clients/default_profile.jpg") {
                $destination = public_path("storage\\" . $client->client_image);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            
            $user = User::find($client->user_id);
            if ($user) {
                Client::destroy($clients_data['id']);
            }else{         
                Client::destroy($clients_data['id']);  
            User::destroy($user->id);
            }
            return response()->json(['msg' => 'cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al eliminar el cliente'], 500);
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
            }else{
                $clientesConMasDeTresReservas = Client::whereHas('reservations', function ($query) use ($currentDate, $data) {
                    $query->whereDate('data', $currentDate);
                })
                ->whereHas('reservations', function ($query) {
                    $query->groupBy('client_id')
                          ->havingRaw('COUNT(*) > 3');
                })
                ->get();
            }            
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
            if ($data['branch_id'] !=0) {
                $clientesConMasDeTresReservas = Client::withCount(['reservations' => function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                }])->get()->map(function ($query){
                    if($query->reservations_count >= 12){
                        $frecuence = 'Fiel';
                        }
                    if($query->reservations_count >= 2){
                        $frecuence = 'Frecuente';
                      } 
                    else{
                        $frecuence = 'No Frecuente';
                    }                    
                    return [
                        'name' => $query->name.' '.$query->surname.' ' .$query->second_surname,
                        'email' =>$query->email,
                        'phone' =>$query->phone,
                        'client_image' =>$query->client_image,
                        'frecuence' =>  $frecuence,
                        'cant_visist' => $query->reservations_count,
                    ];
                });
            }else{
                $clientesConMasDeTresReservas = Client::withCount('reservations')->get()->map(function ($query){
                    if($query->reservations_count >= 12){
                        $frecuence = 'Fiel';
                    }
                    if($query->reservations_count >= 2){
                        $frecuence = 'Frecuente';
                    } 
                    else{
                        $frecuence = 'No Frecuente';
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
            return response()->json($clientesConMasDeTresReservas, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error del servidor"], 500);
        }
    }
    
    public function clients_frecuence_periodo(Request $request)
    {
        try {
            // Validar los datos de entrada
            $data = $request->validate([
                'branch_id' => 'required|integer',
                'startDate' => 'required|date',
                'endDate' => 'required|date',
            ]);

            // Obtener la última reserva por cliente dentro del período
            $clientReservations = Reservation::selectRaw('client_professional.client_id as client_id, MAX(reservations.data) as latest_data')
                ->join('cars', 'reservations.car_id', '=', 'cars.id')
                ->join('client_professional', 'cars.client_professional_id', '=', 'client_professional.id')
                ->where('reservations.branch_id', $data['branch_id'])
                ->whereDate('reservations.data', '>=', $data['startDate'])
                ->whereDate('reservations.data', '<=', $data['endDate'])
                ->groupBy('client_professional.client_id')
                ->get()
                ->keyBy('client_id'); // Organizar por client_id

            // Filtrar clientes con al menos una reserva en la sucursal y contar sus reservas
            $clientesConMasDeTresReservas = Client::whereHas('clientProfessionals.cars.reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->withCount(['reservations' => function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])
                    ->whereDate('data', '>=', $data['startDate'])
                    ->whereDate('data', '<=', $data['endDate']);
            }])->get();

            // Mapear datos de clientes y calcular frecuencia
            $result = $clientesConMasDeTresReservas->map(function ($client) use ($clientReservations) {
                // Obtener la última fecha de atención
                $reservation = $clientReservations->get($client->id);

                // Calcular frecuencia según la cantidad de visitas
                $frecuence = match(true) {
                    $client->reservations_count >= 12 => 'Fiel',
                    $client->reservations_count >= 2 => 'Frecuente',
                    default => 'No Frecuente',
                };

                return [
                    'name' => $client->name . ' ' . $client->surname . ' ' . $client->second_surname,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'client_image' => $client->client_image,
                    'frecuence' => $frecuence,
                    'cant_visist' => $client->reservations_count,
                    'data' => $reservation ? $reservation->latest_data : 'No ha sido atendido',
                ];
            });

            return response()->json($result->values(), 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . " Error del servidor"], 500);
        }
    }
    public function client_email_phone(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required'
            ]);
            $input = $request->email;

           
            if (preg_match('/^\+?\d+$/', $input)) {
               
                if (!str_starts_with($input, '56')) {
                    $input = '56' . ltrim($input, '+');
                    $data['email'] = $input;
                }
            }
            $clients = Client::where('email', $request->email)->orwhere('phone', '+'.$data['email'])->get();
            return response()->json(['client' => $clients], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error interno del sitema"], 500);
        }
    }


    public function client_email(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:clients'
            ]);
            if ($validator->fails()) {
                $user = '';
                $clientName = '';
                $clientImage = '';
                $type = 'Client';
                return response()->json(['user' => $user, 'type' => $type], 200, [], JSON_NUMERIC_CHECK);
                
            }
            //$client = Client::with('user')->where('email', $request->email)->first();
            $professional = Professional::with('user')->where('email', $request->email)->first();
            if($professional != null){
                $user = $professional->user->id;
                $clientName = $professional->name;
                $clientImage = $professional->image_url;
                $type = 'Professional';
            }else {
                $user = '';
                $clientName = '';
                $clientImage = '';
                $type = 'No';
            }
            return response()->json(['user' => $user, 'clientName' => $clientName, 'clientImage' => $clientImage, 'type' => $type], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

}
