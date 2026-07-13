<?php

namespace Database\Seeders;

use App\Models\Negocio;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $playita = Negocio::create([
            'nombre' => 'Compraventa La Playita',
            'ciudad' => 'Isnos - Huila',
            'nit' => '1.084.258.602-9',
            'representante' => 'Yuri Yisset Sacanboy Muñoz',
            'direccion' => 'Calle 6 No. 5-43',
            'telefono' => '312 556 7832',
            'consecutivo_inicial' => 5364,
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'sms_activo' => true, 'caja' => 8000000,
        ]);

        $progreso = Negocio::create([
            'nombre' => 'Compraventa El Progreso',
            'ciudad' => 'Pitalito - Huila',
            'nit' => '901.234.567-1',
            'representante' => 'María Elena Ortiz',
            'direccion' => 'Carrera 4 No. 7-22',
            'telefono' => '311 445 9900',
            'consecutivo_inicial' => 1000,
            'plazo_default' => 4, 'pct_default' => 18, 'ltv_default' => 50,
            'sms_activo' => true, 'caja' => 5000000,
        ]);

        // Usuarios (contraseña: "password")
        $admin = User::factory()->create([
            'name' => 'Julian (Admin)', 'email' => 'admin@boveda.test', 'role' => 'admin', 'negocio_id' => null,
        ]);
        $dueno = User::factory()->create([
            'name' => 'Dueño', 'email' => 'dueno@boveda.test', 'role' => 'owner', 'negocio_id' => $playita->id,
        ]);
        $empleado = User::factory()->create([
            'name' => 'Empleado', 'email' => 'empleado@boveda.test', 'role' => 'employee', 'negocio_id' => $playita->id,
        ]);

        // Accesos: el dueño ve AMBOS locales; el empleado solo La Playita.
        $dueno->negocios()->attach([$playita->id, $progreso->id]);
        $empleado->negocios()->attach([$playita->id]);

        // --- Datos de La Playita ---
        $c1 = $playita->clientes()->create(['nombre' => 'Juan Carlos Muñoz', 'cedula' => '1.083.912.334', 'tel' => '320 445 7788', 'direccion' => 'Cra 5 # 4-21']);
        $c2 = $playita->clientes()->create(['nombre' => 'María Fernanda Rojas', 'cedula' => '1.083.556.201', 'tel' => '311 902 3341', 'direccion' => 'Calle 6 # 8-10']);
        $c3 = $playita->clientes()->create(['nombre' => 'Pedro Antonio Guzmán', 'cedula' => '4.612.889', 'tel' => '312 667 1120', 'direccion' => 'Vereda El Rosal']);

        $anio = now()->year;

        $e1 = Ledger::crearEmpeno($playita, $c1->id, [
            'articulo' => 'Moto Yamaha 150 KAB-12C', 'categoria' => 'Moto',
            'atributos' => ['marca' => 'Yamaha', 'cilindraje' => '150', 'anio' => (string) ($anio - 3), 'placa' => 'KAB-12C'],
            'serial' => 'MOTOR 5D9-1194xx', 'principal' => 1200000, 'pct' => 20, 'plazo' => 4,
            'inicio' => now()->subDays(70)->toDateString(),
        ]);
        Ledger::pagarInteres($e1);
        Ledger::pagarInteres($e1);

        Ledger::crearEmpeno($playita, $c2->id, [
            'articulo' => 'Celular Apple (iPhone) iPhone 12 128 GB', 'categoria' => 'Celular',
            'atributos' => ['marca' => 'Apple (iPhone)', 'modelo' => 'iPhone 12', 'capacidad' => '128 GB', 'anio' => (string) ($anio - 2)],
            'serial' => 'IMEI 3557xx', 'principal' => 400000, 'pct' => 20, 'plazo' => 4,
            'inicio' => now()->subDays(18)->toDateString(),
        ]);

        $e3 = Ledger::crearEmpeno($playita, $c3->id, [
            'articulo' => 'Televisor LG 50 4K UHD', 'categoria' => 'Televisor',
            'atributos' => ['marca' => 'LG', 'pulgadas' => '50', 'resolucion' => '4K UHD'],
            'serial' => 'S/N 208MXxx', 'principal' => 600000, 'pct' => 20, 'plazo' => 4,
            'inicio' => now()->subDays(135)->toDateString(),
        ]);
        Ledger::pagarInteres($e3);

        // Empeño VENCIDO (5 meses sin pagar) -> estado "Por perder" + botón "Pasar a inventario"
        $c5 = $playita->clientes()->create(['nombre' => 'Carlos Pérez Díaz', 'cedula' => '12.345.678', 'tel' => '320 111 2233', 'direccion' => 'Calle 2 # 1-10']);
        Ledger::crearEmpeno($playita, $c5->id, [
            'articulo' => 'Equipo de sonido Sony', 'categoria' => 'Otro',
            'atributos' => ['desc' => 'Equipo de sonido Sony'],
            'principal' => 350000, 'pct' => 20, 'plazo' => 4,
            'inicio' => now()->subDays(150)->toDateString(),
        ]);

        // --- Datos de El Progreso (para ver el consolidado) ---
        $c4 = $progreso->clientes()->create(['nombre' => 'Sandra Milena López', 'cedula' => '1.075.443.210', 'tel' => '300 221 5566', 'direccion' => 'Calle 8 # 3-40']);
        $e4 = Ledger::crearEmpeno($progreso, $c4->id, [
            'articulo' => 'Moto Honda 125', 'categoria' => 'Moto',
            'atributos' => ['marca' => 'Honda', 'cilindraje' => '125', 'anio' => (string) ($anio - 1)],
            'serial' => 'MOTOR JC61Exx', 'principal' => 900000, 'pct' => 18, 'plazo' => 4,
            'inicio' => now()->subDays(25)->toDateString(),
        ]);
        Ledger::pagarInteres($e4);
    }
}
