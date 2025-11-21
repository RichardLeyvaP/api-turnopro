<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Support\Facades\Log as Logger;
use Exception;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
 * Lista todos los permisos del sistema.
 *
 * Retorna una colección completa de permisos con sus atributos (`id`, `name`, `module`, `description`).
 *
 * @authenticated
 *
 * @response 200 [
 *   {
 *     "id": 1,
 *     "name": "Crear usuarios",
 *     "module": "Usuarios",
 *     "description": "Permite registrar nuevos usuarios en el sistema"
 *   },
 *   {
 *     "id": 2,
 *     "name": "Eliminar reservas",
 *     "module": "Reservas",
 *     "description": "Permite cancelar reservas de clientes"
 *   }
 * ]
 * @response 500 "Error Interno del servidor"
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
 * Crea un nuevo permiso.
 *
 * El nombre del permiso debe ser único en la base de datos.
 *
 * @authenticated
 * @bodyParam name string required Nombre único del permiso. Example: "Editar cursos"
 * @bodyParam module string required Módulo al que pertenece el permiso. Example: "Academia"
 * @bodyParam description string optional Descripción del permiso. Example: "Permite modificar la información de los cursos"
 *
 * @response 200 "Se guardó correctamente el permiso"
 * @response 422 { "name": ["The name has already been taken."] }
 * @response 500 "Error Interno del servidor"
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
 * Muestra los detalles de un permiso específico.
 *
 * @authenticated
 * @queryParam id integer required ID del permiso. Example: 1
 *
 * @response 200 {
 *   "id": 1,
 *   "name": "Crear usuarios",
 *   "module": "Usuarios",
 *   "description": "Permite registrar nuevos usuarios en el sistema"
 * }
 * @response 500 "Error Interno del servidor"
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
 * Actualiza un permiso existente.
 *
 * Requiere el `id` del permiso a modificar. El nombre **no** se valida como único en esta operación (puede causar duplicados si no se gestiona externamente).
 *
 * @authenticated
 * @bodyParam id integer required ID del permiso. Example: 1
 * @bodyParam name string required Nuevo nombre del permiso. Example: "Registrar usuarios"
 * @bodyParam module string required Nuevo módulo. Example: "Gestión de Usuarios"
 * @bodyParam description string optional Nueva descripción. Example: "Permite crear nuevos perfiles de usuario"
 *
 * @response 200 "Se actualizó correctamente el permiso"
 * @response 500 "Error Interno del servidor"
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
 * Elimina un permiso del sistema.
 *
 * ⚠️ **Advertencia**: Esta acción es irreversible y puede afectar roles que dependan de este permiso.
 *
 * @authenticated
 * @bodyParam id integer required ID del permiso a eliminar. Example: 1
 *
 * @response 200 "Se eliminó correctamente el permiso"
 * @response 500 "Error Interno del servidor"
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
