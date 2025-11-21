<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Comment;
use App\Models\Professional;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{

    /**
 * Lista todos los comentarios con sus relaciones.
 *
 * Incluye información del cliente y del profesional asociados a través de la relación `clientProfessional`.
 *
 * @authenticated
 *
 * @response 200 {
 *   "comments": [
 *     {
 *       "id": 1,
 *       "client_professional_id": 3,
 *       "look": "Profesional muy atento",
 *       "client_look": "comments/1.jpg",
 *       "data": "2025-11-21 14:30:00",
 *       "clientProfessional": {
 *         "id": 3,
 *         "client": { "id": 5, "name": "Yasmany Sánchez" },
 *         "professional": { "id": 2, "name": "Dr. López" }
 *       }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los carros"}
 */
    public function index()
    {
        try {             
            $comments = Comment::with('clientProfessional.client', 'clientProfessional.professional')->get();
            return response()->json(['comments' => $comments], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    /**
 * Crea un nuevo comentario asociado a un cliente y un profesional.
 *
 * Requiere los IDs directos del cliente y profesional. Adjunta opcionalmente una imagen del cliente.
 *
 * @authenticated
 * @bodyParam client_id integer required ID del cliente. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 2
 * @bodyParam look string required Texto del comentario. Example: "Excelente atención"
 * @bodyParam client_look file optional Imagen del cliente
 *
 * @response 200 {"msg": "Comment guardado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error al guardar el comentario"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'look' => 'required'
            ]); 
            $comment = new Comment();
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $client_professional_id = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first()->pivot->id;
            
            $comment->client_professional_id = $client_professional_id;
            $comment->data = Carbon::now();
            $comment->look = $data['look'];
            $comment->save();
            
            $filename = "comments/default.jpg";
            if ($request->hasFile('client_look')) {
                $filename = $request->file('client_look')->storeAs('comments',$comment->id.'.'.$request->file('client_look')->extension(),'public');
                //$client = Client::find($comment->clientProfessional->client->id);
                $client->client_image = $filename;
                $client->save();
             }
            $comment->client_look = $filename;
            $comment->save();
            return response()->json(['msg' => 'Comment guardado correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' =>$th->getMessage().'Error al guardar el comentario'], 500);
        }
    }

    /**
 * Crea un comentario a partir de un ID de reserva.
 *
 * Finaliza automáticamente la reserva y su cola asociada (`tail`). Actualiza la imagen del cliente si se proporciona.
 *
 * @authenticated
 * @bodyParam reservation_id integer required ID de la reserva existente. Example: 10
 * @bodyParam look string required Texto del comentario.
 * @bodyParam client_look file optional Imagen del cliente.
 *
 * @response 200 {"msg": "Comment guardado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error al el comentario"}
 */
    public function storeByReservationId(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'reservation_id' => 'required|numeric|exists:reservations,id',
                'look' => 'required'
            ]); 
            $filename = "comments/default_profile.jpg";
            
            $comment = new Comment();
            $reservation = Reservation::findOrFail($data['reservation_id']);
            $client_professional_id = $reservation->car->clientProfessional->id;
            $comment->client_professional_id = $client_professional_id;
            $comment->data = Carbon::now();
            $comment->look = $data['look'];
            $comment->save();

            if ($request->hasFile('client_look')) {
               $filename = $request->file('client_look')->storeAs('comments',$comment->id.'.'.$request->file('client_look')->extension(),'public');
               $client = Client::withTrashed()->find($reservation->car->clientProfessional->client_id);
               $client->client_image = $filename;
                $client->save();
            }          
            $comment->client_look = $filename;
            $comment->save();

            $reservation->finished_at = now();
            $reservation->confirmation = 2;
            $reservation->save();

            $tail = $reservation->tail;
            $tail->attended = 2;
            $tail->save();
            DB::commit();
            return response()->json(['msg' => 'Comment guardado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
        return response()->json(['msg' =>$th->getMessage().'Error al el comentario'], 500);
        }
    }

    /**
 * Crea un comentario automático a partir del ID de un carro (car).
 *
 * Finaliza la reserva y su cola asociada sin requerir texto de comentario (usa mensaje predeterminado).
 * No permite subir imagen personalizada; usa imagen por defecto.
 *
 * @authenticated
 * @bodyParam car_id integer required ID del carro asociado a una reserva. Example: 7
 *
 * @response 200 {"msg": "Comment guardado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error al el comentario"}
 */
    public function storeByCarId(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'car_id' => 'required|numeric|exists:cars,id'
            ]); 
            $filename = "comments/default_profile.jpg";
            
            $comment = new Comment();
            $reservation = Reservation::where('car_id', $data['car_id'])->firstOrFail();
            $client_professional_id = $reservation->car->clientProfessional->id;
            $comment->client_professional_id = $client_professional_id;
            $comment->data = Carbon::now();
            $comment->look = "Cliente finalizado desde la caja";
            $comment->client_look = $filename;
            $comment->save();

            $reservation->finished_at = now();
            $reservation->confirmation = 2;
            $reservation->save();

            $tail = $reservation->tail;
            $tail->attended = 2;
            $tail->save();
            DB::commit();
            return response()->json(['msg' => 'Comment guardado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
        return response()->json(['msg' =>$th->getMessage().'Error al el comentario'], 500);
        }
    }

    /**
 * Muestra un comentario específico por su ID.
 *
 * Incluye las relaciones completas de cliente y profesional.
 *
 * @authenticated
 * @queryParam id integer required ID del comentario. Example: 1
 *
 * @response 200 {
 *   "branch": {
 *     "id": 1,
 *     "look": "Muy satisfecho",
 *     "clientProfessional": {
 *       "client": { "name": "Yasmany Sánchez" },
 *       "professional": { "name": "Dr. López" }
 *     }
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el comment"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['branch' => Comment::with('clientProfessional.client', 'clientProfessional.professional')->find($data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el comment"], 500);
        }
    }

    /**
 * Actualiza un comentario existente.
 *
 * Permite modificar el texto y reemplazar la imagen del cliente. Elimina la imagen anterior si no es la predeterminada.
 *
 * @authenticated
 * @bodyParam id integer required ID del comentario. Example: 1
 * @bodyParam look string required Nuevo texto del comentario. Example: "Actualizado: muy buen servicio"
 * @bodyParam client_look file optional Nueva imagen del cliente.
 *
 * @response 200 {"msg": "Comment actualizado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error al actualizar el comments"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required',
                'look' => 'required'
            ]); 
            $comment = Comment::find($data['id']);
                $filename = $comment->client_look;
                if ($request->hasFile('client_look')) {
                    if($comment->client_look != 'comments/default.jpg'){
                    $destination = public_path("storage\\" . $comment->client_look);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }              
                        $filename = $request->file('client_look')->storeAs('comments',$comment->id.'.'.$request->file('client_look')->extension(),'public');
                        $client = Client::find($comment->clientProfessional->client_id);
                        $client->client_image = $filename;
                        $client->save();
                    }
                }
            $comment->look = $data['look'];
            $comment->client_look = $filename;
            $comment->save();
            return response()->json(['msg' => 'Comment actualizado correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' =>$th->getMessage().'Error al actualizar el comments'], 500);
        }
    }

    /**
 * Elimina un comentario y su imagen asociada (si existe).
 *
 * Restaura la imagen del cliente a la predeterminada tras la eliminación.
 *
 * @authenticated
 * @bodyParam id integer required ID del comentario a eliminar. Example: 1
 *
 * @response 200 {"msg": "Comment eliminado correctamente"}
 * @response 500 {"msg": "[mensaje de error]Error al eliminar el comment"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required'
            ]); 
            $comment = Comment::find($data['id']);
            if ($comment->client_look != "comments/default.jpg") {
                $destination=public_path("storage\\".$comment->client_look);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                $client = Client::find($comment->clientProfessional->client->id);
            $client->client_image = "comments/default.jpg";
            $client->save();
                Comment::destroy($data['id']);
            return response()->json(['msg' => 'Comment eliminado correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' =>$th->getMessage().'Error al eliminar el comment'], 500);
        }
    }
}
