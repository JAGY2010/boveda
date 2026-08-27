<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empeno;
use App\Models\Negocio;
use App\Models\RangoNumero;
use App\Models\User;
use App\Support\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lo que el servidor tiene que garantizar para que se pueda trabajar sin
 * internet: repartir números de contrato que no choquen y aceptar después
 * lo que se registró estando desconectado.
 */
class SinConexionTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    private User $empleado;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::create([
            'nombre' => 'Compraventa Prueba', 'ciudad' => 'Isnos',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 10000000, 'consecutivo_inicial' => 100,
        ]);

        $this->empleado = User::factory()->create(['role' => 'employee', 'negocio_id' => $this->negocio->id]);
        $this->empleado->negocios()->attach($this->negocio->id);

        $this->cliente = $this->negocio->clientes()->create(['nombre' => 'Ana Ruiz']);
    }

    #[Test]
    public function test_un_dispositivo_aparta_su_bloque_de_numeros(): void
    {
        $this->actingAs($this->empleado)
            ->postJson(route('numeros.reservar'), ['dispositivo' => 'tablet-1', 'cuantos' => 20])
            ->assertOk()
            ->assertJson(['desde' => 100, 'hasta' => 119]);
    }

    #[Test]
    public function test_dos_dispositivos_no_comparten_numeros(): void
    {
        $uno = Ledger::reservarNumeros($this->negocio, 'tablet-1', 20);
        $dos = Ledger::reservarNumeros($this->negocio, 'tablet-2', 20);

        $this->assertSame(100, (int) $uno->desde);
        $this->assertSame(119, (int) $uno->hasta);
        // El segundo empieza donde termina el primero: no se pisan.
        $this->assertSame(120, (int) $dos->desde);
        $this->assertSame(139, (int) $dos->hasta);
    }

    #[Test]
    public function test_quien_tiene_internet_no_usa_numeros_ya_apartados(): void
    {
        Ledger::reservarNumeros($this->negocio, 'tablet-1', 20); // 100-119

        // Un empeño creado con conexión debe saltarse el bloque entero.
        $empeno = Ledger::crearEmpeno($this->negocio, $this->cliente->id, [
            'articulo' => 'Moto', 'principal' => 100000, 'pct' => 20, 'plazo' => 4,
        ]);

        $this->assertSame(120, (int) $empeno->numero);
    }

    #[Test]
    public function test_lo_registrado_sin_conexion_entra_con_su_numero_apartado(): void
    {
        $rango = Ledger::reservarNumeros($this->negocio, 'tablet-1', 20);

        /* La tablet creó el empeño sin señal usando el 100 de su bloque y lo
           imprimió. Al volver el internet, la cola lo envía tal cual. */
        $this->actingAs($this->empleado)->post(route('empenos.store'), [
            'cliente_id' => $this->cliente->id,
            'categoria' => 'Moto',
            'articulo' => 'Moto Yamaha',
            'principal' => 100000, 'pct' => 20, 'plazo' => 4,
            'numero' => $rango->desde,
            '_clave' => 'op-sin-conexion-1',
        ])->assertRedirect();

        $this->assertSame(100, (int) Empeno::firstOrFail()->numero);
    }

    #[Test]
    public function test_reenviar_lo_de_la_cola_no_duplica_el_empeno(): void
    {
        $rango = Ledger::reservarNumeros($this->negocio, 'tablet-1', 20);

        $datos = [
            'cliente_id' => $this->cliente->id,
            'categoria' => 'Moto',
            'articulo' => 'Moto Yamaha',
            'principal' => 100000, 'pct' => 20, 'plazo' => 4,
            'numero' => $rango->desde,
            '_clave' => 'op-sin-conexion-2',
        ];

        // La cola reenvía porque no supo si la primera llegó.
        $this->actingAs($this->empleado)->post(route('empenos.store'), $datos);
        $this->actingAs($this->empleado)->post(route('empenos.store'), $datos);

        $this->assertSame(1, Empeno::count());
    }

    #[Test]
    public function test_el_bloque_es_de_un_local_y_no_afecta_a_otro(): void
    {
        Ledger::reservarNumeros($this->negocio, 'tablet-1', 20);

        $otro = Negocio::create([
            'nombre' => 'Otro', 'ciudad' => 'Pitalito',
            'plazo_default' => 4, 'pct_default' => 20, 'ltv_default' => 50,
            'caja' => 1000000, 'consecutivo_inicial' => 100,
        ]);

        $this->assertSame(100, Ledger::siguienteNumero($otro));
    }

    #[Test]
    public function test_la_pantalla_de_respaldo_carga_sin_sesion(): void
    {
        // El service worker la muestra cuando no hay copia guardada, y ahí
        // no hay servidor que valide la sesión.
        $this->get(route('offline'))
            ->assertOk()
            ->assertSee('Sin internet');
    }

    #[Test]
    public function test_el_manifiesto_y_el_service_worker_se_sirven(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('js/offline.js'));
        $this->assertFileExists(public_path('js/numeros.js'));
        $this->assertFileExists(public_path('js/idempotencia.js'));

        $manifiesto = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('Bóveda', $manifiesto['name']);
        $this->assertSame('standalone', $manifiesto['display']);
    }

    #[Test]
    public function test_el_service_worker_no_guarda_documentos_ni_la_sonda(): void
    {
        /* Un recibo o un contrato guardado en cache podria imprimirse con
           cifras viejas, y /health tiene que decir la verdad del momento. */
        $sw = (string) file_get_contents(public_path('sw.js'));

        foreach (['health', 'recibo', 'contrato', 'acta'] as $ruta) {
            $this->assertStringContainsString($ruta, $sw);
        }
    }

    #[Test]
    public function test_al_cerrar_sesion_se_borran_las_pantallas_del_dispositivo(): void
    {
        /* Quedan datos de clientes y de plata guardados en el equipo; el
           siguiente que entre no puede verlos sin contrasena. */
        $sw = (string) file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('olvidarPantallas', $sw);
        $this->assertStringContainsString("dato.tipo === 'olvidar'", $sw);

        $offline = (string) file_get_contents(public_path('js/offline.js'));
        $this->assertStringContainsString('logout', $offline);
        // Pero la cola de pendientes tiene que sobrevivir: es plata del negocio.
        $this->assertStringNotContainsString('deleteDatabase', $sw);
    }

    #[Test]
    public function test_la_cola_no_borra_lo_que_no_llego_a_entrar(): void
    {
        /* El caso peligroso: la sesion caduca mientras el equipo esta sin
           senal. Al reenviar, el servidor manda al login y fetch —que sigue
           las redirecciones— devuelve un 200. Si la cola se fiara de ese 200
           borraria el abono dandolo por hecho, y nadie se enteraria. */
        $sw = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('necesitaSesion', $sw);
        $this->assertStringContainsString('419', $sw);
        $this->assertStringContainsString("r.url.indexOf('/login')", $sw);

        // Una operacion rechazada tampoco se borra en silencio.
        $this->assertStringContainsString('op.rechazada = true', $sw);
    }

    #[Test]
    public function test_se_reintenta_aunque_el_navegador_se_crea_conectado(): void
    {
        /* Si el que se cae es el SERVIDOR y no la red, el evento 'online'
           nunca se dispara. Sin un reintento por tiempo, lo pendiente se
           quedaria parado hasta que alguien cambie de pantalla. */
        $offline = (string) file_get_contents(public_path('js/offline.js'));

        $this->assertStringContainsString('setInterval', $offline);
        $this->assertStringContainsString('30000', $offline);
    }

    #[Test]
    public function test_el_servidor_rechaza_lo_reenviado_sin_sesion(): void
    {
        // Sin sesion, un abono reenviado no puede entrar por la puerta de atras.
        $this->post('/separados/1/abonar', ['monto' => 50000, '_clave' => 'sin-sesion'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function test_solo_se_reserva_dentro_del_local_propio(): void
    {
        $rango = Ledger::reservarNumeros($this->negocio, 'tablet-1', 5);

        $this->assertSame($this->negocio->id, (int) $rango->negocio_id);
        $this->assertSame(1, RangoNumero::count());
    }
}
