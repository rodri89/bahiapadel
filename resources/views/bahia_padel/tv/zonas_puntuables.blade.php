<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahia Padel - Zonas Puntuables TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #050505;
            --bg-2: #0b0b0b;
            --panel: rgba(5, 5, 5, 0.92);
            --panel-border: rgba(255, 255, 255, 0.06);
            --text: #e6e6e6;
            --muted: #9aa0a6;
            --accent: #f97316;
            --accent-2: #06b6d4;
            --accent-3: #34d399;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            font-family: "Space Grotesk", sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.03), transparent 55%),
                        radial-gradient(circle at 80% 5%, rgba(255, 255, 255, 0.02), transparent 50%),
                        linear-gradient(180deg, var(--bg-1), var(--bg-2));
        }

        /* eliminar cuadriculado de fondo */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: none;
            pointer-events: none;
        }

        .tv-shell {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .tv-topbar {
            height: 8vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5vw;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            background: rgba(5, 5, 5, 0.8);
        }

        .tv-topbar .brand {
            font-size: 2.4vh;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .tv-topbar .title {
            font-size: 3vh;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .tv-topbar .meta {
            font-size: 1.6vh;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }

        .progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            width: 0;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            z-index: 10;
        }

        .slides {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .zone-slide {
            position: absolute;
            inset: 0;
            padding: 2vh 2.5vw 2.5vh;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.6s ease;
            display: flex;
            flex-direction: column;
            gap: 2vh;
        }

        .zone-slide.active {
            opacity: 1;
            pointer-events: auto;
        }

        .zone-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .zone-title {
            display: flex;
            align-items: center;
            gap: 1.5vw;
        }

        .cat-badge {
            background: var(--accent-2);
            color: #04101c;
            font-weight: 700;
            padding: 0.6vh 1.4vw;
            border-radius: 999px;
            font-size: 2.4vh;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        .zone-name {
            font-size: 4.4vh;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tournament-name {
            font-size: 2vh;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }

        .zone-info {
            display: flex;
            gap: 1vw;
        }

        .info-pill {
            background: rgba(10, 10, 10, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.6vh 1.2vw;
            border-radius: 999px;
            font-size: 1.6vh;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: var(--muted);
        }

        .zone-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2vh;
            min-height: 0;
        }

        .matrix-card {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
            overflow: hidden;
            flex: 1;
            min-height: 0;
        }

        .matrix {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 2px solid rgba(255,255,255,0.15);
        }

        .matrix th,
        .matrix td {
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 0.8vh 0.6vw;
            text-align: center;
            vertical-align: middle;
        }

        .matrix thead th {
            background: rgba(255, 255, 255, 0.04);
            font-size: 1.4vh;
            text-transform: uppercase;
            color: var(--muted);
        }

        .corner {
            font-size: 1.4vh;
            color: var(--muted);
            letter-spacing: 0.2em;
        }

        .pair-chip {
            display: flex;
            align-items: center;
            gap: 0.6vw;
            justify-content: center;
        }

        .pair-avatars {
            display: flex;
            align-items: center;
        }

        .pair-avatars img {
            width: 6vh;
            height: 6vh;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: #0b1320;
        }

        .pair-avatars img + img {
            margin-left: -1.2vh;
        }

        .pair-names {
            display: flex;
            flex-direction: column;
            gap: 0.2vh;
            font-size: 2.2vh;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }

        .matrix tbody th {
            background: rgba(255, 255, 255, 0.03);
        }

        .score-cell {
            font-size: 2.4vh;
            font-weight: 600;
            color: var(--muted);
            transition: all 0.3s ease;
        }

        .score-cell.has-score {
            color: #34d399;
            background: rgba(52, 211, 153, 0.08);
        }

        .match-score {
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.28);
            border-radius: 8px;
            padding: 0.4vh 0.6vw;
            display: inline-block;
            white-space: nowrap;
            letter-spacing: 0.05em;
        }

        .no-score {
            opacity: 0.3;
        }

        .diagonal {
            background: rgba(255, 255, 255, 0.04);
        }

        .logo-cell {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-cell img {
            width: 4vh;
            height: 4vh;
            opacity: 0.35;
        }

        .ads {
            overflow: hidden;
            min-height: 14vh;
            background: rgba(5, 5, 5, 0.9);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .ads-track {
            display: flex;
            width: 100%;
            transition: transform 0.6s ease;
            align-items: stretch;
            gap: 0;
        }

        .ad-card {
            background: rgba(10, 10, 10, 0.9);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 1.4vh;
            color: var(--muted);
            min-height: 6vh;
            position: relative;
            overflow: hidden;
            width: calc(100% / 6);
            flex: 0 0 calc(100% / 6);
            box-sizing: border-box;
            padding: 0 0.5vw;
        }

        .ad-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 10px;
        }

        .ad-card-label {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(2, 6, 23, 0.55);
        }

        .empty-state {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3vh;
            color: var(--muted);
        }

        @media (max-width: 1200px) {
            .ads-track {
                width: 300%;
            }

            .ads-page {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="progress" id="progress"></div>
    <div class="tv-shell">
        <header class="tv-topbar">
            <div class="brand">Bahia Padel</div>
            <div class="title">Zonas en Juego</div>

        </header>

        <main class="slides">
            @if(empty($slides))
                <div class="empty-state">No hay zonas para mostrar</div>
            @else
                @foreach($slides as $index => $slide)
                    <section class="zone-slide {{ $loop->first ? 'active' : '' }}" data-index="{{ $index }}">
                        <div class="zone-header">
                            <div class="zone-title">
                                <div class="cat-badge">{{ $slide['categoria'] }}a</div>
                                <div>
                                    <div class="zone-name">Zona {{ $slide['zona'] }}</div>
                                    <div class="tournament-name">{{ $slide['torneo_nombre'] }}</div>
                                </div>
                            </div>
                            <div class="zone-info">
                                <div class="info-pill">Puntuable</div>
                                <div class="info-pill">En vivo</div>
                            </div>
                        </div>

                        <div class="zone-body">
                            <div class="matrix-card">
                                <table class="matrix">
                                    <thead>
                                        <tr>
                                            <th class="corner">Parejas</th>
                                            @foreach($slide['parejas'] as $p)
                                                <th>
                                                    <div class="pair-chip">

                                                        <div class="pair-names">
                                                            <span>{{ $p['apellido_1'] }}</span>
                                                            <span>{{ $p['apellido_2'] }}</span>
                                                        </div>
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($slide['parejas'] as $row)
                                            <tr>
                                                <th>
                                                    <div class="pair-chip">
                                                        <div class="pair-avatars">
                                                            <img src="{{ asset($row['foto_1'] ?? 'images/jugador_img.png') }}" onerror="this.src='{{ asset('images/jugador_img.png') }}'">
                                                            <img src="{{ asset($row['foto_2'] ?? 'images/jugador_img.png') }}" onerror="this.src='{{ asset('images/jugador_img.png') }}'">
                                                        </div>
                                                        
                                                        <div class="pair-names">
                                                            <span>{{ $row['apellido_1'] }}</span>
                                                            <span>{{ $row['apellido_2'] }}</span>
                                                        </div>
                                                    </div>
                                                </th>
                                                @foreach($slide['parejas'] as $col)
                                                    @if($row['key'] === $col['key'])
                                                        <td class="diagonal">
                                                            <div class="logo-cell">
                                                                <img src="{{ asset('bahiapadel/iconos/bahia_padel_ic.png') }}" onerror="this.style.display='none'">
                                                            </div>
                                                        </td>
                                                    @else
                                                        @php
                                                            $matchKey = $row['key'] < $col['key'] ? $row['key'] . '|' . $col['key'] : $col['key'] . '|' . $row['key'];
                                                            $match = $slide['matches'][$matchKey] ?? null;
                                                        @endphp
                                                        <td class="score-cell {{ ($match && $match['has_result']) ? 'has-score' : '' }}">
                                                            @if($match && $match['has_result'])
                                                                @php
                                                                    $origP1Key = $match['data']['original_p1_key'];
                                                                    $isRowOriginalP1 = ($row['key'] === $origP1Key);
                                                                    
                                                                    $styledSets = [];
                                                                    foreach($match['data']['sets'] as $set) {
                                                                        $s1 = (int)$set['p1'];
                                                                        $s2 = (int)$set['p2'];
                                                                        $styledSets[] = $isRowOriginalP1 ? "$s1-$s2" : "$s2-$s1";
                                                                    }
                                                                @endphp
                                                                <div class="match-score">
                                                                    {{ implode(' / ', $styledSets) }}
                                                                </div>
                                                            @else
                                                                <span class="no-score">-</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <aside class="ads">
                                <div class="ads-track" data-ads-track>
                                    @php
                                        // Usamos los sponsors de la DB si existen, sino fallback a los estáticos
                                        $displaySponsors = $sponsors ?? collect();
                                        $totalReal = count($displaySponsors);
                                        
                                        if ($totalReal > 0) {
                                            // Duplicamos los primeros 6 para el efecto de scroll infinito continuo
                                            $extra = $displaySponsors->take(6);
                                            $finalList = $displaySponsors->concat($extra);
                                        } else {
                                            $finalList = collect();
                                        }
                                    @endphp

                                    @foreach($finalList as $s)
                                        <div class="ad-card">
                                            <img class="ad-image" src="{{ asset('images/ads/' . ($s->imagen ?? 'no-image.png')) }}" onerror="this.src='{{ asset('images/no-image.png') }}'">
                                            @if(!($s->imagen))
                                                <span class="ad-card-label">{{ $s->nombre }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </aside>
                        </div>
                    </section>
                @endforeach
            @endif
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        (function() {
            const slides = Array.from(document.querySelectorAll('.zone-slide'));
            const progress = document.getElementById('progress');
            const countdown = document.getElementById('countdown');
            const intervalMs = {{ (int) ($intervalo ?? 20) }} * 1000;
            const adTracks = Array.from(document.querySelectorAll('[data-ads-track]'));

            // IDs de torneos para polling
            const torneoIds = @json(collect($slides ?? [])->pluck('torneo_id')->unique()->values()->all());
            let versionesConocidas = {};

            if (!slides.length) {
                return;
            }

            let index = 0;
            let remaining = intervalMs;
            let progressTimer = null;
            let slideTimer = null;
            let countdownTimer = null;
            let adsTimer = null;
            let adsIndex = 0;

            // Sistema de polling para actualizar cuando cambian los resultados
            function verificarVersionesYActualizar() {
                if (!torneoIds.length) return;

                $.get('{{ route("tvtorneosversiones") }}', { torneo_ids: torneoIds.join(',') })
                    .done(function(response) {
                        const versiones = response.versiones || {};
                        let cambio = false;

                        torneoIds.forEach(function(id) {
                            const idStr = String(id);
                            const versionActual = Number(versiones[idStr] ?? versiones[id] ?? 0);
                            const tieneVersion = Object.prototype.hasOwnProperty.call(versionesConocidas, idStr);
                            const versionConocida = Number(versionesConocidas[idStr] ?? 0);

                            if (tieneVersion && versionActual > versionConocida) {
                                console.log('Torneo ' + idStr + ' cambió:', versionConocida, '->', versionActual);
                                cambio = true;
                            }

                            versionesConocidas[idStr] = versionActual;
                        });

                        if (cambio) {
                            window.location.reload();
                        }
                    })
                    .fail(function() {
                        // Silencioso, reintentar en el próximo intervalo
                    });
            }

            // Iniciar polling inmediato y luego cada 2 segundos
            if (torneoIds.length) {
                verificarVersionesYActualizar();
                setInterval(verificarVersionesYActualizar, 2000);
            }

            function setActive(nextIndex) {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === nextIndex);
                });
                progress.style.transition = 'none';
                progress.style.width = '0%';
                requestAnimationFrame(() => {
                    progress.style.transition = `width ${intervalMs}ms linear`;
                    progress.style.width = '100%';
                });
                remaining = intervalMs;
                updateCountdown();
            }

            function updateCountdown() {
                if (countdown) {
                    const secs = Math.max(1, Math.ceil(remaining / 1000));
                    countdown.textContent = `${secs}s`;
                }
            }

            function startRotation() {
                if (slides.length <= 1) {
                    setActive(0);
                    return;
                }

                slideTimer = setInterval(() => {
                    index = (index + 1) % slides.length;
                    setActive(index);
                }, intervalMs);

                countdownTimer = setInterval(() => {
                    remaining -= 1000;
                    if (remaining < 0) {
                        remaining = intervalMs;
                    }
                    updateCountdown();
                }, 1000);
            }

            setActive(0);
            startRotation();

            if (adTracks.length) {
                const totalOriginalAds = {{ count($sponsors ?? []) }};

                const getAdStep = () => {
                    const firstTrack = adTracks[0];
                    const firstCard = firstTrack ? firstTrack.querySelector('.ad-card') : null;
                    return firstCard ? firstCard.getBoundingClientRect().width : 0;
                };

                if (totalOriginalAds > 0) {
                    adsTimer = setInterval(() => {
                        const step = getAdStep();
                        adsIndex++;
                        adTracks.forEach(track => {
                            track.style.transition = 'transform 0.6s ease';
                            track.style.transform = `translateX(-${adsIndex * step}px)`;
                        });

                        if (adsIndex >= totalOriginalAds) {
                            setTimeout(() => {
                                adsIndex = 0;
                                adTracks.forEach(track => {
                                    track.style.transition = 'none';
                                    track.style.transform = 'translateX(0)';
                                });
                            }, 300);
                        }
                    }, 3000);
                }
            }
        })();
    </script>
</body>
</html>
