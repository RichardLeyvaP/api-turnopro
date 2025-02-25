<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\BranchRuleProfessional;
use App\Models\BranchRule;
use App\Models\Notification;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use App\Models\Reservation;
use App\Services\NotificationService;
use Twilio\Rest\Client;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
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

    public function store_ANTERIOR(Request $request)
    {
        Log::info('Entra a registrar las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'tittle' => 'required|string',
                'description' => 'required|string',
                'type' => 'required|string',
            ]);
            $stateApk = "";
            $branch = Branch::find($data['branch_id']);
            if ($data['type'] == 'Ambos') {
                if ($request->has('stateApk')) {
                    $stateApk = $request->stateApk;
                }
                $professionals = BranchProfessional::with(['professional' => function ($query) {
                    $query->select('id', 'charge_id'); // Especifica los campos necesarios
                }, 'professional.charge' => function ($query) {
                    $query->select('id', 'name'); // Especifica los campos necesarios
                }])
                    ->where('branch_id', $data['branch_id'])
                    ->whereHas('professional.charge', function ($query) {
                        $query->whereIn('name', ['Coordinador', 'Encargado', 'Barbero y Encargado']);
                    })
                    ->get(['id', 'professional_id', 'branch_id']); // Especifica los campos necesarios de BranchProfessional
                // Agrupa los profesionales por su cargo
                $groupedProfessionals = $professionals->groupBy('professional.charge.name');
                $encargados = $groupedProfessionals->has('Encargado') ? $groupedProfessionals->get('Encargado')->pluck('professional_id') : collect();
                $coordinadors = $groupedProfessionals->has('Coordinador') ? $groupedProfessionals->get('Coordinador')->pluck('professional_id') : collect();
                $barberoEncargados = $groupedProfessionals->has('Barbero y Encargado') ? $groupedProfessionals->get('Barbero y Encargado')->pluck('professional_id') : collect();
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $data['tittle'];
                        $notification->description = $data['description'];
                        $notification->type = 'Encargado';
                        $notification->stateApk = $stateApk;
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
                        $notification->stateApk = $stateApk;
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
                        $notification->stateApk = $stateApk;
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

    public function store_MAL_ANTE(Request $request)
    {
        Log::info('Entra a registrar las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'tittle' => 'required|string',
                'description' => 'required|string',
                'type' => 'required|string',
            ]);
            $stateApk = "";
            $branch = Branch::find($data['branch_id']);
            if ($data['type'] == 'Ambos') {
                if ($request->has('stateApk')) {
                    $stateApk = $request->stateApk;
                }
                $professionals = BranchProfessional::with(['professional' => function ($query) {
                    $query->select('id', 'charge_id'); // Especifica los campos necesarios
                }, 'professional.charge' => function ($query) {
                    $query->select('id', 'name'); // Especifica los campos necesarios
                }])
                    ->where('branch_id', $data['branch_id'])
                    ->whereHas('professional.charge', function ($query) {
                        $query->whereIn('name', ['Coordinador', 'Encargado', 'Barbero y Encargado']);
                    })
                    ->get(['id', 'professional_id', 'branch_id']); // Especifica los campos necesarios de BranchProfessional
                // Agrupa los profesionales por su cargo
                $groupedProfessionals = $professionals->groupBy('professional.charge.name');
                $encargados = $groupedProfessionals->has('Encargado') ? $groupedProfessionals->get('Encargado')->pluck('professional_id') : collect();
                $coordinadors = $groupedProfessionals->has('Coordinador') ? $groupedProfessionals->get('Coordinador')->pluck('professional_id') : collect();
                $barberoEncargados = $groupedProfessionals->has('Barbero y Encargado') ? $groupedProfessionals->get('Barbero y Encargado')->pluck('professional_id') : collect();
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $data['tittle'];
                        $notification->description = $data['description'];
                        $notification->type = 'Encargado';
                        $notification->stateApk = $stateApk;
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
                        $notification->stateApk = $stateApk;
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
                        $notification->stateApk = $stateApk;
                        $branch->notifications()->save($notification);
                    }
                }
            } elseif ($data['type'] == 'Barbero') {
                $branchrule = BranchRule::whereHas('rule', function ($query) {
                    $query->where('type', 'Tiempo');
                })
                    ->where('branch_id', $data['branch_id'])
                    ->first();

                $existencia = BranchRuleProfessional::whereDate('data', Carbon::now())
                    ->where('branch_rule_id', $branchrule->id)
                    ->where('professional_id', $data['professional_id'])
                    ->first();
            }
            if ($existencia && $existencia->estado != 0) {
                $notification = new Notification();
                $notification->professional_id = $data['professional_id'];
                $notification->tittle = $data['tittle'];
                $notification->description = $data['description'];
                $notification->type = $data['type'];
                $branch->notifications()->save($notification);
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

    public function store(Request $request)
    {
        Log::info('Entra a registrar las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'tittle' => 'required|string',
                'description' => 'required|string',
                'type' => 'required|string',
            ]);
            $stateApk = "";
            $branch = Branch::find($data['branch_id']);
            if ($data['type'] == 'Ambos') {
                if ($request->has('stateApk')) {
                    $stateApk = $request->stateApk;
                }
                $professionals = BranchProfessional::with(['professional' => function ($query) {
                    $query->select('id', 'charge_id'); // Especifica los campos necesarios
                }, 'professional.charge' => function ($query) {
                    $query->select('id', 'name'); // Especifica los campos necesarios
                }])
                    ->where('branch_id', $data['branch_id'])
                    ->whereHas('professional.charge', function ($query) {
                        $query->whereIn('name', ['Coordinador', 'Encargado', 'Barbero y Encargado']);
                    })
                    ->get(['id', 'professional_id', 'branch_id']); // Especifica los campos necesarios de BranchProfessional
                // Agrupa los profesionales por su cargo
                $groupedProfessionals = $professionals->groupBy('professional.charge.name');
                $encargados = $groupedProfessionals->has('Encargado') ? $groupedProfessionals->get('Encargado')->pluck('professional_id') : collect();
                $coordinadors = $groupedProfessionals->has('Coordinador') ? $groupedProfessionals->get('Coordinador')->pluck('professional_id') : collect();
                $barberoEncargados = $groupedProfessionals->has('Barbero y Encargado') ? $groupedProfessionals->get('Barbero y Encargado')->pluck('professional_id') : collect();
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $data['tittle'];
                        $notification->description = $data['description'];
                        $notification->type = 'Encargado';
                        $notification->stateApk = $stateApk;
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
                        $notification->stateApk = $stateApk;
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
                        $notification->stateApk = $stateApk;
                        $branch->notifications()->save($notification);
                    }
                }
                return response()->json(['msg' => 'Notifications creada correctamente'], 200);
            } elseif ($data['type'] == 'Barbero') {
                $branchrule = BranchRule::whereHas('rule', function ($query) {
                    $query->where('type', 'Tiempo');
                })
                    ->where('branch_id', $data['branch_id'])
                    ->first();

                $existencia = BranchRuleProfessional::whereDate('data', Carbon::now())
                    ->where('branch_rule_id', $branchrule->id)
                    ->where('professional_id', $data['professional_id'])
                    ->first();

                if ($existencia && $existencia->estado != 0) {
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->tittle = $data['tittle'];
                    $notification->description = $data['description'];
                    $notification->type = $data['type'];
                    $branch->notifications()->save($notification);
                }
                return response()->json(['msg' => 'Notifications creada correctamente'], 200);
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
            //  $tokenNEW = 'EAARHBCxovkoBOzeY2mavELTq6ZBbfCYVYDqDhZCsWoiqxk9qAMymnsqPVfoMd7rIWqWzL1IDZCdCOvRTigNVguLQV14xuaU5qIpnqAiAsZAkZBn5MQR4XdHa9tHj2Gf1I3Qmxll4TNYlIKBHqfpvoqsou1Ip2hPGnSo2HhoYwdqnfYSl68QAnHdH3FLuQJPhiggZDZD';
            //$whatsappBusinessId = '61568543272906'; 

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
            // $response = Http::withToken($token)->post("https://graph.facebook.com/v21.0/472310509300893/messages", $body);
            Log::info($response);

            return response()->json("Este es el número de celular " . $data['telefone_client'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notificaciones"], 500);
        }
    }

    public function notification_truncate_ANTERIOR()
    {
        try {

            Log::info("Mandar a eliminar las notificaciones");
            Notification::truncate();
            return response()->json(['msg' => "Notificaciones eliminadas correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }


    public function notification_truncate(Request $request)
    {
        $codigo = $request->query('codigo');  // Captura el parámetro "codigo" de la URL

        // Log para verificar el valor de código

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            return response()->json(['msg' => 'Código inválido'], 403);
        }
        try {

            Log::info("Mandar a eliminar las notificaciones");
            Notification::truncate();
            return response()->json(['msg' => "Notificaciones eliminadas correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    public function professional_show_ANTERIOR(Request $request)
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
            $record = $professional->records()->whereDate('start_time', now())->first();
            if ($record) {
                $startTime = $record->start_time;

                if ($professional->state != 0 && $professional->state != 2) {
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
                                ->where('created_at', '>', $startTime)
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
                    } else {
                        $notifications = $branch->notifications()
                            ->where('professional_id', $professional->id)
                            ->whereDate('created_at', Carbon::now())
                            ->where('created_at', '>', $startTime)
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
                    } //end else sino
                } elseif ($professional->state == 0 || $professional->state == 2) {
                    $frase = 'Aceptada su solicitud de Salida';
                    $frase1 = 'Aceptada su solicitud de Colación';
                    $notifications1 = $branch->notifications()
                        ->where('professional_id', $data['professional_id'])
                        ->whereDate('created_at', Carbon::now())
                        ->whereNot('state', 1)
                        ->where('created_at', '>', $startTime)
                        ->where(function ($query) use ($frase, $frase1) {
                            $query->where('tittle', 'like', '%' . $frase . '%')
                                ->orWhere('tittle', 'like', '%' . $frase1 . '%');
                        })
                        ->latest('created_at') // Ordena por 'created_at' en orden descendente
                        ->first(); // Obtiene el primer registro en el orden especificado

                    if ($notifications1 != null) {
                        $notifications = [
                            [
                                'id' => $notifications1->id,
                                'professional_id' => $notifications1->professional_id,
                                'branch_id' => $notifications1->branch_id,
                                'tittle' => $notifications1->tittle,
                                'description' => $notifications1->description,
                                'state' => $notifications1->state,
                                'type' => $notifications1->type,
                                'created_at' => Carbon::parse($notifications1->created_at)->format('Y-m-d h:i A'),
                                'updated_at' => Carbon::parse($notifications1->updated_at)->format('Y-m-d h:i A')
                            ]
                        ];
                        //$notifications1->state = 1;
                        //$notifications1->save();
                    }
                }
            } //if record
            else {
                if ($professional->state != 0 && $professional->state != 2) {
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
                    } else {
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
                    } //end else sino
                } elseif ($professional->state == 0 || $professional->state == 2) {
                    $frase = 'Aceptada su solicitud de Salida';
                    $frase1 = 'Aceptada su solicitud de Colación';
                    $notifications1 = $branch->notifications()
                        ->where('professional_id', $data['professional_id'])
                        ->whereDate('created_at', Carbon::now())
                        ->whereNot('state', 1)
                        ->where(function ($query) use ($frase, $frase1) {
                            $query->where('tittle', 'like', '%' . $frase . '%')
                                ->orWhere('tittle', 'like', '%' . $frase1 . '%');
                        })
                        ->latest('created_at') // Ordena por 'created_at' en orden descendente
                        ->first(); // Obtiene el primer registro en el orden especificado

                    if ($notifications1 != null) {
                        $notifications = [
                            [
                                'id' => $notifications1->id,
                                'professional_id' => $notifications1->professional_id,
                                'branch_id' => $notifications1->branch_id,
                                'tittle' => $notifications1->tittle,
                                'description' => $notifications1->description,
                                'state' => $notifications1->state,
                                'type' => $notifications1->type,
                                'created_at' => Carbon::parse($notifications1->created_at)->format('Y-m-d h:i A'),
                                'updated_at' => Carbon::parse($notifications1->updated_at)->format('Y-m-d h:i A')
                            ]
                        ];
                        //$notifications1->state = 1;
                        //$notifications1->save();
                    }
                }
            }
            return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
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
            $now = Carbon::now();
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $record = $professional->records()->whereDate('start_time', now())->first();
            if ($record) {
                $startTime = $record->start_time;

                if ($professional->state != 0 && $professional->state != 2) {
                    if ($professional->charge->name == "Tecnico") {
                        $workplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->whereDate('data', $now)->where('state', 1)->orderByDesc('created_at')->first();
                        Log::info('Workplaces');
                        Log::info($workplace);
                        if ($workplace) {
                            $places = json_decode($workplace->places, true);
                            $professionals = ProfessionalWorkPlace::whereHas('workplace', function ($query) use ($places) {
                                $query->whereIn('id', $places)->where('select', 1);
                            })->where('state', 1)->whereDate('data', $now)->orderByDesc('created_at')->get()->pluck('professional_id');
                            $notifications1 = $branch->notifications()
                                ->whereIn('professional_id', $professionals)
                                ->whereDate('created_at', $now)
                                ->where('type', 'Tecnico')
                                ->where('state', '!=', 1)
                                ->where('created_at', '>', $startTime)
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
                    } else {
                        $notifications = $branch->notifications()
                            ->where('professional_id', $professional->id)
                            ->whereDate('created_at', $now)
                            ->where('created_at', '>', $startTime)
                            ->where('state', '!=', 1)
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
                    } //end else sino
                } elseif ($professional->state == 0 || $professional->state == 2) {
                    $frase = 'Aceptada su solicitud de Salida';
                    $frase1 = 'Aceptada su solicitud de Colación';
                    $notifications1 = $branch->notifications()
                        ->where('professional_id', $data['professional_id'])
                        ->whereDate('created_at', $now)
                        ->where('state', '!=', 1)
                        ->where('created_at', '>', $startTime)
                        ->where(function ($query) use ($frase, $frase1) {
                            $query->where('tittle', 'like', '%' . $frase . '%')
                                ->orWhere('tittle', 'like', '%' . $frase1 . '%');
                        })
                        ->latest('created_at') // Ordena por 'created_at' en orden descendente
                        ->first(); // Obtiene el primer registro en el orden especificado

                    if ($notifications1 != null) {
                        $notifications = [
                            [
                                'id' => $notifications1->id,
                                'professional_id' => $notifications1->professional_id,
                                'branch_id' => $notifications1->branch_id,
                                'tittle' => $notifications1->tittle,
                                'description' => $notifications1->description,
                                'state' => $notifications1->state,
                                'type' => $notifications1->type,
                                'created_at' => Carbon::parse($notifications1->created_at)->format('Y-m-d h:i A'),
                                'updated_at' => Carbon::parse($notifications1->updated_at)->format('Y-m-d h:i A')
                            ]
                        ];
                        //$notifications1->state = 1;
                        //$notifications1->save();
                    }
                }
            } //if record
            else {
                if ($professional->state != 0 && $professional->state != 2) {
                    if ($professional->charge->name == "Tecnico") {
                        $workplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->whereDate('data', $now)->where('state', 1)->orderByDesc('created_at')->first();
                        Log::info('Workplaces');
                        Log::info($workplace);
                        if ($workplace) {
                            $places = json_decode($workplace->places, true);
                            $professionals = ProfessionalWorkPlace::whereHas('workplace', function ($query) use ($places) {
                                $query->whereIn('id', $places)->where('select', 1);
                            })->where('state', 1)->whereDate('data', $now)->orderByDesc('created_at')->get()->pluck('professional_id');
                            $notifications1 = $branch->notifications()
                                ->whereIn('professional_id', $professionals)
                                ->whereDate('created_at', $now)
                                ->where('type', 'Tecnico')
                                ->where('state', '!=', 1)
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
                    } else {
                        $notifications = $branch->notifications()
                            ->where('professional_id', $professional->id)
                            ->whereDate('created_at', $now)
                            ->where('state', '!=', 1)
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
                    } //end else sino
                } elseif ($professional->state == 0 || $professional->state == 2) {
                    $frase = 'Aceptada su solicitud de Salida';
                    $frase1 = 'Aceptada su solicitud de Colación';
                    $notifications1 = $branch->notifications()
                        ->where('professional_id', $data['professional_id'])
                        ->whereDate('created_at', $now)
                        ->whereNot('state', 1)
                        ->where(function ($query) use ($frase, $frase1) {
                            $query->where('tittle', 'like', '%' . $frase . '%')
                                ->orWhere('tittle', 'like', '%' . $frase1 . '%');
                        })
                        ->latest('created_at') // Ordena por 'created_at' en orden descendente
                        ->first(); // Obtiene el primer registro en el orden especificado

                    if ($notifications1 != null) {
                        $notifications = [
                            [
                                'id' => $notifications1->id,
                                'professional_id' => $notifications1->professional_id,
                                'branch_id' => $notifications1->branch_id,
                                'tittle' => $notifications1->tittle,
                                'description' => $notifications1->description,
                                'state' => $notifications1->state,
                                'type' => $notifications1->type,
                                'created_at' => Carbon::parse($notifications1->created_at)->format('Y-m-d h:i A'),
                                'updated_at' => Carbon::parse($notifications1->updated_at)->format('Y-m-d h:i A')
                            ]
                        ];
                        //$notifications1->state = 1;
                        //$notifications1->save();
                    }
                }
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
                $notifications = Notification::where(function ($query) {
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

    public function update_state3(Request $request)
    {
        Log::info('Modificar el estado de una notificacion de colacion y salida a 1');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $frase = 'Aceptada su solicitud de Salida';
            $frase1 = 'Aceptada su solicitud de Colación';
            $branch = Branch::find($data['branch_id']);
            //$professional = Professional::find($data['professional_id']);
            $branch->notifications()
                ->where('professional_id', $data['professional_id'])
                ->where(function ($query) use ($frase, $frase1) {
                    $query->where('tittle', 'like', '%' . $frase . '%')
                        ->orWhere('tittle', 'like', '%' . $frase1 . '%');
                })
                ->where('state', 3)
                ->update(['state' => 1]);

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

    public function whatsapp_notification_remember(Request $request)
    {
        // Captura el parámetro "codigo" de la URL
        $codigo = $request->query('codigo');

        // Verifica si el código es válido
         if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            Log::info("Código no coincide");
            return response()->json(['msg' => 'Código inválido'], 403);
        }

        try {
            Log::info("Obteniendo clientes atendidos en un día específico sin reservaciones recientes");

            // Obtener la fecha actual
            $fechaActual = Carbon::today()->toDateString(); // Formato: "YYYY-MM-DD"

            // Obtener la fecha específica (hace 15 días)
            $fechaEspecifica = Carbon::today()->subDays(20)->toDateString(); // Hace 15 días

            // Obtener los clientes atendidos en el día específico (hace 15 días)
            $clientesDiaEspecifico = Reservation::whereDate('data', $fechaEspecifica)
                ->where('confirmation', 2) // Especificar que confirmation sea igual a 2
                ->with([
                    'car.clientProfessional.client' => function ($query) {
                        $query->select('id', 'name', 'phone');
                    },
                    'branch' => function ($query) {
                        $query->select('id', 'name'); // Incluir el nombre de la sucursal
                    }
                ])
                ->get()
                ->map(function ($reservation) {
                    return [
                        'client' => $reservation->car->clientProfessional->client,
                        'branch' => $reservation->branch, // Incluir la sucursal
                    ];
                })
                ->unique('client.id'); // Evitar duplicados por cliente

            // Filtrar los clientes que no han tenido reservaciones desde el día específico hasta la fecha actual
            $clientesFiltrados = $clientesDiaEspecifico->filter(function ($item) use ($fechaEspecifica, $fechaActual) {
                $client = $item['client'];
                $branchId = $item['branch']->id; // Obtener el branch_id de la reservación

                // Verificar si el cliente tiene reservaciones desde el día específico hasta la fecha actual
                $tieneReservacionesRecientes = Reservation::whereHas('car.clientProfessional.client', function ($query) use ($client) {
                    $query->where('id', $client->id);
                })
                    ->where('branch_id', $branchId) // Especificar el branch_id
                    ->whereDate('data', '>', $fechaEspecifica) // Reservaciones después del día específico
                    ->whereDate('data', '<=', $fechaActual) // Hasta la fecha actual
                    ->exists();

                // Devolver solo los clientes que NO tienen reservaciones en ese período
                return !$tieneReservacionesRecientes;
            });

            // Recorrer los clientes, formatear los datos y enviar notificaciones en un solo ciclo
            $clientesFinales = $clientesFiltrados->map(function ($item) {
                // Formatear los datos del cliente
                $cliente = [
                    'id' => $item['client']->id,
                    'name' => $item['client']->name,
                    'phone' => $item['client']->phone,
                    'branch' => $item['branch']->name, // Nombre de la sucursal
                ];

                // Enviar el WhatsApp
                $envioExitoso = $this->notificationService->sendWhatsAppRemember($cliente['phone'], $cliente['name'], $cliente['branch']);

                // Registrar el resultado
                if ($envioExitoso) {
                    Log::info("Notificación enviada correctamente a {$cliente['name']} ({$cliente['phone']})");
                } else {
                    Log::warning("Error al enviar notificación a {$cliente['name']} ({$cliente['phone']})");
                }

                // Retornar los datos del cliente
                return $cliente;
            })->values()->toArray();

            return response()->json([
                'msg' => 'Proceso de notificación completado',
                'clientes' => $clientesFinales,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al obtener los clientes: ' . $th->getMessage(), [
                'exception' => $th,
            ]);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /*protected function enviarWhatsApp($phone, $name, $branch)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');
        $recipientNumber = $phone;
        //$message = 'Usted va ser atendido aproximadamente en 3 minutos';

        if (empty($recipientNumber)) {
            return back()->with(['error' => 'El número de teléfono es obligatorio.']);
        }

        // Asegúrate de que el número de teléfono esté en el formato correcto
        if (strpos($recipientNumber, 'whatsapp:') === false) {
            $recipientNumber = 'whatsapp:' . $recipientNumber;
        }

        try {
            $twilio = new Client($twilioSid, $twilioToken);

            // Enviar un mensaje usando la plantilla aprobada
            $twilio->messages->create(
                $recipientNumber,
                [
                    "from" => "whatsapp:56931435036", // Número de WhatsApp de Twilio
                    "template_sid" => "HXabc5167c48681a4eaeeff4323505064a", // SID de la nueva plantilla
                    "contentVariables" => json_encode([
                        "1" => $name, // Nombre del cliente
                        "2" => $branch, // Nombre de la barbería
                        "3" => "https://reservasbh.simplifies.cl/", // Enlace de reserva
                    ]),
                ]
            );

            Log::info('Message sent successfully'. $twilio);

            return back()->with(['success' => 'WhatsApp message sent successfully!']);
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message: ' . $e->getMessage());
            return back()->with(['error' => $e->getMessage()]);
        }
    }*/
}
