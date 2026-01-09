<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Resultados TV</title>
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
            overflow: hidden; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }

        .tv-card-header {
            background-color: #1f1f1f;
            padding: 15px;
            border-bottom: 2px solid #3d3d3d;
            text-align: center;
        }

        .tv-card-header h3 {
            margin: 0;
            color: #4e73df;
            font-weight: 700;
        }

        .tv-card-body {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        
        .tv-table { 
            width: 100%; 
            border-collapse: collapse;
        }
        
        .tv-table th { 
            background-color: #2d2d2d; 
            color: #aaa; 
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .tv-table td { 
            padding: 15px 12px; 
            border-bottom: 1px solid #333;
            vertical-align: middle;
        }
        
        .tv-table tr:nth-child(even) {
            background-color: #222;
        }
        
        .player-img { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 2px solid #555; 
            margin-right: 12px; 
        }
        
        .player-info {
            display: flex;
            align-items: center;
        }
        
        .player-names {
            line-height: 1.2;
        }
        
        .player-name {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .score-cell {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            text-align: center;
            min-width: 40px; /* Reduced from fixed width to allow flex */
        }
        
        .score-container {
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .score-active {
            color: #4e73df;
        }
        
        .tv-table td {
            padding: 8px 6px; /* Reduced padding */
        }
        
        .player-img {
            width: 40px; /* Smaller image */
            height: 40px;
        }
        
        .player-name {
            font-size: 1rem; /* Smaller font */
        }
        
        .tv-header {
            font-size: 2rem;
            margin-bottom: 10px;
        }
            font-weight: 800;
            color: #888;
            text-align: center;
        }
        
        .pos-rank-1 { color: #FFD700; text-shadow: 0 0 10px rgba(255, 215, 0, 0.3); }
        .pos-rank-2 { color: #C0C0C0; }
        .pos-rank-3 { color: #CD7F32; }

        .progress-bar-top {
            position: fixed;
            top: 0;
            left: 0;
            height: 5px;
            background-color: #4e73df;
            width: 0%;
            z-index: 9999;
            transition: width 1s linear;
        }
    </style>
</head>
<body>
    <div class="progress-bar-top" id="progress-bar"></div>

    <div id="app-container">
        @if(empty($partidosPorZona))
            <div style="display:flex; justify-content:center; align-items:center; height:100vh;">
                <h1>Esperando Partidos...</h1>
            </div>
        @else
            @php
                // Fetch groups for score ordering logic
                $gruposExistentes = collect(DB::table('grupos')
                    ->where('torneo_id', $torneo->id)
                    ->orderBy('partido_id')
                    ->orderBy('id')
                    ->get());
                
                $jugadoresArray = is_array($jugadores) ? $jugadores : (is_object($jugadores) ? $jugadores->toArray() : []);
                $jugadoresKeyed = collect($jugadoresArray)->keyBy('id');
            @endphp

            @foreach($partidosPorZona as $zona => $partidos)
              <div class="zona-slide" id="zona-{{ $loop->index }}">
                 <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <h1 class="tv-header">Zona {{ $zona }} <span style="font-weight:300; font-size:1.5rem; color:#888;">| {{ $torneo->nombre ?? 'Torneo' }}</span></h1>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Partidos -->
                        <div class="col-8">
                            <div class="tv-card">
                                 <div class="tv-card-header">
                                     <h3>Partidos</h3>
                                 </div>
                                 <div class="tv-card-body">
                                     <table class="tv-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40%; text-align:left; padding-left:20px;">Pareja 1</th>
                                                <th style="width:20%;" class="text-center">Score</th>
                                                <th style="width:40%; text-align:right; padding-right:20px;">Pareja 2</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                // Function to render match row
                                                $renderRow = function($partido) use ($jugadoresKeyed, $gruposExistentes, $partidosConResultados) {
                                                    $jugador1_1 = $jugadoresKeyed[$partido['pareja_1']['jugador_1']] ?? null;
                                                    $jugador1_2 = $jugadoresKeyed[$partido['pareja_1']['jugador_2']] ?? null;
                                                    $jugador2_1 = $jugadoresKeyed[$partido['pareja_2']['jugador_1']] ?? null;
                                                    $jugador2_2 = $jugadoresKeyed[$partido['pareja_2']['jugador_2']] ?? null;
                                                    
                                                    $partidoIdKey = isset($partido['partido_id']) ? $partido['partido_id'] : null;
                                                    $resultado = ($partidoIdKey && isset($partidosConResultados[$partidoIdKey])) ? $partidosConResultados[$partidoIdKey] : null;
                                                    
                                                    $s1 = '-'; $s2 = '-';
                                                    
                                                    if ($resultado) {
                                                        $gruposPartido = $gruposExistentes->where('partido_id', $partidoIdKey)->sortBy('id')->values();
                                                        if ($gruposPartido->count() >= 2) {
                                                            $grupo1 = $gruposPartido[0];
                                                            if ($grupo1->jugador_1 == $partido['pareja_1']['jugador_1'] && 
                                                                $grupo1->jugador_2 == $partido['pareja_1']['jugador_2']) {
                                                                $s1 = $resultado->pareja_1_set_1;
                                                                $s2 = $resultado->pareja_2_set_1;
                                                            } else {
                                                                $s1 = $resultado->pareja_2_set_1;
                                                                $s2 = $resultado->pareja_1_set_1;
                                                            }
                                                        } else {
                                                            $s1 = $resultado->pareja_1_set_1;
                                                            $s2 = $resultado->pareja_2_set_1;
                                                        }
                                                    }
                                                    
                                                    return '<tr>' .
                                                        '<td>' .
                                                            '<div class="player-info">' .
                                                                ($jugador1_1 ? '<img src="' . asset($jugador1_1->foto ?? 'images/jugador_img.png') . '" class="player-img">' : '') .
                                                                '<div class="player-names">' .
                                                                    '<span class="player-name">' . ($jugador1_1->apellido ?? '') . '</span>' .
                                                                    ($jugador1_2 ? '<span class="player-name">' . ($jugador1_2->apellido ?? '') . '</span>' : '') .
                                                                '</div>' .
                                                            '</div>' .
                                                        '</td>' .
                                                        '<td class="text-center">' .
                                                            '<div class="score-container">' .
                                                                '<div class="score-cell ' . ((is_numeric($s1) && is_numeric($s2) && $s1 > $s2) ? 'score-active' : '') . '">' . $s1 . '</div>' .
                                                                '<span style="color:#555; font-size:1.5rem; margin:0 5px;">-</span>' .
                                                                '<div class="score-cell ' . ((is_numeric($s1) && is_numeric($s2) && $s2 > $s1) ? 'score-active' : '') . '">' . $s2 . '</div>' .
                                                            '</div>' .
                                                        '</td>' .
                                                        '<td style="text-align:right;">' .
                                                            '<div class="player-info" style="flex-direction:row-reverse; text-align:right;">' .
                                                                ($jugador2_1 ? '<img src="' . asset($jugador2_1->foto ?? 'images/jugador_img.png') . '" class="player-img" style="margin-right:0; margin-left:12px;">' : '') .
                                                                '<div class="player-names">' .
                                                                    '<span class="player-name">' . ($jugador2_1->apellido ?? '') . '</span>' .
                                                                    ($jugador2_2 ? '<span class="player-name">' . ($jugador2_2->apellido ?? '') . '</span>' : '') .
                                                                '</div>' .
                                                            '</div>' .
                                                        '</td>' .
                                                    '</tr>';
                                                };
                                                
                                                // If more than 8 matches, split into 2 columns
                                                // But table structure doesn't support columns easily inside table.
                                                // We can render two tables if needed.
                                                // For now, simpler compact view.
                                                foreach($partidos as $partido) {
                                                    echo $renderRow($partido);
                                                }
                                            @endphp
                                        </tbody>
                                     </table>
                                 </div>
                            </div>
                        </div>
                        
                        <!-- Posiciones -->
                        <div class="col-4">
                            <div class="tv-card">
                                <div class="tv-card-header">
                                    <h3>Posiciones</h3>
                                </div>
                                <div class="tv-card-body">
                                    <table class="tv-table">
                                       <thead>
                                           <tr>
                                               <th class="text-center">#</th>
                                               <th>Pareja</th>
                                               <th class="text-center">PG</th>
                                               <th class="text-center">Games</th>
                                           </tr>
                                       </thead>
                                       <tbody>
                                            @if(isset($posicionesPorZona[$zona]))
                                                @foreach($posicionesPorZona[$zona] as $index => $pos)
                                                    @php
                                                        $p1 = $jugadoresKeyed[$pos['jugador_1']] ?? null;
                                                        $p2 = $jugadoresKeyed[$pos['jugador_2']] ?? null;
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center"><span class="pos-rank pos-rank-{{ $index + 1 }}">{{ $index + 1 }}</span></td>
                                                        <td>
                                                            <div class="player-info">
                                                                @if($p1)<img src="{{ asset($p1->foto ?? 'images/jugador_img.png') }}" class="player-img" style="width:40px;height:40px;">@endif
                                                                <div class="player-names">
                                                                    <span class="player-name" style="font-size:0.9rem;">{{ $p1->apellido ?? '' }}</span>
                                                                    @if($p2)<span class="player-name" style="font-size:0.9rem;">{{ $p2->apellido ?? '' }}</span>@endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center"><span style="font-size:1.5rem; font-weight:bold;">{{ $pos['partidos_ganados'] ?? 0 }}</span></td>
                                                        <td class="text-center"><span style="font-size:1.5rem; font-weight:bold; color:#aaa;">{{ $pos['puntos_ganados'] ?? 0 }}</span></td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr><td colspan="4" class="text-center">No info</td></tr>
                                            @endif
                                       </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
              </div>
            @endforeach
        @endif
    </div>

    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            let slides = $('.zona-slide');
            let currentIndex = 0;
            // 20 seconds slide interval
            let slideInterval = 20000; 
            // 2 minutes full reload to get new results
            let totalRefreshTime = 120000; 
            
            function showSlide(index) {
                slides.removeClass('active');
                $(slides[index]).addClass('active');
                
                // Reset and animate progress bar
                $('#progress-bar').remove();
                $('body').append('<div class="progress-bar-top" id="progress-bar"></div>');
                
                setTimeout(function() {
                    $('#progress-bar').css('transition', 'width ' + (slideInterval/1000) + 's linear').css('width', '100%');
                }, 50);
            }
            
            if (slides.length > 0) {
                showSlide(currentIndex);
                
                if (slides.length > 1) {
                    setInterval(function() {
                        currentIndex = (currentIndex + 1) % slides.length;
                        showSlide(currentIndex);
                    }, slideInterval);
                } else {
                     // Single slide, just animate bar repeatedly to show "alive"
                     setInterval(function() {
                         showSlide(currentIndex);
                     }, slideInterval);
                }
            }
            
            // Reload page periodically
            setTimeout(function() {
                window.location.reload();
            }, totalRefreshTime);
        });
    </script>
</body>
</html>
