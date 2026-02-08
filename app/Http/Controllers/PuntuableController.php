<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Partido;
use App\Grupo;

class PuntuableController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra la vista de cruces puntuables (versión antigua)
     */
    public function adminTorneoPuntuableCruces(Request $request) {
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
        
        // Obtener todos los grupos eliminatorios con sus partidos
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                $partidosAgrupados[$partidoId] = [
                    'zona' => $grupo->zona,
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
        }
        
        // Obtener los datos de los partidos
        $partidosIds = array_keys($partidosAgrupados);
        $partidos = [];
        if (count($partidosIds) > 0) {
            $partidos = DB::table('partidos')
                ->whereIn('id', $partidosIds)
                ->get()
                ->keyBy('id');
        }
        
        // Construir cruces desde los partidos existentes
        $cruces = [];
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'cuartos';
                if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                // Crear el cruce
                $cruce = [
                    'id' => $ronda . '_' . $partidoId,
                    'partido_id' => $partidoId,
                    'pareja_1' => [
                        'jugador_1' => $g1->jugador_1,
                        'jugador_2' => $g1->jugador_2,
                        'zona' => null,
                        'posicion' => null
                    ],
                    'pareja_2' => [
                        'jugador_1' => $g2->jugador_1,
                        'jugador_2' => $g2->jugador_2,
                        'zona' => null,
                        'posicion' => null
                    ],
                    'ronda' => $ronda,
                    'partido' => $partido // Incluir el objeto partido completo
                ];
                
                $cruces[] = $cruce;
            }
        }
        
        return View('bahia_padel.admin.torneo.cruces_puntuable')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('cruces', $cruces);
    }

    /**
     * Muestra la vista de cruces puntuables V2 (con soporte para octavos)
     */
    public function adminTorneoPuntuableCrucesV2(Request $request) {
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
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Usar DISTINCT para evitar duplicados
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereIn('zona', ['octavos final', 'cuartos final', 'semifinal', 'final'])
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id, asegurándose de que cada partido solo aparezca una vez
        $partidosAgrupados = [];
        $partidosProcesados = []; // Para evitar procesar el mismo partido múltiples veces
        
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            
            // Solo procesar si este partido_id no ha sido procesado aún
            if (!in_array($partidoId, $partidosProcesados)) {
                // Obtener todos los grupos de este partido
                $gruposDelPartido = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('partido_id', $partidoId)
                    ->orderBy('id')
                    ->get();
                
                if ($gruposDelPartido->count() >= 2) {
                    $partidosAgrupados[$partidoId] = [
                        'zona' => $grupo->zona,
                        'partido_id' => $partidoId,
                        'grupos' => $gruposDelPartido->toArray()
                    ];
                    $partidosProcesados[] = $partidoId;
                }
            }
        }
        
        // Obtener los datos de los partidos
        $partidosIds = array_keys($partidosAgrupados);
        $partidos = [];
        if (count($partidosIds) > 0) {
            $partidos = DB::table('partidos')
                ->whereIn('id', $partidosIds)
                ->get()
                ->keyBy('id');
        }
        
        // Construir cruces desde los partidos existentes
        $cruces = [];
        $resultadosGuardados = [];
        $crucesPorPartidoId = []; // Para evitar duplicados
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            // Evitar procesar el mismo partido_id múltiples veces
            if (isset($crucesPorPartidoId[$partidoId])) {
                continue;
            }
            
            if (count($datosPartido['grupos']) >= 2) {
                // Convertir arrays a objetos si es necesario
                $g1 = is_array($datosPartido['grupos'][0]) ? (object)$datosPartido['grupos'][0] : $datosPartido['grupos'][0];
                $g2 = is_array($datosPartido['grupos'][1]) ? (object)$datosPartido['grupos'][1] : $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'octavos';
                if ($datosPartido['zona'] === 'cuartos final') {
                    $ronda = 'cuartos';
                } else if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                // Crear el cruce
                $cruce = [
                    'id' => $ronda . '_' . $partidoId,
                    'partido_id' => $partidoId,
                    'pareja_1' => [
                        'jugador_1' => $g1->jugador_1,
                        'jugador_2' => $g1->jugador_2,
                        'zona' => null,
                        'posicion' => null
                    ],
                    'pareja_2' => [
                        'jugador_1' => $g2->jugador_1,
                        'jugador_2' => $g2->jugador_2,
                        'zona' => null,
                        'posicion' => null
                    ],
                    'ronda' => $ronda,
                    'partido' => $partido // Incluir el objeto partido completo
                ];
                
                // Solo agregar si no existe ya un cruce con este partido_id
                if (!isset($crucesPorPartidoId[$partidoId])) {
                    $cruces[] = $cruce;
                    $crucesPorPartidoId[$partidoId] = true;
                }
                
                // Guardar resultado si existe (verificar si hay al menos un set con resultado)
                // Incluir resultados incluso si algunos sets son 0, siempre que haya al menos un set con resultado
                if ($partido && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0 || 
                    $partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0 || 
                    $partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0)) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruce['id'],
                        'ronda' => $ronda,
                        'pareja_1_jugador_1' => $g1->jugador_1,
                        'pareja_1_jugador_2' => $g1->jugador_2,
                        'pareja_2_jugador_1' => $g2->jugador_1,
                        'pareja_2_jugador_2' => $g2->jugador_2,
                        'pareja_1_set_1' => isset($partido->pareja_1_set_1) ? (int)$partido->pareja_1_set_1 : null,
                        'pareja_1_set_2' => isset($partido->pareja_1_set_2) ? (int)$partido->pareja_1_set_2 : null,
                        'pareja_1_set_3' => isset($partido->pareja_1_set_3) ? (int)$partido->pareja_1_set_3 : null,
                        'pareja_2_set_1' => isset($partido->pareja_2_set_1) ? (int)$partido->pareja_2_set_1 : null,
                        'pareja_2_set_2' => isset($partido->pareja_2_set_2) ? (int)$partido->pareja_2_set_2 : null,
                        'pareja_2_set_3' => isset($partido->pareja_2_set_3) ? (int)$partido->pareja_2_set_3 : null,
                    ];
                }
            }
        }
        
        // Determinar si hay octavos
        $tieneOctavos = false;
        foreach ($cruces as $cruce) {
            if (isset($cruce['ronda']) && $cruce['ronda'] === 'octavos') {
                $tieneOctavos = true;
                break;
            }
        }
                        
        $crucesOctavos = $this->obtenerCrucesPorZona($cruces, 'octavos final');
        $crucesCuartos = $this->obtenerCrucesPorZona($cruces, 'cuartos final');
        $crucesSemifinales = $this->obtenerCrucesPorZona($cruces, 'semifinal');
        $crucesFinales = $this->obtenerCrucesPorZona($cruces, 'final');

        //return $crucesOctavos;

        return View('bahia_padel.admin.torneo.cruces_puntuable_v2')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('cruces', $cruces)
                    ->with('crucesOctavos', $crucesOctavos)
                    ->with('crucesCuartos', $crucesCuartos)
                    ->with('crucesSemifinales', $crucesSemifinales)
                    ->with('crucesFinales', $crucesFinales)
                    ->with('resultadosGuardados', $resultadosGuardados);
    }

    /**
     * Obtiene los cruces filtrados por zona/ronda
     * 
     * @param array $cruces Array de cruces
     * @param string $zona Zona a filtrar ('octavos final', 'cuartos final', 'semifinal', 'final')
     * @return array Array de cruces filtrados por la zona especificada
     */
    private function obtenerCrucesPorZona($cruces, $zona) {
        // Mapear zona a ronda
        $rondaMap = [
            'octavos final' => 'octavos',
            'cuartos final' => 'cuartos',
            'semifinal' => 'semifinales',
            'final' => 'final'
        ];
        
        // Obtener la ronda correspondiente a la zona
        $ronda = $rondaMap[$zona] ?? null;
        
        if (!$ronda) {
            \Log::warning('Zona no reconocida en obtenerCrucesPorZona: ' . $zona);
            return [];
        }
        
        // Filtrar cruces por ronda
        $crucesFiltrados = array_filter($cruces, function($cruce) use ($ronda) {
            return isset($cruce['ronda']) && $cruce['ronda'] === $ronda;
        });
        
        // Reindexar el array para que tenga índices numéricos consecutivos
        return array_values($crucesFiltrados);
    } 

    /**
     * Guarda el resultado de un partido para torneo puntuable
     */
    public function guardarResultadoPartidoPuntuable(Request $request) {
        try {
            \Log::info('=== INICIO guardarResultadoPartidoPuntuable ===');
            \Log::info('Request completo: ' . json_encode($request->all()));
            
            $partidoId = $request->partido_id;
            $torneoId = $request->torneo_id;
            $ronda = $request->ronda;
            
            \Log::info('Partido ID recibido: ' . $partidoId);
            \Log::info('Torneo ID recibido: ' . $torneoId);
            \Log::info('Ronda recibida: ' . $ronda);
            
            // Validar que partido_id existe
            if (!$partidoId) {
                \Log::error('Partido ID inválido o vacío');
                return response()->json([
                    'success' => false,
                    'message' => 'Partido ID inválido'
                ]);
            }
            
            // Buscar el partido
            $partido = Partido::find($partidoId);
            
            if (!$partido) {
                \Log::error('Partido no encontrado con ID: ' . $partidoId);
                return response()->json([
                    'success' => false,
                    'message' => 'Partido no encontrado'
                ]);
            }
            
            \Log::info('Partido encontrado: ID ' . $partido->id);
            
            // Obtener los valores de los sets
            $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
            $pareja1Set2 = $request->pareja_1_set_2 ?? 0;
            $pareja1Set3 = $request->pareja_1_set_3 ?? 0;
            $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
            $pareja2Set2 = $request->pareja_2_set_2 ?? 0;
            $pareja2Set3 = $request->pareja_2_set_3 ?? 0;
            
            \Log::info('Sets recibidos - Pareja 1: ' . $pareja1Set1 . '/' . $pareja1Set2 . '/' . $pareja1Set3);
            \Log::info('Sets recibidos - Pareja 2: ' . $pareja2Set1 . '/' . $pareja2Set2 . '/' . $pareja2Set3);
            
            // Obtener información de las parejas para identificar el orden
            $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
            $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
            $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
            $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
            
            \Log::info('Jugadores - Pareja 1: ' . $pareja1Jugador1 . '/' . $pareja1Jugador2);
            \Log::info('Jugadores - Pareja 2: ' . $pareja2Jugador1 . '/' . $pareja2Jugador2);
            
            // Guardar el resultado usando el método separado
            \Log::info('Llamando a guardarResultadoPartido...');
            $this->guardarResultadoPartido($partido, $torneoId, $pareja1Set1, $pareja1Set2, $pareja1Set3, 
                                          $pareja2Set1, $pareja2Set2, $pareja2Set3,
                                          $pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2);
            
            // Refrescar el partido desde la base de datos para obtener los valores actualizados
            $partido->refresh();
            
            \Log::info('Resultado guardado - Partido ID: ' . $partidoId . ', Ronda: ' . $ronda . 
                      ', Sets P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . 
                      ', Sets P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
            
            // Crear siguientes rondas si es necesario
            if ($ronda === 'octavos') {
                $this->crearCuartosDesdeOctavos($torneoId);
            } else if ($ronda === 'cuartos') {
                $this->crearSemifinalesSiEsNecesario($torneoId);
            } else if ($ronda === 'semifinales') {
                $this->crearFinalSiEsNecesario($torneoId);
            }

            \Log::info('=== FIN guardarResultadoPartidoPuntuable (éxito) ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Resultado guardado correctamente',
                'partido_id' => $partido->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('=== ERROR en guardarResultadoPartidoPuntuable ===');
            \Log::error('Error al guardar resultado del partido: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('==================================================');
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el resultado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda el resultado de un partido en la base de datos
     * 
     * @param Partido $partido El objeto Partido a actualizar
     * @param int $torneoId ID del torneo
     * @param int $pareja1Set1 Set 1 de la pareja 1
     * @param int $pareja1Set2 Set 2 de la pareja 1
     * @param int $pareja1Set3 Set 3 de la pareja 1
     * @param int $pareja2Set1 Set 1 de la pareja 2
     * @param int $pareja2Set2 Set 2 de la pareja 2
     * @param int $pareja2Set3 Set 3 de la pareja 2
     * @param int|null $pareja1Jugador1 ID del jugador 1 de la pareja 1
     * @param int|null $pareja1Jugador2 ID del jugador 2 de la pareja 1
     * @param int|null $pareja2Jugador1 ID del jugador 1 de la pareja 2
     * @param int|null $pareja2Jugador2 ID del jugador 2 de la pareja 2
     */
    private function guardarResultadoPartido($partido, $torneoId, $pareja1Set1, $pareja1Set2, $pareja1Set3,
                                            $pareja2Set1, $pareja2Set2, $pareja2Set3,
                                            $pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
        \Log::info('=== INICIO guardarResultadoPartido ===');
        \Log::info('Partido ID: ' . $partido->id . ', Torneo ID: ' . $torneoId);
        
        // Obtener los grupos asociados a este partido para identificar el orden
        $grupos = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->where('torneo_id', $torneoId)
                    ->orderBy('id')
                    ->get();
        
        \Log::info('Grupos encontrados: ' . $grupos->count());
        
        // Guardar resultado según el orden de los grupos
        if ($grupos->count() >= 2) {
            $g1 = $grupos->get(0);
            $g2 = $grupos->get(1);
            
            \Log::info('Grupo 1 - Jugadores: ' . $g1->jugador_1 . '/' . $g1->jugador_2);
            \Log::info('Grupo 2 - Jugadores: ' . $g2->jugador_1 . '/' . $g2->jugador_2);
            \Log::info('Pareja 1 request - Jugadores: ' . $pareja1Jugador1 . '/' . $pareja1Jugador2);
            \Log::info('Pareja 2 request - Jugadores: ' . $pareja2Jugador1 . '/' . $pareja2Jugador2);
            
            // Verificar qué pareja corresponde a cada grupo
            if ($g1->jugador_1 == $pareja1Jugador1 && $g1->jugador_2 == $pareja1Jugador2) {
                // Pareja 1 del request corresponde al grupo 1
                \Log::info('Pareja 1 del request corresponde al grupo 1 - Asignando directamente');
                $partido->pareja_1_set_1 = $pareja1Set1;
                $partido->pareja_1_set_2 = $pareja1Set2;
                $partido->pareja_1_set_3 = $pareja1Set3;
                $partido->pareja_2_set_1 = $pareja2Set1;
                $partido->pareja_2_set_2 = $pareja2Set2;
                $partido->pareja_2_set_3 = $pareja2Set3;
            } else {
                // Pareja 2 del request corresponde al grupo 1, invertir
                \Log::info('Pareja 2 del request corresponde al grupo 1 - Invirtiendo');
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_1_set_2 = $pareja2Set2;
                $partido->pareja_1_set_3 = $pareja2Set3;
                $partido->pareja_2_set_1 = $pareja1Set1;
                $partido->pareja_2_set_2 = $pareja1Set2;
                $partido->pareja_2_set_3 = $pareja1Set3;
            }
        } else {
            // Si no hay grupos suficientes, guardar directamente
            \Log::info('No hay grupos suficientes (' . $grupos->count() . ') - Guardando directamente');
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_1_set_2 = $pareja1Set2;
            $partido->pareja_1_set_3 = $pareja1Set3;
            $partido->pareja_2_set_1 = $pareja2Set1;
            $partido->pareja_2_set_2 = $pareja2Set2;
            $partido->pareja_2_set_3 = $pareja2Set3;
        }
        
        \Log::info('Valores antes de guardar - P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3);
        \Log::info('Valores antes de guardar - P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Guardar el partido
        $resultadoSave = $partido->save();
        
        \Log::info('Resultado de save(): ' . ($resultadoSave ? 'true' : 'false'));
        \Log::info('=== FIN guardarResultadoPartido ===');
    }

    /**
     * Guarda el resultado de un cruce eliminatorio para torneo puntuable
     */
    public function guardarResultadoCrucePuntuable(Request $request) {
        $torneoId = $request->torneo_id;
        $ronda = $request->ronda; // 'octavos', 'cuartos', 'semifinales', 'final'
        $pareja1Set1 = $request->pareja_1_set_1 ?? 0;
        $pareja1Set2 = $request->pareja_1_set_2 ?? 0;
        $pareja1Set3 = $request->pareja_1_set_3 ?? 0;
        $pareja2Set1 = $request->pareja_2_set_1 ?? 0;
        $pareja2Set2 = $request->pareja_2_set_2 ?? 0;
        $pareja2Set3 = $request->pareja_2_set_3 ?? 0;
        $pareja1Jugador1 = $request->pareja_1_jugador_1 ?? null;
        $pareja1Jugador2 = $request->pareja_1_jugador_2 ?? null;
        $pareja2Jugador1 = $request->pareja_2_jugador_1 ?? null;
        $pareja2Jugador2 = $request->pareja_2_jugador_2 ?? null;
        
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === 'octavos') {
            $zonaRonda = 'octavos final';
        } else if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Buscar si ya existe un partido eliminatorio con estas parejas
        $query = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->whereNotNull('partido_id')
            ->where(function($q) use ($pareja1Jugador1, $pareja1Jugador2, $pareja2Jugador1, $pareja2Jugador2) {
                $q->where(function($q2) use ($pareja1Jugador1, $pareja1Jugador2) {
                    $q2->where('jugador_1', $pareja1Jugador1)
                       ->where('jugador_2', $pareja1Jugador2);
                })
                ->orWhere(function($q2) use ($pareja2Jugador1, $pareja2Jugador2) {
                    $q2->where('jugador_1', $pareja2Jugador1)
                       ->where('jugador_2', $pareja2Jugador2);
                });
            });
        
        // Para octavos y cuartos, buscar por zona que comience con el nombre correspondiente
        if ($ronda === 'octavos') {
            $query->where('zona', 'like', 'octavos final%');
        } else if ($ronda === 'cuartos') {
            $query->where('zona', 'like', 'cuartos final%');
        } else {
            $query->where('zona', $zonaRonda);
        }
        
        $grupo1Encontrado = $query->first();
        
        $partido = null;
        
        if ($grupo1Encontrado) {
            // Buscar el otro grupo del mismo partido
            $query2 = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('partido_id', $grupo1Encontrado->partido_id)
                ->where('id', '!=', $grupo1Encontrado->id);
            
            // Para octavos y cuartos, buscar por zona que comience con el nombre correspondiente
            if ($ronda === 'octavos') {
                $query2->where('zona', 'like', 'octavos final%');
            } else if ($ronda === 'cuartos') {
                $query2->where('zona', 'like', 'cuartos final%');
            } else {
                $query2->where('zona', $zonaRonda);
            }
            
            $grupo2Encontrado = $query2->first();
            
            if ($grupo2Encontrado) {
                // Verificar que el segundo grupo tenga la otra pareja
                $tienePareja1 = ($grupo1Encontrado->jugador_1 == $pareja1Jugador1 && $grupo1Encontrado->jugador_2 == $pareja1Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja1Jugador1 && $grupo2Encontrado->jugador_2 == $pareja1Jugador2);
                $tienePareja2 = ($grupo1Encontrado->jugador_1 == $pareja2Jugador1 && $grupo1Encontrado->jugador_2 == $pareja2Jugador2) ||
                                ($grupo2Encontrado->jugador_1 == $pareja2Jugador1 && $grupo2Encontrado->jugador_2 == $pareja2Jugador2);
                
                if ($tienePareja1 && $tienePareja2) {
                    $partido = Partido::find($grupo1Encontrado->partido_id);
                    
                    // Si existe el partido pero no tiene el número de partido y se está guardando uno, actualizar
                    if (($ronda === 'octavos' || $ronda === 'cuartos') && strpos($grupo1Encontrado->zona, '|') === false) {
                        DB::table('grupos')
                            ->where('partido_id', $grupo1Encontrado->partido_id)
                            ->where('torneo_id', $torneoId)
                            ->update(['zona' => $zonaRonda]);
                    }
                }
            }
        }
        
        // Si no existe, crear nuevo partido y grupos
        if (!$partido) {
            $partido = $this->crearPartido();
            
            // Crear grupo para pareja 1
            $grupo1 = new Grupo;
            $grupo1->torneo_id = $torneoId;
            $grupo1->zona = $zonaRonda;
            $grupo1->fecha = '2000-01-01';
            $grupo1->horario = '00:00';
            $grupo1->jugador_1 = $pareja1Jugador1;
            $grupo1->jugador_2 = $pareja1Jugador2;
            $grupo1->partido_id = $partido->id;
            $grupo1->save();
            
            // Crear grupo para pareja 2
            $grupo2 = new Grupo;
            $grupo2->torneo_id = $torneoId;
            $grupo2->zona = $zonaRonda;
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
                    ->where('torneo_id', $torneoId)
                    ->orderBy('id')
                    ->get();
        
        \Log::info('Grupos obtenidos para partido_id=' . $partido->id . ': ' . $grupos->count());
        
        // Guardar resultado según el orden de los grupos
        if ($grupos->count() >= 2) {
            $g1 = $grupos[0];
            $g2 = $grupos[1];
            
            // Verificar qué pareja corresponde a cada grupo
            if ($g1->jugador_1 == $pareja1Jugador1 && $g1->jugador_2 == $pareja1Jugador2) {
                $partido->pareja_1_set_1 = $pareja1Set1;
                $partido->pareja_1_set_2 = $pareja1Set2;
                $partido->pareja_1_set_3 = $pareja1Set3;
                $partido->pareja_2_set_1 = $pareja2Set1;
                $partido->pareja_2_set_2 = $pareja2Set2;
                $partido->pareja_2_set_3 = $pareja2Set3;
            } else {
                $partido->pareja_1_set_1 = $pareja2Set1;
                $partido->pareja_1_set_2 = $pareja2Set2;
                $partido->pareja_1_set_3 = $pareja2Set3;
                $partido->pareja_2_set_1 = $pareja1Set1;
                $partido->pareja_2_set_2 = $pareja1Set2;
                $partido->pareja_2_set_3 = $pareja1Set3;
            }
            
            // Log para debugging
            \Log::info('Guardando resultado de ' . $ronda . ': partido_id=' . $partido->id . ', pareja_1 sets=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', pareja_2 sets=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        } else {
            $partido->pareja_1_set_1 = $pareja1Set1;
            $partido->pareja_1_set_2 = $pareja1Set2;
            $partido->pareja_1_set_3 = $pareja1Set3;
            $partido->pareja_2_set_1 = $pareja2Set1;
            $partido->pareja_2_set_2 = $pareja2Set2;
            $partido->pareja_2_set_3 = $pareja2Set3;
        }
        
        $partido->save();
        
        \Log::info('Resultado guardado para ronda: ' . $ronda . ', partido_id: ' . $partido->id . ', sets P1: ' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', sets P2: ' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Si se guardó un resultado de octavos, crear grupo de cuartos para el ganador
        if ($ronda === 'octavos') {
            // PRIMERO verificar si ya existen todos los cuartos antes de intentar crear más
            $totalGruposCuartos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->count();
            
            $totalPartidosCuartos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select('partido_id')
                ->distinct()
                ->count();
            
            // NO crear más cuartos si ya existen 4 partidos o 8 grupos
            if ($totalGruposCuartos >= 8 || $totalPartidosCuartos >= 4) {
                \Log::info('Ya existen todos los cuartos de final (grupos: ' . $totalGruposCuartos . ', partidos: ' . $totalPartidosCuartos . '). No se llamará a crearGrupoCuartosDesdeOctavos para partido_id: ' . $partido->id);
            } else {
                \Log::info('Llamando a crearGrupoCuartosDesdeOctavos para partido_id: ' . $partido->id . ' (grupos cuartos existentes: ' . $totalGruposCuartos . ', partidos: ' . $totalPartidosCuartos . ')');
                $this->crearGrupoCuartosDesdeOctavos($torneoId, $partido, $grupos);
            }
        }
        
        // Si se guardó un resultado de cuartos, verificar si se pueden crear las semifinales automáticamente
        if ($ronda === 'cuartos') {
            $this->crearSemifinalesSiEsNecesario($torneoId);
        }
        
        // Si se guardó un resultado de semifinales, verificar si se puede crear la final automáticamente
        if ($ronda === 'semifinales') {
            $this->crearFinalSiEsNecesario($torneoId);
        }
        
        return response()->json([
            'success' => true, 
            'partido' => $partido, 
            'partido_id' => $partido->id
        ]);
    }

    /**
     * Crea un nuevo partido vacío
     */
    private function crearPartido() {
        $partido = new Partido;
        $partido->pareja_1_set_1 = 0;
        $partido->pareja_1_set_1_tie_break = 0;
        $partido->pareja_2_set_1 = 0;
        $partido->pareja_2_set_1_tie_break = 0;
        $partido->pareja_1_set_2 = 0;
        $partido->pareja_1_set_2_tie_break = 0;
        $partido->pareja_2_set_2 = 0;
        $partido->pareja_2_set_2_tie_break = 0;
        $partido->pareja_1_set_3 = 0;
        $partido->pareja_1_set_3_tie_break = 0;    
        $partido->pareja_2_set_3 = 0;
        $partido->pareja_2_set_3_tie_break = 0;
        $partido->pareja_1_set_super_tie_break = 0;
        $partido->pareja_2_set_super_tie_break = 0;
        $partido->save();

        return $partido;
    }

    /**
     * Determina el ganador de un partido basándose en los sets
     * Retorna 1 si ganó pareja_1, 2 si ganó pareja_2
     */
    private function determinarGanadorPartido($partido) {
        $setsGanadosP1 = 0;
        $setsGanadosP2 = 0;
        
        // Set 1
        if (isset($partido->pareja_1_set_1) && isset($partido->pareja_2_set_1)) {
            if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) {
                $setsGanadosP2++;
            }
        }
        
        // Set 2
        if (isset($partido->pareja_1_set_2) && isset($partido->pareja_2_set_2)) {
            if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) {
                $setsGanadosP2++;
            }
        }
        
        // Set 3 (si existe)
        if (isset($partido->pareja_1_set_3) && isset($partido->pareja_2_set_3)) {
            if ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) {
                if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                    $setsGanadosP1++;
                } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                    $setsGanadosP2++;
                }
            }
        }
        
        // Si hay empate en sets, usar super tie break (si existe)
        if ($setsGanadosP1 == $setsGanadosP2) {
            $superTieBreak1 = isset($partido->pareja_1_set_super_tie_break) ? $partido->pareja_1_set_super_tie_break : 0;
            $superTieBreak2 = isset($partido->pareja_2_set_super_tie_break) ? $partido->pareja_2_set_super_tie_break : 0;
            
            if ($superTieBreak1 > $superTieBreak2) {
                return 1;
            } else if ($superTieBreak2 > $superTieBreak1) {
                return 2;
            }
        }
        
        // Si no hay ganador claro (empate sin super tie break), retornar null
        if ($setsGanadosP1 == $setsGanadosP2) {
            return null;
        }
        
        return $setsGanadosP1 > $setsGanadosP2 ? 1 : 2;
    }

    /**
     * Crea grupo de cuartos de final cuando se completa un partido de octavos
     * Respeta el orden: Partido 1 vs Partido 2, Partido 3 vs Partido 4, etc.
     */
    private function crearGrupoCuartosDesdeOctavos($torneoId, $partido, $grupos) {
        // PRIMERO: Verificar si ya existen todos los cuartos posibles (4 partidos de cuartos = 8 grupos)
        // Si ya hay 8 grupos de cuartos final, no intentar crear más
        $totalGruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->count();
        
        // También verificar partidos únicos de cuartos
        $partidosCuartosUnicos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
            ->value('count');
        
        if ($totalGruposCuartos >= 8 || $partidosCuartosUnicos >= 4) {
            \Log::info('Ya existen todos los cuartos de final (grupos: ' . $totalGruposCuartos . ', partidos únicos: ' . $partidosCuartosUnicos . '). No se crearán más.');
            return;
        }
        
        // Verificar que haya un ganador claro (al menos 2 sets ganados)
        $setsGanadosP1 = 0;
        $setsGanadosP2 = 0;
        
        if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) {
            $setsGanadosP2++;
        }
        
        if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) {
            $setsGanadosP2++;
        }
        
        if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
            $setsGanadosP2++;
        }
        
        // Solo crear grupo si hay un ganador claro (al menos 2 sets ganados)
        \Log::info('Verificando ganador en crearGrupoCuartosDesdeOctavos: partido_id=' . $partido->id . ', sets P1=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', sets P2=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3 . ', sets ganados P1=' . $setsGanadosP1 . ', P2=' . $setsGanadosP2);
        
        if ($setsGanadosP1 < 2 && $setsGanadosP2 < 2) {
            \Log::info('Partido de octavos sin ganador claro aún. Sets ganados: P1=' . $setsGanadosP1 . ', P2=' . $setsGanadosP2);
            return;
        }
        
        if ($grupos->count() < 2) {
            \Log::error('No se encontraron los grupos del partido de octavos. Grupos encontrados: ' . $grupos->count() . ', partido_id=' . $partido->id . ', torneo_id=' . $torneoId);
            return;
        }
        
        // Obtener todos los partidos de octavos ordenados por el id del primer grupo (orden de creación)
        // Esto asegura que el orden sea el mismo que en la vista
        $partidosOctavos = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'octavos final')
            ->whereNotNull('grupos.partido_id')
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_1_set_2', 'partidos.pareja_1_set_3',
                     'partidos.pareja_2_set_1', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_3',
                     DB::raw('MIN(grupos.id) as grupo_min_id'))
            ->groupBy('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_1_set_2', 'partidos.pareja_1_set_3',
                     'partidos.pareja_2_set_1', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_3')
            ->orderBy('grupo_min_id')
            ->get();
        
        \Log::info('Partidos de octavos encontrados: ' . $partidosOctavos->count());
        
        // Identificar la posición del partido actual en el orden
        $posicionPartidoActual = -1;
        foreach ($partidosOctavos as $index => $p) {
            if ($p->id == $partido->id) {
                $posicionPartidoActual = $index;
                break;
            }
        }
        
        if ($posicionPartidoActual < 0) {
            \Log::error('No se encontró el partido de octavos en la lista ordenada');
            return;
        }
        
        // Determinar qué número de partido de octavos es (1-8)
        $numeroPartidoOctavos = $posicionPartidoActual + 1;
        
        // Determinar qué par de partidos debe crear el partido de cuartos:
        // Partidos 1 y 2 → Cuartos 1
        // Partidos 3 y 4 → Cuartos 2
        // Partidos 5 y 6 → Cuartos 3
        // Partidos 7 y 8 → Cuartos 4
        $numeroCuartos = (int)(($numeroPartidoOctavos - 1) / 2) + 1;
        $partidoParOctavos = ($numeroCuartos - 1) * 2 + 2; // El otro partido del par
        
        \Log::info('Partido de octavos completado: número=' . $numeroPartidoOctavos . ', debe crear cuartos número=' . $numeroCuartos . ' con partidos ' . (($numeroCuartos - 1) * 2 + 1) . ' y ' . $partidoParOctavos);
        
        // Determinar cuál es el primer partido del par y cuál es el segundo
        $primerPartidoPar = ($numeroCuartos - 1) * 2 + 1;
        $segundoPartidoPar = $partidoParOctavos;
        
        // Si el partido actual es el segundo del par, necesitamos obtener el ganador del primer partido
        // Si el partido actual es el primero del par, necesitamos obtener el ganador del segundo partido
        $esPrimerPartido = ($numeroPartidoOctavos == $primerPartidoPar);
        $esSegundoPartido = ($numeroPartidoOctavos == $segundoPartidoPar);
        
        \Log::info('Partido actual es: ' . ($esPrimerPartido ? 'PRIMERO' : ($esSegundoPartido ? 'SEGUNDO' : 'DESCONOCIDO')) . ' del par');
        
        // Obtener el ganador del partido actual
        $ganador = $this->determinarGanadorPartido($partido);
        
        \Log::info('Ganador determinado para partido actual (partido_id=' . $partido->id . '): ' . $ganador . ' (1=pareja_1, 2=pareja_2)');
        \Log::info('Sets del partido actual: pareja_1=' . $partido->pareja_1_set_1 . '/' . $partido->pareja_1_set_2 . '/' . $partido->pareja_1_set_3 . ', pareja_2=' . $partido->pareja_2_set_1 . '/' . $partido->pareja_2_set_2 . '/' . $partido->pareja_2_set_3);
        
        // Acceder a los grupos de la colección
        $g1 = $grupos[0];
        $g2 = $grupos[1];
        
        \Log::info('Grupos del partido actual: g1 (primer grupo) jugadores=' . $g1->jugador_1 . '/' . $g1->jugador_2 . ', g2 (segundo grupo) jugadores=' . $g2->jugador_1 . '/' . $g2->jugador_2);
        
        // Si el ganador es 1, significa que pareja_1 ganó
        // Si el ganador es 2, significa que pareja_2 ganó
        // Asumimos que g1 es pareja_1 y g2 es pareja_2
        $ganadorActualJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorActualJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        
        \Log::info('Ganador partido actual determinado: jugador_1=' . $ganadorActualJugador1 . ', jugador_2=' . $ganadorActualJugador2);
        
        // Verificar si el partido par también está completo
        $partidoParCompleto = false;
        $ganadorParJugador1 = null;
        $ganadorParJugador2 = null;
        
        // Determinar qué partido necesitamos verificar como "par"
        // Si el partido actual es el primero del par, necesitamos el segundo
        // Si el partido actual es el segundo del par, necesitamos el primero
        $partidoParANumero = $esPrimerPartido ? $segundoPartidoPar : $primerPartidoPar;
        
        \Log::info('Buscando partido par: número=' . $partidoParANumero . ' (actual es ' . ($esPrimerPartido ? 'PRIMERO' : 'SEGUNDO') . ' del par)');
        
        // Obtener el partido par (el otro partido del par)
        if ($partidoParANumero <= count($partidosOctavos) && $partidoParANumero != $numeroPartidoOctavos) {
            $partidoPar = $partidosOctavos[$partidoParANumero - 1];
            
            \Log::info('Verificando partido par: partido_id=' . $partidoPar->id . ' (número ' . $partidoParANumero . '), sets P1=' . $partidoPar->pareja_1_set_1 . '/' . $partidoPar->pareja_1_set_2 . '/' . $partidoPar->pareja_1_set_3 . ', sets P2=' . $partidoPar->pareja_2_set_1 . '/' . $partidoPar->pareja_2_set_2 . '/' . $partidoPar->pareja_2_set_3);
            
            // Verificar si el partido par tiene ganador claro
            $setsGanadosP1Par = 0;
            $setsGanadosP2Par = 0;
            
            if ($partidoPar->pareja_1_set_1 > $partidoPar->pareja_2_set_1) {
                $setsGanadosP1Par++;
            } else if ($partidoPar->pareja_2_set_1 > $partidoPar->pareja_1_set_1) {
                $setsGanadosP2Par++;
            }
            
            if ($partidoPar->pareja_1_set_2 > $partidoPar->pareja_2_set_2) {
                $setsGanadosP1Par++;
            } else if ($partidoPar->pareja_2_set_2 > $partidoPar->pareja_1_set_2) {
                $setsGanadosP2Par++;
            }
            
            if ($partidoPar->pareja_1_set_3 > $partidoPar->pareja_2_set_3) {
                $setsGanadosP1Par++;
            } else if ($partidoPar->pareja_2_set_3 > $partidoPar->pareja_1_set_3) {
                $setsGanadosP2Par++;
            }
            
            \Log::info('Sets ganados partido par: P1=' . $setsGanadosP1Par . ', P2=' . $setsGanadosP2Par);
            
            // Verificar si el partido par tiene ganador claro (al menos 2 sets ganados)
            if ($setsGanadosP1Par >= 2 || $setsGanadosP2Par >= 2) {
                $partidoParCompleto = true;
                
                // Obtener los grupos del partido par
                $gruposPar = DB::table('grupos')
                    ->where('partido_id', $partidoPar->id)
                    ->where('torneo_id', $torneoId)
                    ->orderBy('id')
                    ->get();
                
                \Log::info('Grupos encontrados para partido par: ' . $gruposPar->count());
                
                if ($gruposPar->count() >= 2) {
                    $g1Par = $gruposPar[0];
                    $g2Par = $gruposPar[1];
                    
                    // Obtener el objeto Partido completo para usar determinarGanadorPartido
                    $partidoParCompletoObj = Partido::find($partidoPar->id);
                    
                    if ($partidoParCompletoObj) {
                        // Usar el mismo método que para el partido actual
                        $ganadorPar = $this->determinarGanadorPartido($partidoParCompletoObj);
                        $ganadorParJugador1 = ($ganadorPar == 1) ? $g1Par->jugador_1 : $g2Par->jugador_1;
                        $ganadorParJugador2 = ($ganadorPar == 1) ? $g1Par->jugador_2 : $g2Par->jugador_2;
                        
                        \Log::info('Ganador partido par determinado usando determinarGanadorPartido: ganador=' . $ganadorPar . ', jugador_1=' . $ganadorParJugador1 . ', jugador_2=' . $ganadorParJugador2);
                        \Log::info('Grupos partido par: g1Par jugadores=' . $g1Par->jugador_1 . '/' . $g1Par->jugador_2 . ', g2Par jugadores=' . $g2Par->jugador_1 . '/' . $g2Par->jugador_2);
                    } else {
                        \Log::error('No se encontró el objeto Partido para partido_id=' . $partidoPar->id);
                    }
                } else {
                    \Log::error('No se encontraron suficientes grupos para el partido par. Grupos encontrados: ' . $gruposPar->count());
                }
            } else {
                \Log::info('Partido par no tiene ganador claro aún. Sets ganados: P1=' . $setsGanadosP1Par . ', P2=' . $setsGanadosP2Par);
            }
        } else {
            if ($partidoParANumero == $numeroPartidoOctavos) {
                \Log::info('El partido par es el mismo que el partido actual (número ' . $numeroPartidoOctavos . '). Esto no debería pasar.');
            } else {
                \Log::info('Partido par no existe aún (número ' . $partidoParANumero . ' > ' . count($partidosOctavos) . ')');
            }
        }
        
        // Si ambos partidos del par están completos, verificar si ya existe el partido de cuartos antes de crear
        if ($partidoParCompleto && $ganadorParJugador1 !== null) {
            // PRIMERO: Verificar si ya existe un partido de cuartos con estos mismos ganadores
            $cuartosExistentesConMismosJugadores = DB::table('grupos as g1')
                ->join('grupos as g2', function($join) {
                    $join->on('g1.partido_id', '=', 'g2.partido_id')
                         ->whereRaw('g1.id != g2.id')
                         ->whereNotNull('g1.partido_id')
                         ->whereNotNull('g2.partido_id');
                })
                ->where('g1.torneo_id', $torneoId)
                ->where('g1.zona', 'cuartos final')
                ->where('g2.torneo_id', $torneoId)
                ->where('g2.zona', 'cuartos final')
                ->where(function($query) use ($ganadorActualJugador1, $ganadorActualJugador2, $ganadorParJugador1, $ganadorParJugador2) {
                    $query->where(function($q) use ($ganadorActualJugador1, $ganadorActualJugador2, $ganadorParJugador1, $ganadorParJugador2) {
                        $q->where('g1.jugador_1', $ganadorActualJugador1)
                          ->where('g1.jugador_2', $ganadorActualJugador2)
                          ->where('g2.jugador_1', $ganadorParJugador1)
                          ->where('g2.jugador_2', $ganadorParJugador2);
                    })
                    ->orWhere(function($q) use ($ganadorActualJugador1, $ganadorActualJugador2, $ganadorParJugador1, $ganadorParJugador2) {
                        $q->where('g1.jugador_1', $ganadorParJugador1)
                          ->where('g1.jugador_2', $ganadorParJugador2)
                          ->where('g2.jugador_1', $ganadorActualJugador1)
                          ->where('g2.jugador_2', $ganadorActualJugador2);
                    });
                })
                ->select('g1.partido_id')
                ->distinct()
                ->first();
            
            if ($cuartosExistentesConMismosJugadores) {
                \Log::info('Ya existe un partido de cuartos con estos mismos ganadores. partido_id=' . $cuartosExistentesConMismosJugadores->partido_id . '. No se creará duplicado.');
                return;
            }
            
            // SEGUNDO: Verificar si ya existe el partido de cuartos correspondiente por número
            $partidosCuartosExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select('partido_id')
                ->distinct()
                ->orderBy('partido_id')
                ->get();
            
            $numeroCuartosExistentes = count($partidosCuartosExistentes);
            
            \Log::info('Cuartos existentes: ' . $numeroCuartosExistentes . ', cuartos necesarios para este par: ' . $numeroCuartos);
            
            // Si ya existen 4 o más partidos de cuartos, no crear más
            if ($numeroCuartosExistentes >= 4) {
                \Log::info('Ya existen todos los partidos de cuartos (4 partidos encontrados). No se creará duplicado.');
                return;
            }
            
            if ($numeroCuartosExistentes >= $numeroCuartos) {
                \Log::info('Ya existe el partido de cuartos número ' . $numeroCuartos . '. Total existentes: ' . $numeroCuartosExistentes . '. No se creará duplicado.');
                return;
            }
            
            \Log::info('Verificando condiciones para crear cuartos: partidoParCompleto=' . ($partidoParCompleto ? 'true' : 'false') . ', ganadorParJugador1=' . ($ganadorParJugador1 !== null ? $ganadorParJugador1 : 'null'));
            
            // Validar que los ganadores sean diferentes
            if ($ganadorActualJugador1 == $ganadorParJugador1 && $ganadorActualJugador2 == $ganadorParJugador2) {
                \Log::error('ERROR: Los ganadores son iguales! Ganador actual: ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . ', Ganador par: ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
                return;
            }
            
            // Validar que no haya jugadores repetidos entre las parejas
            if (($ganadorActualJugador1 == $ganadorParJugador1 || $ganadorActualJugador1 == $ganadorParJugador2) ||
                ($ganadorActualJugador2 == $ganadorParJugador1 || $ganadorActualJugador2 == $ganadorParJugador2)) {
                \Log::error('ERROR: Hay jugadores repetidos entre las parejas! Ganador actual: ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . ', Ganador par: ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
                return;
            }
            
            // ÚLTIMA VERIFICACIÓN antes de crear: asegurarse de que aún no existen todos los cuartos
            $verificacionFinalGrupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->count();
            
            $verificacionFinalPartidos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select('partido_id')
                ->distinct()
                ->count();
            
            if ($verificacionFinalGrupos >= 8 || $verificacionFinalPartidos >= 4) {
                \Log::info('VERIFICACIÓN FINAL: Ya existen todos los cuartos (grupos: ' . $verificacionFinalGrupos . ', partidos: ' . $verificacionFinalPartidos . '). No se creará el partido.');
                return;
            }
            
            \Log::info('Creando partido de cuartos número ' . $numeroCuartos);
            \Log::info('Ganador partido octavos ' . (($numeroCuartos - 1) * 2 + 1) . ': ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2);
            \Log::info('Ganador partido octavos ' . $partidoParOctavos . ': ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
            
            // Crear el partido de cuartos
            $partidoCuartos = $this->crearPartido();
            
            \Log::info('Partido de cuartos creado con ID: ' . $partidoCuartos->id);
            
            // Crear grupo para el ganador del primer partido del par
            $grupoCuartos1 = new Grupo;
            $grupoCuartos1->torneo_id = $torneoId;
            $grupoCuartos1->zona = 'cuartos final';
            $grupoCuartos1->fecha = '2000-01-01';
            $grupoCuartos1->horario = '00:00';
            $grupoCuartos1->jugador_1 = $ganadorActualJugador1;
            $grupoCuartos1->jugador_2 = $ganadorActualJugador2;
            $grupoCuartos1->partido_id = $partidoCuartos->id;
            $grupoCuartos1->save();
            
            \Log::info('Grupo cuartos 1 guardado con ID: ' . $grupoCuartos1->id . ', jugadores: ' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2);
            
            // Crear grupo para el ganador del segundo partido del par
            $grupoCuartos2 = new Grupo;
            $grupoCuartos2->torneo_id = $torneoId;
            $grupoCuartos2->zona = 'cuartos final';
            $grupoCuartos2->fecha = '2000-01-01';
            $grupoCuartos2->horario = '00:00';
            $grupoCuartos2->jugador_1 = $ganadorParJugador1;
            $grupoCuartos2->jugador_2 = $ganadorParJugador2;
            $grupoCuartos2->partido_id = $partidoCuartos->id;
            $grupoCuartos2->save();
            
            \Log::info('Grupo cuartos 2 guardado con ID: ' . $grupoCuartos2->id . ', jugadores: ' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
            
            \Log::info('Creado partido de cuartos número ' . $numeroCuartos . ' desde octavos: partido_id=' . $partidoCuartos->id . 
                      ', pareja1 (octavos ' . (($numeroCuartos - 1) * 2 + 1) . ')=' . $ganadorActualJugador1 . '/' . $ganadorActualJugador2 . 
                      ', pareja2 (octavos ' . $partidoParOctavos . ')=' . $ganadorParJugador1 . '/' . $ganadorParJugador2);
        } else {
            \Log::info('Esperando que se complete el partido de octavos ' . $partidoParOctavos . ' para crear el partido de cuartos número ' . $numeroCuartos . 
                      '. partidoParCompleto=' . ($partidoParCompleto ? 'true' : 'false') . ', ganadorParJugador1=' . ($ganadorParJugador1 !== null ? $ganadorParJugador1 : 'null'));
        }
    }

    /**
     * Endpoint público para crear cuartos desde octavos (para debugging)
     */
    public function crearCuartosDesdeOctavosEndpoint(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            
            if (!$torneoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Torneo ID requerido'
                ]);
            }
            
            $this->crearCuartosDesdeOctavos($torneoId);
            
            return response()->json([
                'success' => true,
                'message' => 'Proceso de creación de cuartos ejecutado'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en crearCuartosDesdeOctavosEndpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea los cuartos de final automáticamente cuando se completan los partidos de octavos necesarios
     * Respeta el orden: Partido 1 vs Partido 2, Partido 3 vs Partido 4, etc.
     */
    private function crearCuartosDesdeOctavos($torneoId) {
        // Verificar si ya existen todos los cuartos posibles (4 partidos de cuartos = 8 grupos)
        $totalGruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->count();
        
        $partidosCuartosUnicos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
            ->value('count');
        
        if ($totalGruposCuartos >= 8 || $partidosCuartosUnicos >= 4) {
            \Log::info('Ya existen todos los cuartos de final (grupos: ' . $totalGruposCuartos . ', partidos únicos: ' . $partidosCuartosUnicos . '). No se crearán más.');
            return;
        }
        
        // Obtener todos los partidos de octavos ordenados por el id del primer grupo (orden de creación)
        $partidosOctavos = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'octavos final')
            ->whereNotNull('grupos.partido_id')
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_1_set_2', 'partidos.pareja_1_set_3',
                     'partidos.pareja_2_set_1', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_3',
                     DB::raw('MIN(grupos.id) as grupo_min_id'))
            ->groupBy('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_1_set_2', 'partidos.pareja_1_set_3',
                     'partidos.pareja_2_set_1', 'partidos.pareja_2_set_2', 'partidos.pareja_2_set_3')
            ->orderBy('grupo_min_id')
            ->get();
        
        \Log::info('Revisando partidos de octavos para crear cuartos. Total encontrados: ' . $partidosOctavos->count());
        
        // Procesar los partidos de octavos en pares para crear cuartos
        // Partidos 1 y 2 → Cuartos 1, Partidos 3 y 4 → Cuartos 2, etc.
        for ($i = 0; $i < $partidosOctavos->count(); $i += 2) {
            if ($i + 1 >= $partidosOctavos->count()) {
                // No hay par completo, salir
                break;
            }
            
            $partido1 = $partidosOctavos[$i];
            $partido2 = $partidosOctavos[$i + 1];
            
            // Verificar que ambos partidos tengan ganador claro
            $ganador1 = $this->determinarGanadorPartido($partido1);
            $ganador2 = $this->determinarGanadorPartido($partido2);
            
            // Verificar que haya un ganador claro (al menos 2 sets ganados)
            $setsGanadosP1_1 = 0;
            $setsGanadosP2_1 = 0;
            if ($partido1->pareja_1_set_1 > $partido1->pareja_2_set_1) {
                $setsGanadosP1_1++;
            } else if ($partido1->pareja_2_set_1 > $partido1->pareja_1_set_1) {
                $setsGanadosP2_1++;
            }
            if ($partido1->pareja_1_set_2 > $partido1->pareja_2_set_2) {
                $setsGanadosP1_1++;
            } else if ($partido1->pareja_2_set_2 > $partido1->pareja_1_set_2) {
                $setsGanadosP2_1++;
            }
            if ($partido1->pareja_1_set_3 > $partido1->pareja_2_set_3) {
                $setsGanadosP1_1++;
            } else if ($partido1->pareja_2_set_3 > $partido1->pareja_1_set_3) {
                $setsGanadosP2_1++;
            }
            
            $setsGanadosP1_2 = 0;
            $setsGanadosP2_2 = 0;
            if ($partido2->pareja_1_set_1 > $partido2->pareja_2_set_1) {
                $setsGanadosP1_2++;
            } else if ($partido2->pareja_2_set_1 > $partido2->pareja_1_set_1) {
                $setsGanadosP2_2++;
            }
            if ($partido2->pareja_1_set_2 > $partido2->pareja_2_set_2) {
                $setsGanadosP1_2++;
            } else if ($partido2->pareja_2_set_2 > $partido2->pareja_1_set_2) {
                $setsGanadosP2_2++;
            }
            if ($partido2->pareja_1_set_3 > $partido2->pareja_2_set_3) {
                $setsGanadosP1_2++;
            } else if ($partido2->pareja_2_set_3 > $partido2->pareja_1_set_3) {
                $setsGanadosP2_2++;
            }
            
            if ($setsGanadosP1_1 < 2 && $setsGanadosP2_1 < 2) {
                \Log::info('Partido de octavos ' . ($i + 1) . ' sin ganador claro aún. Continuando...');
                continue;
            }
            
            if ($setsGanadosP1_2 < 2 && $setsGanadosP2_2 < 2) {
                \Log::info('Partido de octavos ' . ($i + 2) . ' sin ganador claro aún. Continuando...');
                continue;
            }
            
            // Obtener los grupos de cada partido para identificar los ganadores
            $grupos1 = DB::table('grupos')
                ->where('partido_id', $partido1->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->orderBy('id')
                ->get();
            
            $grupos2 = DB::table('grupos')
                ->where('partido_id', $partido2->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->orderBy('id')
                ->get();
            
            if ($grupos1->count() < 2 || $grupos2->count() < 2) {
                \Log::error('No se encontraron los grupos completos para los partidos de octavos');
                continue;
            }
            
            $g1_1 = $grupos1->get(0);
            $g1_2 = $grupos1->get(1);
            $g2_1 = $grupos2->get(0);
            $g2_2 = $grupos2->get(1);
            
            // Determinar ganadores
            $ganador1Pareja = ($ganador1 == 1) ? 
                ['jugador_1' => $g1_1->jugador_1, 'jugador_2' => $g1_1->jugador_2] : 
                ['jugador_1' => $g1_2->jugador_1, 'jugador_2' => $g1_2->jugador_2];
            
            $ganador2Pareja = ($ganador2 == 1) ? 
                ['jugador_1' => $g2_1->jugador_1, 'jugador_2' => $g2_1->jugador_2] : 
                ['jugador_1' => $g2_2->jugador_1, 'jugador_2' => $g2_2->jugador_2];
            
            // Verificar si ya existe un partido de cuartos con estos ganadores
            $numeroCuartos = ($i / 2) + 1;
            $partidoCuartosExistente = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->where(function($q) use ($ganador1Pareja, $ganador2Pareja) {
                    $q->where(function($q2) use ($ganador1Pareja, $ganador2Pareja) {
                        $q2->where('jugador_1', $ganador1Pareja['jugador_1'])
                           ->where('jugador_2', $ganador1Pareja['jugador_2']);
                    })
                    ->orWhere(function($q2) use ($ganador1Pareja, $ganador2Pareja) {
                        $q2->where('jugador_1', $ganador2Pareja['jugador_1'])
                           ->where('jugador_2', $ganador2Pareja['jugador_2']);
                    });
                })
                ->whereNotNull('partido_id')
                ->first();
            
            if ($partidoCuartosExistente) {
                \Log::info('Ya existe un partido de cuartos con estos ganadores. No se creará duplicado.');
                continue;
            }
            
            // Verificación final antes de crear
            $verificacionFinalGrupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->count();
            
            $verificacionFinalPartidos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
                ->value('count');
            
            if ($verificacionFinalGrupos >= 8 || $verificacionFinalPartidos >= 4) {
                \Log::info('VERIFICACIÓN FINAL: Ya existen todos los cuartos (grupos: ' . $verificacionFinalGrupos . ', partidos: ' . $verificacionFinalPartidos . '). No se creará el partido.');
                continue;
            }
            
            // Crear el partido de cuartos
            $partidoCuartos = $this->crearPartido();
            
            \Log::info('Creando partido de cuartos número ' . $numeroCuartos);
            \Log::info('Ganador partido octavos ' . ($i + 1) . ': ' . $ganador1Pareja['jugador_1'] . '/' . $ganador1Pareja['jugador_2']);
            \Log::info('Ganador partido octavos ' . ($i + 2) . ': ' . $ganador2Pareja['jugador_1'] . '/' . $ganador2Pareja['jugador_2']);
            
            // Crear grupo para el ganador del primer partido
            $grupoCuartos1 = new Grupo;
            $grupoCuartos1->torneo_id = $torneoId;
            $grupoCuartos1->zona = 'cuartos final';
            $grupoCuartos1->fecha = '2000-01-01';
            $grupoCuartos1->horario = '00:00';
            $grupoCuartos1->jugador_1 = $ganador1Pareja['jugador_1'];
            $grupoCuartos1->jugador_2 = $ganador1Pareja['jugador_2'];
            $grupoCuartos1->partido_id = $partidoCuartos->id;
            $grupoCuartos1->save();
            
            // Crear grupo para el ganador del segundo partido
            $grupoCuartos2 = new Grupo;
            $grupoCuartos2->torneo_id = $torneoId;
            $grupoCuartos2->zona = 'cuartos final';
            $grupoCuartos2->fecha = '2000-01-01';
            $grupoCuartos2->horario = '00:00';
            $grupoCuartos2->jugador_1 = $ganador2Pareja['jugador_1'];
            $grupoCuartos2->jugador_2 = $ganador2Pareja['jugador_2'];
            $grupoCuartos2->partido_id = $partidoCuartos->id;
            $grupoCuartos2->save();
            
            \Log::info('Partido de cuartos creado con ID: ' . $partidoCuartos->id . ', grupos: ' . $grupoCuartos1->id . ' y ' . $grupoCuartos2->id);
        }
    }

    /**
     * Crea las semifinales automáticamente cuando se completan los cuartos necesarios
     */
    private function crearSemifinalesSiEsNecesario($torneoId) {
        // Verificar que existan al menos 4 partidos de cuartos completos antes de crear semifinales
        $totalPartidosCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
            ->value('count');
        
        if ($totalPartidosCuartos < 4) {
            \Log::info('Aún no hay suficientes partidos de cuartos completos (' . $totalPartidosCuartos . '/4). No se crearán semifinales.');
            return;
        }
        
        // Buscar todos los partidos de cuartos con resultados en la tabla grupos
        // Obtener todos los partidos de cuartos con resultados
        $partidosIds = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'like', 'cuartos final%')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0);
            })
            ->select('partidos.id')
            ->distinct()
            ->pluck('id');
        
        // Luego obtener los datos completos de los partidos
        $partidosCuartos = DB::table('partidos')
            ->whereIn('id', $partidosIds)
            ->get();
        
        // Ordenar los partidos por partido_id
        $partidosCuartosOrdenados = $partidosCuartos->sortBy('id')->values();
        
        // Obtener los ganadores de cada partido de cuartos, agrupados por semifinal
        $ganadoresPorSemifinal = [
            'Semifinal 1' => [],
            'Semifinal 2' => []
        ];
        $partidosProcesados = [];
        
        foreach ($partidosCuartosOrdenados as $index => $partido) {
            if (in_array($partido->id, $partidosProcesados)) {
                continue;
            }
            
            $gruposCompletos = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'like', 'cuartos final%')
                ->orderBy('id')
                ->get();
            
            if ($gruposCompletos->count() >= 2) {
                $g1 = $gruposCompletos[0];
                $g2 = $gruposCompletos[1];
                
                if ($partido->pareja_1_set_1 == 0 && $partido->pareja_2_set_1 == 0) {
                    continue;
                }
                
                // Determinar ganador usando el método determinarGanadorPartido
                $ganadorPartido = $this->determinarGanadorPartido($partido);
                
                if ($ganadorPartido === null) {
                    \Log::info('Partido de cuartos ' . $partido->id . ' no tiene ganador claro aún. Saltando.');
                    continue;
                }
                
                // Determinar ganador según el resultado del partido
                $ganador = ($ganadorPartido == 1) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                // Los primeros 2 partidos de cuartos van a Semifinal 1, los siguientes 2 a Semifinal 2
                $semifinalAsignada = ($index < 2) ? 'Semifinal 1' : 'Semifinal 2';
                
                // Verificar que no haya duplicados
                $yaExiste = false;
                foreach ($ganadoresPorSemifinal[$semifinalAsignada] as $ganadorExistente) {
                    if ($ganadorExistente['jugador_1'] == $ganador['jugador_1'] && 
                        $ganadorExistente['jugador_2'] == $ganador['jugador_2']) {
                        $yaExiste = true;
                        break;
                    }
                }
                
                if (!$yaExiste) {
                    $ganadoresPorSemifinal[$semifinalAsignada][] = $ganador;
                    $partidosProcesados[] = $partido->id;
                }
            }
        }
        
        // Obtener las semifinales existentes ordenadas por partido_id
        $semifinalesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        $semifinalesPorPartido = [];
        foreach ($semifinalesExistentes as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($semifinalesPorPartido[$partidoId])) {
                $semifinalesPorPartido[$partidoId] = [];
            }
            $semifinalesPorPartido[$partidoId][] = $grupo;
        }
        
        // Si no hay semifinales, crearlas vacías primero
        if (count($semifinalesPorPartido) == 0) {
            $parejaVacia = ['jugador_1' => 0, 'jugador_2' => 0];
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
            
            // Re-obtener las semifinales después de crearlas
            $semifinalesExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            
            $semifinalesPorPartido = [];
            foreach ($semifinalesExistentes as $grupo) {
                $partidoId = $grupo->partido_id;
                if (!isset($semifinalesPorPartido[$partidoId])) {
                    $semifinalesPorPartido[$partidoId] = [];
                }
                $semifinalesPorPartido[$partidoId][] = $grupo;
            }
        }
        
        // Actualizar Semifinal 1 SOLO con los ganadores de "Semifinal 1"
        if (count($ganadoresPorSemifinal['Semifinal 1']) == 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 0) {
                $partidoIdSemifinal1 = $partidosIds[0];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal1]) && count($semifinalesPorPartido[$partidoIdSemifinal1]) >= 2) {
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal1][0]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 1'][0]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 1'][0]['jugador_2']
                        ]);
                    
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal1][1]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 1'][1]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 1'][1]['jugador_2']
                        ]);
                }
            }
        }
        
        // Actualizar Semifinal 2 SOLO con los ganadores de "Semifinal 2"
        if (count($ganadoresPorSemifinal['Semifinal 2']) == 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 1) {
                $partidoIdSemifinal2 = $partidosIds[1];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal2]) && count($semifinalesPorPartido[$partidoIdSemifinal2]) >= 2) {
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal2][0]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 2'][0]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 2'][0]['jugador_2']
                        ]);
                    
                    DB::table('grupos')
                        ->where('id', $semifinalesPorPartido[$partidoIdSemifinal2][1]->id)
                        ->update([
                            'jugador_1' => $ganadoresPorSemifinal['Semifinal 2'][1]['jugador_1'],
                            'jugador_2' => $ganadoresPorSemifinal['Semifinal 2'][1]['jugador_2']
                        ]);
                }
            }
        }
    }

    /**
     * Crea la final automáticamente cuando se completan las semifinales necesarias
     */
    private function crearFinalSiEsNecesario($torneoId) {
        // Obtener todos los partidos de semifinales con resultados
        $partidosSemifinales = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'semifinal')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Verificar si hay al menos 2 semifinales completas
        $ganadoresSemifinales = [];
        foreach ($partidosSemifinales as $index => $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                // Determinar ganador
                $ganador = ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                $ganadoresSemifinales[$index] = $ganador;
            }
        }
        
        // Actualizar final existente con los ganadores de semifinales
        if (count($ganadoresSemifinales) >= 2 && isset($ganadoresSemifinales[0]) && isset($ganadoresSemifinales[1])) {
            // Buscar la final existente
            $finalExistente = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            
            if ($finalExistente->count() >= 2) {
                // Actualizar los grupos de la final
                DB::table('grupos')
                    ->where('id', $finalExistente[0]->id)
                    ->update([
                        'jugador_1' => $ganadoresSemifinales[0]['jugador_1'],
                        'jugador_2' => $ganadoresSemifinales[0]['jugador_2']
                    ]);
                
                DB::table('grupos')
                    ->where('id', $finalExistente[1]->id)
                    ->update([
                        'jugador_1' => $ganadoresSemifinales[1]['jugador_1'],
                        'jugador_2' => $ganadoresSemifinales[1]['jugador_2']
                    ]);
            } else {
                // Si no existe, crearla
                $this->crearPartidoEliminatorio($torneoId, $ganadoresSemifinales[0], $ganadoresSemifinales[1], 'final');
            }
        }
    }

    /**
     * Crea un partido eliminatorio (semifinal o final)
     */
    private function crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, $ronda) {
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Para partidos vacíos (jugadores 0), verificar cantidad de partidos existentes de esa ronda
        if ($pareja1['jugador_1'] == 0 && $pareja1['jugador_2'] == 0 && 
            $pareja2['jugador_1'] == 0 && $pareja2['jugador_2'] == 0) {
            // Contar cuántos partidos de esta ronda ya existen
            $partidosExistentes = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zonaRonda)
                ->whereNotNull('partido_id')
                ->select(DB::raw('COUNT(DISTINCT partido_id) as count'))
                ->value('count');
            
            // Si es semifinales, solo crear 2 máximo
            if ($ronda === 'semifinales' && $partidosExistentes >= 2) {
                return;
            }
            // Si es final, solo crear 1 máximo
            if ($ronda === 'final' && $partidosExistentes >= 1) {
                return;
            }
        } else {
            // Si no son jugadores vacíos, verificar si ya existe este partido específico
            $partidoExistente = DB::table('grupos as g1')
                ->join('grupos as g2', function($join) {
                    $join->on('g1.partido_id', '=', 'g2.partido_id')
                         ->whereRaw('g1.id != g2.id')
                         ->whereNotNull('g1.partido_id')
                         ->whereNotNull('g2.partido_id');
                })
                ->where('g1.torneo_id', $torneoId)
                ->where('g1.zona', $zonaRonda)
                ->where('g2.torneo_id', $torneoId)
                ->where('g2.zona', $zonaRonda)
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
                ->first();
            
            if ($partidoExistente) {
                return; // Ya existe este partido
            }
        }
        
        // Crear el partido
        $partido = $this->crearPartido();
        
        // Crear grupo para pareja 1
        $grupo1 = new Grupo;
        $grupo1->torneo_id = $torneoId;
        $grupo1->zona = $zonaRonda;
        $grupo1->fecha = '2000-01-01';
        $grupo1->horario = '00:00';
        $grupo1->jugador_1 = $pareja1['jugador_1'];
        $grupo1->jugador_2 = $pareja1['jugador_2'];
        $grupo1->partido_id = $partido->id;
        $grupo1->save();
        
        // Crear grupo para pareja 2
        $grupo2 = new Grupo;
        $grupo2->torneo_id = $torneoId;
        $grupo2->zona = $zonaRonda;
        $grupo2->fecha = '2000-01-01';
        $grupo2->horario = '00:00';
        $grupo2->jugador_1 = $pareja2['jugador_1'];
        $grupo2->jugador_2 = $pareja2['jugador_2'];
        $grupo2->partido_id = $partido->id;
        $grupo2->save();
    }
}

