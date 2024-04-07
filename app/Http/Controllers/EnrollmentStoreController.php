<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\EnrollmentStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrollmentStoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Asignar almacén a una academia");

        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric',
                'store_id' => 'required|numeric'
            ]);
            $enrollment = Enrollment::find($data['enrollment_id']);
            $store = Store::find($data['store_id']);

            $enrollment->stores()->attach($store->id);

            return response()->json(['msg' => 'Almacén asignado correctamente a la academia'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>'Error al asignar el producto a este almacén'], 500);
    }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            $enrollmentStores = EnrollmentStore::where('enrollment_id', $data['enrollment_id'])->get()->map(function ($enrollmentStore) {
                return [
                    'id' => $enrollmentStore->id,
                    'address' => $enrollmentStore->store->address,
                    'description' => $enrollmentStore->store->description,
                    'reference' => $enrollmentStore->store->reference,
                    'store_id' => $enrollmentStore->store_id
                ];
            });
            return response()->json(['enrollmentStores' => $enrollmentStores], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el almacén en esta sucursal'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EnrollmentStore $enrollmentStore)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric',
                'enrollment_id' => 'required|numeric'
            ]);
            $store = Store::find($data['store_id']);
            $enrollment = Enrollment::find($data['enrollment_id']);
            Log::info('$store');
            Log::info($store);
            Log::info('$enrollment');
            Log::info($enrollment);
            $enrollment->stores()->detach($store->id);
            return response()->json(['msg' => 'Almacén eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al eliminar el almacén en esta academia'], 500);
        }
    }
}
