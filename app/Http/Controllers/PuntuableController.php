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
            ->whereIn('zona', ['octavos final', 'cuartos final', 'semifinal', 'final', '16avos final'])
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
                if ($datosPartido['zona'] === '16avos final') {
                    $ronda = '16avos';
                } else if ($datosPartido['zona'] === 'cuartos final') {
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
        
        // SIEMPRE generar cruces desde la configuración para completar los que faltan
        // (especialmente los cruces de cuartos que esperan ganadores de octavos)
        // Esto asegura que se muestren todos los cruces configurados, incluso los que esperan ganadores
        \Log::info('Generando cruces desde configuración para torneo: ' . $torneoId . ' (cruces en BD: ' . count($cruces) . ')');
        
        // Calcular posiciones por zona
        $grupos = DB::table('grupos')
                    ->where('grupos.torneo_id', $torneoId)
                    ->where(function($query) {
                        $query->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'])
                              ->where('grupos.zona', 'not like', 'cuartos final|%')
                              ->where('grupos.zona', 'not like', 'ganador %')
                              ->where('grupos.zona', 'not like', 'perdedor %');
                    })
                    ->orderBy('grupos.zona')
                    ->orderBy('grupos.id')
                    ->get();
        
        $posicionesPorZona = [];
        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        
        foreach ($zonas as $zona) {
                $gruposZona = $grupos->where('zona', $zona)->filter(function($grupo) {
                    return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
                });
                
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
                            'puntos_perdidos' => 0,
                            'partidos_directos' => []
                        ];
                    }
                }
                
                $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
                $partidosZona = DB::table('partidos')
                                ->whereIn('id', $partidosIds)
                                ->get();
                
                $gruposPorPartido = [];
                foreach ($gruposZona as $grupo) {
                    if ($grupo->partido_id) {
                        if (!isset($gruposPorPartido[$grupo->partido_id])) {
                            $gruposPorPartido[$grupo->partido_id] = [];
                        }
                        $gruposPorPartido[$grupo->partido_id][] = $grupo;
                    }
                }
                
                foreach ($partidosZona as $partido) {
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
                            $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                            $parejas[$key2]['partidos_perdidos']++;
                            $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                            $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        } else if ($puntosPareja2 > $puntosPareja1) {
                            $parejas[$key2]['partidos_ganados']++;
                            $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                            $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                            $parejas[$key1]['partidos_perdidos']++;
                            $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                            $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        }
                    }
                }
                
                // Ordenar por partidos ganados, diferencia de games, etc.
                foreach ($parejas as $key => $pareja) {
                    $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
                }
                
                $posiciones = array_values($parejas);
                usort($posiciones, function($a, $b) {
                    if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                        return $b['partidos_ganados'] - $a['partidos_ganados'];
                    }
                    return $b['diferencia_games'] - $a['diferencia_games'];
                });
                
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        // Obtener configuración de cruces (prioridad: torneo_id, luego global)
        $totalParejasClasificadas = 0;
        foreach ($posicionesPorZona as $zona => $posiciones) {
            $totalParejasClasificadas += count($posiciones);
        }
        
        $configuracionCruces = $this->getConfiguracionCruces($torneoId, $totalParejasClasificadas);
        
        if ($configuracionCruces) {
            \Log::info('Configuración encontrada para ' . $totalParejasClasificadas . ' parejas (torneo ' . $torneoId . ')');
            $crucesDesdeConfig = $this->generarCrucesDesdeConfiguracion($configuracionCruces, $posicionesPorZona, $zonas);
            
            // Asegurar que cada cruce de primera ronda (octavos/16avos) tenga partido en BD
            foreach ($crucesDesdeConfig as $idx => $cruceConfig) {
                $ronda = $cruceConfig['ronda'] ?? null;
                if ($ronda !== 'octavos' && $ronda !== '16avos') {
                    continue;
                }
                $p1 = $cruceConfig['pareja_1'] ?? null;
                $p2 = $cruceConfig['pareja_2'] ?? null;
                if (!$p1 || !$p2 || !isset($p1['jugador_1'], $p1['jugador_2'], $p2['jugador_1'], $p2['jugador_2'])) {
                    continue;
                }
                $pareja1 = ['jugador_1' => $p1['jugador_1'], 'jugador_2' => $p1['jugador_2']];
                $pareja2 = ['jugador_1' => $p2['jugador_1'], 'jugador_2' => $p2['jugador_2']];
                
                $partidoExistente = DB::table('grupos as g1')
                    ->join('grupos as g2', function($join) {
                        $join->on('g1.partido_id', '=', 'g2.partido_id')->whereRaw('g1.id != g2.id')
                             ->whereNotNull('g1.partido_id')->whereNotNull('g2.partido_id');
                    })
                    ->where('g1.torneo_id', $torneoId)
                    ->where('g1.zona', $ronda === '16avos' ? '16avos final' : 'octavos final')
                    ->where('g2.torneo_id', $torneoId)
                    ->where(function($q) use ($pareja1, $pareja2) {
                        $q->where(function($q2) use ($pareja1, $pareja2) {
                            $q2->where('g1.jugador_1', $pareja1['jugador_1'])->where('g1.jugador_2', $pareja1['jugador_2'])
                               ->where('g2.jugador_1', $pareja2['jugador_1'])->where('g2.jugador_2', $pareja2['jugador_2']);
                        })->orWhere(function($q2) use ($pareja1, $pareja2) {
                            $q2->where('g1.jugador_1', $pareja2['jugador_1'])->where('g1.jugador_2', $pareja2['jugador_2'])
                               ->where('g2.jugador_1', $pareja1['jugador_1'])->where('g2.jugador_2', $pareja1['jugador_2']);
                        });
                    })
                    ->select('g1.partido_id')
                    ->first();
                
                if ($partidoExistente) {
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoExistente->partido_id;
                } else {
                    $nuevoPartidoId = $this->crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, $ronda);
                    if ($nuevoPartidoId) {
                        $crucesDesdeConfig[$idx]['partido_id'] = $nuevoPartidoId;
                    }
                }
            }
            
            // Rellenar partido y resultados en cada cruce; para cuartos/semifinal/final rellenar parejas desde BD si existen
            $partidosCuartosBD = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')->orderBy('id')
                ->get()
                ->groupBy('partido_id');
            $partidosSemifinalBD = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')->orderBy('id')
                ->get()
                ->groupBy('partido_id');
            $partidosFinalBD = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')->orderBy('id')
                ->get()
                ->groupBy('partido_id');
            
            // Cargar datos de TODOS los partidos (octavos + cuartos + semi + final) para que los resultados se muestren al recargar
            $partidosIds = [];
            foreach ($crucesDesdeConfig as $c) {
                if (!empty($c['partido_id'])) {
                    $partidosIds[] = $c['partido_id'];
                }
            }
            $partidosIds = array_merge(
                $partidosIds,
                $partidosCuartosBD->keys()->all(),
                $partidosSemifinalBD->keys()->all(),
                $partidosFinalBD->keys()->all()
            );
            $partidosIds = array_unique(array_filter($partidosIds));
            $partidosObj = [];
            if (count($partidosIds) > 0) {
                $partidosObj = DB::table('partidos')->whereIn('id', $partidosIds)->get()->keyBy('id');
            }
            
            $cuartosOrdenados = $partidosCuartosBD->keys()->sort()->values()->all();
            $semifinalOrdenados = $partidosSemifinalBD->keys()->sort()->values()->all();
            $finalOrdenados = $partidosFinalBD->keys()->sort()->values()->all();
            $partidosCuartosUsados = []; // Evitar asignar el mismo partido a varios cruces
            
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (!empty($c['partido_id'])) {
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$c['partido_id']] ?? null;
                }
                $ronda = $c['ronda'] ?? null;
                if ($ronda === 'cuartos') {
                    // Buscar el partido en BD que corresponde a ESTE cruce (por parejas), no por índice
                    $expectedP1 = $c['pareja_1'] ?? null;
                    $expectedP2 = $c['pareja_2'] ?? null;
                    $partidoEncontrado = null;
                    foreach ($partidosCuartosBD as $partidoId => $gruposC) {
                        if (isset($partidosCuartosUsados[$partidoId]) || $gruposC->count() < 2) continue;
                        $g1 = $gruposC[0]; $g2 = $gruposC[1];
                        $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                        $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                        $g1MatchP1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        $g1MatchP2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        $g2MatchP1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        $g2MatchP2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        if ($g1MatchP1 || $g1MatchP2 || $g2MatchP1 || $g2MatchP2) {
                            $partidoEncontrado = ['partido_id' => $partidoId, 'g1' => $g1, 'g2' => $g2, 'g1Vacio' => $g1Vacio, 'g2Vacio' => $g2Vacio];
                            break;
                        }
                    }
                    if ($partidoEncontrado) {
                        $partidoId = $partidoEncontrado['partido_id'];
                        $g1 = $partidoEncontrado['g1']; $g2 = $partidoEncontrado['g2'];
                        $g1Vacio = $partidoEncontrado['g1Vacio']; $g2Vacio = $partidoEncontrado['g2Vacio'];
                        $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                        $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                        $matchG1P2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        $matchG2P2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                        $matchG1P1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        $matchG2P1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                        if ($matchG1P2) {
                            $crucesDesdeConfig[$idx]['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        } elseif ($matchG2P2) {
                            $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        } elseif ($matchG1P1) {
                            $crucesDesdeConfig[$idx]['pareja_1'] = ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        } elseif ($matchG2P1) {
                            $crucesDesdeConfig[$idx]['pareja_1'] = ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        } else {
                            $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        }
                        $partidosCuartosUsados[$partidoId] = true;
                    }
                }
                if ($ronda === 'semifinales' && isset($c['id']) && preg_match('/semifinales_(\d+)/', $c['id'], $m)) {
                    $i = (int)$m[1] - 1;
                    if (isset($semifinalOrdenados[$i])) {
                        $gruposS = $partidosSemifinalBD[$semifinalOrdenados[$i]];
                        if ($gruposS->count() >= 2) {
                            $g1 = $gruposS[0]; $g2 = $gruposS[1];
                            $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                            $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                            $crucesDesdeConfig[$idx]['partido_id'] = $semifinalOrdenados[$i];
                            $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$semifinalOrdenados[$i]] ?? null;
                            $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        }
                    }
                }
                if ($ronda === 'final' && isset($c['id']) && preg_match('/final_(\d+)/', $c['id'], $m)) {
                    $i = (int)$m[1] - 1;
                    if (isset($finalOrdenados[$i])) {
                        $gruposF = $partidosFinalBD[$finalOrdenados[$i]];
                        if ($gruposF->count() >= 2) {
                            $g1 = $gruposF[0]; $g2 = $gruposF[1];
                            $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                            $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                            $crucesDesdeConfig[$idx]['partido_id'] = $finalOrdenados[$i];
                            $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$finalOrdenados[$i]] ?? null;
                            $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                            $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        }
                    }
                }
            }
            
            // Segunda pasada cuartos: cruces que siguen sin partido_id — buscar partido no usado que coincida por parejas
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (($c['ronda'] ?? '') !== 'cuartos') continue;
                if (!empty($c['partido_id'])) continue;
                $expectedP1 = $c['pareja_1'] ?? null;
                $expectedP2 = $c['pareja_2'] ?? null;
                foreach ($partidosCuartosBD as $partidoId => $gruposC) {
                    if (isset($partidosCuartosUsados[$partidoId]) || $gruposC->count() < 2) continue;
                    $g1 = $gruposC[0]; $g2 = $gruposC[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $g1MatchP1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                    $g1MatchP2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                    $g2MatchP1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                    $g2MatchP2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                    if (!$g1MatchP1 && !$g1MatchP2 && !$g2MatchP1 && !$g2MatchP2) continue;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $matchG1P2 = $expectedP2 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                    $matchG2P2 = $expectedP2 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP2['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP2['jugador_2'] ?? 0);
                    $matchG1P1 = $expectedP1 && !$g1Vacio && (int)$g1->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g1->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                    $matchG2P1 = $expectedP1 && !$g2Vacio && (int)$g2->jugador_1 === (int)($expectedP1['jugador_1'] ?? 0) && (int)$g2->jugador_2 === (int)($expectedP1['jugador_2'] ?? 0);
                    if ($matchG1P2) {
                        $crucesDesdeConfig[$idx]['pareja_1'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        $crucesDesdeConfig[$idx]['pareja_2'] = ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    } elseif ($matchG2P2) {
                        $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        $crucesDesdeConfig[$idx]['pareja_2'] = ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    } elseif ($matchG1P1) {
                        $crucesDesdeConfig[$idx]['pareja_1'] = ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    } elseif ($matchG2P1) {
                        $crucesDesdeConfig[$idx]['pareja_1'] = ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                        $crucesDesdeConfig[$idx]['pareja_2'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    } else {
                        $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                        $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    }
                    $partidosCuartosUsados[$partidoId] = true;
                    break;
                }
            }
            
            // Tercera pasada: asignar por índice los partidos de BD que no se matchearon (evita perder parejas al recargar)
            foreach ($crucesDesdeConfig as $idx => $c) {
                $ronda = $c['ronda'] ?? null;
                $tieneParejas = isset($c['pareja_1']['jugador_1'], $c['pareja_2']['jugador_1']) && (int)($c['pareja_1']['jugador_1'] ?? 0) > 0 && (int)($c['pareja_2']['jugador_1'] ?? 0) > 0;
                if ($tieneParejas && !empty($c['partido_id'])) continue;
                if ($ronda === 'cuartos') {
                    $i = null;
                    if (isset($c['id']) && preg_match('/cuartos_(\d+)/', $c['id'], $m)) $i = (int)$m[1] - 1;
                    if ($i === null || !isset($cuartosOrdenados[$i])) continue;
                    $partidoId = $cuartosOrdenados[$i];
                    if (isset($partidosCuartosUsados[$partidoId])) continue;
                    $gruposC = $partidosCuartosBD[$partidoId] ?? null;
                    if (!$gruposC || $gruposC->count() < 2) continue;
                    $g1 = $gruposC[0]; $g2 = $gruposC[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                    $partidosCuartosUsados[$partidoId] = true;
                }
                if ($ronda === 'semifinales') {
                    $i = null;
                    if (isset($c['id']) && preg_match('/semifinales_(\d+)/', $c['id'], $m)) $i = (int)$m[1] - 1;
                    if ($i === null || !isset($semifinalOrdenados[$i])) continue;
                    $partidoId = $semifinalOrdenados[$i];
                    $gruposS = $partidosSemifinalBD[$partidoId] ?? null;
                    if (!$gruposS || $gruposS->count() < 2) continue;
                    $g1 = $gruposS[0]; $g2 = $gruposS[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                }
                if ($ronda === 'final') {
                    $i = null;
                    if (isset($c['id']) && preg_match('/final_(\d+)/', $c['id'], $m)) $i = (int)$m[1] - 1;
                    if ($i === null || !isset($finalOrdenados[$i])) continue;
                    $partidoId = $finalOrdenados[$i];
                    $gruposF = $partidosFinalBD[$partidoId] ?? null;
                    if (!$gruposF || $gruposF->count() < 2) continue;
                    $g1 = $gruposF[0]; $g2 = $gruposF[1];
                    $g1Vacio = (int)($g1->jugador_1 ?? 0) === 0 && (int)($g1->jugador_2 ?? 0) === 0;
                    $g2Vacio = (int)($g2->jugador_1 ?? 0) === 0 && (int)($g2->jugador_2 ?? 0) === 0;
                    $crucesDesdeConfig[$idx]['partido_id'] = $partidoId;
                    $crucesDesdeConfig[$idx]['partido'] = $partidosObj[$partidoId] ?? null;
                    $crucesDesdeConfig[$idx]['pareja_1'] = $g1Vacio ? null : ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2];
                    $crucesDesdeConfig[$idx]['pareja_2'] = $g2Vacio ? null : ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                }
            }
            
            // Si un partido de cuartos tiene resultados pero una pareja sigue null (grupo en BD con 0,0), rellenar con el ganador de la referencia (O1, O2, G1-8vos, etc.)
            $ganadoresOctavosPorNumero = [];
            $partidoIdToONumeroRelleno = [];
            if ($configuracionCruces->llave_8vos ?? null) {
                $llave8vos = json_decode($configuracionCruces->llave_8vos, true);
                if ($llave8vos && is_array($llave8vos)) {
                    $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                    $zonaALetra = [];
                    foreach ($zonas as $idx => $z) {
                        if (isset($letrasZonas[$idx])) $zonaALetra[$z] = $letrasZonas[$idx];
                    }
                    $gruposOctavos = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->where('zona', 'octavos final')
                        ->whereNotNull('partido_id')
                        ->orderBy('partido_id')->orderBy('id')
                        ->get()->groupBy('partido_id');
                    foreach ($llave8vos as $idx => $partido8vos) {
                        $p1Ref = $partido8vos['pareja_1'] ?? null;
                        $p2Ref = $partido8vos['pareja_2'] ?? null;
                        if (!$p1Ref || !$p2Ref) continue;
                        $j1 = $j2 = null;
                        if (preg_match('/^([A-P])(\d+)$/', $p1Ref, $m)) {
                            foreach ($zonaALetra as $zona => $letra) {
                                if ($letra === $m[1] && isset($posicionesPorZona[$zona][(int)$m[2] - 1])) {
                                    $p = $posicionesPorZona[$zona][(int)$m[2] - 1];
                                    $j1 = ($p['jugador_1'] ?? 0) . '_' . ($p['jugador_2'] ?? 0);
                                    break;
                                }
                            }
                        }
                        if (preg_match('/^([A-P])(\d+)$/', $p2Ref, $m)) {
                            foreach ($zonaALetra as $zona => $letra) {
                                if ($letra === $m[1] && isset($posicionesPorZona[$zona][(int)$m[2] - 1])) {
                                    $p = $posicionesPorZona[$zona][(int)$m[2] - 1];
                                    $j2 = ($p['jugador_1'] ?? 0) . '_' . ($p['jugador_2'] ?? 0);
                                    break;
                                }
                            }
                        }
                        if (!$j1 || !$j2) continue;
                        foreach ($gruposOctavos as $pid => $gruposP) {
                            if ($gruposP->count() < 2) continue;
                            $g1 = $gruposP[0]; $g2 = $gruposP[1];
                            $k1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                            $k2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                            if (($k1 === $j1 && $k2 === $j2) || ($k1 === $j2 && $k2 === $j1)) {
                                $partidoIdToONumeroRelleno[$pid] = $idx + 1;
                                break;
                            }
                        }
                    }
                }
            }
            $partidoIdsOctavosOrden = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->pluck('partido_id')->unique()->values();
            foreach ($partidoIdsOctavosOrden as $num => $pid) {
                $partidoO = $partidosObj[$pid] ?? null;
                if (!$partidoO) {
                    $partidoO = DB::table('partidos')->where('id', $pid)->first();
                }
                if ($partidoO) {
                    $gruposO = DB::table('grupos')->where('torneo_id', $torneoId)->where('partido_id', $pid)->orderBy('id')->get();
                    if ($gruposO->count() >= 2) {
                        $ganador = $this->determinarGanadorPartido($partidoO);
                        if ($ganador) {
                            $g1 = $gruposO[0]; $g2 = $gruposO[1];
                            $onum = $partidoIdToONumeroRelleno[$pid] ?? ($num + 1);
                            $ganadoresOctavosPorNumero[$onum] = [
                                'jugador_1' => $ganador == 1 ? $g1->jugador_1 : $g2->jugador_1,
                                'jugador_2' => $ganador == 1 ? $g1->jugador_2 : $g2->jugador_2
                            ];
                        }
                    }
                }
            }
            foreach ($crucesDesdeConfig as $idx => $c) {
                if (($c['ronda'] ?? '') !== 'cuartos') continue;
                $partido = $c['partido'] ?? null;
                if (!$partido || empty($c['partido_id'])) continue;
                $p = is_array($partido) ? (object)$partido : $partido;
                $tieneResultados = ((int)($p->pareja_1_set_1 ?? 0) > 0 || (int)($p->pareja_2_set_1 ?? 0) > 0 || (int)($p->pareja_1_set_2 ?? 0) > 0 || (int)($p->pareja_2_set_2 ?? 0) > 0);
                if (!$tieneResultados) continue;
                $ref1 = $c['referencia_1'] ?? '';
                $ref2 = $c['referencia_2'] ?? '';
                $pareja1Null = !isset($c['pareja_1']['jugador_1']) || (int)($c['pareja_1']['jugador_1'] ?? 0) === 0;
                $pareja2Null = !isset($c['pareja_2']['jugador_1']) || (int)($c['pareja_2']['jugador_1'] ?? 0) === 0;
                if ($pareja1Null && $ref1 && (preg_match('/^O(\d+)$/', $ref1, $m) || preg_match('/^G(\d+)-8vos$/', $ref1, $m) || preg_match('/^G(\d+)-octavos$/', $ref1, $m))) {
                    $n = (int)$m[1];
                    if (!empty($ganadoresOctavosPorNumero[$n])) {
                        $crucesDesdeConfig[$idx]['pareja_1'] = $ganadoresOctavosPorNumero[$n];
                    }
                }
                if ($pareja2Null && $ref2 && (preg_match('/^O(\d+)$/', $ref2, $m) || preg_match('/^G(\d+)-8vos$/', $ref2, $m) || preg_match('/^G(\d+)-octavos$/', $ref2, $m))) {
                    $n = (int)$m[1];
                    if (!empty($ganadoresOctavosPorNumero[$n])) {
                        $crucesDesdeConfig[$idx]['pareja_2'] = $ganadoresOctavosPorNumero[$n];
                    }
                }
            }
            
            // Usar solo los cruces generados desde la configuración (todos conforme a la config)
            $cruces = $crucesDesdeConfig;
            
            // Reconstruir resultadosGuardados desde los cruces que tengan partido con resultado
            $resultadosGuardados = [];
            foreach ($cruces as $cruce) {
                $partido = $cruce['partido'] ?? null;
                if (!$partido || !isset($cruce['pareja_1']['jugador_1'], $cruce['pareja_2']['jugador_1'])) {
                    continue;
                }
                $p = is_array($partido) ? (object)$partido : $partido;
                if (($p->pareja_1_set_1 ?? 0) > 0 || ($p->pareja_2_set_1 ?? 0) > 0 ||
                    ($p->pareja_1_set_2 ?? 0) > 0 || ($p->pareja_2_set_2 ?? 0) > 0 ||
                    ($p->pareja_1_set_3 ?? 0) > 0 || ($p->pareja_2_set_3 ?? 0) > 0) {
                    $resultadosGuardados[] = [
                        'partido_id' => $cruce['partido_id'],
                        'cruce_id' => $cruce['id'],
                        'ronda' => $cruce['ronda'],
                        'pareja_1_jugador_1' => $cruce['pareja_1']['jugador_1'],
                        'pareja_1_jugador_2' => $cruce['pareja_1']['jugador_2'],
                        'pareja_2_jugador_1' => $cruce['pareja_2']['jugador_1'],
                        'pareja_2_jugador_2' => $cruce['pareja_2']['jugador_2'],
                        'pareja_1_set_1' => $p->pareja_1_set_1 ?? null,
                        'pareja_1_set_2' => $p->pareja_1_set_2 ?? null,
                        'pareja_1_set_3' => $p->pareja_1_set_3 ?? null,
                        'pareja_2_set_1' => $p->pareja_2_set_1 ?? null,
                        'pareja_2_set_2' => $p->pareja_2_set_2 ?? null,
                        'pareja_2_set_3' => $p->pareja_2_set_3 ?? null,
                    ];
                }
            }
            
            \Log::info('Cruces armados desde configuración: ' . count($cruces));
        }
                        
        $crucesOctavos = $this->obtenerCrucesPorZona($cruces, 'octavos final');
        $crucesCuartos = $this->obtenerCrucesPorZona($cruces, 'cuartos final');
        $crucesSemifinales = $this->obtenerCrucesPorZona($cruces, 'semifinal');
        $crucesFinales = $this->obtenerCrucesPorZona($cruces, 'final');
        
        \Log::info('Cruces de cuartos filtrados: ' . count($crucesCuartos));
        \Log::info('Detalle cruces de cuartos: ' . json_encode($crucesCuartos));

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
     * GET: Participantes del torneo puntuable (jugadores que aparecen en grupos) y referencias de puntos.
     * Calcula automáticamente posición (campeón, sub, 3º/4º, cuartos, octavos, 16avos, no clasificados) según resultados del cuadro
     * y ordena la lista por esa posición.
     */
    public function getParticipantesTorneoPuntuable(Request $request) {
        $torneoId = $request->get('torneo_id');
        if (!$torneoId) {
            return response()->json(['success' => false, 'message' => 'torneo_id requerido'], 400);
        }
        // La tabla grupos tiene jugador_1 y jugador_2 por fila (una pareja por fila)
        $ids = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->selectRaw('jugador_1 as id')
            ->unionAll(DB::table('grupos')->where('torneo_id', $torneoId)->selectRaw('jugador_2 as id'))
            ->pluck('id')
            ->filter(function ($id) { return $id > 0; })
            ->unique()
            ->values();
        $posiciones = $this->calcularPosicionesDesdeCruces($torneoId);
        $referencias = DB::table('puntos_ranking_referencia')->orderBy('orden')->get(['codigo', 'nombre', 'puntos']);
        $refMap = $referencias->keyBy('codigo');
        $ordenPosicion = ['campeon' => 1, 'subcampeon' => 2, 'tercero_cuarto' => 3, 'cuartos' => 4, 'octavos' => 5, '16avos' => 6, 'no_clasificados' => 7];
        $jugadores = DB::table('jugadores')->whereIn('id', $ids)->get(['id', 'nombre', 'apellido']);
        foreach ($jugadores as $j) {
            $codigo = $posiciones[$j->id] ?? 'no_clasificados';
            $ref = $refMap->get($codigo);
            $j->referencia_codigo = $codigo;
            $j->puntos = $ref ? (int) $ref->puntos : 5;
            $j->orden_posicion = $ordenPosicion[$codigo] ?? 99;
        }
        $jugadores = $jugadores->sortBy(function ($j) {
            return sprintf('%02d_%s %s', $j->orden_posicion, $j->nombre ?? '', $j->apellido ?? '');
        })->values()->all();
        return response()->json(['success' => true, 'jugadores' => $jugadores, 'referencias' => $referencias]);
    }

    /**
     * Calcula la posición de cada jugador en el torneo según resultados del cuadro eliminatorio.
     * Retorna [ jugador_id => referencia_codigo ] (campeon, subcampeon, tercero_cuarto, cuartos, octavos, 16avos, no_clasificados).
     */
    private function calcularPosicionesDesdeCruces($torneoId) {
        $posiciones = [];
        $zonasOrden = [
            ['zona' => 'final',           'ganador_codigo' => 'campeon',    'perdedor_codigo' => 'subcampeon'],
            ['zona' => 'semifinal',       'ganador_codigo' => null,         'perdedor_codigo' => 'tercero_cuarto'],
            ['zona' => 'cuartos final',   'ganador_codigo' => null,         'perdedor_codigo' => 'cuartos'],
            ['zona' => 'octavos final',   'ganador_codigo' => null,         'perdedor_codigo' => 'octavos'],
            ['zona' => '16avos final',   'ganador_codigo' => null,         'perdedor_codigo' => '16avos'],
        ];
        foreach ($zonasOrden as $config) {
            $zona = $config['zona'];
            $gruposZona = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->whereNotNull('partido_id')
                ->where('partido_id', '>', 0)
                ->orderBy('partido_id')
                ->orderBy('id')
                ->get();
            $partidoIds = $gruposZona->pluck('partido_id')->unique()->filter()->values();
            $partidos = $partidoIds->isEmpty() ? collect() : DB::table('partidos')->whereIn('id', $partidoIds)->get()->keyBy('id');
            foreach ($partidoIds as $pid) {
                $partido = $partidos->get($pid);
                if (!$partido || !$this->partidoTieneResultado($partido)) continue;
                $ganador = $this->determinarGanadorPartido($partido);
                if ($ganador === null) continue;
                $perdedor = $ganador === 1 ? 2 : 1;
                $gruposPartido = $gruposZona->where('partido_id', $pid)->sortBy('id')->values();
                if ($gruposPartido->count() < 2) continue;
                $gGanador = $gruposPartido[$ganador - 1];
                $gPerdedor = $gruposPartido[$perdedor - 1];
                $idsGanador = [(int) $gGanador->jugador_1, (int) $gGanador->jugador_2];
                $idsPerdedor = [(int) $gPerdedor->jugador_1, (int) $gPerdedor->jugador_2];
                foreach ($idsGanador as $id) {
                    if ($id > 0 && !isset($posiciones[$id]) && $config['ganador_codigo']) $posiciones[$id] = $config['ganador_codigo'];
                }
                foreach ($idsPerdedor as $id) {
                    if ($id > 0 && !isset($posiciones[$id]) && $config['perdedor_codigo']) $posiciones[$id] = $config['perdedor_codigo'];
                }
            }
        }
        return $posiciones;
    }

    /** True si el partido tiene al menos un set con resultado cargado. */
    private function partidoTieneResultado($partido) {
        if (isset($partido->pareja_1_set_1) && ($partido->pareja_1_set_1 > 0 || (isset($partido->pareja_2_set_1) && $partido->pareja_2_set_1 > 0))) return true;
        if (isset($partido->pareja_1_set_2) && ($partido->pareja_1_set_2 > 0 || (isset($partido->pareja_2_set_2) && $partido->pareja_2_set_2 > 0))) return true;
        if (isset($partido->pareja_1_set_3) && ($partido->pareja_1_set_3 > 0 || (isset($partido->pareja_2_set_3) && $partido->pareja_2_set_3 > 0))) return true;
        return false;
    }

    /**
     * POST: Guardar puntos de ranking por torneo (ranking_puntos + actualizar ranking_totales).
     */
    public function guardarPuntosRankingTorneo(Request $request) {
        $torneoId = $request->input('torneo_id');
        $items = $request->input('items', []);
        if (!$torneoId || !is_array($items)) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }
        $torneo = DB::table('torneos')->where('id', $torneoId)->first();
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        $categoria = isset($torneo->categoria) ? (int) $torneo->categoria : 6;
        $tipo = isset($torneo->tipo) && in_array($torneo->tipo, ['masculino', 'femenino', 'mixto'], true) ? $torneo->tipo : 'masculino';
        $fecha = isset($torneo->fecha_fin) && $torneo->fecha_fin
            ? $torneo->fecha_fin
            : (isset($torneo->fecha_inicio) ? $torneo->fecha_inicio : now()->format('Y-m-d'));
        $temporada = (int) date('Y', strtotime($fecha));
        $afectados = [];
        foreach ($items as $item) {
            $jugadorId = (int) ($item['jugador_id'] ?? 0);
            $puntos = (int) ($item['puntos'] ?? 0);
            $referenciaCodigo = (string) ($item['referencia_codigo'] ?? 'no_clasificados');
            if ($jugadorId <= 0) continue;
            $now = now();
            DB::table('ranking_puntos')->updateOrInsert(
                ['jugador_id' => $jugadorId, 'torneo_id' => $torneoId],
                [
                    'categoria' => $categoria,
                    'tipo' => $tipo,
                    'puntos' => $puntos,
                    'referencia_codigo' => $referenciaCodigo,
                    'temporada' => $temporada,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            if (!isset($afectados[$jugadorId])) $afectados[$jugadorId] = ['categoria' => $categoria, 'temporada' => $temporada, 'tipo' => $tipo];
        }
        foreach ($afectados as $jugadorId => $par) {
            $total = DB::table('ranking_puntos')
                ->where('jugador_id', $jugadorId)
                ->where('categoria', $par['categoria'])
                ->where('temporada', $par['temporada'])
                ->where('tipo', $par['tipo'])
                ->sum('puntos');
            $now = now();
            DB::table('ranking_totales')->updateOrInsert(
                ['jugador_id' => $jugadorId, 'categoria' => $par['categoria'], 'temporada' => $par['temporada'], 'tipo' => $par['tipo']],
                ['puntos_totales' => $total, 'updated_at' => $now, 'created_at' => $now]
            );
        }
        return response()->json(['success' => true, 'message' => 'Puntos guardados en el ranking correctamente.']);
    }

    /**
     * Obtiene los cruces filtrados por zona/ronda
     * 
     * @param array $cruces Array de cruces
     * @param string $zona Zona a filtrar ('octavos final', 'cuartos final', 'semifinal', 'final')
     * @return array Array de cruces filtrados por la zona especificada
     */
    private function obtenerCrucesPorZona($cruces, $zona) {
        // Mapear zona a ronda(s) - octavos final incluye 16avos y octavos
        $rondaMap = [
            'octavos final' => ['16avos', 'octavos'],
            'cuartos final' => ['cuartos'],
            'semifinal' => ['semifinales'],
            'final' => ['final']
        ];
        
        $rondas = $rondaMap[$zona] ?? null;
        
        if (!$rondas) {
            \Log::warning('Zona no reconocida en obtenerCrucesPorZona: ' . $zona);
            return [];
        }
        
        $rondas = (array) $rondas;
        // Filtrar cruces por ronda(s)
        $crucesFiltrados = array_filter($cruces, function($cruce) use ($rondas) {
            return isset($cruce['ronda']) && in_array($cruce['ronda'], $rondas);
        });
        // Ordenar: 16avos antes que octavos cuando hay ambos
        usort($crucesFiltrados, function($a, $b) use ($rondas) {
            $pa = array_search($a['ronda'], $rondas);
            $pb = array_search($b['ronda'], $rondas);
            if ($pa !== $pb) return $pa - $pb;
            return ($a['id'] ?? '') <=> ($b['id'] ?? '');
        });
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
            
            // Crear siguientes rondas si es necesario y obtener partido_id creado (para actualizar la llave sin recargar)
            $partidoIdSiguiente = null;
            if ($ronda === 'octavos') {
                $partidoIdSiguiente = $this->crearCuartosDesdeConfiguracionYOctavos($torneoId, $partido);
            } else if ($ronda === 'cuartos') {
                $partidoIdSiguiente = $this->crearSemifinalesSiEsNecesario($torneoId);
            } else if ($ronda === 'semifinales') {
                $partidoIdSiguiente = $this->crearFinalSiEsNecesario($torneoId);
            }

            // Preparar datos del ganador para actualizar la llave siguiente en el frontend (sin recargar)
            $ganadorLlave = $this->obtenerGanadorLlaveParaFrontend($partido, $ronda, $request->cruce_id ?? '');
            if ($ganadorLlave && $partidoIdSiguiente) {
                $ganadorLlave['partido_id_siguiente'] = $partidoIdSiguiente;
            }

            \Log::info('=== FIN guardarResultadoPartidoPuntuable (éxito) ===');
            
            // Incrementar versión del torneo para notificar a vistas TV
            \App\Torneo::incrementarVersion($torneoId);
            
            $respuesta = [
                'success' => true,
                'message' => 'Resultado guardado correctamente',
                'partido_id' => $partido->id
            ];
            if ($ganadorLlave) {
                $respuesta['ganador_llave'] = $ganadorLlave;
            }
            return response()->json($respuesta);
            
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
     * Obtiene los datos del ganador del partido para que el frontend actualice la llave siguiente sin recargar.
     * Retorna refs (O1, G1-8vos, etc.), ronda_siguiente, jugadores y datos para mostrar (nombre, foto).
     */
    private function obtenerGanadorLlaveParaFrontend($partido, $ronda, $cruceId) {
        $ganador = $this->determinarGanadorPartido($partido);
        if (!$ganador) {
            return null;
        }
        $gruposPartido = DB::table('grupos')
            ->where('partido_id', $partido->id)
            ->orderBy('id')
            ->get();
        if ($gruposPartido->count() < 2) {
            return null;
        }
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $jugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $jugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;

        // Referencias que este ganador llena en la llave siguiente (para que el frontend encuentre el slot)
        // Importante: aquí incluimos TODAS las variantes que se usan en la configuración
        // para que coincidan con los data-llave-ref1/data-llave-ref2 de la vista.
        $refs = [];
        if (preg_match('/^octavos_(\d+)$/i', trim($cruceId), $m)) {
            // Ganador de octavos N puede referenciarse como:
            //  - O{N}          (código simple de octavos)
            //  - G{N}-8vos     (ganador octavos)
            //  - G{N}-octavos  (alias textual)
            $n = (int)$m[1];
            $refs = [
                'O' . $n,
                'G' . $n . '-8vos',
                'G' . $n . '-octavos',
            ];
        } elseif (preg_match('/^cuartos_(\d+)$/i', trim($cruceId), $m)) {
            // Ganador de cuartos N puede referenciarse como:
            //  - G{N}-4tos     (según comentario de configuración)
            //  - G{N}-cuartos  (alias textual)
            //  - C{N}          (C1,C2,C3,C4 = ganadores de cuartos 1..4)
            $n = (int)$m[1];
            $refs = [
                'G' . $n . '-4tos',
                'G' . $n . '-cuartos',
                'C' . $n,
            ];
        } elseif (preg_match('/^semifinales_(\d+)$/i', trim($cruceId), $m)) {
            // Ganador de semifinales N puede referenciarse como:
            //  - G{N}-2tos       (nombre interno)
            //  - G{N}-semis      (alias interno)
            //  - G{N}-semifinal  (usado en configuración: G1-semifinal, G2-semifinal)
            $n = (int)$m[1];
            $refs = [
                'G' . $n . '-2tos',
                'G' . $n . '-semis',
                'G' . $n . '-semifinal',
            ];
        } elseif (preg_match('/^final_(\d+)$/i', trim($cruceId), $m)) {
            // Ganador de la final: pueden existir varias etiquetas en futuras vistas,
            // pero para el cuadro actual no necesita llenar otra llave.
            $refs = ['Ganador Final', 'Final'];
        }
        if (empty($refs)) {
            return null;
        }

        $rondaSiguiente = null;
        if ($ronda === 'octavos') {
            $rondaSiguiente = 'cuartos';
        } elseif ($ronda === 'cuartos') {
            $rondaSiguiente = 'semifinales';
        } elseif ($ronda === 'semifinales') {
            $rondaSiguiente = 'final';
        } elseif ($ronda === 'final') {
            return null;
        }

        $jugadores = DB::table('jugadores')
            ->whereIn('id', [$jugador1, $jugador2])
            ->get()
            ->keyBy('id');
        $j1 = $jugadores->get($jugador1);
        $j2 = $jugadores->get($jugador2);
        $foto1Path = ($j1 && !empty($j1->foto)) ? $j1->foto : 'images/jugador_img.png';
        $foto2Path = ($j2 && !empty($j2->foto)) ? $j2->foto : 'images/jugador_img.png';

        return [
            'refs' => $refs,
            'ronda_siguiente' => $rondaSiguiente,
            'jugador_1' => (int)$jugador1,
            'jugador_2' => (int)$jugador2,
            'nombre1' => $j1 ? trim(($j1->nombre ?? '') . ' ' . ($j1->apellido ?? '')) : '',
            'nombre2' => $j2 ? trim(($j2->nombre ?? '') . ' ' . ($j2->apellido ?? '')) : '',
            'foto1' => asset($foto1Path),
            'foto2' => asset($foto2Path),
        ];
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
        
        // Si se guardó un resultado de octavos, crear partidos de cuartos basándose en la configuración
        if ($ronda === 'octavos') {
            \Log::info('Resultado de octavos guardado, verificando si se pueden crear cuartos desde configuración');
            $this->crearCuartosDesdeConfiguracionYOctavos($torneoId, $partido);
        }
        
        // Si se guardó un resultado de cuartos, crear semifinales desde configuración (C1..C4); fallback a lógica legacy
        if ($ronda === 'cuartos') {
            $this->crearSemifinalesDesdeConfiguracionYCuartos($torneoId, $partido);
            $this->crearSemifinalesSiEsNecesario($torneoId);
        }
        
        // Si se guardó un resultado de semifinales, crear final desde configuración (G1-semifinal, G2-semifinal); fallback a lógica legacy
        if ($ronda === 'semifinales') {
            $this->crearFinalDesdeConfiguracionYSemifinales($torneoId, $partido);
            $this->crearFinalSiEsNecesario($torneoId);
        }
        
        // Incrementar versión del torneo para notificar a vistas TV
        \App\Torneo::incrementarVersion($torneoId);
        
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
     * Obtiene la configuración de cruces según cantidad de parejas.
     * Primero busca config del torneo, luego global (torneo_id null).
     * Si no hay para la cantidad exacta, intenta con 16 (llave estándar 8 octavos / 4 cuartos).
     */
    private function getConfiguracionCruces($torneoId, $cantidadParejas) {
        foreach ([$torneoId, null] as $tid) {
            $q = DB::table('configuracion_cruces_puntuables')
                ->where('cantidad_parejas', $cantidadParejas)
                ->orderBy('id', 'desc');
            if ($tid === null) {
                $q->whereNull('torneo_id');
            } else {
                $q->where('torneo_id', $tid);
            }
            $config = $q->first();
            if ($config) {
                return $config;
            }
        }
        // Fallback: si hay 24 o más parejas y no hay config, usar config de 16 (misma estructura 8 octavos)
        if ($cantidadParejas >= 16) {
            $config = DB::table('configuracion_cruces_puntuables')
                ->whereNull('torneo_id')
                ->where('cantidad_parejas', 16)
                ->whereNotNull('llave_4tos')
                ->orderBy('id', 'desc')
                ->first();
            if ($config) {
                return $config;
            }
        }
        return null;
    }

    /**
     * Crea un partido eliminatorio (octavos, 16avos, cuartos, semifinal o final).
     * @return int|null ID del partido creado o null si ya existía
     */
    private function crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, $ronda) {
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === '16avos') {
            $zonaRonda = '16avos final';
        } else if ($ronda === 'octavos') {
            $zonaRonda = 'octavos final';
        } else if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        if ($zonaRonda === '') {
            \Log::warning('crearPartidoEliminatorio: ronda no reconocida: ' . $ronda);
            return null;
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
                return null;
            }
            // Si es final, solo crear 1 máximo
            if ($ronda === 'final' && $partidosExistentes >= 1) {
                return null;
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
                ->select('g1.partido_id')
                ->first();
            
            if ($partidoExistente) {
                return $partidoExistente->partido_id ?? null; // Ya existe este partido
            }
        }
        
        // Crear el partido
        $partido = $this->crearPartido();
        
        // Crear grupo para pareja 1 (zona = 'cuartos final', 'semifinal', 'final', etc.)
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
        
        \Log::info('crearPartidoEliminatorio: creados partido_id=' . $partido->id . ', zona=' . $zonaRonda . ', grupos id=' . $grupo1->id . ',' . $grupo2->id);
        return $partido->id;
    }
    
    /**
     * Genera cruces usando la configuración guardada
     */
    private function generarCrucesDesdeConfiguracion($configuracion, $posicionesPorZona, $zonas) {
        $cruces = [];
        $zonasArray = $zonas->toArray();
        sort($zonasArray);
        
        // Mapear zonas a letras (A, B, C, D, etc.)
        $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $zonaALetra = [];
        foreach ($zonasArray as $index => $zona) {
            if (isset($letrasZonas[$index])) {
                $zonaALetra[$zona] = $letrasZonas[$index];
            }
        }
        
        // Función para obtener pareja desde una referencia (ej: "A1", "B2", "G1-4tos")
        // $contextoRonda: 'semifinales' | 'final' | null - para distinguir C1=ganador cuartos vs C1=zona C
        $obtenerParejaDesdeReferencia = function($referencia, $torneoId = null, $contextoRonda = null) use ($posicionesPorZona, $zonaALetra) {
            // En semifinales: C1,C2,C3,C4 = Ganador Cuartos 1,2,3,4 (no zona C). Evitar confusión.
            if (in_array($contextoRonda, ['semifinales']) && in_array($referencia, ['C1','C2','C3','C4'])) {
                return null;
            }
            
            // PRIMERO verificar referencias a ganadores de rondas (antes que zonas)
            if (preg_match('/^G(\d+)-4tos$/', $referencia, $m) || preg_match('/^G(\d+)-cuartos$/', $referencia, $m)) {
                return null;
            }
            if (preg_match('/^O(\d+)$/', $referencia, $m) || preg_match('/^G(\d+)-8vos$/', $referencia, $m) || preg_match('/^G(\d+)-octavos$/', $referencia, $m)) {
                return null;
            }
            if (preg_match('/^G(\d+)-semifinal$/', $referencia, $m)) {
                return null;
            }
            
            // LUEGO verificar referencia directa a zona (ej: "A1", "B2", "C2", "A3")
            if (preg_match('/^([A-P])(\d+)$/', $referencia, $matches)) {
                $letra = $matches[1];
                $posicion = (int)$matches[2];
                
                // Buscar la zona que corresponde a esta letra
                foreach ($zonaALetra as $zona => $letraZona) {
                    if ($letraZona === $letra) {
                        if (isset($posicionesPorZona[$zona]) && isset($posicionesPorZona[$zona][$posicion - 1])) {
                            $pareja = $posicionesPorZona[$zona][$posicion - 1];
                            return [
                                'jugador_1' => $pareja['jugador_1'],
                                'jugador_2' => $pareja['jugador_2'],
                                'zona' => $zona,
                                'posicion' => $posicion
                            ];
                        }
                    }
                }
            }
            
            return null;
        };
        
        // Generar cruces para cada ronda según la configuración
        // Primero 16avos (si existe)
        if ($configuracion->tiene_16avos_final && $configuracion->llave_16avos) {
            $llave = json_decode($configuracion->llave_16avos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        if ($pareja1 && $pareja2) {
                            $cruces[] = [
                                'id' => '16avos_' . ($index + 1),
                                'partido_id' => null,
                                'pareja_1' => $pareja1,
                                'pareja_2' => $pareja2,
                                'ronda' => '16avos'
                            ];
                        }
                    }
                }
            }
        }
        
        // Luego octavos (si existe)
        if ($configuracion->tiene_8vos_final && $configuracion->llave_8vos) {
            $llave = json_decode($configuracion->llave_8vos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        if ($pareja1 && $pareja2) {
                            $cruces[] = [
                                'id' => 'octavos_' . ($index + 1),
                                'partido_id' => null,
                                'pareja_1' => $pareja1,
                                'pareja_2' => $pareja2,
                                'ronda' => 'octavos'
                            ];
                        }
                    }
                }
            }
        }
        
        // Luego cuartos (si existe) - Incluir cruces incluso si una pareja es null (referencia a ganador)
        if ($configuracion->tiene_4tos_final && $configuracion->llave_4tos) {
            $llave = json_decode($configuracion->llave_4tos, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        
                        // Agregar el cruce incluso si una pareja es null (será "Esperando ganador")
                        $cruces[] = [
                            'id' => 'cuartos_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1, // Puede ser null
                            'pareja_2' => $pareja2, // Puede ser null
                            'ronda' => 'cuartos',
                            'referencia_1' => $pareja1Ref, // Guardar la referencia original
                            'referencia_2' => $pareja2Ref  // Guardar la referencia original
                        ];
                    }
                }
            }
        }
        
        // Semifinales (referencias G1-4tos, G2-4tos o C1,C2,C3,C4 = ganadores Cuartos 1,2,3,4)
        if ($configuracion->llave_semifinal) {
            $llave = json_decode($configuracion->llave_semifinal, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref, null, 'semifinales');
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref, null, 'semifinales');
                        $cruces[] = [
                            'id' => 'semifinales_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1,
                            'pareja_2' => $pareja2,
                            'ronda' => 'semifinales',
                            'referencia_1' => $pareja1Ref,
                            'referencia_2' => $pareja2Ref
                        ];
                    }
                }
            }
        }
        
        // Final (referencias G1-semifinal, G2-semifinal)
        if ($configuracion->llave_final) {
            $llave = json_decode($configuracion->llave_final, true);
            if ($llave && is_array($llave)) {
                foreach ($llave as $index => $partido) {
                    $pareja1Ref = $partido['pareja_1'] ?? null;
                    $pareja2Ref = $partido['pareja_2'] ?? null;
                    if ($pareja1Ref && $pareja2Ref) {
                        $pareja1 = $obtenerParejaDesdeReferencia($pareja1Ref);
                        $pareja2 = $obtenerParejaDesdeReferencia($pareja2Ref);
                        $cruces[] = [
                            'id' => 'final_' . ($index + 1),
                            'partido_id' => null,
                            'pareja_1' => $pareja1,
                            'pareja_2' => $pareja2,
                            'ronda' => 'final',
                            'referencia_1' => $pareja1Ref,
                            'referencia_2' => $pareja2Ref
                        ];
                    }
                }
            }
        }
        
        return $cruces;
    }
    
    /**
     * Crea partidos de cuartos basándose en la configuración cuando se completa un partido de octavos
     * Resuelve las referencias (O1, O2, etc.) a los ganadores reales de octavos
     * @return int|null ID del partido de cuartos creado en esta llamada, o null
     */
    private function crearCuartosDesdeConfiguracionYOctavos($torneoId, $partidoOctavos) {
        \Log::info('=== INICIO crearCuartosDesdeConfiguracionYOctavos ===');
        $partidoIdCreado = null;
        \Log::info('Partido de octavos completado: partido_id=' . $partidoOctavos->id);
        
        // Obtener la configuración de cruces
        $grupos = DB::table('grupos')
                    ->where('grupos.torneo_id', $torneoId)
                    ->where(function($query) {
                        $query->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final', 'octavos final', '16avos final'])
                              ->where('grupos.zona', 'not like', 'cuartos final|%')
                              ->where('grupos.zona', 'not like', 'ganador %')
                              ->where('grupos.zona', 'not like', 'perdedor %');
                    })
                    ->orderBy('grupos.zona')
                    ->orderBy('grupos.id')
                    ->get();
        
        $posicionesPorZona = [];
        $zonas = $grupos->pluck('zona')->unique()->sort()->values();
        
        // Calcular posiciones por zona (necesario para resolver referencias directas A1, B2, etc.)
        $totalParejasClasificadas = 0;
        foreach ($zonas as $zona) {
            $gruposZona = $grupos->where('zona', $zona)->filter(function($grupo) {
                return $grupo->jugador_1 !== null && $grupo->jugador_2 !== null;
            });
            
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
                        'puntos_perdidos' => 0
                    ];
                }
            }
            
            $partidosIds = $gruposZona->pluck('partido_id')->unique()->filter();
            $partidosZona = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            
            $gruposPorPartido = [];
            foreach ($gruposZona as $grupo) {
                if ($grupo->partido_id) {
                    if (!isset($gruposPorPartido[$grupo->partido_id])) {
                        $gruposPorPartido[$grupo->partido_id] = [];
                    }
                    $gruposPorPartido[$grupo->partido_id][] = $grupo;
                }
            }
            
            foreach ($partidosZona as $partido) {
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
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                    } else if ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    }
                }
            }
            
            // Ordenar por partidos ganados, diferencia de games, etc.
            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
            }
            
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['diferencia_games'] - $a['diferencia_games'];
            });
            
            $posicionesPorZona[$zona] = $posiciones;
            $totalParejasClasificadas += count($posiciones);
        }
        
        $configuracionCruces = $this->getConfiguracionCruces($torneoId, $totalParejasClasificadas);
        
        if (!$configuracionCruces || !$configuracionCruces->llave_4tos) {
            \Log::info('No hay configuración de cuartos disponible');
            return;
        }
        
        // Orden de octavos según llave_8vos: posición i en config = O(i+1). Mapear partido_id -> número.
        $partidoIdToONumero = [];
        if ($configuracionCruces->llave_8vos) {
            $llave8vos = json_decode($configuracionCruces->llave_8vos, true);
            if ($llave8vos && is_array($llave8vos)) {
                $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                $zonaALetra = [];
                foreach ($zonas as $idx => $z) {
                    if (isset($letrasZonas[$idx])) {
                        $zonaALetra[$z] = $letrasZonas[$idx];
                    }
                }
                $gruposOctavos = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'octavos final')
                    ->whereNotNull('partido_id')
                    ->orderBy('partido_id')->orderBy('id')
                    ->get()
                    ->groupBy('partido_id');
                
                foreach ($llave8vos as $idx => $partido8vos) {
                    $p1Ref = $partido8vos['pareja_1'] ?? null;
                    $p2Ref = $partido8vos['pareja_2'] ?? null;
                    if (!$p1Ref || !$p2Ref) continue;
                    $j1 = $j2 = null;
                    if (preg_match('/^([A-P])(\d+)$/', $p1Ref, $m)) {
                        foreach ($zonaALetra as $zona => $letra) {
                            if ($letra === $m[1] && isset($posicionesPorZona[$zona][(int)$m[2] - 1])) {
                                $p = $posicionesPorZona[$zona][(int)$m[2] - 1];
                                $j1 = ($p['jugador_1'] ?? 0) . '_' . ($p['jugador_2'] ?? 0);
                                break;
                            }
                        }
                    }
                    if (preg_match('/^([A-P])(\d+)$/', $p2Ref, $m)) {
                        foreach ($zonaALetra as $zona => $letra) {
                            if ($letra === $m[1] && isset($posicionesPorZona[$zona][(int)$m[2] - 1])) {
                                $p = $posicionesPorZona[$zona][(int)$m[2] - 1];
                                $j2 = ($p['jugador_1'] ?? 0) . '_' . ($p['jugador_2'] ?? 0);
                                break;
                            }
                        }
                    }
                    if (!$j1 || !$j2) continue;
                    foreach ($gruposOctavos as $pid => $gruposP) {
                        if ($gruposP->count() < 2) continue;
                        $g1 = $gruposP[0]; $g2 = $gruposP[1];
                        $k1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                        $k2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                        if (($k1 === $j1 && $k2 === $j2) || ($k1 === $j2 && $k2 === $j1)) {
                            $partidoIdToONumero[$pid] = $idx + 1;
                            break;
                        }
                    }
                }
            }
        }
        
        $numeroPartidoOctavos = $partidoIdToONumero[$partidoOctavos->id] ?? 0;
        if ($numeroPartidoOctavos == 0) {
            $partidoIds = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->pluck('partido_id')->unique()->values();
            $pos = $partidoIds->search($partidoOctavos->id);
            if ($pos !== false) {
                $numeroPartidoOctavos = $pos + 1;
            }
        }
        
        // Construir $partidosOctavos ordenados según config (O1=index0, O2=index1...) para resolver "otra pareja"
        // Recargar partidos desde BD para tener resultados actualizados de todos los octavos
        $partidosOctavos = collect();
        if (!empty($partidoIdToONumero)) {
            $onumeroToPid = array_flip($partidoIdToONumero);
            ksort($onumeroToPid);
            $partidos = DB::table('partidos')->whereIn('id', array_values($onumeroToPid))->get()->keyBy('id');
            foreach ($onumeroToPid as $num => $pid) {
                if ($partidos->has($pid)) {
                    $partidosOctavos->push($partidos->get($pid));
                }
            }
        } else {
            $partidoIds = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'octavos final')
                ->whereNotNull('partido_id')
                ->orderBy('partido_id')
                ->pluck('partido_id')->unique()->values();
            if ($partidoIds->isNotEmpty()) {
                $partidos = DB::table('partidos')->whereIn('id', $partidoIds->all())->get()->keyBy('id');
                foreach ($partidoIds as $pid) {
                    if ($partidos->has($pid)) {
                        $partidosOctavos->push($partidos->get($pid));
                    }
                }
            }
        }
        // Asegurar índices 0-based para acceso por número de octavos (O1=0, O2=1, ...)
        $partidosOctavos = $partidosOctavos->values();
        
        if ($numeroPartidoOctavos == 0) {
            \Log::warning('No se pudo determinar el número del partido de octavos');
            return;
        }
        
        \Log::info('Partido de octavos número: ' . $numeroPartidoOctavos . ' (partido_id=' . $partidoOctavos->id . ', orden según llave_8vos)');
        
        // Obtener el ganador del partido de octavos
        $gruposPartido = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partidoOctavos->id)
            ->orderBy('id')
            ->get();
        
        if ($gruposPartido->count() < 2) {
            \Log::warning('No se encontraron los grupos del partido de octavos');
            return;
        }
        
        $ganador = $this->determinarGanadorPartido($partidoOctavos);
        if (!$ganador) {
            \Log::info('Partido de octavos sin ganador claro aún');
            return;
        }
        
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $ganadorJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;
        
        \Log::info('Ganador del partido de octavos ' . $numeroPartidoOctavos . ': J1=' . $ganadorJugador1 . ', J2=' . $ganadorJugador2);
        
        // Decodificar la llave de cuartos
        $llaveCuartos = json_decode($configuracionCruces->llave_4tos, true);
        if (!$llaveCuartos || !is_array($llaveCuartos)) {
            \Log::warning('No se pudo decodificar la llave de cuartos');
            return;
        }
        
        // Buscar en los cruces de cuartos cuáles tienen referencia a este ganador (O1, O2, etc.)
        foreach ($llaveCuartos as $index => $partidoCuartos) {
            $pareja1Ref = $partidoCuartos['pareja_1'] ?? null;
            $pareja2Ref = $partidoCuartos['pareja_2'] ?? null;
            
            // Verificar si alguna de las referencias corresponde a este ganador (O1, G1-8vos, etc.)
            $referenciaCoincide = false;
            $esPareja1 = false;
            
            if (preg_match('/^O(\d+)$/', $pareja1Ref, $matches) || preg_match('/^G(\d+)-8vos$/', $pareja1Ref, $matches) || preg_match('/^G(\d+)-octavos$/', $pareja1Ref, $matches)) {
                $numeroReferencia = (int)$matches[1];
                if ($numeroReferencia == $numeroPartidoOctavos) {
                    $referenciaCoincide = true;
                    $esPareja1 = true;
                }
            }
            
            if (!$referenciaCoincide && (preg_match('/^O(\d+)$/', $pareja2Ref, $matches) || preg_match('/^G(\d+)-8vos$/', $pareja2Ref, $matches) || preg_match('/^G(\d+)-octavos$/', $pareja2Ref, $matches))) {
                $numeroReferencia = (int)$matches[1];
                if ($numeroReferencia == $numeroPartidoOctavos) {
                    $referenciaCoincide = true;
                    $esPareja1 = false;
                }
            }
            
            if (!$referenciaCoincide) {
                continue;
            }
            
            \Log::info('Encontrado cruce de cuartos ' . ($index + 1) . ' que espera ganador de octavos ' . $numeroPartidoOctavos);
            
            // Verificar si ya existe este partido de cuartos
            $cruceId = 'cuartos_' . ($index + 1);
            $partidoCuartosExistente = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->whereNotNull('partido_id')
                ->get()
                ->groupBy('partido_id');
            
            // Buscar si ya existe un partido con esta pareja
            $partidoIdExistente = null;
            foreach ($partidoCuartosExistente as $partidoId => $gruposPartido) {
                foreach ($gruposPartido as $grupo) {
                    if (($grupo->jugador_1 == $ganadorJugador1 && $grupo->jugador_2 == $ganadorJugador2) ||
                        ($grupo->jugador_1 == $ganadorJugador2 && $grupo->jugador_2 == $ganadorJugador1)) {
                        $partidoIdExistente = $partidoId;
                        break 2;
                    }
                }
            }
            
            if ($partidoIdExistente) {
                \Log::info('Ya existe un partido de cuartos con este ganador: partido_id=' . $partidoIdExistente);
                continue;
            }
            
            // Resolver la otra pareja
            $otraReferencia = $esPareja1 ? $pareja2Ref : $pareja1Ref;
            $otraPareja = null;
            \Log::info('Resolviendo otra referencia para cuartos ' . ($index + 1) . ': ' . $otraReferencia);
            
            // PRIMERO: referencias a ganador de octavos (O1, O2, G1-8vos) — antes que referencia directa, porque "O2" coincide con ([A-P])(\d+)
            if (preg_match('/^O(\d+)$/i', trim($otraReferencia ?? ''), $matches) || preg_match('/^G(\d+)-8vos$/i', trim($otraReferencia ?? ''), $matches) || preg_match('/^G(\d+)-octavos$/i', trim($otraReferencia ?? ''), $matches)) {
                // Es referencia a otro ganador de octavos (O1, G1-8vos, etc.)
                $otroNumeroOctavos = (int)$matches[1];
                \Log::info('La otra pareja es ganador de octavos ' . $otroNumeroOctavos . ', partidosOctavos count=' . $partidosOctavos->count());
                
                // Acceso 0-based: O1 = índice 0, O2 = índice 1, ...
                if ($otroNumeroOctavos >= 1 && $otroNumeroOctavos <= $partidosOctavos->count()) {
                    $otroPartidoOctavos = $partidosOctavos->get($otroNumeroOctavos - 1);
                    if ($otroPartidoOctavos) {
                        $otroGanador = $this->determinarGanadorPartido($otroPartidoOctavos);
                        \Log::info('Partido octavos ' . $otroNumeroOctavos . ' (id=' . ($otroPartidoOctavos->id ?? '?') . ') ganador=' . ($otroGanador ?? 'null'));
                        
                        if ($otroGanador) {
                            $otroGruposPartido = DB::table('grupos')
                                ->where('torneo_id', $torneoId)
                                ->where('partido_id', $otroPartidoOctavos->id)
                                ->orderBy('id')
                                ->get();
                            
                            if ($otroGruposPartido->count() >= 2) {
                                $og1 = $otroGruposPartido[0];
                                $og2 = $otroGruposPartido[1];
                                $otraPareja = [
                                    'jugador_1' => ($otroGanador == 1) ? $og1->jugador_1 : $og2->jugador_1,
                                    'jugador_2' => ($otroGanador == 1) ? $og1->jugador_2 : $og2->jugador_2
                                ];
                                \Log::info('Ganador del otro partido de octavos resuelto: J1=' . $otraPareja['jugador_1'] . ', J2=' . $otraPareja['jugador_2']);
                            }
                        }
                    }
                } else {
                    \Log::info('Índice octavos fuera de rango: ' . $otroNumeroOctavos . ' (count=' . $partidosOctavos->count() . ')');
                }
            } else if (preg_match('/^([A-Pa-p])(\d+)$/', trim($otraReferencia ?? ''), $matches)) {
                // Referencia directa a zona (A1, B2, etc.) — excluir "O" que es ganador octavos
                $letra = strtoupper($matches[1]);
                $posicion = (int)$matches[2];
                if ($letra === 'O') {
                    \Log::info('O' . $posicion . ' interpretado como referencia directa (zona O); si es ganador octavos use O1,O2 en llave.');
                }
                $letrasZonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
                $zonaALetra = [];
                foreach ($zonas as $idxZona => $z) {
                    if (isset($letrasZonas[$idxZona])) {
                        $zonaALetra[$z] = $letrasZonas[$idxZona];
                    }
                }
                foreach ($zonaALetra as $zona => $letraZona) {
                    if ($letraZona === $letra && isset($posicionesPorZona[$zona][$posicion - 1])) {
                        $pareja = $posicionesPorZona[$zona][$posicion - 1];
                        $otraPareja = [
                            'jugador_1' => $pareja['jugador_1'],
                            'jugador_2' => $pareja['jugador_2']
                        ];
                        \Log::info('Referencia directa resuelta: ' . $otraReferencia . ' -> J1=' . $otraPareja['jugador_1'] . ', J2=' . $otraPareja['jugador_2']);
                        break;
                    }
                }
                if (!$otraPareja) {
                    \Log::info('No se pudo resolver la referencia directa: ' . $otraReferencia . ' (zonas: ' . implode(',', array_keys($posicionesPorZona ?? [])) . ')');
                }
            } else {
                \Log::info('Formato de referencia no reconocido para cuartos: ' . json_encode($otraReferencia));
            }
            
            // Si ambas parejas están disponibles, crear el partido de cuartos
            $pareja1 = $esPareja1 ? ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2] : $otraPareja;
            $pareja2 = $esPareja1 ? $otraPareja : ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
            
            if ($pareja1 && $pareja2 && isset($pareja1['jugador_1'], $pareja2['jugador_1'])) {
                \Log::info('Ambas parejas disponibles, creando partido de cuartos');
                $partidoCreado = $this->crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, 'cuartos');
                \Log::info('crearPartidoEliminatorio cuartos retornó: ' . ($partidoCreado ?? 'null'));
                if ($partidoCreado) {
                    $partidoIdCreado = $partidoCreado;
                }
            } else {
                \Log::info('Aún falta resolver la otra pareja para crear el partido de cuartos (pareja1=' . json_encode($pareja1) . ', pareja2=' . json_encode($pareja2) . ')');
            }
        }
        
        \Log::info('=== FIN crearCuartosDesdeConfiguracionYOctavos ===');
        return $partidoIdCreado;
    }

    /**
     * Crea partidos de semifinales desde la configuración cuando se completa un partido de cuartos.
     * Resuelve C1,C2,C3,C4 (o G1-4tos, G2-4tos) como ganador de cuartos 1,2,3,4 ANTES que referencia directa (evitar que "C2" se interprete como zona C).
     */
    private function crearSemifinalesDesdeConfiguracionYCuartos($torneoId, $partidoCuartos) {
        \Log::info('=== INICIO crearSemifinalesDesdeConfiguracionYCuartos ===');
        $partidoIdCreado = null;

        $config = DB::table('configuracion_cruces_puntuables')
            ->where(function ($q) use ($torneoId) {
                $q->where('torneo_id', $torneoId)->orWhereNull('torneo_id');
            })
            ->whereNotNull('llave_semifinal')
            ->orderByRaw('torneo_id IS NOT NULL DESC')
            ->first();
        if (!$config || !$config->llave_semifinal) {
            \Log::info('No hay configuración llave_semifinal; usando lógica legacy');
            return $partidoIdCreado;
        }

        $partidosCuartosIds = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        if ($partidosCuartosIds->isEmpty()) {
            return $partidoIdCreado;
        }
        $partidosCuartos = DB::table('partidos')->whereIn('id', $partidosCuartosIds->all())->get()->keyBy('id');
        $partidosCuartosOrdenados = $partidosCuartosIds->map(function ($pid) use ($partidosCuartos) {
            return $partidosCuartos->get($pid);
        })->filter()->values();

        $numeroPartidoCuartos = $partidosCuartosIds->search($partidoCuartos->id);
        if ($numeroPartidoCuartos === false) {
            \Log::info('Partido de cuartos no encontrado en lista de cuartos del torneo');
            return $partidoIdCreado;
        }
        $numeroPartidoCuartos += 1;

        $ganador = $this->determinarGanadorPartido($partidoCuartos);
        if (!$ganador) {
            \Log::info('Partido de cuartos sin ganador claro aún');
            return $partidoIdCreado;
        }
        $gruposPartido = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partidoCuartos->id)
            ->orderBy('id')
            ->get();
        if ($gruposPartido->count() < 2) return $partidoIdCreado;
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $ganadorJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;

        $llaveSemi = json_decode($config->llave_semifinal, true);
        if (!$llaveSemi || !is_array($llaveSemi)) return $partidoIdCreado;

        foreach ($llaveSemi as $index => $partidoSemi) {
            $pareja1Ref = $partidoSemi['pareja_1'] ?? null;
            $pareja2Ref = $partidoSemi['pareja_2'] ?? null;
            $referenciaCoincide = false;
            $esPareja1 = false;

            if (preg_match('/^C(\d+)$/i', trim($pareja1Ref ?? ''), $m) || preg_match('/^G(\d+)-4tos$/i', trim($pareja1Ref ?? ''), $m) || preg_match('/^G(\d+)-cuartos$/i', trim($pareja1Ref ?? ''), $m)) {
                if ((int)$m[1] === $numeroPartidoCuartos) {
                    $referenciaCoincide = true;
                    $esPareja1 = true;
                }
            }
            if (!$referenciaCoincide && (preg_match('/^C(\d+)$/i', trim($pareja2Ref ?? ''), $m) || preg_match('/^G(\d+)-4tos$/i', trim($pareja2Ref ?? ''), $m) || preg_match('/^G(\d+)-cuartos$/i', trim($pareja2Ref ?? ''), $m))) {
                if ((int)$m[1] === $numeroPartidoCuartos) {
                    $referenciaCoincide = true;
                    $esPareja1 = false;
                }
            }
            if (!$referenciaCoincide) continue;

            $otraReferencia = $esPareja1 ? $pareja2Ref : $pareja1Ref;
            $otraPareja = null;

            // PRIMERO: referencias a ganador de cuartos (C1..C4, G1-4tos) — antes que referencia directa
            if (preg_match('/^C(\d+)$/i', trim($otraReferencia ?? ''), $m) || preg_match('/^G(\d+)-4tos$/i', trim($otraReferencia ?? ''), $m) || preg_match('/^G(\d+)-cuartos$/i', trim($otraReferencia ?? ''), $m)) {
                $otroNum = (int)$m[1];
                if ($otroNum >= 1 && $otroNum <= $partidosCuartosOrdenados->count()) {
                    $otroPartido = $partidosCuartosOrdenados->get($otroNum - 1);
                    if ($otroPartido) {
                        $otroGanador = $this->determinarGanadorPartido($otroPartido);
                        if ($otroGanador) {
                            $og = DB::table('grupos')
                                ->where('torneo_id', $torneoId)
                                ->where('partido_id', $otroPartido->id)
                                ->orderBy('id')
                                ->get();
                            if ($og->count() >= 2) {
                                $og1 = $og[0];
                                $og2 = $og[1];
                                $otraPareja = [
                                    'jugador_1' => ($otroGanador == 1) ? $og1->jugador_1 : $og2->jugador_1,
                                    'jugador_2' => ($otroGanador == 1) ? $og1->jugador_2 : $og2->jugador_2
                                ];
                            }
                        }
                    }
                }
            }

            $pareja1 = $esPareja1 ? ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2] : $otraPareja;
            $pareja2 = $esPareja1 ? $otraPareja : ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];

            if ($pareja1 && $pareja2 && isset($pareja1['jugador_1'], $pareja2['jugador_1'])) {
                $partidoIdExistente = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'semifinal')
                    ->whereNotNull('partido_id')
                    ->get()
                    ->groupBy('partido_id')
                    ->keys();
                $yaExiste = false;
                foreach ($partidoIdExistente as $pid) {
                    $gruposP = DB::table('grupos')->where('partido_id', $pid)->where('torneo_id', $torneoId)->where('zona', 'semifinal')->orderBy('id')->get();
                    if ($gruposP->count() >= 2) {
                        $a = $gruposP[0]->jugador_1 . '_' . $gruposP[0]->jugador_2;
                        $b = $gruposP[1]->jugador_1 . '_' . $gruposP[1]->jugador_2;
                        if (($a === $pareja1['jugador_1'] . '_' . $pareja1['jugador_2'] && $b === $pareja2['jugador_1'] . '_' . $pareja2['jugador_2']) ||
                            ($a === $pareja2['jugador_1'] . '_' . $pareja2['jugador_2'] && $b === $pareja1['jugador_1'] . '_' . $pareja1['jugador_2'])) {
                            $yaExiste = true;
                            break;
                        }
                    }
                }
                if (!$yaExiste) {
                    $partidoIdCreado = $this->crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, 'semifinales');
                }
            }
        }
        \Log::info('=== FIN crearSemifinalesDesdeConfiguracionYCuartos ===');
        return $partidoIdCreado;
    }

    /**
     * Crea la final desde la configuración cuando se completa un partido de semifinales.
     * Resuelve G1-semifinal, G2-semifinal ANTES que referencia directa.
     */
    private function crearFinalDesdeConfiguracionYSemifinales($torneoId, $partidoSemifinal) {
        \Log::info('=== INICIO crearFinalDesdeConfiguracionYSemifinales ===');
        $partidoIdCreado = null;

        $config = DB::table('configuracion_cruces_puntuables')
            ->where(function ($q) use ($torneoId) {
                $q->where('torneo_id', $torneoId)->orWhereNull('torneo_id');
            })
            ->whereNotNull('llave_final')
            ->orderByRaw('torneo_id IS NOT NULL DESC')
            ->first();
        if (!$config || !$config->llave_final) {
            \Log::info('No hay configuración llave_final; usando lógica legacy');
            return $partidoIdCreado;
        }

        $partidosSemiIds = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->pluck('partido_id')
            ->unique()
            ->values();
        if ($partidosSemiIds->count() < 2) {
            \Log::info('Aún no hay 2 partidos de semifinal');
            return $partidoIdCreado;
        }
        $partidosSemi = DB::table('partidos')->whereIn('id', $partidosSemiIds->all())->get()->keyBy('id');
        $partidosSemiOrdenados = $partidosSemiIds->map(function ($pid) use ($partidosSemi) {
            return $partidosSemi->get($pid);
        })->filter()->values();

        $numeroPartidoSemi = $partidosSemiIds->search($partidoSemifinal->id);
        if ($numeroPartidoSemi === false) return $partidoIdCreado;
        $numeroPartidoSemi += 1;

        $ganador = $this->determinarGanadorPartido($partidoSemifinal);
        if (!$ganador) return $partidoIdCreado;
        $gruposPartido = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('partido_id', $partidoSemifinal->id)
            ->orderBy('id')
            ->get();
        if ($gruposPartido->count() < 2) return $partidoIdCreado;
        $g1 = $gruposPartido[0];
        $g2 = $gruposPartido[1];
        $ganadorJugador1 = ($ganador == 1) ? $g1->jugador_1 : $g2->jugador_1;
        $ganadorJugador2 = ($ganador == 1) ? $g1->jugador_2 : $g2->jugador_2;

        $llaveFinal = json_decode($config->llave_final, true);
        if (!$llaveFinal || !is_array($llaveFinal)) return $partidoIdCreado;

        foreach ($llaveFinal as $index => $partidoFinal) {
            $pareja1Ref = $partidoFinal['pareja_1'] ?? null;
            $pareja2Ref = $partidoFinal['pareja_2'] ?? null;
            $referenciaCoincide = false;
            $esPareja1 = false;

            if (preg_match('/^G(\d+)-semifinal$/i', trim($pareja1Ref ?? ''), $m) || preg_match('/^S(\d+)$/i', trim($pareja1Ref ?? ''), $m)) {
                $n = (int)($m[1] ?? 0);
                if ($n === $numeroPartidoSemi) {
                    $referenciaCoincide = true;
                    $esPareja1 = true;
                }
            }
            if (!$referenciaCoincide && (preg_match('/^G(\d+)-semifinal$/i', trim($pareja2Ref ?? ''), $m) || preg_match('/^S(\d+)$/i', trim($pareja2Ref ?? ''), $m))) {
                $n = (int)($m[1] ?? 0);
                if ($n === $numeroPartidoSemi) {
                    $referenciaCoincide = true;
                    $esPareja1 = false;
                }
            }
            if (!$referenciaCoincide) continue;

            $otraReferencia = $esPareja1 ? $pareja2Ref : $pareja1Ref;
            $otraPareja = null;

            // PRIMERO: referencias a ganador de semifinal (G1-semifinal, G2-semifinal, S1, S2)
            if (preg_match('/^G(\d+)-semifinal$/i', trim($otraReferencia ?? ''), $m) || preg_match('/^S(\d+)$/i', trim($otraReferencia ?? ''), $m)) {
                $otroNum = (int)$m[1];
                if ($otroNum >= 1 && $otroNum <= $partidosSemiOrdenados->count()) {
                    $otroPartido = $partidosSemiOrdenados->get($otroNum - 1);
                    if ($otroPartido) {
                        $otroGanador = $this->determinarGanadorPartido($otroPartido);
                        if ($otroGanador) {
                            $og = DB::table('grupos')
                                ->where('torneo_id', $torneoId)
                                ->where('partido_id', $otroPartido->id)
                                ->orderBy('id')
                                ->get();
                            if ($og->count() >= 2) {
                                $og1 = $og[0];
                                $og2 = $og[1];
                                $otraPareja = [
                                    'jugador_1' => ($otroGanador == 1) ? $og1->jugador_1 : $og2->jugador_1,
                                    'jugador_2' => ($otroGanador == 1) ? $og1->jugador_2 : $og2->jugador_2
                                ];
                            }
                        }
                    }
                }
            }

            $pareja1 = $esPareja1 ? ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2] : $otraPareja;
            $pareja2 = $esPareja1 ? $otraPareja : ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];

            if ($pareja1 && $pareja2 && isset($pareja1['jugador_1'], $pareja2['jugador_1'])) {
                $finalExistente = DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'final')
                    ->whereNotNull('partido_id')
                    ->get();
                if ($finalExistente->count() >= 2) {
                    DB::table('grupos')->where('id', $finalExistente[0]->id)->update([
                        'jugador_1' => $pareja1['jugador_1'], 'jugador_2' => $pareja1['jugador_2']
                    ]);
                    DB::table('grupos')->where('id', $finalExistente[1]->id)->update([
                        'jugador_1' => $pareja2['jugador_1'], 'jugador_2' => $pareja2['jugador_2']
                    ]);
                    $partidoIdCreado = $finalExistente[0]->partido_id;
                } else {
                    $partidoIdCreado = $this->crearPartidoEliminatorio($torneoId, $pareja1, $pareja2, 'final');
                }
            }
        }
        \Log::info('=== FIN crearFinalDesdeConfiguracionYSemifinales ===');
        return $partidoIdCreado;
    }

    /**
     * GET: Misma funcionalidad que getParticipantesTorneoPuntuable.
     * Devuelve jugadores que participan en el torneo (desde grupos) y referencias de puntuación.
     */
    public function obtenerParticipantesTorneoPuntuable(Request $request) {
        try {
            return $this->getParticipantesTorneoPuntuable($request);
        } catch (\Exception $e) {
            \Log::error('Error al obtener participantes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
