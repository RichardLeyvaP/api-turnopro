<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        Log::info('entra a buscar las notificaciones por professional');
        try {
            return response()->json(['notifications' => Notification::with('professional', 'branch')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
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
               'description' => 'required|string'
           ]);
           
           $professional = Professional::find($data['professional_id']);
           $branch = Branch::find($data['branch_id']);
           $notification = new Notification();
            $notification->professional_id = $professional->id;
            $notification->tittle = $data['tittle'];
            $notification->description = $data['description'];
            $branch->notifications()->save($notification);
                
           return response()->json(['msg' => 'Notifications creada correctamente'], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Notificacion creada correctamente"], 500);
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
               'description' => 'required|string'
           ]);
           
           $professional = Professional::find($data['professional_id']);
           $branch = Branch::find($data['branch_id']);
           $notification = new Notification();
            $notification->professional_id = $professional->id;
            $notification->tittle = $data['tittle'];
            $notification->description = $data['description'];
            $notification->state = 3;
            $branch->notifications()->save($notification);
                
           return response()->json(['msg' => 'Notifications creada correctamente desde Coordinador o Responsable '], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Notificacion no fue creada dio error "], 500);
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
                    'professionalName' => $query->professional->name.' '.$query->professional->surname.' '.$query->professional->surname,
                    'state' => $query->state,
                    'created_at' => $query->created_at->format('Y-m-d h:i:s A'),
                    'updated_at' => $query->updated_at->format('Y-m-d h:i:s A')
                ];
            });
                
           return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar las notifocaciones"], 500);
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
           
           $branch = Branch::find($data['branch_id']);
           $professional = Professional::find($data['professional_id']);
            // $notifications = $branch->notifications()->where('professional_id', $professional->id)->get()->map(function ($query) {
            //     return [
            //         'id' => $query->id,
            //         'professional_id' => $query->professional_id,
            //         'branch_id' => $query->branch_id,
            //         'tittle' => $query->tittle,
            //         'description' => $query->description,
            //         'state' => $query->state,
            //         'created_at' => $query->created_at->format('Y-m-d h:i:s A'),
            //         'updated_at' => $query->updated_at->format('Y-m-d h:i:s A')
            //     ];
            // })->sortByDesc('created_at')
            // ->sortByDesc(function ($notification) {
            //     return $notification['created_at']->format('H:i:s');
            // })
            // ->values();
            $notifications = $branch->notifications()
    ->where('professional_id', $professional->id)
    ->get()
    ->map(function ($query) {
        return [
            'id' => $query->id,
            'professional_id' => $query->professional_id,
            'branch_id' => $query->branch_id,
            'tittle' => $query->tittle,
            'description' => $query->description,
            'state' => $query->state,
            'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i:s A'),
            'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i:s A')
        ];
    })
    ->sortByDesc(function ($notification) {
        return $notification['created_at'];
    })
    ->values();
                
           return response()->json(['notifications' => $notifications], 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar las notifocaciones"], 500);
       }
    }

    public function update(Request $request)
    {
        Log::info('Modificar el estado de una notificacion');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
           ]);
           
           $branch = Branch::find($data['branch_id']);
           $professional = Professional::find($data['professional_id']);
           $branch->notifications()->where('professional_id', $professional->id)->update(['state' => 1]);
           return response()->json(['msg' => 'Notificacion modificada correctamente'], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Estado de la nitificacion modificado correctamente"], 500);
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
            return response()->json(['msg' => $th->getMessage()."Estado de la notificacion modificado correctamente"], 500);
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
           return response()->json(['msg' => $th->getMessage()."Error al eliminar la notificacion"], 500);
       }
    }
}
