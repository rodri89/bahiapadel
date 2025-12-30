<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Torneo;
use App\Jugadore;
use App\Partido;
use App\Grupo;
use Image;

use Session;

class HomeController extends Controller
{

    use AuthenticatesUsers;

    protected $redirectTo = '/homes';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        //$this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('index');
    }

    function adminHome2(){
        return View('padel.admin.home'); 
    }

    function adminHome(){
        return View('bahia_padel.admin.index'); 
    }

    function adminHomeBp() {
        return View('bahia_padel.admin.index'); 
    }
    function adminJugadores() {
        return View('bahia_padel.admin.jugadores.index'); 
    }
    function adminVivo() {
        return View('bahia_padel.admin.vivo.index'); 
    }
    function adminTorneos() {
        return View('bahia_padel.admin.torneo.index'); 
    }
    function adminFotos() {
        return View('bahia_padel.admin.fotos.index'); 
    }

    function registrarTorneo(Request $request) {
        $id = $request->id_torneo;
        if($id == 0){
            $torneo = new Torneo;            
            $torneo->activo = 1;
        } else {
            $torneo = Torneo::find($id);
        }
        
        if($request->nombre != null)        
            $torneo->nombre = $request->nombre;
        else
            $torneo->nombre = '';                

        if($request->tipo != null)        
            $torneo->tipo = $request->tipo;
        else
            $torneo->tipo = '';                
        
        if($request->fechaInicio != null)
            $torneo->fecha_inicio = $request->fechaInicio;
        else
            $torneo->fecha_inicio = '2000-01-01';

        if($request->fechaFin != null)
            $torneo->fecha_fin = $request->fechaFin;
        else
            $torneo->fecha_fin = '2000-01-01';
                
        if($request->premio1 != null)
            $torneo->premio_1 = $request->premio1;
        else
            $torneo->premio_1 = '';

        if($request->premio2 != null)
            $torneo->premio_2 = $request->premio2;
        else
            $torneo->premio_2 = '';

        if($request->descripcion != null)
            $torneo->descripcion = $request->descripcion;
        else
            $torneo->descripcion = '';

        $torneo->es_torneo_individual = $request->tipo_torneo;        
        $torneo->categoria = $request->categoria;
        $torneo->imagen = '';
        
        // Guardar tipo de torneo (americano, puntuable, suma)
        if($request->tipo_torneo_formato != null) {
            $torneo->tipo_torneo_formato = $request->tipo_torneo_formato;
        } else {
            $torneo->tipo_torneo_formato = 'puntuable'; // Por defecto
        }

        $torneo->save();

        return response()->json(array('torneo'=>$torneo));
    }

    function getTorneos(Request $request) {
        $torneos = DB::table('torneos')                                                                
                        ->where('torneos.activo', 1)        
                        ->orderby('torneos.fecha_inicio')
                        ->get(); 
        
        return response()->json(array('torneos'=>$torneos));
    }

    public function adminTorneoSelected(Request $request) {
            $torneo = DB::table('torneos')                                                                
                            ->where('torneos.id', $request->torneo_id)                                
                            ->where('torneos.activo', 1)                                
                            ->first(); 
            
            if (!$torneo) {
                return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
            }
            
            $jugadores = DB::table('jugadores')                                                                                        
                            ->where('jugadores.activo', 1)                                
                            ->get();
            // Obtener grupos excluyendo los de eliminatoria (zona = 'ELIMINATORIA')
            // Los grupos de eliminatoria son solo para los cruces y no deben mostrarse en la configuración inicial
            $grupos = DB::table('grupos')
                            ->where('grupos.torneo_id', $request->torneo_id)
                            ->where('grupos.zona', '!=', 'ELIMINATORIA')
                            ->orderBy('grupos.zona')
                            ->orderBy('grupos.id')
                            ->get();
            
            // Determinar el tipo de torneo (por defecto puntuable si no existe)
            $tipoTorneo = isset($torneo->tipo_torneo_formato) ? $torneo->tipo_torneo_formato : 'puntuable';
            
            // Navegar a la vista correspondiente según el tipo de torneo
            if ($tipoTorneo == 'americano') {
                return View('bahia_padel.admin.torneo.armar_americano')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            } elseif ($tipoTorneo == 'suma') {
                return View('bahia_padel.admin.torneo.armar_suma')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            } else {
                // Puntuable (por defecto)
                return View('bahia_padel.admin.torneo.armar_torneo')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            }
    }

    function adminCrearJugador(Request $request) {        
        
        $jugador = new Jugadore;
        $jugador->activo = 1;                
        $jugador->nombre = $request->nombre;
        $jugador->apellido = $request->apellido;
        $jugador->telefono = $request->telefono ?? 0;
        $jugador->posicion = 0;
        $jugador->foto = 'images/jugador_img.png';
        
        // Manejar subida de foto
        if ($request->hasFile('foto')) {
            $image = $request->file('foto');
            $name = time() . '_' . $image->getClientOriginalName();
            $path = 'images/jugadores/' . $name;
            
            // Crear directorio si no existe
            $directory = public_path('images/jugadores');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Usar Image para procesar y guardar la imagen
            Image::make($image->getRealPath())->save(public_path($path));
            $jugador->foto = $path;
        }
        
        $jugador->save();

        return response()->json([
            'success' => true,
            'jugador' => $jugador
        ]);
    }

    function getJugadores(Request $request) {
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->orderBy('jugadores.nombre')
                        ->orderBy('jugadores.apellido')
                        ->get();
        
        return response()->json(['jugadores' => $jugadores]);
    }

    function adminEliminarJugador(Request $request) {
        $id = $request->id;
        
        $jugador = Jugadore::find($id);
        if (!$jugador) {
            return response()->json([
                'success' => false,
                'message' => 'Jugador no encontrado'
            ]);
        }
        
        // Marcar como inactivo en lugar de eliminar
        $jugador->activo = 0;
        $jugador->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Jugador eliminado correctamente'
        ]);
    }

    function adminEditarJugador(Request $request) {
        $id = $request->id;
        
        $jugador = Jugadore::find($id);
        if (!$jugador) {
            return response()->json([
                'success' => false,
                'message' => 'Jugador no encontrado'
            ]);
        }
        
        $jugador->nombre = $request->nombre;
        $jugador->apellido = $request->apellido;
        $jugador->telefono = $request->telefono ?? 0;
        
        // Manejar subida de foto solo si se envía una nueva
        if ($request->hasFile('foto')) {
            $image = $request->file('foto');
            $name = time() . '_' . $image->getClientOriginalName();
            $path = 'images/jugadores/' . $name;
            
            // Crear directorio si no existe
            $directory = public_path('images/jugadores');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Usar Image para procesar y guardar la imagen
            Image::make($image->getRealPath())->save(public_path($path));
            $jugador->foto = $path;
        }
        
        $jugador->save();
        
        return response()->json([
            'success' => true,
            'jugador' => $jugador
        ]);
    }

    public function guardarFechaAdminTorneo(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        $tieneCuatroParejas = $request->input('tiene_cuatro_parejas', 0) == 1;

        $grupos = \App\Grupo::where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->get();
        if($grupos->count() > 0 ) {
            foreach ($grupos as $grupo) {
                if ($grupo->partido_id) {
                    \App\Partido::where('id', $grupo->partido_id)->delete();
                }
                $grupo->delete();
            }                
            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->delete();
        }
        
        if ($tieneCuatroParejas && $request->pareja_4_idJugadorArriba && $request->pareja_4_idJugadorAbajo) {
            // ESTRUCTURA CON 4 PAREJAS: SEMIFINALES Y FINAL
            // Semifinal 1: Pareja 1 vs Pareja 2
            $partidoSF1 = $this->crearPartido();
            $grupoSF1_P1 = new Grupo;
            $grupoSF1_P1->torneo_id = $torneoId;
            $grupoSF1_P1->zona = $zona;
            $grupoSF1_P1->fecha = $request->input('pareja_1_partido_1_dia', '2000-01-01');
            $grupoSF1_P1->horario = $request->input('pareja_1_partido_1_horario', '00:00');
            $grupoSF1_P1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoSF1_P1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoSF1_P1->partido_id = $partidoSF1->id;
            $grupoSF1_P1->save();
            
            $grupoSF1_P2 = new Grupo;
            $grupoSF1_P2->torneo_id = $torneoId;
            $grupoSF1_P2->zona = $zona;
            $grupoSF1_P2->fecha = $request->input('pareja_2_partido_1_dia', '2000-01-01');
            $grupoSF1_P2->horario = $request->input('pareja_2_partido_1_horario', '00:00');
            $grupoSF1_P2->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoSF1_P2->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoSF1_P2->partido_id = $partidoSF1->id;
            $grupoSF1_P2->save();
            
            // Semifinal 2: Pareja 3 vs Pareja 4
            $partidoSF2 = $this->crearPartido();
            $grupoSF2_P3 = new Grupo;
            $grupoSF2_P3->torneo_id = $torneoId;
            $grupoSF2_P3->zona = $zona;
            $grupoSF2_P3->fecha = $request->input('pareja_3_partido_1_dia', '2000-01-01');
            $grupoSF2_P3->horario = $request->input('pareja_3_partido_1_horario', '00:00');
            $grupoSF2_P3->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoSF2_P3->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoSF2_P3->partido_id = $partidoSF2->id;
            $grupoSF2_P3->save();
            
            $grupoSF2_P4 = new Grupo;
            $grupoSF2_P4->torneo_id = $torneoId;
            $grupoSF2_P4->zona = $zona;
            $grupoSF2_P4->fecha = $request->input('pareja_4_partido_1_dia', '2000-01-01');
            $grupoSF2_P4->horario = $request->input('pareja_4_partido_1_horario', '00:00');
            $grupoSF2_P4->jugador_1 = $request->pareja_4_idJugadorArriba;
            $grupoSF2_P4->jugador_2 = $request->pareja_4_idJugadorAbajo;
            $grupoSF2_P4->partido_id = $partidoSF2->id;
            $grupoSF2_P4->save();
            
            // Final: Ganador SF1 vs Ganador SF2 (se crea pero sin jugadores asignados aún)
            $partidoFinal = $this->crearPartido();
            $grupoFinal = new Grupo;
            $grupoFinal->torneo_id = $torneoId;
            $grupoFinal->zona = $zona;
            $grupoFinal->fecha = $request->input('final_dia', '2000-01-01');
            $grupoFinal->horario = $request->input('final_horario', '00:00');
            $grupoFinal->jugador_1 = 0; // Se asignará después según resultados
            $grupoFinal->jugador_2 = 0;
            $grupoFinal->partido_id = $partidoFinal->id;
            $grupoFinal->save();
            
            // Consolación: Perdedor SF1 vs Perdedor SF2
            $partidoConsolacion = $this->crearPartido();
            $grupoConsolacion = new Grupo;
            $grupoConsolacion->torneo_id = $torneoId;
            $grupoConsolacion->zona = $zona;
            $grupoConsolacion->fecha = $request->input('consolacion_dia', '2000-01-01');
            $grupoConsolacion->horario = $request->input('consolacion_horario', '00:00');
            $grupoConsolacion->jugador_1 = 0; // Se asignará después según resultados
            $grupoConsolacion->jugador_2 = 0;
            $grupoConsolacion->partido_id = $partidoConsolacion->id;
            $grupoConsolacion->save();
            
            return response()->json(['success' => true, 'partidos' => [$partidoSF1->id, $partidoSF2->id, $partidoFinal->id, $partidoConsolacion->id]]);
        } else {
            // ESTRUCTURA CON 3 PAREJAS: TODOS CONTRA TODOS
            $partido1 = $this->crearPartido();
            $partido2 = $this->crearPartido();
            $partido3 = $this->crearPartido();        

            $grupoA1 = new Grupo;
            $grupoA1->torneo_id = $torneoId;
            $grupoA1->zona = $zona;
            $grupoA1->fecha = $request->input('pareja_1_partido_1_dia', '2000-01-01');
            $grupoA1->horario = $request->input('pareja_1_partido_1_horario', '00:00');
            $grupoA1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA1->partido_id = $partido1->id;
            $grupoA1->save();   
            
            $grupoA2 = new Grupo;
            $grupoA2->torneo_id = $torneoId;
            $grupoA2->zona = $zona;
            $grupoA2->fecha = $request->input('pareja_1_partido_2_dia', '2000-01-01');
            $grupoA2->horario = $request->input('pareja_1_partido_2_horario', '00:00');
            $grupoA2->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA2->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA2->partido_id = $partido2->id;
            $grupoA2->save();

            // PAREJA 2 ZONA Y PARTIDOS        
            $grupoA3 = new Grupo;
            $grupoA3->torneo_id = $torneoId;
            $grupoA3->zona = $zona;
            $grupoA3->fecha = $request->input('pareja_2_partido_1_dia', '2000-01-01');
            $grupoA3->horario = $request->input('pareja_2_partido_1_horario', '00:00');
            $grupoA3->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA3->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA3->partido_id = $partido1->id;
            $grupoA3->save();   
            
            $grupoA4 = new Grupo;
            $grupoA4->torneo_id = $torneoId;
            $grupoA4->zona = $zona;
            $grupoA4->fecha = $request->input('pareja_2_partido_2_dia', '2000-01-01');
            $grupoA4->horario = $request->input('pareja_2_partido_2_horario', '00:00');
            $grupoA4->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA4->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA4->partido_id = $partido3->id;
            $grupoA4->save();

            // PAREJA 3 ZONA Y PARTIDOS        
            $grupoA5 = new Grupo;
            $grupoA5->torneo_id = $torneoId;
            $grupoA5->zona = $zona;
            $grupoA5->fecha = $request->input('pareja_3_partido_1_dia', '2000-01-01');
            $grupoA5->horario = $request->input('pareja_3_partido_1_horario', '00:00');
            $grupoA5->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA5->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA5->partido_id = $partido2->id;
            $grupoA5->save();   
            
            $grupoA6 = new Grupo;
            $grupoA6->torneo_id = $torneoId;
            $grupoA6->zona = $zona;
            $grupoA6->fecha = $request->input('pareja_3_partido_2_dia', '2000-01-01');
            $grupoA6->horario = $request->input('pareja_3_partido_2_horario', '00:00');
            $grupoA6->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA6->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA6->partido_id = $partido3->id;
            $grupoA6->save();

            return response()->json(['success' => true, 'partidos' => [$partido1->id, $partido2->id, $partido3->id]]);
        }
    }

    function crearPartido() {
        $partido1 = new Partido;
        $partido1->pareja_1_set_1 = 0;
        $partido1->pareja_1_set_1_tie_break = 0;
        $partido1->pareja_2_set_1 = 0;
        $partido1->pareja_2_set_1_tie_break = 0;
        $partido1->pareja_1_set_2 = 0;
        $partido1->pareja_1_set_2_tie_break = 0;
        $partido1->pareja_2_set_2 = 0;
        $partido1->pareja_2_set_2_tie_break = 0;
        $partido1->pareja_1_set_3 = 0;
        $partido1->pareja_1_set_3_tie_break = 0;    
        $partido1->pareja_2_set_3 = 0;
        $partido1->pareja_2_set_3_tie_break = 0;
        $partido1->pareja_1_set_super_tie_break = 0;
        $partido1->pareja_2_set_super_tie_break = 0;
        $partido1->save();

        return $partido1;
    }

    /**
     * Ordena los partidos de manera que se intercalen las parejas
     * para evitar que la misma pareja aparezca en partidos consecutivos
     */
    private function ordenarPartidosIntercalados($partidos) {
        if (count($partidos) <= 1) {
            return $partidos;
        }
        
        $ordenados = [];
        $usados = [];
        $ultimaPareja1 = null;
        $ultimaPareja2 = null;
        
        // Función para obtener la key de una pareja
        $getParejaKey = function($pareja) {
            return $pareja['jugador_1'] . '_' . $pareja['jugador_2'];
        };
        
        // Función para verificar si un partido tiene alguna pareja en común con el último
        $tieneParejaComun = function($partido, $ultP1, $ultP2) use ($getParejaKey) {
            if (!$ultP1 || !$ultP2) return false;
            $key1 = $getParejaKey($partido['pareja_1']);
            $key2 = $getParejaKey($partido['pareja_2']);
            $ultKey1 = $getParejaKey($ultP1);
            $ultKey2 = $getParejaKey($ultP2);
            return ($key1 == $ultKey1 || $key1 == $ultKey2 || $key2 == $ultKey1 || $key2 == $ultKey2);
        };
        
        // Algoritmo: intentar siempre elegir un partido que no tenga parejas en común con el anterior
        while (count($ordenados) < count($partidos)) {
            $encontrado = false;
            
            // Primera pasada: buscar partidos sin parejas comunes
            foreach ($partidos as $index => $partido) {
                if (isset($usados[$index])) continue;
                
                if (!$tieneParejaComun($partido, $ultimaPareja1, $ultimaPareja2)) {
                    $ordenados[] = $partido;
                    $usados[$index] = true;
                    $ultimaPareja1 = $partido['pareja_1'];
                    $ultimaPareja2 = $partido['pareja_2'];
                    $encontrado = true;
                    break;
                }
            }
            
            // Si no se encontró uno sin parejas comunes, tomar el primero disponible
            if (!$encontrado) {
                foreach ($partidos as $index => $partido) {
                    if (isset($usados[$index])) continue;
                    
                    $ordenados[] = $partido;
                    $usados[$index] = true;
                    $ultimaPareja1 = $partido['pareja_1'];
                    $ultimaPareja2 = $partido['pareja_2'];
                    break;
                }
            }
        }
        
        return $ordenados;
    }

    public function guardarTorneoAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $grupos = $request->grupos; // Array de grupos con zona y parejas
        
        // Eliminar solo los grupos iniciales del torneo (sin partido_id)
        // NO eliminar los grupos que ya tienen partido_id, porque esos son los partidos ya creados
        $gruposExistentes = \App\Grupo::where('torneo_id', $torneoId)
                                        ->whereNull('partido_id')
                                        ->get();
        foreach ($gruposExistentes as $grupo) {
            $grupo->delete();
        }
        
        // Crear nuevos grupos
        foreach ($grupos as $grupoData) {
            $zona = $grupoData['zona'];
            $parejas = $grupoData['parejas'] ?? []; // Array de parejas [{jugador1: id, jugador2: id}, ...]
            
            // Para el torneo americano, guardamos cada pareja
            // NO crear partidos aquí, se crearán cuando se presione "Comenzar Torneo"
            if (count($parejas) > 0) {
                foreach ($parejas as $pareja) {
                    $grupo = new Grupo;
                    $grupo->torneo_id = $torneoId;
                    $grupo->zona = $zona;
                    $grupo->fecha = '2000-01-01'; // Fecha por defecto
                    $grupo->horario = '00:00'; // Horario por defecto
                    $grupo->jugador_1 = $pareja['jugador1'] ?? null;
                    $grupo->jugador_2 = $pareja['jugador2'] ?? null;
                    $grupo->partido_id = null; // Se asignará cuando se creen los partidos
                    $grupo->save();
                }
            }
        }
        
        return response()->json(['success' => true, 'message' => 'Torneo americano guardado correctamente']);
    }

    public function crearPartidosAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json([
                'success' => false,
                'message' => 'Torneo no encontrado'
            ]);
        }
        
        // Obtener solo los grupos iniciales del torneo (sin partido_id)
        // Estos son los grupos que se crearon al guardar el torneo, antes de crear los partidos
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNull('grupos.partido_id') // Solo grupos iniciales, sin partido_id
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona y extraer parejas únicas
        $parejasPorZona = [];
        $parejasUnicas = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($parejasPorZona[$zona])) {
                $parejasPorZona[$zona] = [];
            }
            if ($grupo->jugador_1 && $grupo->jugador_2) {
                $keyPareja = $zona . '-' . min($grupo->jugador_1, $grupo->jugador_2) . '-' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasUnicas[$keyPareja])) {
                    $parejasPorZona[$zona][] = [
                        'grupo_id' => $grupo->id,
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partido_id' => $grupo->partido_id
                    ];
                    $parejasUnicas[$keyPareja] = true;
                }
            }
        }
        
        // Crear todos los partidos posibles (todos contra todos) para cada zona
        foreach ($parejasPorZona as $zona => $parejas) {
            // Generar todas las combinaciones "todos contra todos"
            $combinaciones = [];
            for ($i = 0; $i < count($parejas); $i++) {
                for ($j = $i + 1; $j < count($parejas); $j++) {
                    $combinaciones[] = [
                        'pareja_1' => $parejas[$i],
                        'pareja_2' => $parejas[$j],
                    ];
                }
            }
            
            // Para cada combinación, verificar si existe partido, si no existe crearlo
            foreach ($combinaciones as $combo) {
                $pareja1 = $combo['pareja_1'];
                $pareja2 = $combo['pareja_2'];
                
                // Buscar si ya existe un partido con estas parejas
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id')
                             ->whereNotNull('g1.partido_id')
                             ->whereNotNull('g2.partido_id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->where(function($query) use ($pareja1, $pareja2) {
                        $query->where(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja1['jugador_1'])
                              ->where('g1.jugador_2', $pareja1['jugador_2'])
                              ->where('g2.jugador_1', $pareja2['jugador_1'])
                              ->where('g2.jugador_2', $pareja2['jugador_2']);
                        })
                        ->orWhere(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja2['jugador_1'])
                              ->where('g1.jugador_2', $pareja2['jugador_2'])
                              ->where('g2.jugador_1', $pareja1['jugador_1'])
                              ->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                // Solo crear si no existe
                if (!$partidoExistente) {
                    // Verificar una vez más que no exista antes de crear (doble verificación)
                    $verificacionFinal = DB::table('grupos as g1')
                        ->join('grupos as g2', function($join) {
                            $join->on('g1.partido_id', '=', 'g2.partido_id')
                                 ->whereRaw('g1.id != g2.id')
                                 ->whereNotNull('g1.partido_id')
                                 ->whereNotNull('g2.partido_id');
                        })
                        ->where('g1.torneo_id', $torneoId)
                        ->where('g1.zona', $zona)
                        ->where('g2.torneo_id', $torneoId)
                        ->where('g2.zona', $zona)
                        ->where(function($query) use ($pareja1, $pareja2) {
                            $query->where(function($q) use ($pareja1, $pareja2) {
                                $q->where('g1.jugador_1', $pareja1['jugador_1'])
                                  ->where('g1.jugador_2', $pareja1['jugador_2'])
                                  ->where('g2.jugador_1', $pareja2['jugador_1'])
                                  ->where('g2.jugador_2', $pareja2['jugador_2']);
                            })
                            ->orWhere(function($q) use ($pareja1, $pareja2) {
                                $q->where('g1.jugador_1', $pareja2['jugador_1'])
                                  ->where('g1.jugador_2', $pareja2['jugador_2'])
                                  ->where('g2.jugador_1', $pareja1['jugador_1'])
                                  ->where('g2.jugador_2', $pareja1['jugador_2']);
                            });
                        })
                        ->select('g1.partido_id')
                        ->first();
                    
                    if (!$verificacionFinal) {
                        $nuevoPartido = $this->crearPartido();
                        
                        // Crear nuevos registros de grupo para este partido específico
                        // (cada pareja puede tener múltiples partidos, así que creamos un registro por partido)
                        $grupo1 = new Grupo;
                        $grupo1->torneo_id = $torneoId;
                        $grupo1->zona = $zona;
                        $grupo1->fecha = '2000-01-01';
                        $grupo1->horario = '00:00';
                        $grupo1->jugador_1 = $pareja1['jugador_1'];
                        $grupo1->jugador_2 = $pareja1['jugador_2'];
                        $grupo1->partido_id = $nuevoPartido->id;
                        $grupo1->save();
                        
                        $grupo2 = new Grupo;
                        $grupo2->torneo_id = $torneoId;
                        $grupo2->zona = $zona;
                        $grupo2->fecha = '2000-01-01';
                        $grupo2->horario = '00:00';
                        $grupo2->jugador_1 = $pareja2['jugador_1'];
                        $grupo2->jugador_2 = $pareja2['jugador_2'];
                        $grupo2->partido_id = $nuevoPartido->id;
                        $grupo2->save();
                    }
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Partidos creados correctamente'
        ]);
    }

    public function adminTorneoAmericanoPartidos(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener solo los grupos iniciales del torneo (sin partido_id) para identificar parejas
        // Los grupos con partido_id son los partidos ya creados, no las parejas iniciales
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNull('grupos.partido_id') // Solo grupos iniciales
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona y extraer parejas únicas (sin duplicados)
        $parejasPorZona = [];
        $parejasUnicas = []; // Para evitar duplicados: "zona-jugador1-jugador2"
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($parejasPorZona[$zona])) {
                $parejasPorZona[$zona] = [];
            }
            // Solo agregar si tiene ambos jugadores (es una pareja válida)
            if ($grupo->jugador_1 && $grupo->jugador_2) {
                $keyPareja = $zona . '-' . min($grupo->jugador_1, $grupo->jugador_2) . '-' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasUnicas[$keyPareja])) {
                    $parejasPorZona[$zona][] = [
                        'grupo_id' => $grupo->id,
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partido_id' => null // Los grupos iniciales no tienen partido_id
                    ];
                    $parejasUnicas[$keyPareja] = true;
                }
            }
        }
        
        // Construir TODOS los partidos posibles (todos contra todos) para cada zona
        // Crear los partidos si no existen, o usar los existentes
        $partidosPorZona = [];
        
        foreach ($parejasPorZona as $zona => $parejas) {
            $partidosPorZona[$zona] = [];
            
            // Generar todas las combinaciones "todos contra todos"
            $combinaciones = [];
            for ($i = 0; $i < count($parejas); $i++) {
                for ($j = $i + 1; $j < count($parejas); $j++) {
                    $combinaciones[] = [
                        'pareja_1' => $parejas[$i],
                        'pareja_2' => $parejas[$j],
                    ];
                }
            }
            
            // Para cada combinación, SOLO buscar partidos existentes (NO crear nuevos)
            $partidosTemporales = [];
            foreach ($combinaciones as $combo) {
                $pareja1 = $combo['pareja_1'];
                $pareja2 = $combo['pareja_2'];
                
                // SOLO buscar si existe un partido con estas parejas en la BD
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->whereNotNull('g1.partido_id')
                    ->whereNotNull('g2.partido_id')
                    ->where(function($query) use ($pareja1, $pareja2) {
                        // Caso 1: g1 tiene pareja1 y g2 tiene pareja2
                        $query->where(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja1['jugador_1'])
                              ->where('g1.jugador_2', $pareja1['jugador_2'])
                              ->where('g2.jugador_1', $pareja2['jugador_1'])
                              ->where('g2.jugador_2', $pareja2['jugador_2']);
                        })
                        // Caso 2: g1 tiene pareja2 y g2 tiene pareja1
                        ->orWhere(function($q) use ($pareja1, $pareja2) {
                            $q->where('g1.jugador_1', $pareja2['jugador_1'])
                              ->where('g1.jugador_2', $pareja2['jugador_2'])
                              ->where('g2.jugador_1', $pareja1['jugador_1'])
                              ->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                // Si existe, usar su partido_id, si no existe, usar null
                $partidoIdEncontrado = $partidoExistente ? $partidoExistente->partido_id : null;
                
                // Agregar el partido temporalmente
                $partidosTemporales[] = [
                    'partido_id' => $partidoIdEncontrado,
                    'pareja_1' => $pareja1,
                    'pareja_2' => $pareja2
                ];
            }
            
            // Ordenar los partidos para intercalar las parejas
            $partidosOrdenados = $this->ordenarPartidosIntercalados($partidosTemporales);
            
            // Asignar números de partido en el orden final
            $numeroPartido = 1;
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2'],
                    'numero_partido' => $numeroPartido
                ];
                $numeroPartido++;
            }
        }
        
        // Obtener información de los jugadores (como array, no keyBy para que funcione en JavaScript)
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // Obtener resultados de partidos existentes
        $partidosIds = [];
        foreach ($partidosPorZona as $zona => $partidos) {
            foreach ($partidos as $partido) {
                if ($partido['partido_id']) {
                    $partidosIds[] = $partido['partido_id'];
                }
            }
        }
        $partidosIds = array_unique($partidosIds);
        
        $partidosConResultados = [];
        if (!empty($partidosIds)) {
            $partidos = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            
            foreach ($partidos as $partido) {
                $partidosConResultados[$partido->id] = $partido;
            }
        }
        
        return View('bahia_padel.admin.torneo.partidos_americano')
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona)
                    ->with('jugadores', $jugadores)
                    ->with('partidosConResultados', $partidosConResultados);
    }

    public function guardarResultadoAmericano(Request $request) {
        $partidoId = $request->partido_id;
        $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
        $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
        $torneoId = $request->torneo_id ?? null;
        $zona = $request->zona ?? null;
        $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
        $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
        $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
        $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
        
        $partido = null;
        
        // Si tenemos un partido_id válido (número mayor a 0), usar ese partido directamente
        $partidoIdInt = is_numeric($partidoId) ? (int)$partidoId : 0;
        if ($partidoIdInt > 0) {
            $partido = Partido::find($partidoIdInt);
        }
        
        // Si no encontramos el partido por ID, buscar por las parejas (pero NUNCA crear uno nuevo)
        // Los partidos ya deberían existir porque se crean al cargar la página
        if (!$partido) {
            // Buscar si ya existe un partido con estas parejas
            if ($torneoId && $zona && $pareja1Jugador1 && $pareja1Jugador2 && $pareja2Jugador1 && $pareja2Jugador2) {
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')
                             ->whereRaw('g1.id != g2.id')
                             ->whereNotNull('g1.partido_id')
                             ->whereNotNull('g2.partido_id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $zona)
                    ->where('g2.torneo_id', $torneoId)
                    ->where('g2.zona', $zona)
                    ->where(function($query) use ($pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
                        $query->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2) {
                            $q->where('g1.jugador_1', $pareja1Jugador1)
                              ->where('g1.jugador_2', $pareja1Jugador2);
                        })
                        ->where(function($q) use ($pareja2Jugador1, $pareja2Jugador2) {
                            $q->where('g2.jugador_1', $pareja2Jugador1)
                              ->where('g2.jugador_2', $pareja2Jugador2);
                        });
                    })
                    ->orWhere(function($query) use ($pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
                        $query->where(function($q) use ($pareja2Jugador1, $pareja2Jugador2) {
                            $q->where('g1.jugador_1', $pareja2Jugador1)
                              ->where('g1.jugador_2', $pareja2Jugador2);
                        })
                        ->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2) {
                            $q->where('g2.jugador_1', $pareja1Jugador1)
                              ->where('g2.jugador_2', $pareja1Jugador2);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                if ($partidoExistente && $partidoExistente->partido_id) {
                    // Usar el partido existente
                    $partido = Partido::find($partidoExistente->partido_id);
                }
            }
        }
        
        // Si no tenemos partido, devolver error
        // NO crear nuevos partidos aquí, deberían existir ya
        if (!$partido) {
            return response()->json([
                'success' => false, 
                'message' => 'Partido no encontrado. Por favor recarga la página para crear los partidos.'
            ]);
        }
        
        // Obtener los grupos asociados a este partido para identificar el orden
        $grupos = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->orderBy('id')
                    ->get();
        
        // En americano solo se guarda el set 1
        // Los valores que vienen del request ya están en el orden correcto según la vista
        // (pareja_1_set_1 corresponde a la primera pareja mostrada, pareja_2_set_1 a la segunda)
        // Pero necesitamos guardarlos según el orden de los grupos en la BD
        if ($grupos->count() >= 2) {
            $g1 = $grupos[0];
            $g2 = $grupos[1];
            
            // Verificar qué pareja corresponde a cada grupo
            if ($g1->jugador_1 == $pareja1Jugador1 && $g1->jugador_2 == $pareja1Jugador2) {
                // El primer grupo es pareja 1
                $partido->pareja_1_set_1 = $pareja1Set1;
                $partido->pareja_2_set_1 = $pareja2Set1;
            } else {
                // El primer grupo es pareja 2 (invertido)
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_2_set_1 = $pareja1Set1;
            }
        } else {
            // Si no hay grupos, guardar en el orden recibido
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_2_set_1 = $pareja2Set1;
        }
        
        $partido->save();
        
        // Siempre devolver el partido_id para que el frontend lo actualice
        return response()->json([
            'success' => true, 
            'partido' => $partido, 
            'partido_id' => $partido->id
        ]);
    }

    public function calcularPosicionesAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // Obtener todas las parejas de la zona
        $grupos = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->where('zona', $zona)
                        ->whereNotNull('jugador_1')
                        ->whereNotNull('jugador_2')
                        ->get();
        
        // Agrupar por pareja (jugador_1 y jugador_2)
        $parejas = [];
        foreach ($grupos as $grupo) {
            $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
            if (!isset($parejas[$key])) {
                $parejas[$key] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2,
                    'partidos_ganados' => 0,
                    'partidos_perdidos' => 0,
                    'puntos_ganados' => 0, // Suma de games/sets ganados
                    'partidos_directos' => [] // Para almacenar resultados de partidos directos
                ];
            }
        }
        
        // Obtener todos los partidos de la zona
        $partidosIds = $grupos->pluck('partido_id')->unique();
        $partidos = DB::table('partidos')
                        ->whereIn('id', $partidosIds)
                        ->get();
        
        // Obtener grupos asociados a cada partido para identificar las parejas
        $gruposPorPartido = [];
        foreach ($grupos as $grupo) {
            if (!isset($gruposPorPartido[$grupo->partido_id])) {
                $gruposPorPartido[$grupo->partido_id] = [];
            }
            $gruposPorPartido[$grupo->partido_id][] = $grupo;
        }
        
        // Procesar cada partido identificando las parejas
        foreach ($partidos as $partido) {
            if (!isset($gruposPorPartido[$partido->id]) || count($gruposPorPartido[$partido->id]) < 2) {
                continue; // Necesitamos al menos 2 grupos (2 parejas) para un partido
            }
            
            $gruposPartido = $gruposPorPartido[$partido->id];
            // Ordenar por ID para tener consistencia
            $gruposPartido = collect($gruposPartido)->sortBy('id')->values()->all();
            
            $pareja1Grupo = $gruposPartido[0];
            $pareja2Grupo = $gruposPartido[1];
            
            $key1 = $pareja1Grupo->jugador_1 . '_' . $pareja1Grupo->jugador_2;
            $key2 = $pareja2Grupo->jugador_1 . '_' . $pareja2Grupo->jugador_2;
            
            // Verificar que ambas parejas existan
            if (!isset($parejas[$key1]) || !isset($parejas[$key2])) {
                continue;
            }
            
            // En el torneo americano, pareja_1_set_1 corresponde al primer grupo (menor ID)
            // y pareja_2_set_1 corresponde al segundo grupo (mayor ID)
            $puntosPareja1 = $partido->pareja_1_set_1 ?? 0;
            $puntosPareja2 = $partido->pareja_2_set_1 ?? 0;
            
            // Solo procesar si hay resultado (al menos un punto)
            if ($puntosPareja1 > 0 || $puntosPareja2 > 0) {
                // Determinar ganador
                if ($puntosPareja1 > $puntosPareja2) {
                    $parejas[$key1]['partidos_ganados']++;
                    $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                    $parejas[$key2]['partidos_perdidos']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                } else if ($puntosPareja2 > $puntosPareja1) {
                    $parejas[$key2]['partidos_ganados']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    $parejas[$key1]['partidos_perdidos']++;
                    $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                }
            }
        }
        
        // Agregar keys a cada pareja para poder comparar partidos directos
        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
        }
        
        // Convertir a array y ordenar por posición
        $posiciones = array_values($parejas);
        
        // Función de comparación con todos los criterios de desempate
        usort($posiciones, function($a, $b) {
            // 1. Primero por PARTIDOS GANADOS
            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                return $b['partidos_ganados'] - $a['partidos_ganados'];
            }
            
            // 2. Si tienen los mismos partidos ganados, por PUNTOS GANADOS (games)
            if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            }
            
            // 3. Si siguen empatando, por PARTIDO DIRECTO
            $keyA = $a['key'];
            $keyB = $b['key'];
            
            if (isset($a['partidos_directos'][$keyB])) {
                if ($a['partidos_directos'][$keyB]['ganado']) {
                    return -1; // A gana el partido directo
                } else {
                    return 1; // B gana el partido directo
                }
            }
            
            // 4. Si no hay partido directo o está empatado, mantener orden
            return 0;
        });
        
        return response()->json(['success' => true, 'posiciones' => $posiciones]);
    }

    public function adminTorneoResultados(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')                                                                
                        ->where('torneos.id', $torneoId)                                
                        ->where('torneos.activo', 1)                                
                        ->first(); 
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        $jugadores = DB::table('jugadores')                                                                                        
                        ->where('jugadores.activo', 1)                                
                        ->get();
        
        // Obtener todos los grupos con sus partidos
        $grupos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->select(
                            'grupos.id as grupo_id',
                            'grupos.torneo_id',
                            'grupos.zona',
                            'grupos.fecha',
                            'grupos.horario',
                            'grupos.jugador_1',
                            'grupos.jugador_2',
                            'grupos.partido_id',
                            'partidos.id as partido_id_full',
                            'partidos.pareja_1_set_1',
                            'partidos.pareja_1_set_1_tie_break',
                            'partidos.pareja_2_set_1',
                            'partidos.pareja_2_set_1_tie_break',
                            'partidos.pareja_1_set_2',
                            'partidos.pareja_1_set_2_tie_break',
                            'partidos.pareja_2_set_2',
                            'partidos.pareja_2_set_2_tie_break',
                            'partidos.pareja_1_set_3',
                            'partidos.pareja_1_set_3_tie_break',
                            'partidos.pareja_2_set_3',
                            'partidos.pareja_2_set_3_tie_break',
                            'partidos.pareja_1_set_super_tie_break',
                            'partidos.pareja_2_set_super_tie_break'
                        )
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.partido_id')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona y luego por partido único
        $partidosPorZona = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            $partidoId = $grupo->partido_id;
            
            if (!isset($partidosPorZona[$zona])) {
                $partidosPorZona[$zona] = [];
            }
            
            // Agrupar por partido_id único
            if (!isset($partidosPorZona[$zona][$partidoId])) {
                $partidosPorZona[$zona][$partidoId] = [
                    'partido_id' => $partidoId,
                    'pareja_1' => null,
                    'pareja_2' => null,
                    'fecha' => $grupo->fecha,
                    'horario' => $grupo->horario,
                    'resultados' => $grupo
                ];
            }
            
            // Asignar parejas (cada partido tiene 2 grupos con las dos parejas)
            if (!$partidosPorZona[$zona][$partidoId]['pareja_1']) {
                $partidosPorZona[$zona][$partidoId]['pareja_1'] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2
                ];
            } else {
                $partidosPorZona[$zona][$partidoId]['pareja_2'] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2
                ];
            }
        }
        
        return View('bahia_padel.admin.torneo.resultados_torneo')
                    ->with('jugadores', $jugadores)
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona); 
    }

    public function guardarResultadoPartido(Request $request) {
        $partidoId = $request->partido_id;
        
        $partido = Partido::find($partidoId);
        
        if (!$partido) {
            return response()->json(['success' => false, 'message' => 'Partido no encontrado']);
        }
        
        // Actualizar resultados del partido
        if ($request->has('pareja_1_set_1')) {
            $partido->pareja_1_set_1 = $request->pareja_1_set_1 ?? 0;
        }
        if ($request->has('pareja_1_set_1_tie_break')) {
            $partido->pareja_1_set_1_tie_break = $request->pareja_1_set_1_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_1')) {
            $partido->pareja_2_set_1 = $request->pareja_2_set_1 ?? 0;
        }
        if ($request->has('pareja_2_set_1_tie_break')) {
            $partido->pareja_2_set_1_tie_break = $request->pareja_2_set_1_tie_break ?? 0;
        }
        
        if ($request->has('pareja_1_set_2')) {
            $partido->pareja_1_set_2 = $request->pareja_1_set_2 ?? 0;
        }
        if ($request->has('pareja_1_set_2_tie_break')) {
            $partido->pareja_1_set_2_tie_break = $request->pareja_1_set_2_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_2')) {
            $partido->pareja_2_set_2 = $request->pareja_2_set_2 ?? 0;
        }
        if ($request->has('pareja_2_set_2_tie_break')) {
            $partido->pareja_2_set_2_tie_break = $request->pareja_2_set_2_tie_break ?? 0;
        }
        
        if ($request->has('pareja_1_set_3')) {
            $partido->pareja_1_set_3 = $request->pareja_1_set_3 ?? 0;
        }
        if ($request->has('pareja_1_set_3_tie_break')) {
            $partido->pareja_1_set_3_tie_break = $request->pareja_1_set_3_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_3')) {
            $partido->pareja_2_set_3 = $request->pareja_2_set_3 ?? 0;
        }
        if ($request->has('pareja_2_set_3_tie_break')) {
            $partido->pareja_2_set_3_tie_break = $request->pareja_2_set_3_tie_break ?? 0;
        }
        
        if ($request->has('pareja_1_set_super_tie_break')) {
            $partido->pareja_1_set_super_tie_break = $request->pareja_1_set_super_tie_break ?? 0;
        }
        if ($request->has('pareja_2_set_super_tie_break')) {
            $partido->pareja_2_set_super_tie_break = $request->pareja_2_set_super_tie_break ?? 0;
        }
        
        $partido->save();
        
        return response()->json(['success' => true, 'partido' => $partido]);
    }

    public function verificarPartidosCompletos(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // Obtener todos los partidos únicos de la zona
        $partidos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where('grupos.zona', $zona)
                        ->select('grupos.partido_id', 'partidos.*')
                        ->distinct()
                        ->get();
        
        $totalPartidos = $partidos->count();
        $partidosCompletos = 0;
        
        foreach ($partidos as $partido) {
            // Un partido está completo si tiene al menos un set con resultado > 0
            $tieneResultado = ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) ||
                             ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0) ||
                             ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0);
            
            if ($tieneResultado) {
                $partidosCompletos++;
            }
        }
        
        return response()->json([
            'success' => true,
            'total_partidos' => $totalPartidos,
            'partidos_completos' => $partidosCompletos,
            'todos_completos' => $totalPartidos > 0 && $partidosCompletos == $totalPartidos
        ]);
    }

    public function calcularPosicionesZona(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        
        // Obtener todos los partidos únicos de la zona
        $partidos = DB::table('grupos')
                        ->join('partidos', 'grupos.partido_id', '=', 'partidos.id')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where('grupos.zona', $zona)
                        ->select(
                            'grupos.partido_id',
                            'partidos.pareja_1_set_1',
                            'partidos.pareja_2_set_1',
                            'partidos.pareja_1_set_2',
                            'partidos.pareja_2_set_2',
                            'partidos.pareja_1_set_3',
                            'partidos.pareja_2_set_3',
                            'partidos.pareja_1_set_super_tie_break',
                            'partidos.pareja_2_set_super_tie_break'
                        )
                        ->distinct()
                        ->get();
        
        // Obtener las parejas de la zona
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where('grupos.zona', $zona)
                        ->select('grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                        ->get();
        
        // Agrupar por pareja
        $parejas = [];
        foreach ($grupos as $grupo) {
            $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
            if (!isset($parejas[$key])) {
                $parejas[$key] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2,
                    'partidos_jugados' => 0,
                    'partidos_ganados' => 0,
                    'partidos_perdidos' => 0,
                    'puntos' => 0,
                    'sets_ganados' => 0,
                    'sets_perdidos' => 0,
                    'juegos_ganados' => 0,
                    'juegos_perdidos' => 0,
                    'partidos_directos' => [] // Para almacenar resultados de partidos directos
                ];
            }
        }
        
        // Procesar cada partido
        foreach ($partidos as $partido) {
            // Encontrar las dos parejas que juegan este partido
            $pareja1 = null;
            $pareja2 = null;
            
            foreach ($grupos as $grupo) {
                if ($grupo->partido_id == $partido->partido_id) {
                    $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
                    if (!$pareja1) {
                        $pareja1 = $key;
                    } else if ($key != $pareja1) {
                        $pareja2 = $key;
                        break;
                    }
                }
            }
            
            if ($pareja1 && $pareja2) {
                $parejas[$pareja1]['partidos_jugados']++;
                $parejas[$pareja2]['partidos_jugados']++;
                
                // Calcular sets ganados
                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;
                $ganoPorSuperTB = false;
                
                // Contar sets ganados
                if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) $setsGanadosP1++;
                else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) $setsGanadosP1++;
                else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) $setsGanadosP2++;
                
                // Si hay super tie break, ese determina el tercer set
                if ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0) {
                    $ganoPorSuperTB = true;
                    if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                        $setsGanadosP1 = 2; // Gana por super TB (2-1)
                        $setsGanadosP2 = 1;
                    } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2; // Gana por super TB (2-1)
                    }
                }
                
                // Calcular juegos (games) ganados y perdidos
                $juegosGanadosP1 = $partido->pareja_1_set_1 + $partido->pareja_1_set_2;
                $juegosGanadosP2 = $partido->pareja_2_set_1 + $partido->pareja_2_set_2;
                
                // Si hay super tie break, no se cuentan juegos del super TB (solo sets)
                
                // Actualizar estadísticas de sets
                $parejas[$pareja1]['sets_ganados'] += $setsGanadosP1;
                $parejas[$pareja1]['sets_perdidos'] += $setsGanadosP2;
                $parejas[$pareja2]['sets_ganados'] += $setsGanadosP2;
                $parejas[$pareja2]['sets_perdidos'] += $setsGanadosP1;
                
                // Actualizar estadísticas de juegos
                $parejas[$pareja1]['juegos_ganados'] += $juegosGanadosP1;
                $parejas[$pareja1]['juegos_perdidos'] += $juegosGanadosP2;
                $parejas[$pareja2]['juegos_ganados'] += $juegosGanadosP2;
                $parejas[$pareja2]['juegos_perdidos'] += $juegosGanadosP1;
                
                // Determinar ganador y asignar puntos
                if ($setsGanadosP1 > $setsGanadosP2) {
                    // Pareja 1 gana
                    $parejas[$pareja1]['partidos_ganados']++;
                    $parejas[$pareja2]['partidos_perdidos']++;
                    
                    // Asignar puntos: 2-0 = 2 puntos ganador, 0 perdedor | 2-1 = 2 puntos ganador, 1 perdedor
                    if ($setsGanadosP1 == 2 && $setsGanadosP2 == 0) {
                        $parejas[$pareja1]['puntos'] += 2;
                        $parejas[$pareja2]['puntos'] += 0;
                    } else if ($setsGanadosP1 == 2 && $setsGanadosP2 == 1) {
                        $parejas[$pareja1]['puntos'] += 2;
                        $parejas[$pareja2]['puntos'] += 1;
                    }
                    
                    // Guardar resultado del partido directo
                    $parejas[$pareja1]['partidos_directos'][$pareja2] = ['ganado' => true, 'sets' => $setsGanadosP1 . '-' . $setsGanadosP2];
                    $parejas[$pareja2]['partidos_directos'][$pareja1] = ['ganado' => false, 'sets' => $setsGanadosP2 . '-' . $setsGanadosP1];
                    
                } else if ($setsGanadosP2 > $setsGanadosP1) {
                    // Pareja 2 gana
                    $parejas[$pareja2]['partidos_ganados']++;
                    $parejas[$pareja1]['partidos_perdidos']++;
                    
                    // Asignar puntos
                    if ($setsGanadosP2 == 2 && $setsGanadosP1 == 0) {
                        $parejas[$pareja2]['puntos'] += 2;
                        $parejas[$pareja1]['puntos'] += 0;
                    } else if ($setsGanadosP2 == 2 && $setsGanadosP1 == 1) {
                        $parejas[$pareja2]['puntos'] += 2;
                        $parejas[$pareja1]['puntos'] += 1;
                    }
                    
                    // Guardar resultado del partido directo
                    $parejas[$pareja2]['partidos_directos'][$pareja1] = ['ganado' => true, 'sets' => $setsGanadosP2 . '-' . $setsGanadosP1];
                    $parejas[$pareja1]['partidos_directos'][$pareja2] = ['ganado' => false, 'sets' => $setsGanadosP1 . '-' . $setsGanadosP2];
                }
            }
        }
        
        // Agregar keys a cada pareja para poder comparar partidos directos
        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
        }
        
        // Convertir a array y ordenar por posición
        $posiciones = array_values($parejas);
        
        // Función de comparación con todos los criterios de desempate
        usort($posiciones, function($a, $b) {
            // 1. Primero por PUNTOS (no partidos ganados)
            if ($a['puntos'] != $b['puntos']) {
                return $b['puntos'] - $a['puntos'];
            }
            
            // 2. Si tienen los mismos puntos, aplicar desempates
            $keyA = $a['key'];
            $keyB = $b['key'];
            
            // 2.1. Partido Directo
            if (isset($a['partidos_directos'][$keyB])) {
                if ($a['partidos_directos'][$keyB]['ganado']) {
                    return -1; // A gana el partido directo
                } else {
                    return 1; // B gana el partido directo
                }
            }
            
            // 2.2. Diferencia de Juegos
            $diffJuegosA = $a['juegos_ganados'] - $a['juegos_perdidos'];
            $diffJuegosB = $b['juegos_ganados'] - $b['juegos_perdidos'];
            if ($diffJuegosA != $diffJuegosB) {
                return $diffJuegosB - $diffJuegosA;
            }
            
            // 2.3. Diferencia de Sets
            $diffSetsA = $a['sets_ganados'] - $a['sets_perdidos'];
            $diffSetsB = $b['sets_ganados'] - $b['sets_perdidos'];
            if ($diffSetsA != $diffSetsB) {
                return $diffSetsB - $diffSetsA;
            }
            
            // 2.4. Mayor Número de Juegos Ganados
            if ($a['juegos_ganados'] != $b['juegos_ganados']) {
                return $b['juegos_ganados'] - $a['juegos_ganados'];
            }
            
            // 2.5. Si todo está igual, mantener orden (equivalente a sorteo)
            return 0;
        });
        
        return response()->json(['success' => true, 'posiciones' => $posiciones]);
    }

    public function adminTorneoAmericanoCruces(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener todos los grupos del torneo
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // Calcular posiciones de cada zona
        $posicionesPorZona = [];
        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        
        foreach ($zonas as $zona) {
            // Obtener todas las parejas de la zona
            $gruposZona = $grupos->where('zona', $zona)->filter(function($grupo) {
                return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
            });
            
            // Agrupar por pareja (jugador_1 y jugador_2)
            $parejas = [];
            foreach ($gruposZona as $grupo) {
                $key = $grupo->jugador_1 . '_' . $grupo->jugador_2;
                if (!isset($parejas[$key])) {
                    $parejas[$key] = [
                        'jugador_1' => $grupo->jugador_1,
                        'jugador_2' => $grupo->jugador_2,
                        'partidos_ganados' => 0,
                        'partidos_perdidos' => 0,
                        'puntos_ganados' => 0,
                        'partidos_directos' => []
                    ];
                }
            }
            
            // Obtener todos los partidos de la zona
            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
            $partidos = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            
            // Obtener grupos asociados a cada partido
            $gruposPorPartido = [];
            foreach ($gruposZona as $grupo) {
                if ($grupo->partido_id) {
                    if (!isset($gruposPorPartido[$grupo->partido_id])) {
                        $gruposPorPartido[$grupo->partido_id] = [];
                    }
                    $gruposPorPartido[$grupo->partido_id][] = $grupo;
                }
            }
            
            // Procesar cada partido
            foreach ($partidos as $partido) {
                if (!isset($gruposPorPartido[$partido->id]) || count($gruposPorPartido[$partido->id]) < 2) {
                    continue;
                }
                
                $gruposPartido = collect($gruposPorPartido[$partido->id])->sortBy('id')->values()->all();
                $pareja1Grupo = $gruposPartido[0];
                $pareja2Grupo = $gruposPartido[1];
                
                $key1 = $pareja1Grupo->jugador_1 . '_' . $pareja1Grupo->jugador_2;
                $key2 = $pareja2Grupo->jugador_1 . '_' . $pareja2Grupo->jugador_2;
                
                if (!isset($parejas[$key1]) || !isset($parejas[$key2])) {
                    continue;
                }
                
                $puntosPareja1 = $partido->pareja_1_set_1 ?? 0;
                $puntosPareja2 = $partido->pareja_2_set_1 ?? 0;
                
                if ($puntosPareja1 > 0 || $puntosPareja2 > 0) {
                    if ($puntosPareja1 > $puntosPareja2) {
                        $parejas[$key1]['partidos_ganados']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true];
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false];
                    } else if ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true];
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false];
                    }
                }
            }
            
            // Agregar keys y ordenar
            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['key'] = $key;
            }
            
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                if ($a['puntos_ganados'] != $b['puntos_ganados']) {
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                }
                $keyA = $a['key'];
                $keyB = $b['key'];
                if (isset($a['partidos_directos'][$keyB])) {
                    return $a['partidos_directos'][$keyB]['ganado'] ? -1 : 1;
                }
                return 0;
            });
            
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        // Determinar cuántas parejas clasifican según el número total de parejas
        $totalParejas = 0;
        foreach ($posicionesPorZona as $posiciones) {
            $totalParejas += count($posiciones);
        }
        
        // Aplicar reglas de clasificación según el número de parejas
        // Objetivo: tener 8 parejas en eliminatorias (cuartos de final)
        $clasificados = [];
        $zonasArray = $zonas->toArray();
        
        // Primero, clasificar los primeros de cada grupo
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 0) {
                $clasificados[] = [
                    'zona' => $zona,
                    'posicion' => 1,
                    'jugador_1' => $posicionesPorZona[$zona][0]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][0]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][0]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][0]['puntos_ganados']
                ];
            }
        }
        
        // Luego, clasificar los segundos de cada grupo
        $segundos = [];
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 1) {
                $segundos[] = [
                    'zona' => $zona,
                    'posicion' => 2,
                    'jugador_1' => $posicionesPorZona[$zona][1]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][1]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][1]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][1]['puntos_ganados']
                ];
            }
        }
        
        // Ordenar segundos por partidos ganados y puntos ganados
        usort($segundos, function($a, $b) {
            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                return $b['partidos_ganados'] - $a['partidos_ganados'];
            }
            return $b['puntos_ganados'] - $a['puntos_ganados'];
        });
        
        // Obtener segundos y terceros por zona
        $segundosPorZona = [];
        $tercerosPorZona = [];
        foreach ($zonasArray as $zona) {
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 1) {
                $segundosPorZona[$zona] = [
                    'zona' => $zona,
                    'posicion' => 2,
                    'jugador_1' => $posicionesPorZona[$zona][1]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][1]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][1]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][1]['puntos_ganados']
                ];
            }
            if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 2) {
                $tercerosPorZona[$zona] = [
                    'zona' => $zona,
                    'posicion' => 3,
                    'jugador_1' => $posicionesPorZona[$zona][2]['jugador_1'],
                    'jugador_2' => $posicionesPorZona[$zona][2]['jugador_2'],
                    'partidos_ganados' => $posicionesPorZona[$zona][2]['partidos_ganados'],
                    'puntos_ganados' => $posicionesPorZona[$zona][2]['puntos_ganados']
                ];
            }
        }
        
        // Determinar si tenemos 3 zonas (caso de 15 parejas: A, B, C)
        // Si hay 3 zonas, clasificar A2, B2, C2 y los 2 mejores terceros
        $zonasOrdenadasArray = $zonasArray; // Ya es un array
        sort($zonasOrdenadasArray);
        if (count($zonasOrdenadasArray) == 3) {
            // Agregar segundos de las 3 zonas
            foreach ($zonasOrdenadasArray as $zona) {
                if (isset($segundosPorZona[$zona])) {
                    $clasificados[] = $segundosPorZona[$zona];
                }
            }
            
            // Agregar los 2 mejores terceros
            $terceros = [];
            foreach ($tercerosPorZona as $tercero) {
                $terceros[] = $tercero;
            }
            usort($terceros, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            
            // Agregar los 2 mejores terceros
            for ($i = 0; $i < min(2, count($terceros)); $i++) {
                $clasificados[] = $terceros[$i];
            }
        } else {
            // Lógica estándar para otros casos
            // Agregar segundos hasta completar 8 clasificados
            $segundos = array_values($segundosPorZona);
            usort($segundos, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            
            $necesarios = 8 - count($clasificados);
            for ($i = 0; $i < min($necesarios, count($segundos)); $i++) {
                $clasificados[] = $segundos[$i];
            }
            
            // Si aún no alcanzamos 8, agregar terceros
            if (count($clasificados) < 8) {
                $terceros = array_values($tercerosPorZona);
                usort($terceros, function($a, $b) {
                    if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                        return $b['partidos_ganados'] - $a['partidos_ganados'];
                    }
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                });
                
                $necesarios = 8 - count($clasificados);
                for ($i = 0; $i < min($necesarios, count($terceros)); $i++) {
                    $clasificados[] = $terceros[$i];
                }
            }
        }
        
        // Armar los cruces según las reglas estándar
        // Separar primeros y segundos/terceros
        $primerosPorZonaFinal = [];
        $segundosPorZonaFinal = [];
        $tercerosFinal = [];
        
        foreach ($clasificados as $clasificado) {
            if ($clasificado['posicion'] == 1) {
                $primerosPorZonaFinal[$clasificado['zona']] = $clasificado;
            } else if ($clasificado['posicion'] == 2) {
                $segundosPorZonaFinal[$clasificado['zona']] = $clasificado;
            } else if ($clasificado['posicion'] == 3) {
                $tercerosFinal[] = $clasificado;
            }
        }
        
        // Ordenar terceros por partidos ganados y puntos ganados (los mejores primero)
        usort($tercerosFinal, function($a, $b) {
            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                return $b['partidos_ganados'] - $a['partidos_ganados'];
            }
            return $b['puntos_ganados'] - $a['puntos_ganados'];
        });
        
        $cruces = [];
        $totalClasificados = count($clasificados);
        $zonasOrdenadasFinal = array_keys($primerosPorZonaFinal);
        sort($zonasOrdenadasFinal);
        
        // Caso especial: 6 clasificados
        // Los primeros pasan directo a semifinales, los segundos/terceros juegan cuartos
        if ($totalClasificados == 6) {
            // Separar segundos y terceros por zona
            $segundosPorZona = [];
            $tercerosPorZona = [];
            
            foreach ($resto as $pareja) {
                if ($pareja['posicion'] == 2) {
                    $segundosPorZona[$pareja['zona']] = $pareja;
                } else if ($pareja['posicion'] == 3) {
                    $tercerosPorZona[$pareja['zona']] = $pareja;
                }
            }
            
            // Crear cruces: A2 vs B3 y B2 vs A3 (evitando que equipos del mismo grupo se enfrenten)
            $zonasArray = array_keys($segundosPorZona + $tercerosPorZona);
            sort($zonasArray); // Ordenar zonas (A, B, C, etc.)
            
            if (count($zonasArray) >= 2) {
                // Si hay al menos 2 zonas, crear solo 2 cruces únicos
                $zona1 = $zonasArray[0]; // Primera zona (ej: A)
                $zona2 = $zonasArray[1]; // Segunda zona (ej: B)
                
                // Cruce 1: A2 vs B3
                if (isset($segundosPorZona[$zona1]) && isset($tercerosPorZona[$zona2])) {
                    $cruces[] = [
                        'pareja_1' => $segundosPorZona[$zona1],
                        'pareja_2' => $tercerosPorZona[$zona2],
                        'ronda' => 'cuartos'
                    ];
                }
                
                // Cruce 2: B2 vs A3
                if (isset($segundosPorZona[$zona2]) && isset($tercerosPorZona[$zona1])) {
                    $cruces[] = [
                        'pareja_1' => $segundosPorZona[$zona2],
                        'pareja_2' => $tercerosPorZona[$zona1],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                // Si solo hay una zona o menos, usar la lógica anterior
                if (count($resto) >= 2) {
                    for ($i = 0; $i < count($resto) - 1; $i += 2) {
                        if (isset($resto[$i + 1])) {
                            $cruces[] = [
                                'pareja_1' => $resto[$i],
                                'pareja_2' => $resto[$i + 1],
                                'ronda' => 'cuartos'
                            ];
                        }
                    }
                }
            }
        } else if ($totalClasificados == 8 && count($zonasOrdenadasFinal) == 3) {
            // Caso especial: 8 clasificados con 3 zonas (ej: 15 parejas: A1, A2, B1, B2, C1, C2, + 2 mejores terceros)
            // Crear cruces según el formato: A1 vs mejor tercero, B1 vs otro mejor tercero, C1 vs A2, B2 vs C2
            $zonaA = $zonasOrdenadasFinal[0]; // Zona A
            $zonaB = $zonasOrdenadasFinal[1]; // Zona B
            $zonaC = $zonasOrdenadasFinal[2]; // Zona C
            
            // Cruce 1: A1 vs mejor tercero
            if (isset($primerosPorZonaFinal[$zonaA]) && count($tercerosFinal) > 0) {
                $cruces[] = [
                    'pareja_1' => $primerosPorZonaFinal[$zonaA],
                    'pareja_2' => $tercerosFinal[0],
                    'ronda' => 'cuartos'
                ];
            }
            
            // Cruce 2: B1 vs otro mejor tercero
            if (isset($primerosPorZonaFinal[$zonaB]) && count($tercerosFinal) > 1) {
                $cruces[] = [
                    'pareja_1' => $primerosPorZonaFinal[$zonaB],
                    'pareja_2' => $tercerosFinal[1],
                    'ronda' => 'cuartos'
                ];
            }
            
            // Cruce 3: C1 vs A2
            if (isset($primerosPorZonaFinal[$zonaC]) && isset($segundosPorZonaFinal[$zonaA])) {
                $cruces[] = [
                    'pareja_1' => $primerosPorZonaFinal[$zonaC],
                    'pareja_2' => $segundosPorZonaFinal[$zonaA],
                    'ronda' => 'cuartos'
                ];
            }
            
            // Cruce 4: B2 vs C2
            if (isset($segundosPorZonaFinal[$zonaB]) && isset($segundosPorZonaFinal[$zonaC])) {
                $cruces[] = [
                    'pareja_1' => $segundosPorZonaFinal[$zonaB],
                    'pareja_2' => $segundosPorZonaFinal[$zonaC],
                    'ronda' => 'cuartos'
                ];
            }
        } else {
            // Para otros casos (8, 12, etc.), usar la lógica estándar
            $primerosUsados = [];
            $restoUsados = [];
            
            // Distribuir primeros en partes opuestas del cuadro
            $mitad = ceil(count($primeros) / 2);
            $primerosSuperior = array_slice($primeros, 0, $mitad);
            $primerosInferior = array_slice($primeros, $mitad);
            
            // Crear cruces superiores
            foreach ($primerosSuperior as $primero) {
                // Buscar un segundo/tercero que no sea del mismo grupo
                $encontrado = false;
                foreach ($resto as $index => $r) {
                    if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                        $cruces[] = [
                            'pareja_1' => $primero,
                            'pareja_2' => $r,
                            'ronda' => 'cuartos'
                        ];
                        $restoUsados[] = $index;
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado && count($resto) > 0) {
                    // Si no hay disponible de otro grupo, usar el primero disponible
                    $index = 0;
                    while (in_array($index, $restoUsados) && $index < count($resto)) {
                        $index++;
                    }
                    if ($index < count($resto)) {
                        $cruces[] = [
                            'pareja_1' => $primero,
                            'pareja_2' => $resto[$index],
                            'ronda' => 'cuartos'
                        ];
                        $restoUsados[] = $index;
                    }
                }
            }
            
            // Crear cruces inferiores
            foreach ($primerosInferior as $primero) {
                $encontrado = false;
                foreach ($resto as $index => $r) {
                    if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                        $cruces[] = [
                            'pareja_1' => $primero,
                            'pareja_2' => $r,
                            'ronda' => 'cuartos'
                        ];
                        $restoUsados[] = $index;
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado && count($resto) > 0) {
                    $index = 0;
                    while (in_array($index, $restoUsados) && $index < count($resto)) {
                        $index++;
                    }
                    if ($index < count($resto)) {
                        $cruces[] = [
                            'pareja_1' => $primero,
                            'pareja_2' => $resto[$index],
                            'ronda' => 'cuartos'
                        ];
                        $restoUsados[] = $index;
                    }
                }
            }
            
            // Si quedan parejas sin emparejar, crear cruces entre ellas
            $restantes = [];
            foreach ($resto as $index => $r) {
                if (!in_array($index, $restoUsados)) {
                    $restantes[] = $r;
                }
            }
            if (count($restantes) >= 2) {
                for ($i = 0; $i < count($restantes) - 1; $i += 2) {
                    $cruces[] = [
                        'pareja_1' => $restantes[$i],
                        'pareja_2' => $restantes[$i + 1],
                        'ronda' => 'cuartos'
                    ];
                }
            }
        }
        
        // Obtener partidos eliminatorios existentes (si los hay)
        // Los partidos eliminatorios se guardarán con una zona especial como "ELIMINATORIA"
        $partidosElimIds = DB::table('grupos')
                                ->where('torneo_id', $torneoId)
                                ->where('zona', 'ELIMINATORIA')
                                ->whereNotNull('partido_id')
                                ->select('partido_id')
                                ->distinct()
                                ->pluck('partido_id');
        
        $partidosEliminatorios = [];
        if ($partidosElimIds->count() > 0) {
            $partidosEliminatorios = DB::table('partidos')
                                        ->whereIn('id', $partidosElimIds)
                                        ->orderBy('id')
                                        ->get();
        }
        
        // Organizar resultados por parejas y ronda
        // Identificar la ronda comparando las parejas con los clasificados
        $resultadosGuardados = [];
        $clasificadosKeys = [];
        foreach ($clasificados as $clasificado) {
            $clasificadosKeys[] = $clasificado['jugador_1'] . '_' . $clasificado['jugador_2'];
        }
        
        foreach ($partidosEliminatorios as $index => $partido) {
            $partidoId = $partido->id;
            
            // Obtener los grupos de este partido para identificar las parejas
            $gruposPartido = DB::table('grupos')
                                ->where('partido_id', $partidoId)
                                ->where('torneo_id', $torneoId)
                                ->where('zona', 'ELIMINATORIA')
                                ->orderBy('id')
                                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                $key1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                $key2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                
                // Determinar la ronda:
                // - Si ambas parejas están en clasificados, es cuartos
                // - Si ninguna está en clasificados, verificar si son ganadores de cuartos (semifinales) o de semifinales (final)
                $ronda = 'cuartos'; // Por defecto
                
                $pareja1EnClasificados = in_array($key1, $clasificadosKeys);
                $pareja2EnClasificados = in_array($key2, $clasificadosKeys);
                
                if ($pareja1EnClasificados && $pareja2EnClasificados) {
                    $ronda = 'cuartos';
                } else {
                    // Buscar si alguna de estas parejas ganó en cuartos (está en resultados de cuartos)
                    // Por simplicidad, usaremos el orden: primeros 4 = cuartos, siguientes 2 = semifinales, último = final
                    $partidosAntes = DB::table('partidos')
                                        ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
                                        ->where('partidos.id', '<', $partidoId)
                                        ->where('grupos.torneo_id', $torneoId)
                                        ->where('grupos.zona', 'ELIMINATORIA')
                                        ->select('partidos.id')
                                        ->distinct()
                                        ->count();
                    
                    if ($partidosAntes < 4) {
                        $ronda = 'cuartos';
                    } else if ($partidosAntes < 6) {
                        $ronda = 'semifinales';
                    } else {
                        $ronda = 'final';
                    }
                }
                
                $resultadosGuardados[] = [
                    'partido_id' => $partidoId,
                    'ronda' => $ronda,
                    'pareja_1_jugador_1' => $g1->jugador_1,
                    'pareja_1_jugador_2' => $g1->jugador_2,
                    'pareja_2_jugador_1' => $g2->jugador_1,
                    'pareja_2_jugador_2' => $g2->jugador_2,
                    'pareja_1_set_1' => $partido->pareja_1_set_1 ?? 0,
                    'pareja_2_set_1' => $partido->pareja_2_set_1 ?? 0,
                ];
            }
        }
        
        // Separar primeros para pasarlos a la vista (necesario para el caso de 6 clasificados)
        $primerosClasificados = [];
        foreach ($clasificados as $clasificado) {
            if ($clasificado['posicion'] == 1) {
                $primerosClasificados[] = $clasificado;
            }
        }
        
        return View('bahia_padel.admin.torneo.cruces_americano')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('clasificados', $clasificados)
                    ->with('cruces', $cruces)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('primerosClasificados', $primerosClasificados)
                    ->with('totalClasificados', count($clasificados));
    }

    public function guardarResultadoCruceAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        $ronda = $request->ronda; // 'cuartos', 'semifinales', 'final'
        $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
        $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
        $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
        $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
        $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
        $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
        
        // Buscar si ya existe un partido eliminatorio con estas parejas
        // Buscar grupos que tengan una de las parejas y verificar si el otro grupo del mismo partido tiene la otra pareja
        $grupo1Encontrado = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'ELIMINATORIA')
            ->whereNotNull('partido_id')
            ->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2) {
                $q->where(function($q2) use ($pareja1Jugador1, $pareja1Jugador2) {
                    $q2->where('jugador_1', $pareja1Jugador1)
                       ->where('jugador_2', $pareja1Jugador2);
                })
                ->orWhere(function($q2) use ($pareja2Jugador1, $pareja2Jugador2) {
                    $q2->where('jugador_1', $pareja2Jugador1)
                       ->where('jugador_2', $pareja2Jugador2);
                });
            })
            ->first();
        
        $partido = null;
        
        if ($grupo1Encontrado) {
            // Buscar el otro grupo del mismo partido
            $grupo2Encontrado = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'ELIMINATORIA')
                ->where('partido_id', $grupo1Encontrado->partido_id)
                ->where('id', '!=', $grupo1Encontrado->id)
                ->first();
            
            if ($grupo2Encontrado) {
                // Verificar que el segundo grupo tenga la otra pareja
                $tienePareja1 = ($grupo1Encontrado->jugador_1 == $pareja1Jugador1 && $grupo1Encontrado->jugador_2 == $pareja1Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja1Jugador1 && $grupo2Encontrado->jugador_2 == $pareja1Jugador2);
                $tienePareja2 = ($grupo1Encontrado->jugador_1 == $pareja2Jugador1 && $grupo1Encontrado->jugador_2 == $pareja2Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja2Jugador1 && $grupo2Encontrado->jugador_2 == $pareja2Jugador2);
                
                if ($tienePareja1 && $tienePareja2) {
                    $partido = Partido::find($grupo1Encontrado->partido_id);
                }
            }
        }
        
        // Si no existe, crear nuevo partido y grupos
        if (!$partido) {
            $partido = $this->crearPartido();
            
            // Crear grupo para pareja 1
            $grupo1 = new Grupo;
            $grupo1->torneo_id = $torneoId;
            $grupo1->zona = 'ELIMINATORIA';
            $grupo1->fecha = '2000-01-01';
            $grupo1->horario = '00:00';
            $grupo1->jugador_1 = $pareja1Jugador1;
            $grupo1->jugador_2 = $pareja1Jugador2;
            $grupo1->partido_id = $partido->id;
            $grupo1->save();
            
            // Crear grupo para pareja 2
            $grupo2 = new Grupo;
            $grupo2->torneo_id = $torneoId;
            $grupo2->zona = 'ELIMINATORIA';
            $grupo2->fecha = '2000-01-01';
            $grupo2->horario = '00:00';
            $grupo2->jugador_1 = $pareja2Jugador1;
            $grupo2->jugador_2 = $pareja2Jugador2;
            $grupo2->partido_id = $partido->id;
            $grupo2->save();
        }
        
        // Obtener los grupos asociados a este partido para identificar el orden
        $grupos = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->orderBy('id')
                    ->get();
        
        // Guardar resultado según el orden de los grupos
        if ($grupos->count() >= 2) {
            $g1 = $grupos[0];
            $g2 = $grupos[1];
            
            // Verificar qué pareja corresponde a cada grupo
            if ($g1->jugador_1 == $pareja1Jugador1 && $g1->jugador_2 == $pareja1Jugador2) {
                $partido->pareja_1_set_1 = $pareja1Set1;
                $partido->pareja_2_set_1 = $pareja2Set1;
            } else {
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_2_set_1 = $pareja1Set1;
            }
        } else {
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_2_set_1 = $pareja2Set1;
        }
        
        $partido->save();
        
        return response()->json([
            'success' => true, 
            'partido' => $partido, 
            'partido_id' => $partido->id
        ]);
    }

}


