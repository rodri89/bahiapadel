<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Rotación TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <style>
        /* ========================================
           ESTILOS BASE TV ROTACIÓN
           ======================================== */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background: #0a0f1a;
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #e2e8f0;
            font-weight: 300;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            letter-spacing: 0.01em;
        }
        
        /* ========================================
           HEADER CON COLOR DE CATEGORÍA
           ======================================== */
        .header-tv {
            height: 7vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5vw;
            transition: background 0.5s ease, border-color 0.5s ease;
            backdrop-filter: blur(10px);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5vw;
        }
        
        .categoria-badge {
            font-size: 2.2vh;
            font-weight: 500;
            padding: 0.5vh 1.2vw;
            border-radius: 0.5vh;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #fff;
            text-shadow: 0 0.1vh 0.2vh rgba(0,0,0,0.3);
        }
        
        .header-tv h2 {
            font-size: 2.5vh;
            font-weight: 400;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            margin: 0;
            transition: opacity 0.3s ease;
        }
        
        .fase-badge {
            font-size: 1.6vh;
            font-weight: 400;
            padding: 0.4vh 1vw;
            border-radius: 0.3vh;
            background: rgba(0,0,0,0.3);
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }
        
        .header-indicadores {
            display: flex;
            align-items: center;
            gap: 1vw;
        }
        
        .indicador-torneos {
            display: flex;
            gap: 0.8vw;
        }
        
        .indicador-dot {
            width: 1.5vh;
            height: 1.5vh;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .indicador-dot.active {
            transform: scale(1.4);
        }
        
        .indicador-dot.updated {
            animation: pulse 0.5s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(2); }
            100% { transform: scale(1.4); }
        }
        
        .countdown-display {
            font-size: 1.8vh;
            color: rgba(255,255,255,0.6);
            font-weight: 300;
        }
        
        /* ========================================
           SLIDES CONTAINER
           ======================================== */
        .slides-container {
            height: 93vh;
            position: relative;
            overflow: hidden;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease-in-out;
        }
        
        .slide.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* ========================================
           FASE: CRUCES (BRACKET)
           ======================================== */
        .bracket-container {
            height: 100%;
            display: flex;
            padding: 0;
        }
        
        .bracket-row {
            display: flex;
            width: 100%;
            height: 100%;
        }
        
        .bracket-column {
            display: flex;
            flex-direction: column;
            min-width: 0;
            position: relative;
        }
        
        .bracket-round {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .bracket-round-title {
            font-size: 1.6vh;
            font-weight: 300;
            color: #fbbf24;
            text-align: center;
            padding: 0.4vh 0;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            flex-shrink: 0;
            height: 3vh;
        }
        
        .bracket-round-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 0;
        }
        
        .match-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 0.3vw;
            position: relative;
            flex: 1;
            min-height: 0;
        }
        
        /* Distribución dinámica por ronda */
        .bracket-column--dieciseisavos .match-card { flex: 1; }
        .bracket-column--octavos .match-card { flex: 2; }
        .bracket-column--cuartos .match-card { flex: 4; }
        .bracket-column--semis .match-card { flex: 8; }
        .bracket-column--final .match-card { flex: 16; }
        
        .player-pair {
            display: flex;
            align-items: center;
            padding: 0.2vh 0.4vw;
            background: rgba(30,41,59,0.8);
            border-left: 3px solid rgba(100,116,139,0.5);
            margin: 1px 0;
        }
        
        .player-pair.winner {
            border-left-color: #22c55e;
            background: rgba(34,197,94,0.15);
        }
        
        .player-pair-content {
            flex: 1;
            display: flex;
            align-items: center;
            min-width: 0;
        }
        
        .player-names {
            flex: 1;
            font-size: 2.3vh;
            font-weight: 300;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .player-pair-input { 
            flex-shrink: 0; 
            margin-left: 0.5vw;
            display: flex;
            gap: 0.3vw;
        }
        
        .set-score {
            font-size: 2vh;
            font-weight: 400;
            color: #e2e8f0;
            background: rgba(71,85,105,0.5);
            padding: 0.2vh 0.5vw;
            border-radius: 3px;
            min-width: 1.8vw;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .set-score.won {
            background: #22c55e;
            color: #000;
        }
        
        .set-score.lost {
            background: rgba(71,85,105,0.3);
            color: rgba(255,255,255,0.6);
        }
        
        .player-pair.winner .player-names {
            color: #22c55e;
        }
        
        .match-card.placeholder .player-names {
            color: rgba(148,163,184,0.4);
            font-style: italic;
        }
        
        /* Anchos de columnas por cantidad de rondas */
        .rondas-5 .bracket-column--dieciseisavos { width: 22%; }
        .rondas-5 .bracket-column--octavos { width: 22%; }
        .rondas-5 .bracket-column--cuartos { width: 20%; }
        .rondas-5 .bracket-column--semis { width: 18%; }
        .rondas-5 .bracket-column--final { width: 18%; }
        .rondas-5 .player-names { font-size: 2vh; }
        .rondas-5 .score-display { font-size: 2.2vh; }
        
        .rondas-4 .bracket-column--octavos { width: 28%; }
        .rondas-4 .bracket-column--cuartos { width: 24%; }
        .rondas-4 .bracket-column--semis { width: 24%; }
        .rondas-4 .bracket-column--final { width: 24%; }
        
        .rondas-3 .bracket-column--cuartos { width: 36%; }
        .rondas-3 .bracket-column--semis { width: 32%; }
        .rondas-3 .bracket-column--final { width: 32%; }
        
        .rondas-2 .bracket-column--semis { width: 50%; }
        .rondas-2 .bracket-column--final { width: 50%; }
        
        .rondas-1 .bracket-column--final { width: 100%; }
        
        /* ========================================
           CUADRO DIVIDIDO (16AVOS)
           ======================================== */
        .bracket-split-container {
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .bracket-subslide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease-in-out;
            display: flex;
            flex-direction: column;
        }
        
        .bracket-subslide.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .bracket-parte-label {
            position: absolute;
            top: 1vh;
            right: 1.5vw;
            font-size: 1.8vh;
            font-weight: 500;
            padding: 0.5vh 1.2vw;
            border-radius: 0.4vh;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            z-index: 10;
        }
        
        .bracket-parte-label.alta {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #fff;
        }
        
        .bracket-parte-label.baja {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
        }
        
        .bracket-subslide .bracket-container {
            flex: 1;
        }
        
        /* ========================================
           FASE: GRUPOS (TABLAS)
           ======================================== */
        .grupos-container {
            height: 100%;
            display: flex;
            flex-wrap: wrap;
            padding: 1vh 1vw;
            gap: 1vw;
            overflow: hidden;
        }
        
        .zona-card {
            flex: 1;
            min-width: 45%;
            max-width: 49%;
            background: rgba(30,41,59,0.8);
            border-radius: 1vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        /* Si hay 1-2 zonas, ocupan más espacio */
        .grupos-container.zonas-1 .zona-card,
        .grupos-container.zonas-2 .zona-card {
            min-width: 48%;
            max-width: 49%;
        }
        
        /* Si hay 3-4 zonas */
        .grupos-container.zonas-3 .zona-card,
        .grupos-container.zonas-4 .zona-card {
            min-width: 48%;
            max-width: 49%;
            max-height: 48%;
        }
        
        .zona-header {
            padding: 1vh 1vw;
            text-align: center;
            font-size: 2.5vh;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #fff;
        }
        
        .zona-table {
            flex: 1;
            overflow: hidden;
        }
        
        .zona-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .zona-table th {
            background: rgba(0,0,0,0.3);
            color: rgba(255,255,255,0.7);
            font-size: 1.6vh;
            font-weight: 500;
            padding: 0.8vh 0.5vw;
            text-align: center;
            text-transform: uppercase;
        }
        
        .zona-table th:first-child {
            text-align: left;
            padding-left: 1vw;
            width: 40%;
        }
        
        .zona-table td {
            font-size: 2vh;
            font-weight: 300;
            padding: 0.8vh 0.5vw;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #e2e8f0;
        }
        
        .zona-table td:first-child {
            text-align: left;
            padding-left: 1vw;
            font-weight: 400;
            text-transform: uppercase;
        }
        
        /* Resaltado de posiciones - se aplica dinámicamente con JS según reglas del torneo */
        .zona-table tr.clasificado td:first-child {
            font-weight: 500;
        }
        
        .zona-table .pts {
            font-weight: 600;
            font-size: 2.2vh;
        }
        
        /* ========================================
           NOTIFICACIÓN DE ACTUALIZACIÓN
           ======================================== */
        .update-notification {
            position: fixed;
            top: 9vh;
            left: 50%;
            transform: translateX(-50%);
            background: #22c55e;
            color: #000;
            padding: 1vh 2vw;
            border-radius: 0.5vh;
            font-size: 2vh;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .update-notification.visible {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header dinámico por categoría -->
    <div class="header-tv" id="header-tv">
        <div class="header-left">
            <span class="categoria-badge" id="categoria-badge">{{ $torneosData[0]['colorCategoria']['nombre'] ?? '6TA' }}</span>
            <h2 id="torneo-nombre">{{ $torneosData[0]['nombre'] ?? 'Torneo' }}</h2>
            <span class="fase-badge" id="fase-badge">{{ $torneosData[0]['fase'] === 'cruces' ? 'CRUCES' : 'GRUPOS' }}</span>
        </div>
        <div class="header-indicadores">
            <div class="indicador-torneos">
                @foreach($torneosData as $index => $torneo)
                    <div class="indicador-dot{{ $index === 0 ? ' active' : '' }}" 
                         data-torneo-id="{{ $torneo['id'] }}"
                         data-color="{{ $torneo['colorCategoria']['bg'] }}"
                         style="background: {{ $index === 0 ? $torneo['colorCategoria']['bg'] : 'rgba(255,255,255,0.3)' }}"
                         title="{{ $torneo['nombre'] }} - {{ $torneo['colorCategoria']['nombre'] }}"></div>
                @endforeach
            </div>
            <span class="countdown-display" id="countdown">{{ $intervalo }}s</span>
        </div>
    </div>
    
    <!-- Notificación de actualización -->
    <div class="update-notification" id="update-notification">
        ¡Resultado actualizado!
    </div>
    
    <!-- Container de slides -->
    <div class="slides-container">
        @foreach($torneosData as $torneoIndex => $torneoData)
            @php $esAmericano = ($torneoData['tipo_torneo_formato'] ?? 'puntuable') === 'americano'; @endphp
            <div class="slide{{ $torneoIndex === 0 ? ' active' : '' }}" 
                 data-torneo-id="{{ $torneoData['id'] }}"
                 data-torneo-nombre="{{ $torneoData['nombre'] }}"
                 data-categoria="{{ $torneoData['categoria'] }}"
                 data-categoria-nombre="{{ $torneoData['colorCategoria']['nombre'] }}"
                 data-categoria-bg="{{ $torneoData['colorCategoria']['bg'] }}"
                 data-categoria-border="{{ $torneoData['colorCategoria']['border'] }}"
                 data-fase="{{ $torneoData['fase'] }}"
                 data-tipo-torneo="{{ $torneoData['tipo_torneo_formato'] ?? 'puntuable' }}"
                 data-version="{{ $torneoData['version'] }}">
                
                @if($torneoData['fase'] === 'cruces')
                    {{-- VISTA DE CRUCES --}}
                    @php
                        $cruces = $torneoData['cruces'] ?? [];
                        $crucesPorRonda = [
                            'dieciseisavos final' => [],
                            'octavos final' => [],
                            'cuartos final' => [],
                            'semifinal' => [],
                            'final' => []
                        ];
                        foreach ($cruces as $cruce) {
                            $rondaKey = $cruce['ronda'] ?? '';
                            if (isset($crucesPorRonda[$rondaKey])) {
                                $crucesPorRonda[$rondaKey][] = $cruce;
                            }
                        }
                        
                        $tiene16avos = count($crucesPorRonda['dieciseisavos final']) > 0;
                        
                        // Si hay 16avos, dividimos en parte alta y baja
                        if ($tiene16avos) {
                            $totalDieciseisavos = count($crucesPorRonda['dieciseisavos final']);
                            $mitadDieciseisavos = (int)ceil($totalDieciseisavos / 2);
                            
                            $totalOctavos = count($crucesPorRonda['octavos final']);
                            $mitadOctavos = (int)ceil($totalOctavos / 2);
                            
                            $totalCuartos = count($crucesPorRonda['cuartos final']);
                            $mitadCuartos = (int)ceil($totalCuartos / 2);
                            
                            // Parte ALTA: primeros partidos de cada ronda + final
                            $cuadroAlta = [
                                'dieciseisavos final' => array_slice($crucesPorRonda['dieciseisavos final'], 0, $mitadDieciseisavos),
                                'octavos final' => array_slice($crucesPorRonda['octavos final'], 0, $mitadOctavos),
                                'cuartos final' => array_slice($crucesPorRonda['cuartos final'], 0, $mitadCuartos),
                                'semifinal' => array_slice($crucesPorRonda['semifinal'], 0, 1),
                                'final' => $crucesPorRonda['final']
                            ];
                            
                            // Parte BAJA: últimos partidos de cada ronda + final también
                            $cuadroBaja = [
                                'dieciseisavos final' => array_slice($crucesPorRonda['dieciseisavos final'], $mitadDieciseisavos),
                                'octavos final' => array_slice($crucesPorRonda['octavos final'], $mitadOctavos),
                                'cuartos final' => array_slice($crucesPorRonda['cuartos final'], $mitadCuartos),
                                'semifinal' => array_slice($crucesPorRonda['semifinal'], 1),
                                'final' => $crucesPorRonda['final'] // La final se muestra en ambas partes
                            ];
                        }
                    @endphp
                    
                    @if($tiene16avos)
                        {{-- CUADRO DIVIDIDO EN PARTE ALTA Y BAJA --}}
                        <div class="bracket-split-container" data-torneo-id="{{ $torneoData['id'] }}">
                            @foreach(['alta' => $cuadroAlta, 'baja' => $cuadroBaja] as $parte => $cuadroParte)
                                @php
                                    $rondasMostrar = [];
                                    if (count($cuadroParte['dieciseisavos final']) > 0) {
                                        $rondasMostrar[] = ['key' => 'dieciseisavos final', 'title' => '16VOS', 'class' => 'dieciseisavos'];
                                    }
                                    if (count($cuadroParte['octavos final']) > 0) {
                                        $rondasMostrar[] = ['key' => 'octavos final', 'title' => 'OCTAVOS', 'class' => 'octavos'];
                                    }
                                    if (count($cuadroParte['cuartos final']) > 0) {
                                        $rondasMostrar[] = ['key' => 'cuartos final', 'title' => 'CUARTOS', 'class' => 'cuartos'];
                                    }
                                    if (count($cuadroParte['semifinal']) > 0) {
                                        $rondasMostrar[] = ['key' => 'semifinal', 'title' => 'SEMI', 'class' => 'semis'];
                                    }
                                    if (count($cuadroParte['final']) > 0) {
                                        $rondasMostrar[] = ['key' => 'final', 'title' => 'FINAL', 'class' => 'final'];
                                    }
                                    $numRondas = count($rondasMostrar);
                                @endphp
                                <div class="bracket-subslide{{ $parte === 'alta' ? ' active' : '' }}" data-parte="{{ $parte }}">
                                    <div class="bracket-parte-label {{ $parte }}">
                                        CUADRO {{ strtoupper($parte) }}
                                    </div>
                                    <div class="bracket-container">
                                        <div class="bracket-row rondas-{{ $numRondas }}">
                                            @foreach($rondasMostrar as $rondaInfo)
                                                @php $crucesRonda = $cuadroParte[$rondaInfo['key']] ?? []; @endphp
                                                <div class="bracket-column bracket-column--{{ $rondaInfo['class'] }}">
                                                    <div class="bracket-round">
                                                        <div class="bracket-round-title">{{ $rondaInfo['title'] }}</div>
                                                        <div class="bracket-round-body">
                                                            @forelse($crucesRonda as $cruce)
                                                                @include('bahia_padel.tv.partials.match_card', ['cruce' => $cruce, 'esAmericano' => $esAmericano])
                                                            @empty
                                                                <div class="match-card placeholder">
                                                                    <div class="player-pair">
                                                                        <div class="player-pair-content">
                                                                            <div class="player-names">Esperando...</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- CUADRO NORMAL SIN 16AVOS --}}
                        @php
                            $rondasMostrar = [];
                            if (count($crucesPorRonda['octavos final']) > 0) {
                                $rondasMostrar[] = ['key' => 'octavos final', 'title' => 'OCTAVOS', 'class' => 'octavos'];
                            }
                            if (count($crucesPorRonda['cuartos final']) > 0) {
                                $rondasMostrar[] = ['key' => 'cuartos final', 'title' => 'CUARTOS', 'class' => 'cuartos'];
                            }
                            $rondasMostrar[] = ['key' => 'semifinal', 'title' => 'SEMIFINALES', 'class' => 'semis'];
                            $rondasMostrar[] = ['key' => 'final', 'title' => 'FINAL', 'class' => 'final'];
                            $numRondas = count($rondasMostrar);
                        @endphp
                        
                        <div class="bracket-container">
                            <div class="bracket-row rondas-{{ $numRondas }}">
                                @foreach($rondasMostrar as $rondaInfo)
                                    @php $crucesRonda = $crucesPorRonda[$rondaInfo['key']] ?? []; @endphp
                                    <div class="bracket-column bracket-column--{{ $rondaInfo['class'] }}">
                                        <div class="bracket-round">
                                            <div class="bracket-round-title">{{ $rondaInfo['title'] }}</div>
                                            <div class="bracket-round-body">
                                                @forelse($crucesRonda as $cruce)
                                                    @include('bahia_padel.tv.partials.match_card', ['cruce' => $cruce, 'esAmericano' => $esAmericano])
                                                @empty
                                                    <div class="match-card placeholder">
                                                        <div class="player-pair">
                                                            <div class="player-pair-content">
                                                                <div class="player-names">Esperando...</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    {{-- VISTA DE GRUPOS/ZONAS --}}
                    @php
                        $zonas = $torneoData['zonas'] ?? [];
                        $tablas = $torneoData['tablasPosiciones'] ?? [];
                        $numZonas = count($zonas);
                    @endphp
                    
                    <div class="grupos-container zonas-{{ min($numZonas, 4) }}">
                        @foreach($zonas as $zona)
                            @php $posiciones = $tablas[$zona] ?? []; @endphp
                            <div class="zona-card">
                                <div class="zona-header" style="background: {{ $torneoData['colorCategoria']['bg'] }};">
                                    {{ $zona }}
                                </div>
                                <div class="zona-table">
                                    <table>
                                        <thead>
                                            @if($esAmericano)
                                                {{-- COLUMNAS AMERICANO: Sin sets, solo games --}}
                                                <tr>
                                                    <th>Pareja</th>
                                                    <th>PJ</th>
                                                    <th>PG</th>
                                                    <th>PP</th>
                                                    <th>GF</th>
                                                    <th>GC</th>
                                                </tr>
                                            @else
                                                {{-- COLUMNAS PUNTUABLE: Con sets y puntos --}}
                                                <tr>
                                                    <th>Pareja</th>
                                                    <th>PJ</th>
                                                    <th>PG</th>
                                                    <th>PP</th>
                                                    <th>SF</th>
                                                    <th>SC</th>
                                                    <th>PTS</th>
                                                </tr>
                                            @endif
                                        </thead>
                                        <tbody>
                                            @forelse($posiciones as $pos)
                                                @if($esAmericano)
                                                    {{-- FILA AMERICANO --}}
                                                    <tr>
                                                        <td>{{ $pos['nombre'] }}</td>
                                                        <td>{{ $pos['pj'] }}</td>
                                                        <td>{{ $pos['pg'] }}</td>
                                                        <td>{{ $pos['pp'] }}</td>
                                                        <td>{{ $pos['gf'] }}</td>
                                                        <td>{{ $pos['gc'] }}</td>
                                                    </tr>
                                                @else
                                                    {{-- FILA PUNTUABLE --}}
                                                    <tr>
                                                        <td>{{ $pos['nombre'] }}</td>
                                                        <td>{{ $pos['pj'] }}</td>
                                                        <td>{{ $pos['pg'] }}</td>
                                                        <td>{{ $pos['pp'] }}</td>
                                                        <td>{{ $pos['sf'] }}</td>
                                                        <td>{{ $pos['sc'] }}</td>
                                                        <td class="pts">{{ $pos['pts'] }}</td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $esAmericano ? 6 : 7 }}" style="text-align:center; color:rgba(255,255,255,0.5);">Sin datos</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($numZonas === 0)
                            <div style="width:100%; text-align:center; padding:10vh 0; color:rgba(255,255,255,0.5); font-size:3vh;">
                                No hay zonas configuradas
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    // Configuración
    const INTERVALO_ROTACION = {{ $intervalo }} * 1000;
    const INTERVALO_CHECK_VERSION = 2000;
    const torneoIdsParam = '{{ $torneoIdsParam }}';
    const INTERVALO_SUBSLIDE = Math.floor({{ $intervalo }} / 2); // Mitad del intervalo para sub-slides
    
    // Estado
    let torneoActualIndex = 0;
    let versiones = {};
    let countdownSegundos = {{ $intervalo }};
    let subSlideActual = 'alta'; // Para cuadros divididos (alta/baja)
    let tieneSubSlides = false;
    let subSlideCountdown = INTERVALO_SUBSLIDE;
    
    // Inicializar versiones conocidas
    @foreach($torneosData as $torneo)
        versiones[{{ $torneo['id'] }}] = {{ $torneo['version'] }};
    @endforeach
    
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.indicador-dot');
    const headerTv = document.getElementById('header-tv');
    const categoriaBadge = document.getElementById('categoria-badge');
    const nombreDisplay = document.getElementById('torneo-nombre');
    const faseBadge = document.getElementById('fase-badge');
    const countdownDisplay = document.getElementById('countdown');
    const notification = document.getElementById('update-notification');
    
    // Verificar si el slide tiene sub-slides (cuadro dividido con 16avos)
    function slideTieneSubSlides(slide) {
        return slide.querySelector('.bracket-split-container') !== null;
    }
    
    // Mostrar un sub-slide específico dentro de un slide con cuadro dividido
    function mostrarSubSlide(slide, parte) {
        const subSlides = slide.querySelectorAll('.bracket-subslide');
        subSlides.forEach(subSlide => {
            if (subSlide.dataset.parte === parte) {
                subSlide.classList.add('active');
            } else {
                subSlide.classList.remove('active');
            }
        });
        subSlideActual = parte;
        subSlideCountdown = INTERVALO_SUBSLIDE;
    }
    
    // Alternar entre parte alta y baja
    function alternarSubSlide() {
        const slideActivo = slides[torneoActualIndex];
        if (!slideTieneSubSlides(slideActivo)) return;
        
        const nuevaParte = subSlideActual === 'alta' ? 'baja' : 'alta';
        mostrarSubSlide(slideActivo, nuevaParte);
    }
    
    // Mostrar slide específico
    function mostrarSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        
        // Actualizar dots
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('active');
                dot.style.background = dot.dataset.color;
            } else {
                dot.classList.remove('active');
                dot.style.background = 'rgba(255,255,255,0.3)';
            }
        });
        
        const slideActivo = slides[index];
        const bg = slideActivo.dataset.categoriaBg;
        const border = slideActivo.dataset.categoriaBorder;
        const fase = slideActivo.dataset.fase;
        
        // Actualizar header con color de categoría
        headerTv.style.background = `linear-gradient(135deg, ${bg} 0%, ${border} 100%)`;
        headerTv.style.borderBottom = `2px solid ${border}`;
        
        // Actualizar textos
        categoriaBadge.textContent = slideActivo.dataset.categoriaNombre;
        categoriaBadge.style.background = 'rgba(0,0,0,0.3)';
        nombreDisplay.textContent = slideActivo.dataset.torneoNombre;
        faseBadge.textContent = fase === 'cruces' ? 'CRUCES' : 'GRUPOS';
        
        // Verificar si tiene sub-slides y reiniciar a parte alta
        tieneSubSlides = slideTieneSubSlides(slideActivo);
        if (tieneSubSlides) {
            mostrarSubSlide(slideActivo, 'alta');
        }
        
        torneoActualIndex = index;
        countdownSegundos = {{ $intervalo }};
        actualizarCountdown();
    }
    
    function siguienteSlide() {
        const siguiente = (torneoActualIndex + 1) % slides.length;
        mostrarSlide(siguiente);
    }
    
    function actualizarCountdown() {
        countdownDisplay.textContent = countdownSegundos + 's';
    }
    
    function mostrarNotificacion() {
        notification.classList.add('visible');
        setTimeout(() => {
            notification.classList.remove('visible');
        }, 2000);
    }
    
    function verificarVersiones() {
        $.get('{{ route("tvtorneosversiones") }}', { torneo_ids: torneoIdsParam })
            .done(function(response) {
                const nuevasVersiones = response.versiones || {};
                
                for (const torneoId in nuevasVersiones) {
                    const versionNueva = nuevasVersiones[torneoId];
                    const versionAnterior = versiones[torneoId] || 0;
                    
                    if (versionNueva > versionAnterior) {
                        console.log('Torneo ' + torneoId + ' actualizado: v' + versionAnterior + ' -> v' + versionNueva);
                        versiones[torneoId] = versionNueva;
                        
                        let torneoIndex = -1;
                        slides.forEach((slide, i) => {
                            if (slide.dataset.torneoId == torneoId) {
                                torneoIndex = i;
                            }
                        });
                        
                        if (torneoIndex >= 0) {
                            dots[torneoIndex].classList.add('updated');
                            setTimeout(() => dots[torneoIndex].classList.remove('updated'), 1000);
                            
                            if (torneoIndex !== torneoActualIndex) {
                                mostrarSlide(torneoIndex);
                            }
                            
                            mostrarNotificacion();
                            
                            // Recargar para datos frescos
                            setTimeout(() => window.location.reload(), 500);
                        }
                    }
                }
            });
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar header con el primer torneo
        mostrarSlide(0);
        
        // Rotación automática con soporte para sub-slides
        setInterval(() => {
            countdownSegundos--;
            
            // Si el slide actual tiene sub-slides (16avos), manejar la alternancia
            if (tieneSubSlides) {
                subSlideCountdown--;
                
                // Alternar entre alta y baja
                if (subSlideCountdown <= 0) {
                    if (subSlideActual === 'alta') {
                        // Pasar a parte baja
                        alternarSubSlide();
                    } else {
                        // Ya estamos en baja, ir al siguiente torneo
                        siguienteSlide();
                    }
                }
                
                // Actualizar countdown visual (mostrar el countdown del sub-slide)
                countdownDisplay.textContent = subSlideCountdown + 's';
            } else {
                // Slide normal sin sub-slides
                if (countdownSegundos <= 0) {
                    siguienteSlide();
                } else {
                    actualizarCountdown();
                }
            }
        }, 1000);
        
        // Verificación de versiones
        setInterval(verificarVersiones, INTERVALO_CHECK_VERSION);
        
        // Click en dots para cambio manual
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => mostrarSlide(i));
        });
    });
</script>
</body>
</html>
