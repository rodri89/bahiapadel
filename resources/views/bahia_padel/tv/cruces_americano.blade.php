<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Bahia Padel - Cruces TV</title>
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bracket.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
    <style>
        /* Estilos optimizados para vista TV sin scroll */
        * {
            box-sizing: border-box;
        }
        
        html, body {
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            width: 100vw;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #111827 40%, #1f2937 100%);
            display: flex;
            flex-direction: column;
            color: #0f172a;
            font-family: "Poppins", "Segoe UI", sans-serif;
        }
        
        /* Header con título */
        .header-tv {
            flex: 0 0 auto;
            padding: 0.8vw 1.2vw;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1vw;
        }
        
        h2.torneo-title {
            font-size: 1.8vw;
            color: rgba(255, 255, 255, 0.95);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin: 0;
            flex: 1;
            text-align: center;
        }
        
        .header-tv .btn {
            font-size: 0.9vw;
            padding: 0.5vw 1vw;
            flex: 0 0 auto;
        }
        
        /* Contenedor principal de bracket */
        .bracket-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            width: 100%;
            padding: 0.2vw 0.3vw;
            min-height: 0;
        }
        
        .container-fluid {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 0;
            margin: 0;
        }
        
        /* Fila de bracket */
        .bracket-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.3vw;
            align-items: stretch;
            height: 100%;
            margin: 0;
        }
        
        /* Columnas del bracket */
        .bracket-column {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
            min-width: 0;
            padding: 0.3vw 0.2vw;
            overflow: visible;
        }
        
        .bracket-column--cuartos,
        .bracket-column--semis,
        .bracket-column--final {
            padding: 0.3vw 0.2vw;
        }
        
        /* Ronda */
        .bracket-round {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        
        .bracket-round-title {
            font-size: 1.2vw;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.85);
            text-transform: uppercase;
            text-align: center;
            margin: 0 0 0.6vw 0;
            flex: 0 0 auto;
            white-space: nowrap;
            padding: 0;
        }
        
        /* Cuerpo de la ronda con matches */
        .bracket-round-body {
            display: flex;
            flex-direction: column;
            gap: 0.5vw;
            justify-content: flex-start;
            overflow: visible;
            flex: 1;
        }
        
        /* Centrar semifinales y final verticalmente */
        .bracket-round--semis .bracket-round-body,
        .bracket-round--final .bracket-round-body {
            justify-content: center;
        }
        
        /* Tarjeta de partido */
        .match-card {
            display: flex;
            flex-direction: column;
            height: 8.5vw;
            flex: 0 0 8.5vw;
            padding: 0.4vw;
            border-radius: 0.6vw;
            background: rgba(255, 255, 255, 0.93);
            border: 2px solid rgba(15, 23, 42, 0.4);
            box-shadow: 0 0.8vw 1.8vw rgba(17, 24, 39, 0.25);
            backdrop-filter: blur(6px);
            transition: box-shadow 0.3s ease;
            overflow: visible;
        }
        
        .match-card.winner {
            box-shadow: none;
        }
        
        .match-card.placeholder {
            justify-content: center;
            text-align: center;
            background: rgba(255, 255, 255, 0.45);
            border: 2px dashed rgba(148, 163, 184, 0.55);
            box-shadow: none;
        }
        
        /* Pareja */
        .player-pair {
            display: flex;
            align-items: center;
            gap: 0.25vw;
            flex: 1;
            min-height: 0;
            background: rgba(248, 250, 252, 0.85);
            border-radius: 0.5vw;
            padding: 0.2vw 0.25vw;
            border: 1px solid rgba(226, 232, 240, 0.6);
            margin-bottom: 0.25vw;
        }
        
        .player-pair:last-child {
            margin-bottom: 0;
        }
        
        .player-pair.winner {
            background: rgba(46, 204, 113, 0.25);
            border-color: rgba(46, 204, 113, 0.6);
            box-shadow: inset 0 0 0.6vw rgba(46, 204, 113, 0.3);
        }
        
        .player-pair-content {
            display: flex;
            align-items: center;
            gap: 0.25vw;
            flex: 1;
            min-width: 0;
        }
        
        .match-card.placeholder .player-pair-content {
            justify-content: center;
        }
        
        /* Imágenes de jugadores */
        .player-images {
            display: flex;
            gap: 0.15vw;
            flex: 0 0 auto;
        }
        
        .player-images img {
            width: 1.5vw;
            height: 1.5vw;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(148, 163, 184, 0.65);
            box-shadow: 0 0.4vw 1vw rgba(15, 23, 42, 0.25);
        }
        
        /* Nombres de jugadores */
        .player-names {
            font-size: 0.7vw;
            font-weight: 500;
            line-height: 1.1;
            flex: 1;
            min-width: 0;
        }
        
        .player-name {
            text-transform: uppercase;
            letter-spacing: 0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .match-card.placeholder .player-names {
            font-size: 0.85vw;
            color: rgba(15, 23, 42, 0.65);
        }
        
        /* Input de puntuación */
        .player-pair-input {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 0 0 auto;
            padding-left: 0.3vw;
        }
        
        .score-display {
            font-size: 0.9vw;
            font-weight: 700;
            color: #0b1120;
            background: rgba(11, 17, 32, 0.55);
            padding: 0.1vw 0.25vw;
            border-radius: 0.3vw;
            min-width: 2vw;
            text-align: center;
            line-height: 1;
            flex: 0 0 auto;
        }
        
        /* Badge de posición */
        .badge {
            font-size: 0.6vw;
            padding: 0.1vw 0.3vw;
            border-radius: 0.3vw;
            letter-spacing: 0.02em;
            flex: 0 0 auto;
            white-space: nowrap;
        }
        
        /* Botón de tema */
        .theme-toggle {
            position: fixed;
            top: 0.8vw;
            right: 0.8vw;
            z-index: 1001;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 50%;
            width: 2.8vw;
            height: 2.8vw;
            font-size: 1.4vw;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0.3vw 0.8vw rgba(0,0,0,0.3);
            transition: background-color 0.3s ease;
        }
        
        .theme-toggle:hover {
            background-color: #5a7ee5;
        }
    </style>
</head>
<body>

<button class="theme-toggle" id="theme-toggle">
    <i class="fas fa-sun"></i>
</button>

<div class="header-tv">
    <button type="button" class="btn btn-secondary" id="btn-volver-clasificacion">
        ← Volver
    </button>
    <h2 class="torneo-title">{{ $torneo->nombre ?? 'Torneo' }}</h2>
    <div style="width: 80px;"></div> <!-- Spacer para balance -->
</div>

<input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">

<div class="bracket-container">
    <div class="container-fluid">
        
        @php
            $crucesCuartos = collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'cuartos')->values();
            $crucesSemifinales = collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'semifinales')->values();
            $crucesFinal = collect($cruces ?? [])->filter(fn($c) => ($c['ronda'] ?? '') === 'final')->values();
        @endphp

        <div class="bracket-row">
            <!-- Cuartos de Final -->
            <div class="bracket-column bracket-column--cuartos">
                <div class="bracket-round bracket-round--cuartos">
                    <div class="bracket-round-title">CUARTOS</div>
                    <div class="bracket-round-body">
                        @forelse($crucesCuartos as $index => $cruce)
                            @php
                                $pareja1 = is_array($cruce['pareja_1'] ?? null) ? $cruce['pareja_1'] : [];
                                $pareja2 = is_array($cruce['pareja_2'] ?? null) ? $cruce['pareja_2'] : [];
                                $jugadoresCollection = collect($jugadores);
                                $jugador1Id = $pareja1['jugador_1'] ?? null;
                                $jugador1PartnerId = $pareja1['jugador_2'] ?? null;
                                $jugador2Id = $pareja2['jugador_1'] ?? null;
                                $jugador2PartnerId = $pareja2['jugador_2'] ?? null;
                                $jugador1_1 = $jugador1Id !== null ? $jugadoresCollection->firstWhere('id', $jugador1Id) : null;
                                $jugador1_2 = $jugador1PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador1PartnerId) : null;
                                $jugador2_1 = $jugador2Id !== null ? $jugadoresCollection->firstWhere('id', $jugador2Id) : null;
                                $jugador2_2 = $jugador2PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador2PartnerId) : null;
                            @endphp
                            <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="cuartos">
                                <div class="player-pair pareja-cruce"
                                     data-pareja="1"
                                     data-jugador-1="{{ $pareja1['jugador_1'] ?? '' }}"
                                     data-jugador-2="{{ $pareja1['jugador_2'] ?? '' }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset(optional($jugador1_1)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador1_1)->nombre ?? '') . ' ' . (optional($jugador1_1)->apellido ?? '')) }}">
                                            <img src="{{ asset(optional($jugador1_2)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador1_2)->nombre ?? '') . ' ' . (optional($jugador1_2)->apellido ?? '')) }}">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ optional($jugador1_1)->nombre }} {{ optional($jugador1_1)->apellido }}</div>
                                            <div class="player-name">{{ optional($jugador1_2)->nombre }} {{ optional($jugador1_2)->apellido }}</div>
                                        </div>
                                        @if(($pareja1['zona'] ?? null) && ($pareja1['posicion'] ?? null))
                                            <span class="badge badge-info">{{ $pareja1['zona'] }}{{ $pareja1['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <span class="score-display" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-pareja="1">0</span>
                                    </div>
                                </div>

                                <div class="player-pair pareja-cruce"
                                     data-pareja="2"
                                     data-jugador-1="{{ $pareja2['jugador_1'] ?? '' }}"
                                     data-jugador-2="{{ $pareja2['jugador_2'] ?? '' }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset(optional($jugador2_1)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador2_1)->nombre ?? '') . ' ' . (optional($jugador2_1)->apellido ?? '')) }}">
                                            <img src="{{ asset(optional($jugador2_2)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador2_2)->nombre ?? '') . ' ' . (optional($jugador2_2)->apellido ?? '')) }}">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ optional($jugador2_1)->nombre }} {{ optional($jugador2_1)->apellido }}</div>
                                            <div class="player-name">{{ optional($jugador2_2)->nombre }} {{ optional($jugador2_2)->apellido }}</div>
                                        </div>
                                        @if(($pareja2['zona'] ?? null) && ($pareja2['posicion'] ?? null))
                                            <span class="badge badge-info">{{ $pareja2['zona'] }}{{ $pareja2['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <span class="score-display" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-pareja="2">0</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="match-card placeholder" data-ronda="cuartos">
                                <div class="player-pair">
                                    <div class="player-pair-content">
                                        <div class="player-names">
                                            Cruces de cuartos en preparación...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Semifinales -->
            <div class="bracket-column bracket-column--semis">
                <div class="bracket-round bracket-round--semis" id="semifinales-container">
                    <div class="bracket-round-title">SEMIFINALES</div>
                    <div class="bracket-round-body" id="semifinales-content">
                        @forelse($crucesSemifinales as $index => $cruce)
                            @php
                                $pareja1 = is_array($cruce['pareja_1'] ?? null) ? $cruce['pareja_1'] : [];
                                $pareja2 = is_array($cruce['pareja_2'] ?? null) ? $cruce['pareja_2'] : [];
                                $jugadoresCollection = collect($jugadores);
                                $jugador1Id = $pareja1['jugador_1'] ?? null;
                                $jugador1PartnerId = $pareja1['jugador_2'] ?? null;
                                $jugador2Id = $pareja2['jugador_1'] ?? null;
                                $jugador2PartnerId = $pareja2['jugador_2'] ?? null;
                                $jugador1_1 = $jugador1Id !== null ? $jugadoresCollection->firstWhere('id', $jugador1Id) : null;
                                $jugador1_2 = $jugador1PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador1PartnerId) : null;
                                $jugador2_1 = $jugador2Id !== null ? $jugadoresCollection->firstWhere('id', $jugador2Id) : null;
                                $jugador2_2 = $jugador2PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador2PartnerId) : null;
                            @endphp
                            <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="semifinales">
                                <div class="player-pair pareja-cruce"
                                     data-pareja="1"
                                     data-jugador-1="{{ $pareja1['jugador_1'] ?? '' }}"
                                     data-jugador-2="{{ $pareja1['jugador_2'] ?? '' }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset(optional($jugador1_1)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador1_1)->nombre ?? '') . ' ' . (optional($jugador1_1)->apellido ?? '')) }}">
                                            <img src="{{ asset(optional($jugador1_2)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador1_2)->nombre ?? '') . ' ' . (optional($jugador1_2)->apellido ?? '')) }}">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ optional($jugador1_1)->nombre }} {{ optional($jugador1_1)->apellido }}</div>
                                            <div class="player-name">{{ optional($jugador1_2)->nombre }} {{ optional($jugador1_2)->apellido }}</div>
                                        </div>
                                        @if(($pareja1['zona'] ?? null) && ($pareja1['posicion'] ?? null))
                                            <span class="badge badge-info">{{ $pareja1['zona'] }}{{ $pareja1['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <span class="score-display" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-pareja="1">0</span>
                                    </div>
                                </div>

                                <div class="player-pair pareja-cruce"
                                     data-pareja="2"
                                     data-jugador-1="{{ $pareja2['jugador_1'] ?? '' }}"
                                     data-jugador-2="{{ $pareja2['jugador_2'] ?? '' }}">
                                    <div class="player-pair-content">
                                        <div class="player-images">
                                            <img src="{{ asset(optional($jugador2_1)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador2_1)->nombre ?? '') . ' ' . (optional($jugador2_1)->apellido ?? '')) }}">
                                            <img src="{{ asset(optional($jugador2_2)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador2_2)->nombre ?? '') . ' ' . (optional($jugador2_2)->apellido ?? '')) }}">
                                        </div>
                                        <div class="player-names">
                                            <div class="player-name">{{ optional($jugador2_1)->nombre }} {{ optional($jugador2_1)->apellido }}</div>
                                            <div class="player-name">{{ optional($jugador2_2)->nombre }} {{ optional($jugador2_2)->apellido }}</div>
                                        </div>
                                        @if(($pareja2['zona'] ?? null) && ($pareja2['posicion'] ?? null))
                                            <span class="badge badge-info">{{ $pareja2['zona'] }}{{ $pareja2['posicion'] }}º</span>
                                        @endif
                                    </div>
                                    <div class="player-pair-input">
                                        <span class="score-display" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-pareja="2">0</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="match-card placeholder" data-ronda="semifinales">
                                <div class="player-pair">
                                    <div class="player-pair-content">
                                        <div class="player-names">
                                            Esperando definiciones para semifinales...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Final -->
            <div class="bracket-column bracket-column--final">
                <div class="bracket-round bracket-round--final" id="final-container">
                    <div class="bracket-round-title">FINAL</div>
                    <div class="bracket-round-body" id="final-content">
                        @forelse($crucesFinal as $index => $cruce)
                                @php
                                    $pareja1 = is_array($cruce['pareja_1'] ?? null) ? $cruce['pareja_1'] : [];
                                    $pareja2 = is_array($cruce['pareja_2'] ?? null) ? $cruce['pareja_2'] : [];
                                    $jugadoresCollection = collect($jugadores);
                                    $jugador1Id = $pareja1['jugador_1'] ?? null;
                                    $jugador1PartnerId = $pareja1['jugador_2'] ?? null;
                                    $jugador2Id = $pareja2['jugador_1'] ?? null;
                                    $jugador2PartnerId = $pareja2['jugador_2'] ?? null;
                                    $jugador1_1 = $jugador1Id !== null ? $jugadoresCollection->firstWhere('id', $jugador1Id) : null;
                                    $jugador1_2 = $jugador1PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador1PartnerId) : null;
                                    $jugador2_1 = $jugador2Id !== null ? $jugadoresCollection->firstWhere('id', $jugador2Id) : null;
                                    $jugador2_2 = $jugador2PartnerId !== null ? $jugadoresCollection->firstWhere('id', $jugador2PartnerId) : null;
                                @endphp
                                <div class="match-card" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-ronda="final">
                                    <!-- Pareja 1 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="1"
                                         data-jugador-1="{{ $pareja1['jugador_1'] ?? '' }}"
                                         data-jugador-2="{{ $pareja1['jugador_2'] ?? '' }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset(optional($jugador1_1)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador1_1)->nombre ?? '') . ' ' . (optional($jugador1_1)->apellido ?? '')) }}">
                                                <img src="{{ asset(optional($jugador1_2)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador1_2)->nombre ?? '') . ' ' . (optional($jugador1_2)->apellido ?? '')) }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ optional($jugador1_1)->nombre }} {{ optional($jugador1_1)->apellido }}</div>
                                                <div class="player-name">{{ optional($jugador1_2)->nombre }} {{ optional($jugador1_2)->apellido }}</div>
                                            </div>
                                            @if(($pareja1['zona'] ?? null) && ($pareja1['posicion'] ?? null))
                                                <span class="badge badge-info">{{ $pareja1['zona'] }}{{ $pareja1['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <span class="score-display" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-pareja="1">0</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Pareja 2 -->
                                    <div class="player-pair pareja-cruce" 
                                         data-pareja="2"
                                         data-jugador-1="{{ $pareja2['jugador_1'] ?? '' }}"
                                         data-jugador-2="{{ $pareja2['jugador_2'] ?? '' }}">
                                        <div class="player-pair-content">
                                            <div class="player-images">
                                                <img src="{{ asset(optional($jugador2_1)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador2_1)->nombre ?? '') . ' ' . (optional($jugador2_1)->apellido ?? '')) }}">
                                                <img src="{{ asset(optional($jugador2_2)->foto ?? 'images/jugador_img.png') }}" alt="{{ trim((optional($jugador2_2)->nombre ?? '') . ' ' . (optional($jugador2_2)->apellido ?? '')) }}">
                                            </div>
                                            <div class="player-names">
                                                <div class="player-name">{{ optional($jugador2_1)->nombre }} {{ optional($jugador2_1)->apellido }}</div>
                                                <div class="player-name">{{ optional($jugador2_2)->nombre }} {{ optional($jugador2_2)->apellido }}</div>
                                            </div>
                                            @if(($pareja2['zona'] ?? null) && ($pareja2['posicion'] ?? null))
                                                <span class="badge badge-info">{{ $pareja2['zona'] }}{{ $pareja2['posicion'] }}º</span>
                                            @endif
                                        </div>
                                        <div class="player-pair-input">
                                            <span class="score-display" data-cruce-id="{{ $cruce['id'] ?? $index }}" data-pareja="2">0</span>
                                        </div>
                                    </div>
                                    
                                </div>
                        @empty
                            <div class="match-card placeholder" data-ronda="final">
                                <div class="player-pair">
                                    <div class="player-pair-content">
                                        <div class="player-names">
                                            La final se mostrará aquí cuando esté definida.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Ganadores -->
<div id="modal-ganadores" class="modal-ganadores">
    <div class="modal-content-ganadores">
        <h2>🏆 ¡GANADORES! 🏆</h2>
        <div class="ganadores-fotos" id="ganadores-fotos">
            <!-- Se llenará dinámicamente -->
        </div>
        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalGanadores()">Cerrar</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script type="text/javascript">
    const cruces = @json($cruces ?? []);
    const jugadores = @json($jugadores ?? []);
    const resultadosGuardados = @json($resultadosGuardados ?? []);
    const baseUrl = '{{ url("/") }}';
    const jugadoresMap = new Map(jugadores.map(j => [Number(j.id), j]));
    let torneoId = 0;

    document.addEventListener('DOMContentLoaded', () => {
        torneoId = Number(document.getElementById('torneo_id')?.value || 0);
        inicializarTema();
        inicializarBotonVolver();
        cargarResultadosGuardados();

        // Ajustar escala para que todo quepa en pantalla (sin scroll)
        setTimeout(ajustarEscalaBracket, 120);
        window.addEventListener('resize', () => setTimeout(ajustarEscalaBracket, 120));
    });

    function inicializarTema() {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;

        const body = document.body;
        const icon = themeToggle.querySelector('i');

        aplicarTema(localStorage.getItem('theme') || 'dark', body, icon);

        themeToggle.addEventListener('click', () => {
            const nextTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
            aplicarTema(nextTheme, body, icon);
        });
    }

    function aplicarTema(theme, body, icon) {
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            body.style.background = 'linear-gradient(135deg, #0f172a 0%, #111827 40%, #1f2937 100%)';
            body.style.color = '#e2e8f0';
            icon?.classList.remove('fa-sun');
            icon?.classList.add('fa-moon');
        } else {
            body.classList.remove('dark-mode');
            body.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 55%, #cbd5f5 100%)';
            body.style.color = '#0f172a';
            icon?.classList.remove('fa-moon');
            icon?.classList.add('fa-sun');
        }

        localStorage.setItem('theme', theme);
    }

    // Ajusta el layout del bracket para que todo quepa en la pantalla sin scroll
    function ajustarEscalaBracket() {
        const container = document.querySelector('.bracket-container');
        const bracket = document.querySelector('.bracket-row');
        const header = document.querySelector('.header-tv');

        if (!container || !bracket) return;

        // Calcular espacio disponible
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const availableHeight = window.innerHeight - headerHeight;
        const availableWidth = window.innerWidth;

        // Obtener tamaño actual del bracket
        const bracketRect = bracket.getBoundingClientRect();
        const bracketHeight = bracketRect.height;
        const bracketWidth = bracketRect.width;

        // Si el contenido cabe naturalmente, no hacer nada especial
        if (bracketHeight <= availableHeight && bracketWidth <= availableWidth) {
            container.style.transform = 'scale(1)';
            return;
        }

        // Calcular escala necesaria para que quepa
        const scaleHeight = availableHeight / bracketHeight;
        const scaleWidth = availableWidth / bracketWidth;
        const scale = Math.min(1, scaleHeight, scaleWidth) * 0.98; // Dejar pequeño margen

        container.style.transform = `scale(${scale})`;
        container.style.transformOrigin = 'top center';
    }


    function inicializarBotonVolver() {
        const boton = document.getElementById('btn-volver-clasificacion');
        if (!boton) return;

        boton.addEventListener('click', () => {
            if (!torneoId) return;
            window.location.href = '{{ route("admintorneoamericanopartidos") }}?torneo_id=' + torneoId;
        });
    }

    function clavePareja(jugador1, jugador2) {
        return [Number(jugador1) || 0, Number(jugador2) || 0].sort((a, b) => a - b).join('-');
    }

    function parejasCoinciden(pareja, claveObjetivo) {
        if (!pareja) return false;
        return clavePareja(pareja.jugador_1, pareja.jugador_2) === claveObjetivo;
    }

    function encontrarCruce(resultado) {
        const ronda = resultado.ronda;
        const cruceId = resultado.cruce_id;

        if (cruceId !== undefined && cruceId !== null) {
            const crucePorId = cruces.find(c => String(c.id) === String(cruceId));
            if (crucePorId) {
                return crucePorId;
            }
        }

        const clavePareja1 = clavePareja(resultado.pareja_1_jugador_1, resultado.pareja_1_jugador_2);
        const clavePareja2 = clavePareja(resultado.pareja_2_jugador_1, resultado.pareja_2_jugador_2);

        return cruces.find(c => c.ronda === ronda && parejasCoinciden(c.pareja_1, clavePareja1) && parejasCoinciden(c.pareja_2, clavePareja2)) ||
               cruces.find(c => c.ronda === ronda && parejasCoinciden(c.pareja_1, clavePareja2) && parejasCoinciden(c.pareja_2, clavePareja1));
    }

    function obtenerMatchCard(cruce, ronda, resultado) {
        const candidatos = [];

        if (cruce?.id !== undefined && cruce?.id !== null) {
            candidatos.push(cruce.id);
        }

        const posicion = cruces.indexOf(cruce);
        if (posicion >= 0) {
            candidatos.push(posicion);
        }

        if (resultado?.cruce_id !== undefined && resultado?.cruce_id !== null) {
            candidatos.push(resultado.cruce_id);
        }

        for (const candidato of candidatos) {
            const card = $(`.match-card[data-cruce-id="${candidato}"][data-ronda="${ronda}"]`);
            if (card.length) {
                return card;
            }
        }

        const clavePareja1 = clavePareja(resultado.pareja_1_jugador_1, resultado.pareja_1_jugador_2);
        const clavePareja2 = clavePareja(resultado.pareja_2_jugador_1, resultado.pareja_2_jugador_2);

        return $(`.match-card[data-ronda="${ronda}"]`).filter(function() {
            const parejaDom1 = $(this).find('.pareja-cruce[data-pareja="1"]');
            const parejaDom2 = $(this).find('.pareja-cruce[data-pareja="2"]');
            const domClave1 = clavePareja(parejaDom1.data('jugador-1'), parejaDom1.data('jugador-2'));
            const domClave2 = clavePareja(parejaDom2.data('jugador-1'), parejaDom2.data('jugador-2'));
            return (domClave1 === clavePareja1 && domClave2 === clavePareja2) ||
                   (domClave1 === clavePareja2 && domClave2 === clavePareja1);
        }).first();
    }

    function actualizarMarcador(matchCard, resultado, cruce) {
        const score1 = Number(resultado.pareja_1_set_1) || 0;
        const score2 = Number(resultado.pareja_2_set_1) || 0;

        matchCard.find(`.score-display[data-pareja="1"]`).text(score1);
        matchCard.find(`.score-display[data-pareja="2"]`).text(score2);

        matchCard.removeClass('winner');
        matchCard.find('.player-pair').removeClass('winner');

        const hayResultado = score1 > 0 || score2 > 0;
        if (!hayResultado || score1 === score2) {
            return null;
        }

        const parejaGanadora = score1 > score2 ? cruce.pareja_1 : cruce.pareja_2;
        const parejaGanadoraNumero = score1 > score2 ? 1 : 2;

        matchCard.addClass('winner');
        matchCard.find(`.player-pair[data-pareja="${parejaGanadoraNumero}"]`).addClass('winner');

        return parejaGanadora;
    }

    function cargarResultadosGuardados() {
        let parejaGanadoraFinal = null;
        let aplicados = 0;

        // Guardar estado de reintentos en una variable global simple
        window.__crucesResultadosRetries = window.__crucesResultadosRetries || 0;

        resultadosGuardados.forEach(resultado => {
            const cruce = encontrarCruce(resultado);
            if (!cruce) return;

            const matchCard = obtenerMatchCard(cruce, resultado.ronda, resultado);
            if (!matchCard || !matchCard.length) return;

            const parejaGanadora = actualizarMarcador(matchCard, resultado, cruce);

            // Mostrar sets numéricos en caso de que estén definidos
            const score1 = Number(resultado.pareja_1_set_1) || 0;
            const score2 = Number(resultado.pareja_2_set_1) || 0;
            matchCard.find(`.score-display[data-pareja="1"]`).text(score1);
            matchCard.find(`.score-display[data-pareja="2"]`).text(score2);

            aplicados++;

            if (resultado.ronda === 'final' && parejaGanadora) {
                parejaGanadoraFinal = parejaGanadora;
            }
        });

        // Si no se aplicaron todos los resultados, reintentamos un par de veces (por si el DOM estaba incompleto)
        if (aplicados < resultadosGuardados.length && window.__crucesResultadosRetries < 3) {
            window.__crucesResultadosRetries++;
            setTimeout(cargarResultadosGuardados, 400);
            return;
        }

        if (parejaGanadoraFinal) {
            mostrarModalGanadores(parejaGanadoraFinal);
        }

        // Recalcular escala y aplicar compact si es necesario
        setTimeout(() => {
            ajustarEscalaBracket();
        }, 80);
    }

    function obtenerJugadorPorId(id) {
        return jugadoresMap.get(Number(id)) || null;
    }

    function mostrarModalGanadores(parejaGanadora) {
        if (!parejaGanadora) return;

        const jugador1 = obtenerJugadorPorId(parejaGanadora.jugador_1);
        const jugador2 = obtenerJugadorPorId(parejaGanadora.jugador_2);

        const getFotoUrl = (jugador) => {
            if (!jugador || !jugador.foto) return `${baseUrl}/images/jugador_img.png`;
            const ruta = jugador.foto.startsWith('/') ? jugador.foto.substring(1) : jugador.foto;
            return `${baseUrl}/${ruta}`;
        };

        const nombreJugador = (jugador) => {
            if (!jugador) return 'A confirmar';
            const nombre = jugador.nombre ?? '';
            const apellido = jugador.apellido ?? '';
            return `${nombre} ${apellido}`.trim() || 'A confirmar';
        };

        const html = `
            <div class="ganador-foto">
                <img src="${getFotoUrl(jugador1)}" alt="${nombreJugador(jugador1)}">
                <div class="nombre">${nombreJugador(jugador1)}</div>
            </div>
            <div class="ganador-foto">
                <img src="${getFotoUrl(jugador2)}" alt="${nombreJugador(jugador2)}">
                <div class="nombre">${nombreJugador(jugador2)}</div>
            </div>
        `;

        $('#ganadores-fotos').html(html);
        $('#modal-ganadores').addClass('show');
        crearConfetti();
    }

    function cerrarModalGanadores() {
        $('#modal-ganadores').removeClass('show');
        $('.confetti').remove();
    }

    function crearConfetti() {
        for (let i = 0; i < 100; i++) {
            const confetti = $('<div class="confetti"></div>');
            confetti.css({
                left: Math.random() * 100 + 'vw',
                animationDuration: (Math.random() * 3 + 2) + 's',
                animationDelay: Math.random() * 2 + 's',
                backgroundColor: `hsl(${Math.random() * 360}, 100%, 50%)`
            });
            $('body').append(confetti);
        }
    }
</script>

</body>
</html>

