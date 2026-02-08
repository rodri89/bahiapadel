@extends('bahia_padel/admin/plantilla')

@section('title_header','Cruces Eliminatorios - Torneo Puntuable')

@section('contenedor')
<link rel="stylesheet" href="{{ asset('css/bracket.css') }}">
<link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">

<div class="bracket-container">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-secondary" id="btn-volver-clasificacion">
                        ← Volver a Clasificación
                    </button>
                    
                    <h2 class="text-center flex-grow-1 mb-0" style="color: #000;">{{ $torneo->nombre ?? 'Torneo' }}</h2>
                    
                    <div class="d-flex align-items-center">
                        <a href="{{ route('tvtorneoamericanocruces') }}?torneo_id={{ $torneo->id }}" target="_blank" class="btn btn-primary ml-2">
                            <i class="fa fa-desktop"></i> TV
                        </a>
                    </div>
                </div>
                <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
            </div>
        </div>
        
        <div class="row">
            <!-- Ejemplo de Card de Partido -->
            <div class="col-md-3">
                <div class="bracket-round">
                    <div class="bracket-round-title">Octavos Final</div>
                    @foreach($crucesOctavos as $cruce)
                        @php
                            // Obtener datos de los jugadores
                            $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                            $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                            $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                            $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            
                            // Obtener resultados del partido si existen
                            $partido = $cruce['partido'] ?? null;
                            $pareja1_set1 = $partido ? ($partido->pareja_1_set_1 ?? 0) : 0;
                            $pareja1_set2 = $partido ? ($partido->pareja_1_set_2 ?? 0) : 0;
                            $pareja1_set3 = $partido ? ($partido->pareja_1_set_3 ?? 0) : 0;
                            $pareja2_set1 = $partido ? ($partido->pareja_2_set_1 ?? 0) : 0;
                            $pareja2_set2 = $partido ? ($partido->pareja_2_set_2 ?? 0) : 0;
                            $pareja2_set3 = $partido ? ($partido->pareja_2_set_3 ?? 0) : 0;
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] }}" 
                             style="padding: 15px; margin-bottom: 20px;">
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón guardar -->
                            <div class="text-center mt-2">
                                <button type="button" 
                                        class="btn btn-primary btn-sm guardar-cruce" 
                                        data-cruce-id="{{ $cruce['id'] }}"
                                        data-ronda="{{ $cruce['ronda'] }}">
                                    Guardar
                                </button>
                            </div>
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            
            <!-- Cuartos de Final -->
            @if(count($crucesCuartos) > 0)
            <div class="col-md-3">
                <div class="bracket-round">
                    <div class="bracket-round-title">Cuartos Final</div>
                    @foreach($crucesCuartos as $cruce)
                        @php
                            // Obtener datos de los jugadores
                            $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                            $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                            $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                            $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            
                            // Obtener resultados del partido si existen
                            $partido = $cruce['partido'] ?? null;
                            $pareja1_set1 = $partido ? ($partido->pareja_1_set_1 ?? 0) : 0;
                            $pareja1_set2 = $partido ? ($partido->pareja_1_set_2 ?? 0) : 0;
                            $pareja1_set3 = $partido ? ($partido->pareja_1_set_3 ?? 0) : 0;
                            $pareja2_set1 = $partido ? ($partido->pareja_2_set_1 ?? 0) : 0;
                            $pareja2_set2 = $partido ? ($partido->pareja_2_set_2 ?? 0) : 0;
                            $pareja2_set3 = $partido ? ($partido->pareja_2_set_3 ?? 0) : 0;
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] }}" 
                             style="padding: 15px; margin-bottom: 20px;">
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón guardar -->
                            <div class="text-center mt-2">
                                <button type="button" 
                                        class="btn btn-primary btn-sm guardar-cruce" 
                                        data-cruce-id="{{ $cruce['id'] }}"
                                        data-ronda="{{ $cruce['ronda'] }}">
                                    Guardar
                                </button>
                            </div>
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Semifinales -->
            @if(count($crucesSemifinales) > 0)
            <div class="col-md-3">
                <div class="bracket-round">
                    <div class="bracket-round-title">Semifinales</div>
                    @foreach($crucesSemifinales as $cruce)
                        @php
                            // Obtener datos de los jugadores
                            $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                            $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                            $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                            $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            
                            // Obtener resultados del partido si existen
                            $partido = $cruce['partido'] ?? null;
                            $pareja1_set1 = $partido ? ($partido->pareja_1_set_1 ?? 0) : 0;
                            $pareja1_set2 = $partido ? ($partido->pareja_1_set_2 ?? 0) : 0;
                            $pareja1_set3 = $partido ? ($partido->pareja_1_set_3 ?? 0) : 0;
                            $pareja2_set1 = $partido ? ($partido->pareja_2_set_1 ?? 0) : 0;
                            $pareja2_set2 = $partido ? ($partido->pareja_2_set_2 ?? 0) : 0;
                            $pareja2_set3 = $partido ? ($partido->pareja_2_set_3 ?? 0) : 0;
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] }}" 
                             style="padding: 15px; margin-bottom: 20px;">
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón guardar -->
                            <div class="text-center mt-2">
                                <button type="button" 
                                        class="btn btn-primary btn-sm guardar-cruce" 
                                        data-cruce-id="{{ $cruce['id'] }}"
                                        data-ronda="{{ $cruce['ronda'] }}">
                                    Guardar
                                </button>
                            </div>
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Final -->
            @if(count($crucesFinales) > 0)
            <div class="col-md-3">
                <div class="bracket-round">
                    <div class="bracket-round-title">Final</div>
                    @foreach($crucesFinales as $cruce)
                        @php
                            // Obtener datos de los jugadores
                            $jugador1_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_1']);
                            $jugador1_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_1']['jugador_2']);
                            $jugador2_1 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_1']);
                            $jugador2_2 = collect($jugadores)->firstWhere('id', $cruce['pareja_2']['jugador_2']);
                            
                            // Obtener resultados del partido si existen
                            $partido = $cruce['partido'] ?? null;
                            $pareja1_set1 = $partido ? ($partido->pareja_1_set_1 ?? 0) : 0;
                            $pareja1_set2 = $partido ? ($partido->pareja_1_set_2 ?? 0) : 0;
                            $pareja1_set3 = $partido ? ($partido->pareja_1_set_3 ?? 0) : 0;
                            $pareja2_set1 = $partido ? ($partido->pareja_2_set_1 ?? 0) : 0;
                            $pareja2_set2 = $partido ? ($partido->pareja_2_set_2 ?? 0) : 0;
                            $pareja2_set3 = $partido ? ($partido->pareja_2_set_3 ?? 0) : 0;
                        @endphp
                        <!-- CARD DE PARTIDO -->
                        <div class="match-card" 
                             data-cruce-id="{{ $cruce['id'] }}" 
                             data-ronda="{{ $cruce['ronda'] }}" 
                             data-partido-id="{{ $cruce['partido_id'] }}" 
                             style="padding: 15px; margin-bottom: 20px;">
                            <!-- Pareja 1 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="1"
                                 data-jugador-1="{{ $cruce['pareja_1']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_1']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador1_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador1_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_1->nombre ?? '' }} {{ $jugador1_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador1_2->nombre ?? '' }} {{ $jugador1_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 1 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="1"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja1_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inputs Sets Pareja 2 -->
                            <div class="mb-3">                                    
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 1</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="1"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set1 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 2</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="2"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set2 }}"
                                               placeholder="0">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <label class="small mb-1" style="color: #000;">Set 3</label>
                                        <input type="number" 
                                               class="form-control resultado-cruce" 
                                               style="width: 60px;"
                                               data-cruce-id="{{ $cruce['id'] }}"
                                               data-pareja="2"
                                               data-set="3"
                                               data-ronda="{{ $cruce['ronda'] }}"
                                               min="0"
                                               max="99"
                                               value="{{ $pareja2_set3 }}"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pareja 2 -->
                            <div class="d-flex align-items-center mb-3" 
                                 data-pareja="2"
                                 data-jugador-1="{{ $cruce['pareja_2']['jugador_1'] }}"
                                 data-jugador-2="{{ $cruce['pareja_2']['jugador_2'] }}">
                                <!-- Imágenes -->
                                <div class="d-flex mr-3">
                                    <img src="{{ asset($jugador2_1->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover; margin-right: 5px;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                    <img src="{{ asset($jugador2_2->foto ?? 'images/jugador_img.png') }}" 
                                         alt="{{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}" 
                                         class="rounded-circle"
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         onerror="this.src='{{ asset('images/jugador_img.png') }}?v=' + Date.now()">
                                </div>
                                <!-- Nombres a la derecha -->
                                <div class="d-flex flex-column justify-content-center" style="height: 60px;">
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_1->nombre ?? '' }} {{ $jugador2_1->apellido ?? '' }}
                                    </div>
                                    <div class="player-name" style="font-weight: bold; color: #000; font-size: 0.875rem;">
                                        {{ $jugador2_2->nombre ?? '' }} {{ $jugador2_2->apellido ?? '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón guardar -->
                            <div class="text-center mt-2">
                                <button type="button" 
                                        class="btn btn-primary btn-sm guardar-cruce" 
                                        data-cruce-id="{{ $cruce['id'] }}"
                                        data-ronda="{{ $cruce['ronda'] }}">
                                    Guardar
                                </button>
                            </div>
                        </div>
                        <!-- FIN CARD DE PARTIDO -->
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar" class="snackbar">Resultado guardado correctamente</div>

<script type="text/javascript">
    console.log('Script cargado. Verificando jQuery...');
    console.log('jQuery disponible:', typeof jQuery !== 'undefined');
    console.log('$ disponible:', typeof $ !== 'undefined');
    
    $(document).ready(function() {
        console.log('Document ready ejecutado');
        console.log('Botones .guardar-cruce encontrados:', $('.guardar-cruce').length);
    });
    
    let torneoId = $('#torneo_id').val();
    let resultadosGuardados = @json($resultadosGuardados ?? []);
    
    console.log('Torneo ID:', torneoId);
    console.log('Resultados guardados:', resultadosGuardados.length);
    
    // Función para mostrar snackbar
    function mostrarSnackbar(mensaje) {
        let snackbar = document.getElementById("snackbar");
        snackbar.textContent = mensaje;
        snackbar.className = "snackbar show";
        setTimeout(function(){ snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }
    
    // Función para verificar ganador y deshabilitar set 3
    function verificarGanadorYDeshabilitarSet3(cruceId) {
        let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
        let pareja1Set1 = parseInt(matchCard.find('input[data-pareja="1"][data-set="1"]').val()) || 0;
        let pareja1Set2 = parseInt(matchCard.find('input[data-pareja="1"][data-set="2"]').val()) || 0;
        let pareja2Set1 = parseInt(matchCard.find('input[data-pareja="2"][data-set="1"]').val()) || 0;
        let pareja2Set2 = parseInt(matchCard.find('input[data-pareja="2"][data-set="2"]').val()) || 0;
        
        let pareja1SetsGanados = 0;
        let pareja2SetsGanados = 0;
        
        // Contar sets ganados (solo si ambos tienen score > 0)
        if (pareja1Set1 > 0 && pareja2Set1 > 0) {
            if (pareja1Set1 > pareja2Set1) {
                pareja1SetsGanados++;
            } else if (pareja2Set1 > pareja1Set1) {
                pareja2SetsGanados++;
            }
        }
        
        if (pareja1Set2 > 0 && pareja2Set2 > 0) {
            if (pareja1Set2 > pareja2Set2) {
                pareja1SetsGanados++;
            } else if (pareja2Set2 > pareja1Set2) {
                pareja2SetsGanados++;
            }
        }
        
        // Si alguna pareja ganó 2 sets, deshabilitar set 3
        let set3Pareja1 = matchCard.find('input[data-pareja="1"][data-set="3"]');
        let set3Pareja2 = matchCard.find('input[data-pareja="2"][data-set="3"]');
        
        if (pareja1SetsGanados >= 2 || pareja2SetsGanados >= 2) {
            set3Pareja1.prop('disabled', true).val('');
            set3Pareja2.prop('disabled', true).val('');
        } else {
            set3Pareja1.prop('disabled', false);
            set3Pareja2.prop('disabled', false);
        }
    }
    
    // Cargar resultados guardados al cargar la página
    function cargarResultadosGuardados() {
        if (!resultadosGuardados || resultadosGuardados.length === 0) {
            return;
        }
        
        resultadosGuardados.forEach(function(resultado) {
            let cruceId = resultado.cruce_id;
            let matchCard = $(`.match-card[data-cruce-id="${cruceId}"]`);
            
            if (matchCard.length === 0) {
                return;
            }
            
            // Cargar valores de los sets
            if (resultado.pareja_1_set_1 !== null && resultado.pareja_1_set_1 !== undefined) {
                matchCard.find('input[data-pareja="1"][data-set="1"]').val(resultado.pareja_1_set_1);
            }
            if (resultado.pareja_1_set_2 !== null && resultado.pareja_1_set_2 !== undefined) {
                matchCard.find('input[data-pareja="1"][data-set="2"]').val(resultado.pareja_1_set_2);
            }
            if (resultado.pareja_1_set_3 !== null && resultado.pareja_1_set_3 !== undefined) {
                matchCard.find('input[data-pareja="1"][data-set="3"]').val(resultado.pareja_1_set_3);
            }
            if (resultado.pareja_2_set_1 !== null && resultado.pareja_2_set_1 !== undefined) {
                matchCard.find('input[data-pareja="2"][data-set="1"]').val(resultado.pareja_2_set_1);
            }
            if (resultado.pareja_2_set_2 !== null && resultado.pareja_2_set_2 !== undefined) {
                matchCard.find('input[data-pareja="2"][data-set="2"]').val(resultado.pareja_2_set_2);
            }
            if (resultado.pareja_2_set_3 !== null && resultado.pareja_2_set_3 !== undefined) {
                matchCard.find('input[data-pareja="2"][data-set="3"]').val(resultado.pareja_2_set_3);
            }
            
            // Verificar ganador después de cargar
            verificarGanadorYDeshabilitarSet3(cruceId);
        });
    }
    
    // Event listener para cambios en inputs de sets 1 y 2
    $(document).on('input change', '.resultado-cruce[data-set="1"], .resultado-cruce[data-set="2"]', function() {
        let cruceId = $(this).data('cruce-id');
        verificarGanadorYDeshabilitarSet3(cruceId);
    });
    
    // Guardar resultado de cruce
    $(document).on('click', '.guardar-cruce', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        try {
            console.log('=== BOTÓN GUARDAR CLICKEADO ===');
            
            let cruceId = $(this).data('cruce-id');
            let ronda = $(this).data('ronda');
            let matchCard = $(this).closest('.match-card');
            
            console.log('Cruce ID:', cruceId);
            console.log('Ronda:', ronda);
            console.log('Match Card encontrado:', matchCard.length > 0);
            
            if (matchCard.length === 0) {
                console.error('ERROR: No se encontró el match-card');
                mostrarSnackbar('Error: No se encontró la tarjeta del partido');
                return;
            }
            
            // Obtener información de las parejas
            console.log('Buscando elementos de pareja...');
            // Buscar el div que tiene los atributos data-jugador-1 y data-jugador-2 (no los inputs)
            let pareja1Element = matchCard.find('[data-pareja="1"][data-jugador-1]').first();
            let pareja2Element = matchCard.find('[data-pareja="2"][data-jugador-1]').first();
        
            console.log('Pareja 1 element encontrado:', pareja1Element.length);
            console.log('Pareja 2 element encontrado:', pareja2Element.length);
            
            if (pareja1Element.length === 0 || pareja2Element.length === 0) {
                console.error('ERROR: No se encontraron los elementos de pareja');
                mostrarSnackbar('Error: No se encontraron los elementos de pareja');
                return;
            }
            
            let pareja1Jugador1 = parseInt(pareja1Element.attr('data-jugador-1'));
            let pareja1Jugador2 = parseInt(pareja1Element.attr('data-jugador-2'));
            let pareja2Jugador1 = parseInt(pareja2Element.attr('data-jugador-1'));
            let pareja2Jugador2 = parseInt(pareja2Element.attr('data-jugador-2'));
            
            console.log('Jugadores obtenidos - Pareja 1:', pareja1Jugador1, pareja1Jugador2);
            console.log('Jugadores obtenidos - Pareja 2:', pareja2Jugador1, pareja2Jugador2);
        
            // Obtener valores de los sets
            console.log('Obteniendo valores de los sets...');
            let pareja1Set1 = parseInt(matchCard.find('input[data-pareja="1"][data-set="1"]').val()) || 0;
            let pareja1Set2 = parseInt(matchCard.find('input[data-pareja="1"][data-set="2"]').val()) || 0;
            let pareja1Set3 = parseInt(matchCard.find('input[data-pareja="1"][data-set="3"]').val()) || 0;
            let pareja2Set1 = parseInt(matchCard.find('input[data-pareja="2"][data-set="1"]').val()) || 0;
            let pareja2Set2 = parseInt(matchCard.find('input[data-pareja="2"][data-set="2"]').val()) || 0;
            let pareja2Set3 = parseInt(matchCard.find('input[data-pareja="2"][data-set="3"]').val()) || 0;
            
            console.log('Sets obtenidos - Pareja 1:', pareja1Set1, pareja1Set2, pareja1Set3);
            console.log('Sets obtenidos - Pareja 2:', pareja2Set1, pareja2Set2, pareja2Set3);
            
            // Validar que haya al menos un resultado
            if (pareja1Set1 === 0 && pareja1Set2 === 0 && pareja1Set3 === 0 && 
                pareja2Set1 === 0 && pareja2Set2 === 0 && pareja2Set3 === 0) {
                console.log('VALIDACIÓN FALLIDA: No hay resultados ingresados');
                mostrarSnackbar('Debe ingresar al menos un resultado');
                return;
            }
            
            console.log('Validación de resultados: OK');
            
            // Validar que las parejas estén completas
            if (!pareja1Jugador1 || !pareja1Jugador2 || !pareja2Jugador1 || !pareja2Jugador2) {
                console.log('VALIDACIÓN FALLIDA: Parejas incompletas');
                console.log('Pareja 1:', pareja1Jugador1, pareja1Jugador2);
                console.log('Pareja 2:', pareja2Jugador1, pareja2Jugador2);
                mostrarSnackbar('Error: No se encontró información completa de las parejas');
                return;
            }
            
            console.log('Validación de parejas: OK');
            
            // Deshabilitar botón mientras se guarda
            let btnGuardar = $(this);
            btnGuardar.prop('disabled', true).text('Guardando...');
            
            console.log('Preparando datos para enviar...');
            console.log('Torneo ID:', torneoId);
            console.log('Partido ID:', matchCard.data('partido-id'));
            console.log('Sets Pareja 1:', pareja1Set1, pareja1Set2, pareja1Set3);
            console.log('Sets Pareja 2:', pareja2Set1, pareja2Set2, pareja2Set3);
            
            console.log('Iniciando llamada AJAX a guardarresultadopartidopuntuable...');
            console.log('URL:', '{{ route("guardarresultadopartidopuntuable") }}');
            
            let datosEnvio = {
                torneo_id: torneoId,
                partido_id: matchCard.data('partido-id'),
                ronda: ronda,
                cruce_id: cruceId,
                pareja_1_jugador_1: pareja1Jugador1,
                pareja_1_jugador_2: pareja1Jugador2,
                pareja_2_jugador_1: pareja2Jugador1,
                pareja_2_jugador_2: pareja2Jugador2,
                pareja_1_set_1: pareja1Set1,
                pareja_1_set_2: pareja1Set2,
                pareja_1_set_3: pareja1Set3,
                pareja_2_set_1: pareja2Set1,
                pareja_2_set_2: pareja2Set2,
                pareja_2_set_3: pareja2Set3,
                _token: '{{ csrf_token() }}'
            };
            
            console.log('Datos a enviar:', datosEnvio);
            
            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: '{{ route("guardarresultadopartidopuntuable") }}',
                data: datosEnvio,
            success: function(response) {
                console.log('=== RESPUESTA GUARDAR RESULTADO ===');
                console.log('Response completa:', response);
                console.log('Success:', response.success);
                console.log('Message:', response.message);
                console.log('===================================');
                
                btnGuardar.prop('disabled', false).text('Guardar');
                
                if (response.success) {
                    mostrarSnackbar('Resultado guardado correctamente');
                    console.log('Resultado guardado exitosamente:', response);
                    
                    // Recargar la página después de guardar
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    console.error('Error al guardar resultado:', response);
                    mostrarSnackbar(response.message || 'Error al guardar el resultado');
                }
            },
            error: function(xhr) {
                console.error('=== ERROR AL GUARDAR RESULTADO ===');
                console.error('Status:', xhr.status);
                console.error('Status Text:', xhr.statusText);
                console.error('Response Text:', xhr.responseText);
                if (xhr.responseJSON) {
                    console.error('Response JSON:', xhr.responseJSON);
                }
                console.error('================================');
                
                btnGuardar.prop('disabled', false).text('Guardar');
                let errorMsg = 'Error al guardar el resultado';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                mostrarSnackbar(errorMsg);
            }
        });
        } catch (error) {
            console.error('=== ERROR EN EL CÓDIGO DE GUARDAR ===');
            console.error('Error:', error);
            console.error('Stack:', error.stack);
            console.error('=====================================');
            mostrarSnackbar('Error inesperado: ' + error.message);
            btnGuardar.prop('disabled', false).text('Guardar');
        }
    });
    
    // Botón volver a clasificación
    $('#btn-volver-clasificacion').on('click', function() {
        let torneoId = $('#torneo_id').val();
        window.location.href = '{{ route("admintorneoamericanopartidos") }}?torneo_id=' + torneoId;
    });
    
    // Cargar resultados al cargar la página
    $(document).ready(function() {
        setTimeout(function() {
            cargarResultadosGuardados();
        }, 500);
    });
</script>

@endsection
