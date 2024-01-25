<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Comment;
use App\Models\Professional;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $comments = Comment::with('clientProfessional.client', 'clientProfessional.professional')->get();
            return response()->json(['comments' => $comments], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional");
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
            
            $filename = "image/default.png";
            if ($request->hasFile('client_look')) {
                $filename = $request->file('client_look')->storeAs('comments',$comment->id.'.'.$request->file('client_look')->extension(),'public');
             }
            $comment->image_look = $filename;
            $comment->save();
            return response()->json(['msg' => 'Comment guardado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al guardar el comentario'], 500);
        }
    }

    public function storeByReservationId(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional");
        try {
            $data = $request->validate([
                'reservation_id' => 'required|numeric',
                'look' => 'required'
            ]); 
            $filename = "image/default.png";
            
            $comment = new Comment();
            $reservation = Reservation::find($data['reservation_id']);
            $client_professional_id = $reservation->car->clientProfessional->id;
            $comment->client_professional_id = $client_professional_id;
            $comment->data = Carbon::now();
            $comment->look = $data['look'];
            $comment->save();

            if ($request->hasFile('client_look')) {
               $filename = $request->file('client_look')->storeAs('comments',$comment->id.'.'.$request->file('client_look')->extension(),'public');
            }          
            $comment->client_look = $filename;
            $comment->save();
            return response()->json(['msg' => 'Comment guardado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al el comentario'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['branch' => Comment::with('clientProfessional.client', 'clientProfessional.professional')->find($data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el comment"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info("Editar Comment");
        try {
            $data = $request->validate([
                'id' => 'required',
                'look' => 'required'
            ]); 
            $comment = Comment::find($data['id']);
            if ($comment->client_look) {
                $destination=public_path("storage\\".$comment->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                if ($request->hasFile('client_look')) {
                    $filename =$request->file('client_look')->storeAs('comments',$comment->id.'.'.$request->file('client_look')->extension(),'public');
                }
            $comment->look = $data['look'];
            $comment->client_look = $filename;
            $comment->save();
            return response()->json(['msg' => 'Comment actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al actualizar el comments'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar Comment");
        try {
            $data = $request->validate([
                'id' => 'required'
            ]); 
            $comment = Comment::find($data['id']);
            if ($comment->client_look != "image/default.png") {
                $destination=public_path("storage\\".$comment->client_look);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                Comment::destroy($data['id']);
            return response()->json(['msg' => 'Comment eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al eliminar el comment'], 500);
        }
    }
}
