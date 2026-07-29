<?php

use App\Http\Controllers\Admin\LocalController as AdminLocalController;
use App\Http\Controllers\Admin\UsuarioController as AdminUsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ConsolidadoController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EliminacionController;
use App\Http\Controllers\EmpenoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LocalController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Middleware\CompartirLocales;
use App\Http\Middleware\VerificarSuscripcion;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::middleware(['auth', 'verified', CompartirLocales::class, VerificarSuscripcion::class])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('local/cambiar', [LocalController::class, 'cambiar'])->name('local.cambiar');
    Route::post('local/salir', [LocalController::class, 'salir'])->name('local.salir');
    Route::get('consolidado', [ConsolidadoController::class, 'index'])->name('consolidado');

    // Pantalla que ve el dueño/empleado cuando el local está vencido o suspendido.
    Route::get('suscripcion/bloqueada', [SuscripcionController::class, 'bloqueada'])->name('suscripcion.bloqueada');

    Route::get('clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('clientes/nuevo', [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');

    Route::get('empenos', [EmpenoController::class, 'index'])->name('empenos.index');
    Route::get('empenos/nuevo', [EmpenoController::class, 'create'])->name('empenos.create');
    Route::post('empenos', [EmpenoController::class, 'store'])->name('empenos.store');
    Route::get('empenos/{empeno}', [EmpenoController::class, 'show'])->name('empenos.show');
    Route::post('empenos/{empeno}/pago', [EmpenoController::class, 'pago'])->name('empenos.pago');
    Route::post('empenos/{empeno}/retirar', [EmpenoController::class, 'retirar'])->name('empenos.retirar');
    Route::post('empenos/{empeno}/perder', [EmpenoController::class, 'perder'])->name('empenos.perder');
    Route::get('empenos/{empeno}/contrato', [EmpenoController::class, 'contrato'])->name('empenos.contrato');
    Route::get('empenos/{empeno}/acta', [EmpenoController::class, 'acta'])->name('empenos.acta');
    Route::delete('empenos/{empeno}', [EmpenoController::class, 'destroy'])->name('empenos.destroy');
    Route::put('empenos/{empeno}/datos', [EmpenoController::class, 'actualizarDatos'])->name('empenos.datos');
    Route::delete('pagos/{pago}', [EmpenoController::class, 'deshacerPago'])->name('pagos.deshacer');
    Route::get('pagos/{pago}/recibo', [EmpenoController::class, 'recibo'])->name('pagos.recibo');

    // Historial de empeños eliminados (solo dueño/admin)
    Route::get('eliminaciones', [EliminacionController::class, 'index'])->name('eliminaciones.index');

    Route::get('inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::post('inventario/comprar', [InventarioController::class, 'comprar'])->name('inventario.comprar');
    Route::post('inventario/{item}/vender', [InventarioController::class, 'vender'])->name('inventario.vender');

    Route::get('contabilidad', [ContabilidadController::class, 'index'])->name('contabilidad.index');
    Route::post('contabilidad/capital', [ContabilidadController::class, 'capital'])->name('contabilidad.capital');
    Route::post('contabilidad/gasto', [ContabilidadController::class, 'gasto'])->name('contabilidad.gasto');

    Route::get('reporte', [ReporteController::class, 'index'])->name('reporte.index');

    Route::get('configuracion', [ConfiguracionController::class, 'edit'])->name('config.edit');
    Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('config.update');

    // El dueño gestiona los empleados de su local
    Route::get('equipo', [EquipoController::class, 'index'])->name('equipo.index');
    Route::get('equipo/nuevo', [EquipoController::class, 'create'])->name('equipo.create');
    Route::post('equipo', [EquipoController::class, 'store'])->name('equipo.store');

    // Panel de administración (solo admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('locales', [AdminLocalController::class, 'index'])->name('locales.index');
        Route::get('locales/nuevo', [AdminLocalController::class, 'create'])->name('locales.create');
        Route::post('locales', [AdminLocalController::class, 'store'])->name('locales.store');
        Route::get('locales/{negocio}', [AdminLocalController::class, 'show'])->name('locales.show');
        Route::post('locales/{negocio}/renovar', [AdminLocalController::class, 'renovar'])->name('locales.renovar');
        Route::post('locales/{negocio}/suspender', [AdminLocalController::class, 'suspender'])->name('locales.suspender');
        Route::post('locales/{negocio}/reactivar', [AdminLocalController::class, 'reactivar'])->name('locales.reactivar');

        Route::get('usuarios', [AdminUsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('usuarios/nuevo', [AdminUsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('usuarios', [AdminUsuarioController::class, 'store'])->name('usuarios.store');
        Route::post('usuarios/{usuario}/password', [AdminUsuarioController::class, 'resetPassword'])->name('usuarios.password');
    });
});

require __DIR__.'/settings.php';
