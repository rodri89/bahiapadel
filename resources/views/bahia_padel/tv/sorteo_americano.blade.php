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
            overflow: hidden; 
            font-family: 'Nunito', sans-serif; 
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .zona-slide { 
            display: none; 
            height: 100vh; 
            padding: 20px; 
            box-sizing: border-box;
        }
        .zona-slide.active { 
            display: block; 
            animation: fadeIn 0.8s; 
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .tv-header { 
            font-size: 2.5rem; 
            margin-bottom: 20px; 
            text-align: center; 
            color: #fff; 
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .tv-card { 
            background-color: #252525; 
            border: 1px solid #3d3d3d; 
            border-radius: 15px; 
            margin-bottom: 20px; 
            height: calc(100vh - 120px); 
            overflow-y: auto; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
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
            font-size: 2rem;
        }
        
        .grupo-container {
            padding: 20px;
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
    </style>
</head>
<body class="dark-mode">
    <input type="hidden" id="torneo_id" value="{{ $torneo->id ?? 0 }}">
    
    <div class="tv-header">
        {{ $torneo->nombre ?? 'Sorteo Torneo' }}
    </div>
    
    @php
        $zonas = array_keys($gruposPorZona ?? []);
        $zonaIndex = 0;
    @endphp
    
    @foreach($gruposPorZona ?? [] as $zona => $grupos)
        <div class="zona-slide {{ $zonaIndex === 0 ? 'active' : '' }}" data-zona="{{ $zona }}">
            <div class="tv-card">
                <div class="tv-card-header">
                    <h3>Grupo {{ $zona }}</h3>
                </div>
                <div class="grupo-container">
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
        </div>
        @php $zonaIndex++; @endphp
    @endforeach
    
    @if(empty($gruposPorZona) || count($zonas) == 0)
        <div class="zona-slide active">
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
        </div>
    @endif

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            const torneoId = {{ $torneo->id ?? 0 }};
            const slides = $('.zona-slide');
            let currentIndex = 0;
            
            // Función para cambiar de zona
            function showSlide(index) {
                slides.removeClass('active');
                if (slides.length > 0) {
                    $(slides[index]).addClass('active');
                }
            }
            
            // Cambiar de zona cada 10 segundos
            if (slides.length > 1) {
                setInterval(function() {
                    currentIndex = (currentIndex + 1) % slides.length;
                    showSlide(currentIndex);
                }, 10000);
            }
            
            // Actualizar grupos cada 2 segundos
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
                            // Actualizar cada zona
                            Object.keys(response.gruposPorZona).forEach(function(zona) {
                                const slide = $(`.zona-slide[data-zona="${zona}"]`);
                                if (slide.length) {
                                    const container = slide.find('.grupo-container');
                                    const grupos = response.gruposPorZona[zona];
                                    const jugadores = @json($jugadores ?? []);
                                    
                                    if (grupos.length > 0) {
                                        let html = '';
                                        grupos.forEach(function(grupo) {
                                            const jugador1 = jugadores[grupo.jugador_1];
                                            const jugador2 = jugadores[grupo.jugador_2];
                                            
                                            if (jugador1 && jugador2) {
                                                html += `
                                                    <div class="pareja-item animate-fade-in">
                                                        <img src="${jugador1.foto || '/images/jugador_img.png'}" 
                                                             alt="${jugador1.nombre} ${jugador1.apellido}" 
                                                             class="player-img">
                                                        <div class="player-info">
                                                            <div class="player-name">${jugador1.nombre} ${jugador1.apellido}</div>
                                                        </div>
                                                        <span class="player-plus">+</span>
                                                        <img src="${jugador2.foto || '/images/jugador_img.png'}" 
                                                             alt="${jugador2.nombre} ${jugador2.apellido}" 
                                                             class="player-img">
                                                        <div class="player-info">
                                                            <div class="player-name">${jugador2.nombre} ${jugador2.apellido}</div>
                                                        </div>
                                                    </div>
                                                `;
                                            }
                                        });
                                        container.html(html);
                                    } else {
                                        container.html('<div class="grupo-vacio">Esperando parejas...</div>');
                                    }
                                }
                            });
                        }
                    },
                    error: function() {
                        // Silencioso, no mostrar error si falla
                    }
                });
            }
            
            // Actualizar cada 2 segundos
            setInterval(actualizarGrupos, 2000);
            // Primera actualización después de 2 segundos
            setTimeout(actualizarGrupos, 2000);
        });
    </script>
</body>
</html>

