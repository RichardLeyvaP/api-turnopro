<?php

namespace App\Http\Controllers;

use App\Models\BranchProfessional;
use App\Models\BranchService;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProfessionalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ProfessionalWorkPlace;

class ProfessionalController extends Controller
{

    private ProfessionalService $professionalService;

    public function __construct(ProfessionalService $professionalService)
    {
        $this->professionalService = $professionalService;
    }

    /**
 * Lista todos los profesionales del sistema.
 *
 * Incluye datos del usuario asociado, cargo y una marca de tiempo en la URL de la imagen para evitar caché.
 *
 * @authenticated
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 10,
 *       "name": "Carlos",
 *       "surname": "Pérez",
 *       "fullName": "Carlos Pérez García",
 *       "email": "carlos@example.com",
 *       "phone": "+56912345678",
 *       "state": 1,
 *       "image_url": "professionals/10.jpg?$2025-11-21 17:00:00",
 *       "charge": "Barbero",
 *       "retention": 10
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar las professionales"}
 */
    public function index()
    {
        try {
            $now = Carbon::now();
            $professionals = Professional::with('user', 'charge')->get()->map(function ($professional) use ($now) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'surname' => $professional->surname,
                    'second_surname' => $professional->second_surname,
                    'fullName' => $professional->fullName,
                    'email' => $professional->email,
                    'phone' => $professional->phone,
                    'user_id' => $professional->user_id,
                    'state' => $professional->state,
                    'image_url' => $professional->image_url . '?$' . $now,
                    'charge_id' => $professional->charge_id,
                    'user' => $professional->user->name,
                    'charge' => $professional->charge->name,
                    'retention' => $professional->retention,
                ];
            });
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las professionales"], 500);
        }
    }

    /**
 * Lista profesionales asignados a una sucursal específica.
 *
 * Si el usuario autenticado es **Administrador**, también incluye profesionales sin sucursal asignada y otros administradores.
 * De lo contrario, excluye a los administradores y muestra solo los asignados a la sucursal o sin asignar.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Error al mostrar las professionales"}
 */
    public function professionalsBranch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
            if (auth()->user()->professional->charge->name == 'Administrador') {
                $professionals = Professional::whereDoesntHave('branches')->orWhereHas('branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                    ->orWhereHas('charge', function ($query) {
                        $query->where('name', 'Administrador');
                    })
                    ->with('user', 'charge')
                    ->get()
                    ->map(function ($professional) use ($now) {
                        return [
                            'id' => $professional->id,
                            'name' => $professional->name,
                            'surname' => $professional->surname,
                            'second_surname' => $professional->second_surname,
                            'fullName' => $professional->fullName,
                            'email' => $professional->email,
                            'phone' => $professional->phone,
                            'user_id' => $professional->user_id,
                            'state' => $professional->state,
                            'image_url' => $professional->image_url . '?$' . $now,
                            'charge_id' => $professional->charge_id,
                            'user' => $professional->user->name,
                            'charge' => $professional->charge->name,
                            'retention' => $professional->retention,
                        ];
                    });
            } else {
                $professionals = Professional::whereHas('charge', function ($query) {
                    $query->where('name',  '!=', 'Administrador');
                })->whereDoesntHave('branches')->orWhereHas('branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })->with('user', 'charge')->get()->map(function ($professional) use ($now) {
                    return [
                        'id' => $professional->id,
                        'name' => $professional->name,
                        'surname' => $professional->surname,
                        'second_surname' => $professional->second_surname,
                        'fullName' => $professional->fullName,
                        'email' => $professional->email,
                        'phone' => $professional->phone,
                        'user_id' => $professional->user_id,
                        'state' => $professional->state,
                        'image_url' => $professional->image_url . '?$' . $now,
                        'charge_id' => $professional->charge_id,
                        'user' => $professional->user->name,
                        'charge' => $professional->charge->name,
                        'retention' => $professional->retention,
                    ];
                });
            }
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las professionales"], 500);
        }
    }

    /**
 * Obtiene profesionales **no asignados** a una sucursal (para autocompletado).
 *
 * Útil en interfaces de asignación de personal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "professionals": [
 *     { "id": 15, "name": "María López", "image_url": "professionals/15.jpg", "charge": "Encargado" }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar las branches"}
 */
    public function show_autocomplete_Notin(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branchProfessionals = BranchProfessional::where('branch_id', $data['branch_id'])->get()->pluck('professional_id');
            $professionals = Professional::whereNotin('id', $branchProfessionals)->with('charge')->get()->map(function ($professional) {
                return [
                    'id' => intval($professional->id),
                    'name' => $professional->name,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name

                ];
            });
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Obtiene todos los profesionales (para autocompletado global).
 *
 * @authenticated
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Error al mostrar el professional"}
 */
    public function show_autocomplete(Request $request)
    {
        try {
            $professionals = Professional::with('user', 'charge')->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name

                ];
            });
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar el professional"], 500);
        }
    }

    /**
 * Obtiene profesionales asignados a una sucursal (solo nombre completo e imagen).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "professionals": [
 *     { "id": 10, "name": "Carlos Pérez García", "image_url": "professionals/10.jpg" }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar el professional"}
 */
    public function show_autocomplete_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url

                ];
            });
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar el professional"], 500);
        }
    }

    /**
 * Muestra los detalles de un profesional específico.
 *
 * @authenticated
 * @queryParam id integer required ID del profesional. Example: 10
 *
 * @response 200 {
 *   "professional": {
 *     "id": 10,
 *     "name": "Carlos",
 *     "email": "carlos@example.com",
 *     "user": { "name": "carlos_user" },
 *     "charge": { "name": "Barbero" }
 *   }
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function show(Request $request)
    {
        try {
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['professional' => Professional::with('user', 'charge')->find($professionals_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Obtiene el estado de un profesional para la app móvil.
 *
 * Devuelve:
 * - `3` si tiene una reserva pendiente con cola activa
 * - El `state` actual del profesional en otro caso
 * - `-1` si no se encuentra
 *
 * @queryParam id integer required ID del profesional. Example: 10
 *
 * @response 200 1
 * @response 200 3
 * @response 200 -1
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function show_apk(Request $request)
    {
        try {
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $professional = Professional::where('id', $professionals_data['id'])->first();
            $today = Carbon::now()->toDateString(); // Obtiene la fecha actual
            $branch_id = 0;
            $workplaceProfessional = ProfessionalWorkPlace::where('professional_id', $professional->id)
                ->whereDate('data', $today)
                ->where('state', 1)->orderByDesc('data')->first();
            if ($workplaceProfessional != null) {
                $branch_id = $workplaceProfessional->workplace->branch_id;
            }
            if ($professional !== null) {
                if ($professional->state == 1 && $branch_id != 0) {
                    $reservation = Reservation::where('branch_id', $branch_id)->where('confirmation', 4)->whereHas('car.clientProfessional', function ($query) use ($professionals_data) {
                        $query->where('professional_id', $professionals_data['id']);
                    })->whereHas('tail', function ($query) use ($professionals_data) {
                        $query->where('attended', 3)->whereNot('aleatorie', 1);
                    })->whereDate('data', Carbon::now())->orderBy('start_time')->first();
                    if ($reservation != null) {
                        return 3;
                    } else {
                        return $professional->state;
                    }
                } else {
                    return $professional->state;
                }
            } else
                return -1;
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    /**
 * Obtiene los horarios disponibles de un profesional en una fecha y sucursal.
 *
 * Devuelve una lista de intervalos de 10 minutos libres, considerando reservas confirmadas y horarios de la sucursal.
 * Si es hoy, ajusta el inicio al momento actual + 1 hora.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam data date required Fecha de consulta (Y-m-d). Example: "2025-12-01"
 *
 * @response 200 {
 *   "reservations": ["09:00", "09:10", "10:30", ...]
 * }
 * @response 500 {"msg": "[error]Error al mostrar los profesionales"}
 */
    public function professional_reservations_time(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $nombreDia = ucfirst(strtolower(Carbon::parse($data['data'])->locale('es_ES')->dayName));
            $horario = Schedule::where('branch_id', $data['branch_id'])->where('day', $nombreDia)->first();
            $start_time = Carbon::parse($horario->start_time)->format('H:i');
            $closing_time = Carbon::parse($horario->closing_time)->format('H:i');
            $reservations = [];

            $currentDateTime =  Carbon::now();
            //  if (Carbon::parse($data['data'])->isToday()) {
            if (Carbon::parse($data['data'])->isToday() && $currentDateTime->format('H:i') >= '01:05') {
                $professional = Professional::where('professionals.id', $data['professional_id'])
                    ->whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->with(['reservations' => function ($query) use ($data) {
                        $query->whereDate('data', $data['data'])
                            ->orderBy('start_time')
                            ->whereIn('confirmation', [1, 4])
                            ->whereHas('tail', function ($subquery) {
                                $subquery->where('aleatorie', '!=', 1);
                            });
                    }])/*->whereIn('state', [1, 2])*/->join('branch_professional', function ($join) use ($data) {
                        $join->on('professionals.id', '=', 'branch_professional.professional_id')
                            ->where('branch_professional.branch_id', '=', $data['branch_id'])
                            /*->where('branch_professional.arrival', '!=', NULL)*/;
                    })
                    ->select(
                        'professionals.id',
                        'professionals.name',
                        'professionals.surname',
                        'professionals.second_surname',
                        'professionals.email',
                        'professionals.phone',
                        'professionals.charge_id',
                        'professionals.state',
                        'professionals.image_url',
                        'branch_professional.arrival',
                        'branch_professional.living',
                        'branch_professional.numberRandom'
                    )->first();
                if ($professional == null) {
                    $startTime = Carbon::parse($start_time);
                    //$horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                    $closingTime = Carbon::parse($closing_time);
                    while ($startTime <= $closingTime) {
                        $reservations[] = $startTime->format('H:i');
                        $startTime->addMinutes(10);
                    }
                    sort($reservations);
                    return response()->json(['reservations' => $reservations], 200);
                } else {
                    if ($professional->reservations->isNotEmpty()) {
                        $reservations = $professional->reservations->filter(function ($reservation) {
                            // Filtrar las relaciones 'tails' para que 'aleatorio' sea distinto de 1
                            return $reservation->tail && $reservation->tail->aleatorio != 1;
                        })->map(function ($reservation) use ($start_time) {
                            $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                            $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                            $intervalos = [$startFormatted];
                            $startTime = Carbon::parse($startFormatted);

                            $finalTime = Carbon::parse($reservation->final_hour);
                            $finalMinutes = $finalTime->minute;

                            if ($finalMinutes <= 10) {
                                $roundedMinutes = '05';
                            } elseif ($finalMinutes <= 20) {

                                $roundedMinutes = '15';
                            } elseif ($finalMinutes <= 30) {
                                $roundedMinutes = '25';
                            } elseif ($finalMinutes <= 40) {
                                $roundedMinutes = '35';
                            } elseif ($finalMinutes <= 50) {
                                $roundedMinutes = '45';
                            } elseif ($finalMinutes <= 59) {
                                $roundedMinutes = '55';
                            } else {
                                $finalTime->addHour();
                                $roundedMinutes = '00';
                            }

                            $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                            $finalTime = Carbon::parse($finalFormatted);
                            $horaActual = Carbon::now();
                            $horaActualMas2Horas = $horaActual->copy()->addHours(1);

                            // Si $finalTime es menor que la hora actual más 2 horas, asignar la hora actual más 2 horas a $finalTime
                            if ($finalTime->lessThan($horaActualMas2Horas)) {
                                $finalTime = $horaActualMas2Horas;
                            }
                            while ($startTime->addMinutes(10) <= $finalTime) {
                                $intervalos[] = $startTime->format('H:i');
                            }

                            return $intervalos;
                        })->flatten()->values()->all();
                        $firstReservationStartTime = Carbon::parse($professional->reservations->first()->start_time);
                        $horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                        if ($horaActualMas2Horas->lessThan($firstReservationStartTime)) {
                            $startTime = Carbon::parse($start_time);
                            while ($startTime <= $horaActualMas2Horas) {
                                $reservations[] = $startTime->format('H:i');
                                $startTime->addMinutes(10);
                            }
                        } else {
                            $startTime = Carbon::parse($start_time);
                            while ($startTime <= $horaActualMas2Horas) {
                                $reservations[] = $startTime->format('H:i');
                                $startTime->addMinutes(10);
                            }
                        }

                        sort($reservations);
                        return response()->json(['reservations' => $reservations], 200);
                    } else {
                        $startTime = Carbon::parse($start_time);
                        $horaActualMas2Horas = $currentDateTime->copy()->addHours(1);
                        $closingTime = Carbon::parse($horaActualMas2Horas);
                        while ($startTime <= $closingTime) {
                            $reservations[] = $startTime->format('H:i');
                            $startTime->addMinutes(10);
                        }
                        sort($reservations);
                        return response()->json(['reservations' => $reservations], 200);
                    }
                }
            } else {
                $professional = Professional::where('id', $data['professional_id'])
                    ->whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->with(['reservations' => function ($query) use ($data) {
                        $query->whereDate('data', $data['data'])->orderBy('start_time')->whereIn('confirmation', [1, 4]);
                    }])
                    ->first();
                if ($professional && $professional->reservations->isNotEmpty()) {
                    $reservations = $professional->reservations->map(function ($reservation) {
                        $startFormatted = Carbon::parse($reservation->start_time)->format('H:i');
                        $finalMinutes = Carbon::parse($reservation->final_hour)->minute;

                        $intervalos = [$startFormatted];
                        $startTime = Carbon::parse($startFormatted);

                        $finalTime = Carbon::parse($reservation->final_hour);
                        $finalMinutes = $finalTime->minute;

                        if ($finalMinutes <= 10) {
                            $roundedMinutes = '05';
                        } elseif ($finalMinutes <= 20) {

                            $roundedMinutes = '15';
                        } elseif ($finalMinutes <= 30) {
                            $roundedMinutes = '25';
                        } elseif ($finalMinutes <= 40) {
                            $roundedMinutes = '35';
                        } elseif ($finalMinutes <= 50) {
                            $roundedMinutes = '45';
                        } elseif ($finalMinutes <= 59) {
                            $roundedMinutes = '55';
                        } else {
                            $finalTime->addHour();
                            $roundedMinutes = '00';
                        }


                        $finalFormatted = $finalTime->format('H:') . $roundedMinutes;
                        $finalTime = Carbon::parse($finalFormatted);
                        $horaActual = Carbon::now();
                        // Agregar las horas intermedias de 15 en 15 minutos
                        while ($startTime->addMinutes(10) <= $finalTime) {
                            $intervalos[] = $startTime->format('H:i');
                        }

                        return $intervalos;
                    })->flatten()->values()->all();
                }
                sort($reservations);
                return response()->json(['reservations' => $reservations], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los profesionales"], 500);
        }
    }

    public function professionals_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $professional = $this->professionalService->professionals_branch($data['branch_id'], $data['professional_id']);

            return response()->json(['professional_branch' => $professional], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    /**
 * Obtiene profesionales asignados a una sucursal con relaciones completas.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "Professionals no pertenece a esta Sucursal"}
 */
    public function branch_professionals(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = $this->professionalService->branch_professionals($data['branch_id']);
   
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    /**
 * Obtiene profesionales de una sucursal (formato web).
 *
 * Incluye marca de tiempo en la imagen para evitar caché.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "professionals": [
 *     { "id": 10, "name": "Carlos", "charge": "Barbero", "image_url": "professionals/10.jpg?$2025-11-21 17:00:00" }
 *   ]
 * }
 * @response 500 {"msg": "Professionals no pertenece a esta Sucursal"}
 */
    public function branch_professionals_web(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->get()->map(function ($query) use ($now) {
                return [
                    'id' => $query->id,
                    'name' => $query->name,
                    'charge' => $query->charge->name,
                    'image_url' => $query->image_url . '?$' . $now,
                    'email' => $query->email
                ];
            });

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    /**
 * Obtiene solo los cajeros asignados a una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "professionals": [
 *     { "id": 20, "name": "Ana Martínez Silva", "charge": "Cajero (a)" }
 *   ]
 * }
 * @response 500 {"msg": "Professionals no pertenece a esta Sucursal"}
 */
    public function branch_professionals_cashier(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Cajero (a)');
            })->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'name' => $query->name . ' ' . $query->surname . ' ' . $query->second_surname,
                    'charge' => $query->charge->name
                ];
            });

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    /**
 * Obtiene profesionales disponibles para un conjunto de servicios en una sucursal.
 *
 * Usa lógica de disponibilidad del `ProfessionalService`.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam services array required Lista de IDs de servicios. Example: [1, 5, 8]
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function branch_professionals_service(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $professionals = $this->professionalService->branch_professionals_service($data['branch_id'], $servs);

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    public function branch_professionals_service1(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $professionals = $this->professionalService->branch_professionals_service($data['branch_id'], $servs);

            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    /**
 * Versión alternativa de disponibilidad de profesionales por servicio (totem).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam services array required Lista de IDs de servicios. Example: [1, 5, 8]
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function branch_professionals_service_tottem(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $professionals = $this->professionalService->branch_professionals_service_tottem1($data['branch_id'], $servs);
  
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }
    
    /**
 * Nueva lógica de disponibilidad de profesionales por servicio.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam services array required Lista de IDs de servicios. Example: [1, 5, 8]
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function branch_professionals_serviceNew(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $professionals = $this->professionalService->branch_professionals_serviceNew($data['branch_id'], $servs);
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    /**
 * Obtiene profesionales que ofrecen un servicio específico en una sucursal.
 *
 * @authenticated
 * @queryParam service_id integer required ID del servicio. Example: 5
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "Professionals"}
 */
    public function get_professionals_service(Request $request)
    {
        try {
            $data = $request->validate([
                'service_id' => 'required|numeric',
                'branch_id' => 'required|numeric'

            ]);
            $professionals = $this->professionalService->get_professionals_service($data);
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Professionals"], 500);
        }
    }

    /**
 * Calcula las ganancias diarias de un profesional en un período.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @queryParam day string required Día de la semana (nombre en español). Example: "Lunes"
 * @queryParam startDate date required Fecha de inicio. Example: "2025-11-01"
 * @queryParam endDate date required Fecha de fin. Example: "2025-11-30"
 *
 * @response 200 { "earningByDay": [...] }
 * @response 500 {"msg": "[error]Profssional no obtuvo ganancias en este período"}
 */
    public function professionals_ganancias(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'day' => 'required',
                'startDate' => 'required|date',
                'branch_id' => 'required|numeric',
                'endDate' => 'required|date'
            ]);
            $ganancias = $this->professionalService->professionals_ganancias($data);
            return response()->json(['earningByDay' => $ganancias], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Profssional no obtuvo ganancias en este período"], 500);
        }
    }

    /**
 * Obtiene ganancias de un profesional en una sucursal.
 *
 * Soporta tres modos:
 * - Sin parámetros: ganancias del día actual
 * - Con `mes` y `year`: ganancias mensuales
 * - Con `startDate` y `endDate`: ganancias en rango
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam charge string optional Cargo del profesional. Example: "Barbero"
 * @queryParam mes integer optional Mes (1–12). Example: 11
 * @queryParam year integer optional Año. Example: 2025
 * @queryParam startDate date optional Fecha de inicio. Example: "2025-11-01"
 * @queryParam endDate date optional Fecha de fin. Example: "2025-11-30"
 *
 * @response 200 { "earningPeriodo": [...] }
 * @response 500 {"msg": "[error]Profssional no obtuvo ganancias en este período"}
 */
    public function professionals_ganancias_branch(Request $request)
    {
        try {
            $data = $request->validate([

                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'charge' => 'nullable'
            ]);
            if ($request->has('mes')) {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_month($data, $request->mes, $request->year)], 200, [], JSON_NUMERIC_CHECK);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_Periodo($data, $request->startDate, $request->endDate)], 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json(['earningPeriodo' => $this->professionalService->professionals_ganancias_branch_date($data)], 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Profssional no obtuvo ganancias en este período"], 500);
        }
    }

    /**
 * Crea un nuevo profesional y su usuario asociado.
 *
 * La imagen es opcional; si no se adjunta, se usa `professionals/default.jpg`.
 *
 * @authenticated
 * @bodyParam name string required Nombre. Max: 250. Example: "Carlos"
 * @bodyParam surname string required Primer apellido. Max: 50. Example: "Pérez"
 * @bodyParam second_surname string required Segundo apellido. Max: 50. Example: "García"
 * @bodyParam email string required Correo único. Example: "carlos@example.com"
 * @bodyParam phone string required Teléfono. Max: 15. Example: "+56912345678"
 * @bodyParam charge_id integer required ID del cargo. Example: 2
 * @bodyParam user_id integer required ID del usuario. Example: 25
 * @bodyParam image_url file optional Foto del profesional.
 *
 * @response 200 {"msg": "Profesional insertado correctamente"}
 * @response 500 {"msg": "[error]Error al insertar el professional"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|max:250',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:100|email|unique:professionals',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric'
            ]);
            $professional = new Professional();
            $professional->name = $data['name'];
            //$professional->surname = $data['surname'];
            //$professional->second_surname = $data['second_surname'];
            $professional->email = $data['email'];
            $professional->phone = $data['phone'];
            $professional->charge_id = $data['charge_id'];
            $professional->user_id = $data['user_id'];
            $professional->state = 0;
            $professional->save();
            $filename = "professionals/default.jpg";
            if ($request->hasFile('image_url')) {
                $filename = $request->file('image_url')->storeAs('professionals', $professional->id . '.' . $request->file('image_url')->extension(), 'public');
            }
            $professional->image_url = $filename;
            $professional->save();

            return response()->json(['msg' => 'Profesional insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' =>  $th->getMessage() . 'Error al insertar el professional'], 500);
        }
    }

    /**
 * Actualiza solo el estado de un profesional.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 10
 * @bodyParam state integer required Nuevo estado (0 = inactivo, 1 = activo, etc.). Example: 1
 *
 * @response 200 {"msg": "Estado del Profesional actualizado correctamente"}
 * @response 500 {"msg": "[error]Error al actualizar el estado professional"}
 */
    public function update_state(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'nullable|numeric',
                'state' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);

            $professional->state = $data['state'];
            //$professional->image_url = $filename;
            $professional->save();

            return response()->json(['msg' => 'Estado del Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el estado professional'], 500);
        }
    }

    /**
 * Verifica si un email corresponde a un técnico en una sucursal.
 *
 * @authenticated
 * @queryParam email string required Correo del profesional. Example: "carlos@example.com"
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Error al actualizar el estado professional"}
 */
    public function verifi_tec_profe(Request $request)
    {
        try {

            $data = $request->validate([
                'email' => 'required',
                'branch_id' => 'required|numeric'
            ]);
            $professionals = $this->professionalService->verifi_tec_prof($data['email'], $data['branch_id']);
            return response()->json(['professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el estado professional'], 500);
        }
    }

    /**
 * Actualiza un profesional existente (y su usuario).
 *
 * Valida unicidad de email y nombre de usuario. Permite actualizar imagen.
 *
 * @authenticated
 * @bodyParam id integer required ID del profesional. Example: 10
 * @bodyParam name string required Nombre. Max: 50. Example: "Carlos"
 * @bodyParam email string required Correo. Example: "carlos@example.com"
 * @bodyParam phone string required Teléfono. Max: 15. Example: "+56912345678"
 * @bodyParam charge_id integer required ID del cargo. Example: 2
 * @bodyParam user_id integer required ID del usuario. Example: 25
 * @bodyParam user string required Nombre de usuario (debe ser único). Example: "carlos_barbero"
 * @bodyParam state integer required Estado. Example: 1
 * @bodyParam retention number required Porcentaje de retención. Example: 10
 * @bodyParam image_url file optional Nueva foto.
 *
 * @response 200 {"msg": "Profesional actualizado correctamente"}
 * @response 400 {"msg": "Usuario ya existe"}
 * @response 401 {"msg": ["El correo ya ha sido tomado."]}
 * @response 500 {"msg": "[error]Error al actualizar el professional"}
 */
    public function update(Request $request)
    {
        try {

            $professionals_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                //'surname' => 'required|max:50',
                //'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric',
                'user' => 'required|string',
                'state' => 'required|numeric',
                'retention' => 'required|numeric'
            ]);
            $professional = Professional::find($professionals_data['id']);
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:professionals,email,' . $professional->id,
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 401);
            }
            // $userName = User::where('name', $request->user)->where('id', '!=', $professionals_data['user_id'])->first();
            $userName = User::whereHas('professional')->where('name', $request->user)->where('id', '!=', $professionals_data['user_id'])->first();
            if ($userName) {
                return response()->json([
                    'msg' => 'Usuario ya existe'
                ], 400);
            }
            $user = User::find($professionals_data['user_id']);
            $user->name = $professionals_data['user'];
            $user->email = $professionals_data['email'];
            $user->save();

            if ($request->hasFile('image_url')) {
                if ($professional->image_url != 'professionals/default.jpg') {
                    $destination = public_path("storage\\" . $professional->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                $professional->image_url = $request->file('image_url')->storeAs('professionals', $professional->id . '.' . $request->file('image_url')->extension(), 'public');
            }
            $professional->name = $professionals_data['name'];
            //$professional->surname = $professionals_data['surname'];
            //$professional->second_surname = $professionals_data['second_surname'];
            $professional->email = $professionals_data['email'];
            $professional->phone = $professionals_data['phone'];
            $professional->charge_id = $professionals_data['charge_id'];
            $professional->state = $professionals_data['state'];
            $professional->retention = $professionals_data['retention'];

            //$professional->image_url = $filename;
            $professional->save();


            return response()->json(['msg' => 'Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el professional'], 500);
        }
    }

    /**
 * Elimina un profesional y su usuario asociado.
 *
 * ⚠️ Acción irreversible.
 *
 * @authenticated
 * @bodyParam id integer required ID del profesional. Example: 10
 *
 * @response 200 {"msg": "Profesional eliminado correctamente"}
 * @response 500 {"msg": "[error]Error al eliminar la professional"}
 */
    public function destroy(Request $request)
    {
        try {

            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $professional = Professional::find($professionals_data['id']);
            $user_id = $professional->user_id;
            Professional::destroy($professionals_data['id']);
            User::destroy($user_id);
            return response()->json(['msg' => 'Profesional eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la professional'], 500);
        }
    }

    /**
 * Obtiene profesionales disponibles para reasignar una reserva (vista de coordinador).
 *
 * Considera los servicios de la reserva y excluye al profesional actual si aplica.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @queryParam reservation_id integer required ID de la reserva. Example: 101
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Professionals con orden de disponibilidad"}
 */
    public function professionals_state_coordinador(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'reservation_id' => 'required|numeric'
            ]);
            $reservation = Reservation::find($data['reservation_id']);
            // Verificar si la reserva existe
            if (!$reservation) {
                return response()->json(['msg' => 'Reserva no encontrada'], 404);
            }

            // Verificar si la reserva tiene un car asociado
            if (!$reservation->car) {
                return response()->json(['msg' => 'No se encontró un car asociado a la reserva'], 404);
            }
            $professional_id = $reservation->car->clientProfessional->professional_id;
            $orders = Order::where('car_id', $reservation->car_id)->get()->pluck('branch_service_professional_id');
            $services = BranchServiceProfessional::whereIn('id', $orders)->get()->pluck('branch_service_id');
            $servs = BranchService::whereIn('id', $services)->pluck('service_id');
            $professionals = $this->professionalService->branch_professionals_service_tottem($data['branch_id'], $servs, $professional_id, $reservation);
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Professionals con orden de disponibilidad"], 500);
        }
    }

    /**
 * Obtiene el estado de disponibilidad de profesionales para una reserva.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @queryParam reservation_id integer required ID de la reserva. Example: 101
 *
 * @response 200 { "professionals": [...] }
 * @response 500 {"msg": "[error]Professionals no pertenece a esta Sucursal"}
 */
    public function professionals_state(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'reservation_id' => 'required|numeric'
            ]);
            $professional = $this->professionalService->professionals_state($data['branch_id'], $data['reservation_id']);
            return response()->json(['professionals' => $professional], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Professionals no pertenece a esta Sucursal"], 500);
        }
    }

    /**
 * Verifica si un email ya existe en profesionales o clientes.
 *
 * Devuelve información del cliente si existe, o "No" si está libre.
 *
 * @bodyParam email string required Correo a verificar. Example: "carlos@example.com"
 *
 * @response 200 {
 *   "user": 15,
 *   "clientName": "Carlos Pérez",
 *   "clientImage": "clients/15.jpg",
 *   "type": "Client"
 * }
 * @response 200 {
 *   "user": "",
 *   "type": "No"
 * }
 * @response 200 {
 *   "user": "",
 *   "type": "Professional"
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function professional_email(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:professionals'
            ]);
            if ($validator->fails()) {
                $user = '';
                $clientName = '';
                $clientImage = '';
                $type = 'Professional';
                return response()->json(['user' => $user, 'type' => $type], 200, [], JSON_NUMERIC_CHECK);
            }
            $client = Client::with('user')->where('email', $request->email)->first();
            //$professional = Professional::with('user')->where('email', $request->email)->first();
            if ($client != null) {
                $user = $client->user->id;
                $clientName = $client->name;
                $clientImage = $client->client_image;
                $type = 'Client';
            } else {
                $user = '';
                $clientName = '';
                $clientImage = '';
                $type = 'No';
            }
            return response()->json(['user' => $user, 'clientName' => $clientName, 'clientImage' => $clientImage, 'type' => $type], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }
}
