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
use App\Models\WorkerPurchase;
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

    /**
 * Lista todas las notificaciones con relaciones.
 *
 * Incluye datos del profesional y la sucursal asociados.
 *
 * @authenticated
 *
 * @response 200 {
 *   "notifications": [
 *     {
 *       "id": 1,
 *       "professional": { "id": 5, "name": "Yasmany Sánchez" },
 *       "branch": { "id": 3, "name": "Sucursal Centro" }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las notifocaciones"}
 */
    public function index()
    {
        try {
            return response()->json(['notifications' => Notification::with('professional', 'branch')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las notifocaciones"], 500);
        }
    }

    /**
 * Crea una notificación dirigida a un profesional o grupo específico.
 *
 * Si `type = "Ambos"`, envía a Encargados, Coordinadores y "Barbero y Encargado".  
 * Si `type = "Barbero"`, solo notifica si el profesional tiene registro activo ese día.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional (usado si type ≠ "Ambos"). Example: 5
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam tittle string required Título de la notificación. Example: "Recordatorio de cierre"
 * @bodyParam description string required Descripción. Example: "Por favor cierra caja antes de salir"
 * @bodyParam type string required Tipo: "Barbero", "Encargado", "Coordinador", "Ambos", etc. Example: "Ambos"
 * @bodyParam stateApk string optional Estado adicional para app móvil (solo si type = "Ambos"). Example: "pending"
 *
 * @response 200 {"msg": "Notifications creada correctamente"}
 * @response 500 {"msg": "[error]Notificacion creada correctamente"}
 */
    public function store(Request $request)
    {
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

            return response()->json(['msg' => 'Notifications creada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Notificacion creada correctamente"], 500);
        }
    }

    /**
 * Crea una notificación simple desde un coordinador o responsable.
 *
 * Establece el estado en `3` por defecto.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del destinatario. Example: 5
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam tittle string required Título. Example: "Revisión pendiente"
 * @bodyParam description string required Descripción. Example: "Favor revisar el reporte diario"
 * @bodyParam type string required Tipo de notificación. Example: "Administrador"
 *
 * @response 200 {"msg": "Notifications creada correctamente desde Coordinador o Responsable "}
 * @response 500 {"msg": "[error]Notificacion no fue creada dio error "}
 */
    public function store2(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . "Notificacion no fue creada dio error "], 500);
        }
    }

    /**
 * Obtiene todas las notificaciones de una sucursal.
 *
 * Incluye nombre completo del profesional y formato de fecha legible.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "notifications": [
 *     {
 *       "id": 1,
 *       "professionalName": "Yasmany Sánchez Martínez",
 *       "tittle": "Recordatorio",
 *       "description": "...",
 *       "state": 0,
 *       "type": "Barbero",
 *       "created_at": "2025-11-21 03:45 PM"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar las notifocaciones"}
 */
    public function show(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }

    /**
 * Envía una notificación vía WhatsApp usando plantilla de Meta.
 *
 * Solo envía si se proporciona `telefone_client`. Usa plantilla fija `hello_world`.
 *
 * @authenticated
 * @bodyParam telefone_client string optional Número de teléfono del cliente. Example: "+56912345678"
 *
 * @response 200 "Este es el número de celular +56912345678"
 * @response 500 {"msg": "[error]Error al mostrar las notificaciones"}
 */
    public function whatsapp_notification(Request $request)
    {
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
             return response()->json("Este es el número de celular " . $data['telefone_client'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notificaciones"], 500);
        }
    }

    /**
 * Elimina todas las notificaciones del sistema.
 *
 * Requiere un código de seguridad en la URL para evitar ejecución accidental.
 *
 * @bodyParam codigo string required Código de autenticación. Example: "P{\nkNgP9hjm/L*~Sks25h^C30_|17"
 *
 * @response 200 {"msg": "Notificaciones eliminadas correctamente"}
 * @response 403 {"msg": "Código inválido"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function notification_truncate(Request $request)
    {
        $codigo = $request->query('codigo');  // Captura el parámetro "codigo" de la URL

        // Log para verificar el valor de código

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            return response()->json(['msg' => 'Código inválido'], 403);
        }
        try {

            Notification::truncate();
            return response()->json(['msg' => "Notificaciones eliminadas correctamente"], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Obtiene notificaciones relevantes para un profesional en su jornada actual.
 *
 * Filtra por estado, cargo (incluye lógica especial para "Tecnico") y horario de entrada.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 5
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "notifications": [
 *     {
 *       "id": 12,
 *       "tittle": "Aceptada su solicitud de Salida",
 *       "state": 3,
 *       "created_at": "2025-11-21 02:30 PM"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar las notifocaciones"}
 */
    public function professional_show(Request $request)
    {
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
                    }
                }
            } //if record
            else {
                if ($professional->state != 0 && $professional->state != 2) {
                    if ($professional->charge->name == "Tecnico") {
                        $workplace = ProfessionalWorkPlace::where('professional_id', $data['professional_id'])->whereDate('data', $now)->where('state', 1)->orderByDesc('created_at')->first();
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
                    }
                }
            }
            return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }

    /**
 * Obtiene notificaciones para web (Cajero, Administrador, etc.) y cuenta solicitudes pendientes.
 *
 * Incluye conteo de compras de trabajadores pendientes (`solicitudes`).
 *
 * @authenticated
 * @queryParam professional_id integer required ID del usuario. Example: 10
 * @queryParam branch_id integer required ID de la sucursal (0 = global). Example: 3
 *
 * @response 200 {
 *   "notifications": [...],
 *   "solicitudes": 2
 * }
 * @response 500 {"msg": "[error]Error al mostrar las notifocaciones"}
 */
    public function professional_show_web(Request $request)
    {
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

            $today = Carbon::now()->format('Y-m-d');

        // Construir consulta base
        $query = WorkerPurchase::where('status', 0) // Status Pendiente
            ->whereDate('data', $today); // Solo del día actual

        // Filtrar por branch_id solo si es diferente de 0
        if (isset($data['branch_id']) && $data['branch_id'] != 0) {
            $query->where('branch_id', $data['branch_id']);
        }

        // Filtrar por professional_id si se proporciona
        if (isset($data['professional_id'])) {
            $query->where('professional_id', $data['professional_id']);
        }

        // Obtener resultados
        $pendingCount = $query->count();

            return response()->json(['notifications' => $notifications, 'solicitudes' => $pendingCount], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }

    /**
 * Marca notificaciones como leídas (state = 1) por tipo y profesional.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 5
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam type string required Tipo de notificación a marcar. Example: "Barbero"
 *
 * @response 200 {"msg": "Notificacion modificada correctamente"}
 * @response 500 {"msg": "[error]Estado de la nitificacion modificado correctamente"}
 */
    public function update(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . "Estado de la nitificacion modificado correctamente"], 500);
        }
    }

    /**
 * Restaura una notificación específica a estado no leído (state = 0).
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 5
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam id integer required ID de la notificación. Example: 12
 *
 * @response 200 {"msg": "Notificacion modificada correctamente"}
 * @response 500 {"msg": "[error]Estado de la notificacion modificado correctamente"}
 */
    public function update2(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }

    /**
 * Marca como leídas las notificaciones de salida o colación aceptadas.
 *
 * Busca por título y actualiza si el estado es `3`.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 5
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {"msg": "Notificacion modificada correctamente"}
 * @response 500 {"msg": "[error]Estado de la notificacion modificado correctamente"}
 */
    public function update_state3(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }

    /**
 * Marca una notificación como "resuelta" para roles administrativos.
 *
 * Actualiza `stateCajero`, `stateAdm` o `stateAdmSucur` a `2` según el cargo.
 *
 * @authenticated
 * @bodyParam id integer required ID de la notificación. Example: 12
 * @bodyParam charge string required Cargo: "Cajero (a)", "Administrador", etc. Example: "Cajero (a)"
 *
 * @response 200 {"msg": "Notificacion modificada correctamente"}
 * @response 500 {"msg": "[error]Estado de la notificacion modificado correctamente"}
 */
    public function update3(Request $request)
    {
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

            return response()->json(['msg' => 'Notificacion modificada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }

    /**
 * Marca múltiples notificaciones como vistas para un cargo específico.
 *
 * Actualiza `stateCajero`, `stateAdm` o `stateAdmSucur` a `1`.
 *
 * @authenticated
 * @bodyParam ids array required Lista de IDs de notificaciones. Example: [12, 15, 20]
 * @bodyParam charge string required Cargo del usuario. Example: "Administrador"
 *
 * @response 200 {"msg": "Notificacion modificada correctamente"}
 * @response 500 {"msg": "[error]Estado de la notificacion modificado correctamente"}
 */
    public function update_charge(Request $request)
    {
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
            return response()->json(['msg' => $th->getMessage() . "Estado de la notificacion modificado correctamente"], 500);
        }
    }

    /**
 * Elimina una notificación específica.
 *
 * @authenticated
 * @bodyParam id integer required ID de la notificación. Example: 12
 *
 * @response 200 {"msg": "Notificacion eliminada correctamente"}
 * @response 500 {"msg": "[error]Error al eliminar la notificacion"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            $notification = Notification::find($data['id']);
            $notification->delete();
            return response()->json(['msg' => 'Notificacion eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al eliminar la notificacion"], 500);
        }
    }

    /**
 * Envía recordatorios por WhatsApp a clientes inactivos.
 *
 * Notifica a clientes que asistieron hace 20 días y no han regresado.
 * Requiere código de seguridad en la URL.
 *
 * @queryParam codigo string required Código de autenticación. Example: "P{\nkNgP9hjm/L*~Sks25h^C30_|17"
 *
 * @response 200 {
 *   "msg": "Proceso de notificación completado",
 *   "clientes": [
 *     { "name": "Yasmany Sánchez", "phone": "+5359380373", "branch": "Sucursal Centro" }
 *   ]
 * }
 * @response 403 {"msg": "Código inválido"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function whatsapp_notification_remember(Request $request)
    {
        // Captura el parámetro "codigo" de la URL
        $codigo = $request->query('codigo');

        // Verifica si el código es válido
        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
             return response()->json(['msg' => 'Código inválido'], 403);
        }

        try {
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
                ->unique(function ($item) {
                    return $item['client']->phone; // Agrupar por número de teléfono
                });

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

            // Calcular el tiempo máximo de ejecución en función de la cantidad de clientes y el intervalo de 2 segundos
            $clientes = $clientesFiltrados->count();
            $tiempoEstimado = ($clientes + 2) * 2;
            set_time_limit($tiempoEstimado); //
            
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
                // Pausar entre envíos
                sleep(2); // Pausar 2 segundos entre cada notificación

                // Retornar los datos del cliente
                return $cliente;
            })->values()->toArray();

            return response()->json([
                'msg' => 'Proceso de notificación completado',
                'clientes' => $clientesFinales,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
