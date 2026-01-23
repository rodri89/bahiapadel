<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Sorteo TV</title>
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/dark-mode.css') }}" rel="stylesheet">
    <style>
        body { 
            overflow-y: auto; 
            font-family: 'Nunito', sans-serif; 
            background-color: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .tv-header { 
            font-size: 2.5rem; 
            margin-bottom: 30px; 
            text-align: center; 
            color: #fff; 
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .grupos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            max-width: 100%;
        }
        
        .tv-card { 
            background-color: #252525; 
            border: 1px solid #3d3d3d; 
            border-radius: 15px; 
            margin-bottom: 20px; 
            min-height: 300px;
            max-height: calc(100vh - 200px);
            overflow-y: auto; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }

        .tv-card-header {
            background-color: #1f1f1f;
            padding: 20px;
            border-bottom: 2px solid #3d3d3d;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .tv-card-header h3 {
            margin: 0;
            color: #4e73df;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .grupo-container {
            padding: 20px;
            flex: 1;
        }
        
        .pareja-item {
            background-color: #2d2d2d;
            border: 1px solid #3d3d3d;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .pareja-item:hover {
            background-color: #353535;
            transform: translateX(5px);
        }
        
        .pareja-item.animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        .player-img { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 2px solid #4e73df;
            margin-right: 15px;
        }
        
        .player-info {
            flex: 1;
        }
        
        .player-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 5px;
        }
        
        .player-plus {
            font-size: 1.5rem;
            color: #4e73df;
            margin: 0 15px;
            font-weight: bold;
        }
        
        .grupo-vacio {
            text-align: center;
            padding: 40px;
            color: #888;
            font-size: 1.2rem;
        }
        
        .btn-navegar {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #2d2d2d;
            color: #fff;
            border: 1px solid #3d3d3d;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
            z-index: 1000;
        }
        
        .btn-navegar:hover {
            background-color: #353535;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body class="dark-mode">
    <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
    
    <a href="{{ route('tvtorneoamericano') }}?torneo_id={{ $torneo->id ?? 0 }}" class="btn-navegar">></a>
    
    <div class="tv-header">
        {{ $torneo->nombre ?? 'Sorteo Torneo' }}
    </div>
    
    <div class="grupos-container" id="grupos-container">
        @php
            $zonas = array_keys($gruposPorZona ?? []);
            sort($zonas); // Ordenar zonas alfabéticamente
        @endphp
        
        @if(!empty($gruposPorZona) && count($zonas) > 0)
            @foreach($zonas as $zona)
                @php
                    $grupos = $gruposPorZona[$zona] ?? [];
                @endphp
                <div class="tv-card" data-zona="{{ $zona }}">
                    <div class="tv-card-header">
                        <h3>Grupo {{ $zona }}</h3>
                    </div>
                    <div class="grupo-container" id="grupo-container-{{ $zona }}">
                        @if(count($grupos) > 0)
                            @foreach($grupos as $grupo)
                                @php
                                    $jugador1 = $jugadores[$grupo->jugador_1] ?? null;
                                    $jugador2 = $jugadores[$grupo->jugador_2] ?? null;
                                @endphp
                                @if($jugador1 && $jugador2)
                                    <div class="pareja-item">
                                        <img src="{{ asset($jugador1->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador1->nombre }} {{ $jugador1->apellido }}" 
                                             class="player-img">
                                        <div class="player-info">
                                            <div class="player-name">{{ $jugador1->nombre }} {{ $jugador1->apellido }}</div>
                                        </div>
                                        <span class="player-plus">+</span>
                                        <img src="{{ asset($jugador2->foto ?? 'images/jugador_img.png') }}" 
                                             alt="{{ $jugador2->nombre }} {{ $jugador2->apellido }}" 
                                             class="player-img">
                                        <div class="player-info">
                                            <div class="player-name">{{ $jugador2->nombre }} {{ $jugador2->apellido }}</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="grupo-vacio">
                                Esperando parejas...
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3>Sorteo</h3>
                </div>
                <div class="grupo-container">
                    <div class="grupo-vacio">
                        No hay grupos configurados aún
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            const torneoId = {{ $torneo->id ?? 0 }};
            let jugadores = @json($jugadores ?? []);
            let intervaloActualizacion = null;
            
            // Actualizar grupos cada 3 segundos
            function actualizarGrupos() {
                if (!torneoId) return;
                
                $.ajax({
                    type: 'POST',
                    url: '{{ route("tvtorneoamericanosorteoactualizar") }}',
                    data: {
                        torneo_id: torneoId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.gruposPorZona) {
                            // Actualizar el objeto jugadores con la respuesta del servidor
                            if (response.jugadores) {
                                jugadores = Object.assign({}, jugadores, response.jugadores);
                            }
                            
                            const container = $('#grupos-container');
                            let html = '';
                            
                            // Ordenar zonas alfabéticamente
                            const zonas = Object.keys(response.gruposPorZona).sort();
                            
                            zonas.forEach(function(zona) {
                                const grupos = response.gruposPorZona[zona];
                                
                                html += `
                                    <div class="tv-card" data-zona="${zona}">
                                        <div class="tv-card-header">
                                            <h3>Grupo ${zona}</h3>
                                        </div>
                                        <div class="grupo-container" id="grupo-container-${zona}">
                                `;
                                
                                if (grupos.length > 0) {
                                    grupos.forEach(function(grupo) {
                                        const jugador1 = jugadores[grupo.jugador_1];
                                        const jugador2 = jugadores[grupo.jugador_2];
                                        
                                        if (jugador1 && jugador2) {
                                            const foto1 = jugador1.foto ? (jugador1.foto.startsWith('/') ? jugador1.foto : '/' + jugador1.foto) : '/images/jugador_img.png';
                                            const foto2 = jugador2.foto ? (jugador2.foto.startsWith('/') ? jugador2.foto : '/' + jugador2.foto) : '/images/jugador_img.png';
                                            
                                            html += `
                                                <div class="pareja-item animate-fade-in">
                                                    <img src="${foto1}" 
                                                         alt="${jugador1.nombre} ${jugador1.apellido}" 
                                                         class="player-img">
                                                    <div class="player-info">
                                                        <div class="player-name">${jugador1.nombre} ${jugador1.apellido}</div>
                                                    </div>
                                                    <span class="player-plus">+</span>
                                                    <img src="${foto2}" 
                                                         alt="${jugador2.nombre} ${jugador2.apellido}" 
                                                         class="player-img">
                                                    <div class="player-info">
                                                        <div class="player-name">${jugador2.nombre} ${jugador2.apellido}</div>
                                                    </div>
                                                </div>
                                            `;
                                        }
                                    });
                                } else {
                                    html += '<div class="grupo-vacio">Esperando parejas...</div>';
                                }
                                
                                html += `
                                        </div>
                                    </div>
                                `;
                            });
                            
                            if (zonas.length === 0) {
                                html = `
                                    <div class="tv-card">
                                        <div class="tv-card-header">
                                            <h3>Sorteo</h3>
                                        </div>
                                        <div class="grupo-container">
                                            <div class="grupo-vacio">No hay grupos configurados aún</div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            container.html(html);
                            
                            // NO detener la actualización automática - siempre seguir actualizando
                            // Esto permite que se reflejen cambios si se agregan más parejas o se modifican
                        }
                    },
                    error: function() {
                        // Silencioso, no mostrar error si falla
                    }
                });
            }
            
            // Actualizar cada 3 segundos
            intervaloActualizacion = setInterval(actualizarGrupos, 3000);
            // Primera actualización después de 3 segundos
            setTimeout(actualizarGrupos, 3000);
        });
    </script>
</body>
</html>

