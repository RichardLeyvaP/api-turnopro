<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Support\Facades\Log as Logger;
use Exception;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(Permission::all(), 200);
        } catch (Exception $e) {
            Logger::info('PermissionController->index');
            Logger::error($e->getMessage());
            return response()->json('Error Interno del servidor', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|unique:permissions,name',
                'module' => 'required'
            ]);

            Permission::create(
                [
                    'name' => $request->name,
                    'module' => $request->module,
                    'description' => $request->description
                ]
            );
            return response()->json('Se guardó correctamente el permiso', 200);
        } catch (Exception $e) {
            Logger::info('RoleController->store');
            Logger::error($e->getMessage());
            return response()->json('Error Interno del servidor', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(Permission::whereId($data['id'])->first(), 200);
        } catch (Exception $e) {
            Logger::error('PermissionController->show');
            Logger::error($e->getMessage());
            return response()->json('Error Interno del servidor', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|numeric',
                'name' => 'required',
                'module' => 'required'
            ]);

            $permission = Permission::find($request->id);
            $permission->name = $request->name;
            $permission->module = $request->module;
            $permission->description = $request->description;
            $permission->save();
            return response()->json('Se actualizó correctamente el permiso', 200);
        } catch (Exception $e) {
            Logger::error('PermissionController->update');
            Logger::error($e->getMessage());
            return response()->json('Error Interno del servidor', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|numeric'
            ]);
            Permission::find($request->id)->delete();
            return response()->json('Se eliminó correctamente el permiso', 200);
        } catch (Exception $e) {
            Logger::error('PermissionController->destroy');
            Logger::error($e->getMessage());
            return response()->json('Error Interno del servidor', 500);
        }
    }
}
