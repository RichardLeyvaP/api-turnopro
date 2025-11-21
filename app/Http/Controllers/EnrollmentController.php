<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Knuckles\Scribe\Attributes\Endpoint;

class EnrollmentController extends Controller
{
    
    /**
 * Obtiene todas las academias registradas con sus negocios asociados.
 *
 * @authenticated
 *
 * @response 200 {
 *   "enrollments": [
 *     {
 *       "id": 1,
 *       "name": "Academia Central",
 *       "description": "Formación profesional en barbería",
 *       "business_id": 1,
 *       "image_data": "enrollments/1.jpg",
 *       "business": {
 *         "id": 1,
 *         "name": "Barbería Central"
 *       }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las academias"}
 */
    public function index()
    {
        try {
            return response()->json(['enrollments' => Enrollment::with(['business'])->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las academias"], 500);
        }
    }

    /**
 * Registra una nueva academia.
 *
 * @authenticated
 * @bodyParam name string required Nombre de la academia. Example: Academia Sur
 * @bodyParam description string required Descripción. Example: Centro de formación en técnicas capilares
 * @bodyParam business_id integer required ID del negocio al que pertenece. Example: 1
 * @bodyParam location string optional Coordenadas GPS (lat,lng). Example: -33.456789,-70.645678
 * @bodyParam address string optional Dirección. Example: Avenida Siempre Viva 742
 * @bodyParam phone string optional Teléfono. Example: +5359380373
 * @bodyParam image_data file optional Logo o imagen de la academia.
 *
 * @response 200 {"msg": "Academia insertada correctamente"}
 * @response 500 {"msg": "Error al insertar la academia"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',
                'description' => 'required',
                'business_id' => 'required|numeric',
                'location' => 'nullable',
                'address' => 'nullable',
                'phone' => 'nullable'
            ]);
            
            $enrolment = new Enrollment();
            $enrolment->name = $data['name'];
            $enrolment->description = $data['description'];
            $enrolment->business_id = $data['business_id'];
            $enrolment->location = $data['location'];
            $enrolment->address = $data['address'];
            $enrolment->phone = $data['phone'];
            $enrolment->save();
            $filename = "enrollments/default.jpg";
            if ($request->hasFile('image_data')) {
                $filename = $request->file('image_data')->storeAs('enrollments', $enrolment->id . '.' . $request->file('image_data')->extension(), 'public');
            }
            $enrolment->image_data = $filename;
            $enrolment->save();
            return response()->json(['msg' => 'Academia insertada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar la academia'], 500);
        }
    }

    /**
 * Obtiene las academias asociadas a un negocio específico.
 *
 * @authenticated
 * @queryParam business_id integer required ID del negocio. Example: 1
 *
 * @response 200 {
 *   "enrollments": [
 *     {
 *       "id": 1,
 *       "name": "Academia Central",
 *       "description": "...",
 *       "business_id": 1,
 *       "image_data": "enrollments/1.jpg",
 *       "business": { ... }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las academias"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            $enrollments = Enrollment::where('business_id', $data['business_id'])->with(['business'])->get();
            return response()->json(['enrollments' => $enrollments], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las academias"], 500);
        }
    }

    /**
 * Actualiza los datos de una academia existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la academia. Example: 1
 * @bodyParam name string required Nuevo nombre. Example: Academia Premium
 * @bodyParam description string required Nueva descripción. Example: Formación avanzada en barbería
 * @bodyParam business_id integer required ID del negocio. Example: 1
 * @bodyParam location string optional Nuevas coordenadas GPS. Example: -33.456789,-70.645678
 * @bodyParam address string optional Nueva dirección. Example: Calle Nueva 123
 * @bodyParam phone string optional Nuevo teléfono. Example: +5351234567
 * @bodyParam image_data file optional Nueva imagen.
 *
 * @response 200 {"msg": "Academia actualizada correctamente"}
 * @response 500 {"msg": "Error al actualizar la academia"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'business_id' => 'required|numeric',
                'location' => 'nullable',
                'address' => 'nullable',
                'phone' => 'nullable'
            ]);
            $enrollment = Enrollment::find($data['id']);
            if ($request->hasFile('image_data')) {
                if($enrollment->image_data != 'enrollments/default.jpg'){
                $destination = public_path("storage\\" . $enrollment->image_data);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                }
                $enrollment->image_data = $request->file('image_data')->storeAs('enrollments', $enrollment->id . '.' . $request->file('image_data')->extension(), 'public');
            }
            $enrollment->name = $data['name'];
            $enrollment->description = $data['description'];
            $enrollment->business_id = $data['business_id'];
            $enrollment->location = $data['location'];
            $enrollment->address = $data['address'];
            $enrollment->phone = $data['phone'];
            $enrollment->save();

            return response()->json(['msg' => 'Academia actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al actualizar la academia'], 500);
        }
    }

    /**
 * Elimina una academia del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID de la academia. Example: 1
 *
 * @response 200 {"msg": "academia eliminada correctamente"}
 * @response 500 {"msg": "Error al eliminar la academia"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $enrollment = Enrollment::find($data['id']);
            if ($enrollment->image_data != "enrollments/default.jpg") {
                $destination = public_path("storage\\" . $enrollment->image_data);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            Enrollment::destroy($data['id']);

            return response()->json(['msg' => 'academia eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la academia'], 500);
        }
    }
}
