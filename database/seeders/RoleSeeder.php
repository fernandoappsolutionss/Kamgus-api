<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //lista de permisos generales
        Permission::create(['name' => 'Escritorio', 'guard_name' => 'api']);
        Permission::create(['name' => 'crear usuarios', 'guard_name' => 'api']);
        Permission::create(['name' => 'listar usuarios', 'guard_name' => 'api']);
        Permission::create(['name' => 'eliminar usuarios', 'guard_name' => 'api']);
        Permission::create(['name' => 'actualizar usuarios', 'guard_name' => 'api']);
        Permission::create(['name' => 'crear servicios', 'guard_name' => 'api']); //lo lleva el cliente tambien
        Permission::create(['name' => 'listar todos los servicios activos', 'guard_name' => 'api']); //admin
        Permission::create(['name' => 'listar todos los servicios programados', 'guard_name' => 'api']); //admin
        Permission::create(['name' => 'listar todo el historial de servicios', 'guard_name' => 'api']);
        Permission::create(['name' => 'filtrar servicios administrador', 'guard_name' => 'api']); //permiso solo para administradores
        Permission::create(['name' => 'eliminar servicios', 'guard_name' => 'api']);
        Permission::create(['name' => 'actualizar servicios', 'guard_name' => 'api']); //lo lleva el conductor y el cliente tambien
        Permission::create(['name' => 'crear conductores', 'guard_name' => 'api']);
        Permission::create(['name' => 'listar conductores','guard_name' => 'api']);
        Permission::create(['name' => 'eliminar conductores', 'guard_name' => 'api']);
        Permission::create(['name' => 'actualizar conductores', 'guard_name' => 'api']);
        Permission::create(['name' => 'crear reservas', 'guard_name' => 'api']);
        Permission::create(['name' => 'listar reservas', 'guard_name' => 'api']); //lo lleva el conductor y las empresas tambien
        Permission::create(['name' => 'eliminar reservas', 'guard_name' => 'api']);
        Permission::create(['name' => 'actualizar reservas', 'guard_name' => 'api']); //lo lleva el conductor tambien y la empresa
        Permission::create(['name' => 'crear empresas', 'guard_name' => 'api']);
        Permission::create(['name' => 'listar empresas', 'guard_name' => 'api']);
        Permission::create(['name' => 'eliminar empresas', 'guard_name' => 'api']);
        Permission::create(['name' => 'actualizar empresas', 'guard_name' => 'api']);
        Permission::create(['name' => 'agregar configuracion', 'guard_name' => 'api']);
        Permission::create(['name' => 'listar configuraciones', 'guard_name' => 'api']);
        Permission::create(['name' => 'eliminar configuraciones', 'guard_name' => 'api']);
        Permission::create(['name' => 'actualizar configuraciones', 'guard_name' => 'api']);
        Permission::create(['name' => 'ver perfil', 'guard_name' => 'api']); //lo lleva el conductor, la empresa y los clientes tambien
        Permission::create(['name' => 'actualizar perfil', 'guard_name' => 'api']);  //lo lleva el conductor, la empresa y los clientes tambien
        Permission::create(['name' => 'listar pagos', 'guard_name' => 'api']); //lo lleva el conductor tambien y la empresa
        Permission::create(['name' => 'historial', 'guard_name' => 'api']); //lo lleva el conductor, la empresa y el cliente
        Permission::create(['name' => 'soporte', 'guard_name' => 'api']); //lo lleva el conductor y el cliente tambien
        Permission::create(['name' => 'billetera', 'guard_name' => 'api']); //lo lleva el conductor y el cliente tambien
        Permission::create(['name' => 'terminos y condiciones', 'guard_name' => 'api']); //lo lleva el cliente también
        Permission::create(['name' => 'referidos', 'guard_name' => 'api']); //lo lleva el cliente también
        Permission::create(['name' => 'flota', 'guard_name' => 'api']); //lo llevan las empresas y el administrador
        Permission::create(['name' => 'agregar vehiculo', 'guard_name' => 'api']); //lo lleva el conductor
        Permission::create(['name' => 'listar vehiculos', 'guard_name' => 'api']); //lo lleva el conductor
        Permission::create(['name' => 'eliminar vehiculos', 'guard_name' => 'api']); //lo lleva el conductor
        Permission::create(['name' => 'actualizar vehiculos', 'guard_name' => 'api']);//lo lleva el conductor



        //creando el rol administrador y agregando todos los permisos
        $role = Role::create(['name' => 'Administrador', 'guard_name' => 'api']);
        $role->givePermissionTo(Permission::all());

        //creando el rol de conductor y agregando todos los permisos necesarios
        $role = Role::create(['name' => 'Conductor', 'guard_name' => 'api']);
        $role->givePermissionTo([
            'actualizar servicios', 'ver perfil', 
            'actualizar perfil', 'listar pagos', 'listar reservas',
            'historial', 'soporte', 'billetera'
        ]);

        //creando el rol de cliente y agregando todos los permisos necesarios
        $role = Role::create(['name' => 'Cliente', 'guard_name' => 'api']);
        $role->givePermissionTo([
            'crear servicios', 'actualizar servicios', 'ver perfil', 
            'actualizar perfil','historial', 'soporte', 'billetera', 'terminos y condiciones', 'referidos'
        ]);

        //creando el rol de empresa y agregando todos los permisos necesarios
        $role = Role::create(['name' => 'Empresa', 'guard_name' => 'api']);
        $role->givePermissionTo([
            'crear servicios', 'actualizar servicios', 'ver perfil', 
            'actualizar perfil','historial', 'soporte', 'billetera', 'terminos y condiciones', 'referidos'
        ]);
    }
}
