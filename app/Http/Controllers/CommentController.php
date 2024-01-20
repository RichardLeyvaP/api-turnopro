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
            //$car = Car::join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'professionals.*', 'cars.*']);
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
            $filename = "image/default.png";
            if ($request->hasFile('image_look')) {
                $filename = $request->file('image_url')->storeAs('comments',$request->file('image_look')->getClientOriginalName(),'public');
             }
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $client_professional_id = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first()->pivot->id;
            $comment = new Comment();
            $comment->client_professional_id = $client_professional_id;
            $comment->data = Carbon::now();
            $comment->look = $data['look'];
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
            if ($request->hasFile('image_look')) {
               $filename = $request->file('image_url')->storeAs('comments',$request->file('image_look')->getClientOriginalName(),'public');
            }
            $reservation = Reservation::find($data['reservation_id']);
            $client_professional_id = $reservation->car->clientProfessional->id;
            $comment = new Comment();
            $comment->client_professional_id = $client_professional_id;
            $comment->data = Carbon::now();
            $comment->look = $data['look'];
            $comment->image_look = $filename;
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
            if ($comment->image_look) {
                $destination=public_path("storage\\".$comment->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                if ($request->hasFile('image_look')) {
                    $filename =$request->file('image_url')->storeAs('comments',$request->file('image_look')->getClientOriginalName(),'public');
                }
            $comment->look = $data['look'];
            $comment->image_look = $filename;
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
            if ($comment->image_look) {
                $destination=public_path("storage\\".$comment->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                $comment->destroy();
            return response()->json(['msg' => 'Comment eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al eliminar el comment'], 500);
        }
    }
}
