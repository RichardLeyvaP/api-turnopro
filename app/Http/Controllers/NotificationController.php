<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Notification;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        Log::info('entra a buscar las notificaciones por professional');
        try {
            return response()->json(['notifications' => Notification::with('professional', 'branch')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las notifocaciones"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info('Entra a registrar las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'tittle' => 'required|string',
                'description' => 'required|string',
                'type' => 'required|string'
            ]);
            $branch = Branch::find($data['branch_id']);
            if ($data['type'] == 'Ambos') {
                $professionals = BranchProfessional::with('professional.charge')->where('branch_id', $data['branch_id'])->whereHas('professional.charge', function ($query) {
                    $query->where('name', 'Coordinador')->orWhere('name', 'Encargado')->orWhere('name', 'Barbero y Encargado');
                })->get();
                $encargados = $professionals->where('professional.charge.name', 'Encargado')->pluck('professional_id');
                $coordinadors = $professionals->where('professional.charge.name', 'Coordinador')->pluck('professional_id');
                $barberoEncargados = $professionals->where('professional.charge.name', 'Barbero y Encargado')->pluck('professional_id');
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $data['tittle'];
                        $notification->description = $data['description'];
                        $notification->type = 'Encargado';
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$coordinadors->isEmpty()) {
                    foreach ($coordinadors as $coordinador) {
                        $notification = new Notification();
                        $notification->professional_id = $coordinador;
                        $notification->tittle = $data['tittle'];
                        $notification->description = $data['description'];
                        $notification->type = 'Coordinador';
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$barberoEncargados->isEmpty()) {
                    foreach ($barberoEncargados as $barberoEncargado) {
                        $notification = new Notification();
                        $notification->professional_id = $barberoEncargado;
                        $notification->tittle = $data['tittle'];
                        $notification->description = $data['description'];
                        $notification->type = 'Encargado';
                        $branch->notifications()->save($notification);
                    }
                }
            } else {
                $notification = new Notification();
                $notification->professional_id = $data['professional_id'];
                $notification->tittle = $data['tittle'];
                $notification->description = $data['description'];
                $notification->type = $data['type'];
                $branch->notifications()->save($notification);
            }
            /*$professional = Professional::find($data['professional_id']);
            $branch = Branch::find($data['branch_id']);
            $notification = new Notification();
            $notification->professional_id = $professional->id;
            $notification->tittle = $data['tittle'];
            $notification->description = $data['description'];
            $notification->type = $data['type'];
            $branch->notifications()->save($notification);*/

            return response()->json(['msg' => 'Notifications creada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Notificacion creada correctamente"], 500);
        }
    }

    public function store2(Request $request)
    {
        Log::info('Entra a registrar las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'tittle' => 'required|string',
                'description' => 'required|string',
                'type' => 'required|string'
            ]);

            $professional = Professional::find($data['professional_id']);
            $branch = Branch::find($data['branch_id']);
            $notification = new Notification();
            $notification->professional_id = $professional->id;
            $notification->tittle = $data['tittle'];
            $notification->description = $data['description'];
            $notification->state = 3;
            $notification->type = $data['type'];
            $branch->notifications()->save($notification);

            return response()->json(['msg' => 'Notifications creada correctamente desde Coordinador o Responsable '], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Notificacion no fue creada dio error "], 500);
        }
    }

    public function show(Request $request)
    {
        Log::info('Dada una sucursal devuelve las notificaciones');
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $branch = Branch::find($data['branch_id']);
            $notifications = $branch->notifications()->with('professional')->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'professional_id' => $query->professional_id,
                    'branch_id' => $query->branch_id,
                    'tittle' => $query->tittle,
                    'description' => $query->description,
                    'professionalName' => $query->professional->name . ' ' . $query->professional->surname . ' ' . $query->professional->surname,
                    'state' => $query->state,
                    'type' => $query->type,
                    'created_at' => $query->created_at->format('Y-m-d h:i A'),
                    'updated_at' => $query->updated_at->format('Y-m-d h:i A')
                ];
            });

            return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }

    // public function whatsapp_notification(Request $request)
    // {
    //     Log::info('Eviar notificacion whatsApp');
    //     try {
    //         $data = $request->validate([
    //             'telefone_client' => 'required'
    //         ]);
    //         //funcion
    //         $phone = $data['telefone_client'];
    //         $token = 'EAAagNvvUedwBOZBRlNnV1vpITV9yY021G4IrEy6UJqoB7ErYIA13abKyZA54ZBWm64KS9PTZBaRYBh2zWLn594NZBcPMjt2R14Cx3IB6nOfpfyZBH6a6mNeVxDZC3q6GbBZAs4ZAFI0ZChhY957058Y7tk20s72Se2mk9unBNrfdc7eapXtI9KxWu62mE43lIxpsR3Ob7lwO7ZByB6ZBaslLlQ7JgeqXb7IZD';
    //     // $carbon = new Carbon();
    //     $body = [
    //         'messaging_product' => 'whatsapp',
    //         'to' => $phone,
    //         'type' => 'template',
    //         'template' => [
    //             'name' => 'hello_world',
    //             'language' => [
    //                 'code' => 'en_us'
    //             ],

    //         ]
    //     ];
     

    //     $response = Http::withToken($token)->post('https://graph.facebook.com/v15.0/113984608247982/messages', $body);
    //     Log::error('scanner');
    //     Log::info($response);
    //         return response()->json("Este es el numero de celular ".$data['telefone_client'], 200);
    //     } catch (\Throwable $th) {
    //         Log::error($th);
    //         return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
    //     }
    // }
    
    public function whatsapp_notification(Request $request)
{
    Log::info('Enviar notificación WhatsApp');

    try {
        $data = $request->validate([
            'telefone_client' => 'nullable'
        ]);

        if (is_null($data['telefone_client'])) {
            return response()->json("No se envió la notificación porque el número de teléfono es nulo", 200);
        }

        //funcion
        $phone = $data['telefone_client'];
        $token = 'EAAagNvvUedwBOZBRlNnV1vpITV9yY021G4IrEy6UJqoB7ErYIA13abKyZA54ZBWm64KS9PTZBaRYBh2zWLn594NZBcPMjt2R14Cx3IB6nOfpfyZBH6a6mNeVxDZC3q6GbBZAs4ZAFI0ZChhY957058Y7tk20s72Se2mk9unBNrfdc7eapXtI9KxWu62mE43lIxpsR3Ob7lwO7ZByB6ZBaslLlQ7JgeqXb7IZD';
        // $carbon = new Carbon();
        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => 'hello_world',
                'language' => [
                    'code' => 'en_us'
                ],
            ]
        ];

        $response = Http::withToken($token)->post('https://graph.facebook.com/v15.0/113984608247982/messages', $body);
        Log::info($response);
        
        return response()->json("Este es el número de celular ".$data['telefone_client'], 200);
    } catch (\Throwable $th) {
        Log::error($th);
        return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notificaciones"], 500);
    }
}

    public function notification_truncate()
    {
        try { 
            
            Log::info( "Mandar a eliminar las notificaciones");
            Notification::truncate();
            return response()->json(['msg' => "Notificaciones eliminadas correctamente"], 200);
                } catch (\Throwable $th) {  
                    Log::error($th);
                    return response()->json(['msg' => "Error interno del sistema"], 500);
                } 
    }

    public function professional_show(Request $request)
    {
        Log::info('Dada una sucursal y un professional devuelve las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
            ]);
            $notifications = [];
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            if ($professional->state !=0) {
                if ($professional->charge->name == "Tecnico") {
                    $workplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->whereDate('data', Carbon::now())->where('state', 1)->orderByDesc('created_at')->first();
                    Log::info('Workplaces');
                    Log::info($workplace);
                    if ($workplace) {
                        $places = json_decode($workplace->places, true);
                        $professionals = ProfessionalWorkPlace::whereHas('workplace', function ($query) use ($places) {
                            $query->whereIn('id', $places)->where('select', 1);
                        })->where('state', 1)->whereDate('data', Carbon::now())->orderByDesc('created_at')->get()->pluck('professional_id');
                        $notifications1 = $branch->notifications()
                            ->whereIn('professional_id', $professionals)
                            ->whereDate('created_at', Carbon::now())
                            ->where('type', 'Tecnico')
                            ->orderByDesc('created_at')
                            ->get();
                            Log::info('Notificaciones');
                            Log::info($notifications1);
                        foreach ($notifications1  as $query) {
                            $query->professional_id = $data['professional_id'];
                            $query->save();
                            $notifications[] = [
                                'id' => $query->id,
                                'professional_id' => $query->professional_id,
                                'branch_id' => $query->branch_id,
                                'tittle' => $query->tittle,
                                'description' => $query->description,
                                'state' => $query->state,
                                'type' => $query->type,
                                'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A'),
                                'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i A')
                            ];
                        }
                    } else {
                        $notifications = [];
                    }
                }else {
                    $notifications = $branch->notifications()
                        ->where('professional_id', $professional->id)
                        ->whereDate('created_at', Carbon::now())
                        ->get()
                        ->map(function ($query) {
                            return [
                                'id' => $query->id,
                                'professional_id' => $query->professional_id,
                                'branch_id' => $query->branch_id,
                                'tittle' => $query->tittle,
                                'description' => $query->description,
                                'state' => $query->state,
                                'type' => $query->type,
                                'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A'),
                                'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i A')
                            ];
                        })
                        ->sortByDesc(function ($notification) {
                            return $notification['created_at'];
                        })
                        ->values();
                }
            }else {
                $frase = 'Aceptada su solicitud de Salida';
                $notifications = $branch->notifications()
                            ->where('professional_id', $data['professional_id'])
                            ->whereDate('created_at', Carbon::now())
                            ->where('description', $frase)->get()
                            ->orderByDesc('created_at')
                            ->first()->map(function ($query) {
                                return [
                                    'id' => $query->id,
                                    'professional_id' => $query->professional_id,
                                    'branch_id' => $query->branch_id,
                                    'tittle' => $query->tittle,
                                    'description' => $query->description,
                                    'state' => $query->state,
                                    'type' => $query->type,
                                    'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A'),
                                    'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i A')
                                ];
                            });
            }
            return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }

    public function professional_show_web(Request $request)
    {
        Log::info('Dada una sucursal y un professional devuelve las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
            ]);
            $notifications = [];
            $professional = Professional::find($data['professional_id']);
            $charge = $professional->charge->name;
            if ($data['branch_id'] == 0) {
                $notifications = Notification::where(function($query) {
                    $query->where('type', 'Administrador')
                          ->orWhere('type', 'Caja');
                })
                ->whereDate('created_at', Carbon::now())
                ->where('stateAdm', '<>', 2)
                ->get()
                    ->map(function ($query) {
                        $professional = $query->professional;
                        return [
                            'id' => $query->id,
                            'professional_id' => $query->professional_id,
                            'nameProfessional' => $professional->name . ' ' . $professional->surname,
                            'image_url' => $professional->image_url,
                            'branch_id' => $query->branch_id,
                            'tittle' => $query->tittle,
                            'description' => $query->description,
                            'state' => $query->state,
                            'state2' => $query->stateAdm,
                            'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A')
                        ];
                    })
                    ->sortByDesc(function ($notification) {
                        return $notification['created_at'];
                    })
                    ->values();
            } else {
                if ($charge == 'Cajero (a)') {
                    $branch = Branch::find($data['branch_id']);
                    $notifications = $branch->notifications()
                        ->whereDate('created_at', Carbon::now())
                        ->where('type', 'Caja')
                        ->where('stateCajero', '<>', 2)
                        ->get()
                        ->map(function ($query) {
                            $professional = $query->professional;
                            return [
                                'id' => $query->id,
                                'professional_id' => $query->professional_id,
                                'nameProfessional' => $professional->name . ' ' . $professional->surname,
                                'image_url' => $professional->image_url,
                                'branch_id' => $query->branch_id,
                                'tittle' => $query->tittle,
                                'description' => $query->description,
                                'state' => $query->state,
                                'state2' => $query->stateCajero,
                                'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A')
                            ];
                        })
                        ->sortByDesc(function ($notification) {
                            return $notification['created_at'];
                        })
                        ->values();
                }
                if ($charge == 'Administrador de Sucursal') {
                    $branch = Branch::find($data['branch_id']);
                    $notifications = $branch->notifications()
                        ->whereDate('created_at', Carbon::now())
                        ->where('type', 'Administrador')
                        ->where('stateAdmSucur', '<>', 2)
                        ->get()
                        ->map(function ($query) {
                            $professional = $query->professional;
                            return [
                                'id' => $query->id,
                                'professional_id' => $query->professional_id,
                                'nameProfessional' => $professional->name . ' ' . $professional->surname,
                                'image_url' => $professional->image_url,
                                'branch_id' => $query->branch_id,
                                'tittle' => $query->tittle,
                                'description' => $query->description,
                                'state' => $query->state,
                                'state2' => $query->stateAdmSucur,
                                'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A')
                            ];
                        })
                        ->sortByDesc(function ($notification) {
                            return $notification['created_at'];
                        })
                        ->values();
                }
            }

            return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info('Modificar el estado de una notificacion');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'type' => 'required',
            ]);
            $typeData = $data['type'];

            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $branch->notifications()
                ->where('professional_id', $professional->id)
                ->where('type', $typeData)
                ->update(['state' => 1]);
            return response()->json(['msg' => 'Notificacion modificada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Estado de la nitificacion modificado correctamente"], 500);
        }
    }
    public function update2(Request $request)
    {
        Log::info('Modificar el estado de una notificacion');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'id' => 'required|numeric',
            ]);

            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $branch->notifications()
                ->where('professional_id', $professional->id)
                ->where('id', $data['id']) // Verifica también el ID
                ->update(['state' => 0]);

            return response()->json(['msg' => 'Notificacion modificada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }

    public function update3(Request $request)
    {
        Log::info('Modificar el estado de una notificacion');
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'charge' => 'required'
            ]);
            if ($data['charge'] == 'Cajero (a)') {
                Notification::where('id', $data['id'])
                    ->update(['stateCajero' => 2]);
            }
            if ($data['charge'] == 'Administrador') {
                Notification::where('id', $data['id'])
                    ->update(['stateAdm' => 2]);
            }
            if ($data['charge'] == 'Administrador de Sucursal') {
                Notification::where('id', $data['id'])
                    ->update(['stateAdmSucur' => 2]);
            }
            /*$notification = Notification::find($data['id']);
            $notification->state = 1;
            $notification->save();*/

            return response()->json(['msg' => 'Notificacion modificada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }

    public function update_charge(Request $request)
    {
        Log::info('Modificar el estado de una notificacion');
        try {
            $data = $request->validate([
                'ids' => 'required|array',
                'charge' => 'required'
            ]);
            $ids = $request->input('services');
            if ($data['charge'] == 'Cajero (a)') {
                Notification::whereIn('id', $data['ids'])
                    ->update(['stateCajero' => 1]);
            }
            if ($data['charge'] == 'Administrador') {
                Notification::whereIn('id', $data['ids'])
                    ->update(['stateAdm' => 1]);
            }
            if ($data['charge'] == 'Administrador de Sucursal') {
                Notification::whereIn('id', $data['ids'])
                    ->update(['stateAdmSucur' => 1]);
            }

            return response()->json(['msg' => 'Notificacion modificada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }


    public function destroy(Request $request)
    {
        Log::info('Eliminar una notificacion');
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            $notification = Notification::find($data['id']);
            $notification->delete();
            return response()->json(['msg' => 'Notificacion eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al eliminar la notificacion"], 500);
        }
    }
}
