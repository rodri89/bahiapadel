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
use Intervention\Image\Facades\Image;

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
        $this->middleware('auth', ['except' => ['buscarJugadoresPublico', 'subirFotoJugadorPublico', 'tvTorneoAmericano']]);
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
        try {
            $id = $request->id_torneo;
            if($id == 0){
                $torneo = new Torneo;            
                $torneo->activo = 1;
            } else {
                $torneo = Torneo::find($id);
                if (!$torneo) {
                    return response()->json([
                        'torneo' => null,
                        'error' => 'Torneo no encontrado'
                    ], 404);
                }
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
            
            $torneo->es_torneo_individual = $request->tipo_torneo ?? 2;        
            $torneo->categoria = $request->categoria ?? 1;
            $torneo->imagen = '';
            
            // Guardar tipo de torneo (americano, puntuable, suma)
            if($request->tipo_torneo_formato != null) {
                $torneo->tipo_torneo_formato = $request->tipo_torneo_formato;
            } else {
                $torneo->tipo_torneo_formato = 'puntuable'; // Por defecto
            }

            $torneo->save();

            return response()->json(array('torneo'=>$torneo));
        } catch (\Exception $e) {
            \Log::error('Error al registrar torneo: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'torneo' => null,
                'error' => 'Error al guardar el torneo: ' . $e->getMessage()
            ], 500);
        }
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
            // Determinar el tipo de torneo (por defecto puntuable si no existe)
            $tipoTorneo = isset($torneo->tipo_torneo_formato) ? $torneo->tipo_torneo_formato : 'puntuable';
            
            // Obtener grupos excluyendo los de eliminatoria (zonas: 'cuartos final', 'semifinal', 'final')
            // Los grupos de eliminatoria son solo para los cruces y no deben mostrarse en la configuración inicial
            // Para torneos americanos, verificar si hay grupos con partido_id (torneo comenzado)
            if ($tipoTorneo == 'americano') {
                // Verificar si hay grupos con partido_id (torneo ya comenzado)
                $gruposConPartidos = DB::table('grupos')
                                ->where('grupos.torneo_id', $request->torneo_id)
                                ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                ->whereNotNull('grupos.partido_id')
                                ->whereNotNull('grupos.jugador_1')
                                ->whereNotNull('grupos.jugador_2')
                                ->count();
                
                // Si hay grupos con partido_id, el torneo ya comenzó, redirigir a partidos
                if ($gruposConPartidos > 0) {
                    return redirect()->route('admintorneoamericanopartidos', ['torneo_id' => $request->torneo_id]);
                }
                
                // Si no hay grupos con partido_id, obtener grupos iniciales (borrador) para permitir seguir editando
                $grupos = DB::table('grupos')
                                ->where('grupos.torneo_id', $request->torneo_id)
                                ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                ->whereNull('grupos.partido_id')
                                ->whereNotNull('grupos.jugador_1')
                                ->whereNotNull('grupos.jugador_2')
                                ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                                ->orderBy('grupos.zona')
                                ->orderBy('grupos.jugador_1')
                                ->orderBy('grupos.jugador_2')
                                ->orderBy('grupos.id')
                                ->get();
            } else {
                // Para otros tipos de torneo, usar la lógica original
                // Incluir grupos con jugador_1 = 0 o jugador_2 = 0 (partidos "libres" para zonas de 4 parejas)
                $grupos = DB::table('grupos')
                    ->where('grupos.torneo_id', $request->torneo_id)
                    ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                    ->where(function($query) {
                        $query->where(function($q) {
                            $q->whereNotNull('grupos.jugador_1')
                              ->whereNotNull('grupos.jugador_2');
                        })->orWhere(function($q) {
                            // Incluir grupos con jugador_1 = 0 o jugador_2 = 0
                            $q->where(function($q2) {
                                $q2->where('grupos.jugador_1', 0)
                                   ->whereNotNull('grupos.jugador_2');
                            })->orWhere(function($q2) {
                                $q2->whereNotNull('grupos.jugador_1')
                                   ->where('grupos.jugador_2', 0);
                            });
                        });
                    })
                    ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 
                            'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                    ->orderBy('grupos.id')  // Solo ordenar por ID para mantener el orden de creación
                    ->get();
               
                // Filtrar para obtener solo parejas únicas por zona
                $parejasUnicas = [];
                $gruposFiltrados = collect($grupos)->filter(function($grupo) use (&$parejasUnicas) {
                    $key = $grupo->zona . '_' . min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                    if (!isset($parejasUnicas[$key])) {
                        $parejasUnicas[$key] = true;
                        return true;
                    }
                    return false;
                })->values();
                
                // $grupos = $gruposFiltrados;
            }
            
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
                //return $grupos;
                // Puntuable (por defecto)
                return View('bahia_padel.admin.torneo.armar_torneo_v2')
                            ->with('jugadores', $jugadores)
                            ->with('torneo', $torneo)
                            ->with('grupos', $grupos);
            }
    }

    function adminCrearJugador(Request $request) {
        try {
            $jugador = new Jugadore;
            $jugador->activo = 1;                
            $jugador->nombre = $request->nombre;
            $jugador->apellido = $request->apellido;
            $jugador->telefono = $request->telefono ?? 0;
            $jugador->posicion = 0;
            $jugador->foto = 'images/jugador_img.png';
            
            // Manejar subida de foto
            if ($request->hasFile('foto')) {
                try {
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
                } catch (\Exception $e) {
                    // Si falla la imagen, usar la imagen por defecto
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                    $jugador->foto = 'images/jugador_img.png';
                }
            }
            
            $jugador->save();

            return response()->json([
                'success' => true,
                'jugador' => $jugador
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear jugador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el jugador: ' . $e->getMessage()
            ], 500);
        }
    }

    function getJugadores(Request $request) {
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->orderBy('jugadores.nombre')
                        ->orderBy('jugadores.apellido')
                        ->get();
        
        return response()->json(['jugadores' => $jugadores]);
    }

    // Métodos públicos para subir fotos (sin autenticación)
    function mostrarSubirFotoJugador(Request $request) {
        $jugadorId = $request->query('jugador_id');
        return view('bahia_padel.mobile.subir_foto_jugador', ['jugador_id_seleccionado' => $jugadorId]);
    }
    
    function buscarJugadoresPublico(Request $request) {
        $busqueda = $request->input('busqueda', '');
        
        $query = DB::table('jugadores')
                   ->where('jugadores.activo', 1);
        
        // Si hay búsqueda con al menos 2 caracteres, filtrar
        if (!empty($busqueda) && strlen(trim($busqueda)) >= 2) {
            $busqueda = trim($busqueda);
            $query->where(function($q) use ($busqueda) {
                $q->where('jugadores.nombre', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere('jugadores.apellido', 'LIKE', '%' . $busqueda . '%')
                  ->orWhere(DB::raw("CONCAT(jugadores.nombre, ' ', jugadores.apellido)"), 'LIKE', '%' . $busqueda . '%');
            });
        }
        
        $jugadores = $query->orderBy('jugadores.nombre')
                          ->orderBy('jugadores.apellido')
                          ->limit(100) // Limitar resultados para mejor rendimiento
                          ->get();
        
        return response()->json(['jugadores' => $jugadores]);
    }

    function subirFotoJugadorPublico(Request $request) {
        try {
            $id = $request->input('id');
            
            if (!$id) {
                return redirect()->route('subir.foto.jugador')->with('error', 'ID de jugador requerido');
            }
            
            $jugador = Jugadore::find($id);
            if (!$jugador) {
                return redirect()->route('subir.foto.jugador')->with('error', 'Jugador no encontrado');
            }
            
            // Manejar subida de foto
            if ($request->hasFile('foto')) {
                try {
                    $image = $request->file('foto');
                    
                    // Validar que sea una imagen
                    if (!$image->isValid()) {
                        return redirect()->route('subir.foto.jugador')->with('error', 'El archivo enviado no es válido');
                    }
                    
                    // Sanitizar nombre del archivo
                    $originalName = $image->getClientOriginalName();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $name = time() . '_' . $safeName;
                    $path = 'images/jugadores/' . $name;
                    
                    // Obtener rutas usando base_path para mayor compatibilidad
                    $directory = public_path('images/jugadores');
                    $imgPath = public_path($path);
                    
                    // Logging para diagnóstico
                    \Log::info('=== INICIO SUBIDA FOTO ===');
                    \Log::info('Nombre archivo original: ' . $originalName);
                    \Log::info('Nombre archivo seguro: ' . $safeName);
                    \Log::info('Ruta relativa: ' . $path);
                    \Log::info('Directorio destino: ' . $directory);
                    \Log::info('Ruta completa archivo: ' . $imgPath);
                    \Log::info('base_path(): ' . base_path());
                    \Log::info('public_path(): ' . public_path());
                    
                    // Crear directorio si no existe
                    if (!file_exists($directory)) {
                        \Log::info('Directorio no existe, intentando crear...');
                        if (!mkdir($directory, 0755, true)) {
                            \Log::error('ERROR: No se pudo crear el directorio: ' . $directory);
                            throw new \Exception('No se pudo crear el directorio de imágenes: ' . $directory);
                        }
                        \Log::info('Directorio creado exitosamente');
                    } else {
                        \Log::info('Directorio ya existe');
                    }
                    
                    // Verificar permisos de escritura
                    if (!is_writable($directory)) {
                        \Log::error('ERROR: El directorio no tiene permisos de escritura: ' . $directory);
                        \Log::error('Permisos actuales: ' . substr(sprintf('%o', fileperms($directory)), -4));
                        throw new \Exception('El directorio no tiene permisos de escritura: ' . $directory);
                    }
                    \Log::info('Directorio tiene permisos de escritura');
                    
                    // Cargar imagen con Intervention Image
                    try {
                        $img = Image::make($image->getRealPath());
                    } catch (\Exception $e) {
                        throw new \Exception('No se pudo procesar la imagen. Verifica que sea un formato válido (JPG, PNG, GIF).');
                    }
                    
                    // Tamaño máximo en bytes (5MB)
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    
                    // Redimensionar si es muy grande (mantener aspecto, máximo 1920px)
                    $maxDimension = 1920;
                    if ($img->width() > $maxDimension || $img->height() > $maxDimension) {
                        $img->resize($maxDimension, $maxDimension, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    
                    // Guardar imagen con compresión progresiva si es necesario
                    $quality = 90;
                    $maxAttempts = 8;
                    $attempt = 0;
                    $fileSize = 0;
                    
                    do {
                        try {
                            $img->save($imgPath, $quality);
                            
                            if (!file_exists($imgPath)) {
                                throw new \Exception('No se pudo guardar el archivo en: ' . $imgPath);
                            }
                            
                            $fileSize = filesize($imgPath);
                            
                            // Si el archivo es menor a 5MB, salir del bucle
                            if ($fileSize <= $maxSize) {
                                break;
                            }
                            
                            // Reducir calidad
                            $quality -= 10;
                            $attempt++;
                            
                            // Si la calidad es muy baja y aún es grande, reducir tamaño
                            if ($quality < 60 && $fileSize > $maxSize && $attempt < $maxAttempts) {
                                $currentWidth = $img->width();
                                $currentHeight = $img->height();
                                $newWidth = intval($currentWidth * 0.85);
                                $newHeight = intval($currentHeight * 0.85);
                                $img->resize($newWidth, $newHeight, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                                $quality = 75; // Resetear calidad después de redimensionar
                            }
                            
                        } catch (\Exception $saveError) {
                            if ($attempt >= $maxAttempts - 1) {
                                throw $saveError;
                            }
                            $quality -= 10;
                            $attempt++;
                        }
                        
                    } while ($fileSize > $maxSize && $quality >= 40 && $attempt < $maxAttempts);
                    
                    // Verificar que el archivo se guardó correctamente
                    if (!file_exists($imgPath)) {
                        \Log::error('ERROR: El archivo no existe después de guardar: ' . $imgPath);
                        throw new \Exception('El archivo no se guardó correctamente en: ' . $imgPath);
                    }
                    
                    \Log::info('Archivo guardado exitosamente en: ' . $imgPath);
                    \Log::info('Tamaño del archivo: ' . filesize($imgPath) . ' bytes');
                    
                    // Verificar que el archivo es accesible vía HTTP
                    $urlPublica = asset($path);
                    \Log::info('URL pública generada: ' . $urlPublica);
                    \Log::info('APP_URL desde env: ' . env('APP_URL'));
                    
                    $jugador->foto = $path;
                    \Log::info('Ruta guardada en BD: ' . $jugador->foto);
                    
                    // Verificar archivo después de guardar en BD
                    $filePathVerificacion = public_path($jugador->foto);
                    \Log::info('Verificación post-BD - Ruta completa: ' . $filePathVerificacion);
                    \Log::info('Verificación post-BD - Existe: ' . (file_exists($filePathVerificacion) ? 'SÍ' : 'NO'));
                    
                    // Listar archivos en el directorio para debugging
                    $archivosEnDirectorio = scandir($directory);
                    \Log::info('Archivos en directorio ' . $directory . ': ' . json_encode($archivosEnDirectorio));
                } catch (\Exception $e) {
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                    \Log::error('Stack: ' . $e->getTraceAsString());
                    return redirect()->route('subir.foto.jugador')->with('error', 'Error al procesar la imagen: ' . $e->getMessage());
                }
            } else {
                return redirect()->route('subir.foto.jugador')->with('error', 'No se envió ninguna imagen. Verifica que hayas seleccionado un archivo.');
            }
            
            $jugador->save();
            
            // Verificar que el archivo existe antes de obtener su tamaño
            $filePath = public_path($jugador->foto);
            $fileSizeMB = 0;
            \Log::info('=== VERIFICACIÓN FINAL ===');
            \Log::info('Ruta en BD: ' . $jugador->foto);
            \Log::info('Ruta completa del archivo: ' . $filePath);
            \Log::info('Archivo existe: ' . (file_exists($filePath) ? 'SÍ' : 'NO'));
            
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);
                \Log::info('Foto guardada exitosamente en: ' . $filePath . ' (Tamaño: ' . $fileSizeMB . ' MB)');
                \Log::info('URL pública generada: ' . asset($jugador->foto));
                \Log::info('URL completa esperada: ' . url($jugador->foto));
            } else {
                \Log::error('ERROR: El archivo no existe después de guardar: ' . $filePath);
                \Log::error('Intentando buscar en otras ubicaciones...');
                
                // Buscar el archivo en posibles ubicaciones alternativas
                $nombreArchivo = basename($jugador->foto);
                $posiblesRutas = [
                    base_path('public/images/jugadores/' . $nombreArchivo),
                    storage_path('app/public/images/jugadores/' . $nombreArchivo),
                    public_path('images/jugadores/' . $nombreArchivo),
                ];
                
                foreach ($posiblesRutas as $rutaAlternativa) {
                    if (file_exists($rutaAlternativa)) {
                        \Log::error('ARCHIVO ENCONTRADO EN: ' . $rutaAlternativa);
                    } else {
                        \Log::error('No encontrado en: ' . $rutaAlternativa);
                    }
                }
            }
            
            \Log::info('=== FIN VERIFICACIÓN ===');
            
            $mensaje = 'Foto actualizada correctamente';
            if ($fileSizeMB > 0) {
                $mensaje .= ' (tamaño final: ' . $fileSizeMB . ' MB)';
            }
            
            // Redirigir con el ID del jugador para mantener la selección
            return redirect()->route('subir.foto.jugador', ['jugador_id' => $jugador->id])->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->route('subir.foto.jugador')->with('error', 'Error al subir la foto: ' . $e->getMessage());
        }
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
        try {
            $id = $request->id;
            
            $jugador = Jugadore::find($id);
            if (!$jugador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jugador no encontrado'
                ], 404);
            }
            
            $jugador->nombre = $request->nombre;
            $jugador->apellido = $request->apellido;
            $jugador->telefono = $request->telefono ?? 0;
            
            // Manejar subida de foto solo si se envía una nueva
            if ($request->hasFile('foto')) {
                try {
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
                } catch (\Exception $e) {
                    // Si falla la imagen, mantener la actual
                    \Log::error('Error al procesar imagen: ' . $e->getMessage());
                }
            }
            
            $jugador->save();
            
            return response()->json([
                'success' => true,
                'jugador' => $jugador
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al editar jugador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el jugador: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarFechaAdminTorneo(Request $request) {
        $torneoId = $request->torneo_id;
        $zona = $request->zona;
        $tieneCuatroParejas = $request->input('tiene_cuatro_parejas', 0) == 1;
        $tieneCuatroParejasEliminatoria = $request->input('tiene_cuatro_parejas_eliminatoria', 0) == 1;
        
        \Log::info('=== guardarFechaAdminTorneo ===');
        \Log::info('Torneo ID: ' . $torneoId . ', Zona: ' . $zona . ', 4 parejas eliminatoria: ' . ($tieneCuatroParejasEliminatoria ? 'Sí' : 'No'));

        // Eliminar grupos de la zona actual (incluyendo "ganador X" y "perdedor X")
        $grupos = \App\Grupo::where('torneo_id', $torneoId)
            ->where(function($query) use ($zona) {
                $query->where('zona', $zona)
                      ->orWhere('zona', 'ganador ' . $zona)
                      ->orWhere('zona', 'perdedor ' . $zona);
            })
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
                ->where(function($query) use ($zona) {
                    $query->where('zona', $zona)
                          ->orWhere('zona', 'ganador ' . $zona)
                          ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->delete();
        }
        
        // Función helper para obtener fecha/horario con valores por defecto
        $getFecha = function($value) {
            return !empty($value) && $value !== 'null' && $value !== null ? $value : '2000-01-01';
        };
        $getHorario = function($value) {
            return !empty($value) && $value !== 'null' && $value !== null ? $value : '00:00';
        };
        
        if ($tieneCuatroParejasEliminatoria && $tieneCuatroParejas && $request->pareja_4_idJugadorArriba && $request->pareja_4_idJugadorAbajo) {
            // ESTRUCTURA CON 4 PAREJAS ELIMINATORIA: Partido A, Perdedor, Ganador, Partido B
            // Partido A: Pareja 1 vs Pareja 2
            $partidoA = $this->crearPartido();
            $grupoA_P1 = new Grupo;
            $grupoA_P1->torneo_id = $torneoId;
            $grupoA_P1->zona = $zona;
            $grupoA_P1->fecha = $getFecha($request->input('pareja_1_partido_1_dia'));
            $grupoA_P1->horario = $getHorario($request->input('pareja_1_partido_1_horario'));
            $grupoA_P1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA_P1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA_P1->partido_id = $partidoA->id;
            $grupoA_P1->save();
            
            $grupoA_P2 = new Grupo;
            $grupoA_P2->torneo_id = $torneoId;
            $grupoA_P2->zona = $zona;
            $grupoA_P2->fecha = $getFecha($request->input('pareja_2_partido_1_dia'));
            $grupoA_P2->horario = $getHorario($request->input('pareja_2_partido_1_horario'));
            $grupoA_P2->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA_P2->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA_P2->partido_id = $partidoA->id;
            $grupoA_P2->save();
            
            // Partido B: Pareja 3 vs Pareja 4
            $partidoB = $this->crearPartido();
            $grupoB_P3 = new Grupo;
            $grupoB_P3->torneo_id = $torneoId;
            $grupoB_P3->zona = $zona;
            $grupoB_P3->fecha = $getFecha($request->input('pareja_3_partido_2_dia'));
            $grupoB_P3->horario = $getHorario($request->input('pareja_3_partido_2_horario'));
            $grupoB_P3->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoB_P3->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoB_P3->partido_id = $partidoB->id;
            $grupoB_P3->save();
            
            $grupoB_P4 = new Grupo;
            $grupoB_P4->torneo_id = $torneoId;
            $grupoB_P4->zona = $zona;
            $grupoB_P4->fecha = $getFecha($request->input('pareja_4_partido_2_dia'));
            $grupoB_P4->horario = $getHorario($request->input('pareja_4_partido_2_horario'));
            $grupoB_P4->jugador_1 = $request->pareja_4_idJugadorArriba;
            $grupoB_P4->jugador_2 = $request->pareja_4_idJugadorAbajo;
            $grupoB_P4->partido_id = $partidoB->id;
            $grupoB_P4->save();
            
            // Ganador: Ganador Partido A vs Ganador Partido B (jugadores aún no se conocen)
            // En el formato eliminatoria, la pareja 4 tiene "ganador" en celda 10 (partido 1)
            $partidoGanador = $this->crearPartido();
            $grupoGanador = new Grupo;
            $grupoGanador->torneo_id = $torneoId;
            $grupoGanador->zona = 'ganador ' . $zona; // Identificar como partido de ganadores
            // Buscar la fecha/horario de "ganador" - puede venir de varias celdas sincronizadas
            $fechaGanador = $getFecha($request->input('pareja_4_partido_1_dia')); // Celda 10 (ganador pareja 4)
            if (empty($fechaGanador) || $fechaGanador === '2000-01-01') {
                $fechaGanador = $getFecha($request->input('pareja_2_partido_2_dia')); // Celda 6 (ganador pareja 2)
            }
            if (empty($fechaGanador) || $fechaGanador === '2000-01-01') {
                $fechaGanador = $getFecha($request->input('pareja_1_partido_2_dia')); // Celda 10 (ganador pareja 1)
            }
            $grupoGanador->fecha = $fechaGanador;
            $horarioGanador = $getHorario($request->input('pareja_4_partido_1_horario'));
            if (empty($horarioGanador) || $horarioGanador === '00:00') {
                $horarioGanador = $getHorario($request->input('pareja_2_partido_2_horario'));
            }
            if (empty($horarioGanador) || $horarioGanador === '00:00') {
                $horarioGanador = $getHorario($request->input('pareja_1_partido_2_horario'));
            }
            $grupoGanador->horario = $horarioGanador;
            $grupoGanador->jugador_1 = 0; // Se asignará después según resultados
            $grupoGanador->jugador_2 = 0;
            $grupoGanador->partido_id = $partidoGanador->id;
            $grupoGanador->save();
            
            // Perdedor: Perdedor Partido A vs Perdedor Partido B (jugadores aún no se conocen)
            // En el formato eliminatoria, la pareja 3 tiene "perdedor" en celda 7 (partido 1)
            $partidoPerdedor = $this->crearPartido();
            $grupoPerdedor = new Grupo;
            $grupoPerdedor->torneo_id = $torneoId;
            $grupoPerdedor->zona = 'perdedor ' . $zona; // Identificar como partido de perdedores
            // Buscar la fecha/horario de "perdedor" - puede venir de varias celdas sincronizadas
            $fechaPerdedor = $getFecha($request->input('pareja_3_partido_1_dia')); // Celda 7 (perdedor pareja 3)
            if (empty($fechaPerdedor) || $fechaPerdedor === '2000-01-01') {
                $fechaPerdedor = $getFecha($request->input('pareja_1_partido_2_dia')); // Celda 3 (perdedor pareja 1)
            }
            if (empty($fechaPerdedor) || $fechaPerdedor === '2000-01-01') {
                $fechaPerdedor = $getFecha($request->input('pareja_2_partido_2_dia')); // Celda 11 (perdedor pareja 2)
            }
            if (empty($fechaPerdedor) || $fechaPerdedor === '2000-01-01') {
                $fechaPerdedor = $getFecha($request->input('pareja_4_partido_2_dia')); // Celda 11 (perdedor pareja 4)
            }
            $grupoPerdedor->fecha = $fechaPerdedor;
            $horarioPerdedor = $getHorario($request->input('pareja_3_partido_1_horario'));
            if (empty($horarioPerdedor) || $horarioPerdedor === '00:00') {
                $horarioPerdedor = $getHorario($request->input('pareja_1_partido_2_horario'));
            }
            if (empty($horarioPerdedor) || $horarioPerdedor === '00:00') {
                $horarioPerdedor = $getHorario($request->input('pareja_2_partido_2_horario'));
            }
            if (empty($horarioPerdedor) || $horarioPerdedor === '00:00') {
                $horarioPerdedor = $getHorario($request->input('pareja_4_partido_2_horario'));
            }
            $grupoPerdedor->horario = $horarioPerdedor;
            $grupoPerdedor->jugador_1 = 0; // Se asignará después según resultados
            $grupoPerdedor->jugador_2 = 0;
            $grupoPerdedor->partido_id = $partidoPerdedor->id;
            $grupoPerdedor->save();
            
            return response()->json(['success' => true, 'partidos' => [$partidoA->id, $partidoB->id, $partidoGanador->id, $partidoPerdedor->id]]);
        } else if ($tieneCuatroParejas && $request->pareja_4_idJugadorArriba && $request->pareja_4_idJugadorAbajo) {
            // ESTRUCTURA CON 4 PAREJAS: SEMIFINALES Y FINAL
            // Semifinal 1: Pareja 1 vs Pareja 2
            $partidoSF1 = $this->crearPartido();
            $grupoSF1_P1 = new Grupo;
            $grupoSF1_P1->torneo_id = $torneoId;
            $grupoSF1_P1->zona = $zona;
            $grupoSF1_P1->fecha = $getFecha($request->input('pareja_1_partido_1_dia'));
            $grupoSF1_P1->horario = $getHorario($request->input('pareja_1_partido_1_horario'));
            $grupoSF1_P1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoSF1_P1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoSF1_P1->partido_id = $partidoSF1->id;
            $grupoSF1_P1->save();
            
            $grupoSF1_P2 = new Grupo;
            $grupoSF1_P2->torneo_id = $torneoId;
            $grupoSF1_P2->zona = $zona;
            $grupoSF1_P2->fecha = $getFecha($request->input('pareja_2_partido_1_dia'));
            $grupoSF1_P2->horario = $getHorario($request->input('pareja_2_partido_1_horario'));
            $grupoSF1_P2->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoSF1_P2->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoSF1_P2->partido_id = $partidoSF1->id;
            $grupoSF1_P2->save();
            
            // Semifinal 2: Pareja 3 vs Pareja 4
            $partidoSF2 = $this->crearPartido();
            $grupoSF2_P3 = new Grupo;
            $grupoSF2_P3->torneo_id = $torneoId;
            $grupoSF2_P3->zona = $zona;
            $grupoSF2_P3->fecha = $getFecha($request->input('pareja_3_partido_1_dia'));
            $grupoSF2_P3->horario = $getHorario($request->input('pareja_3_partido_1_horario'));
            $grupoSF2_P3->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoSF2_P3->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoSF2_P3->partido_id = $partidoSF2->id;
            $grupoSF2_P3->save();
            
            $grupoSF2_P4 = new Grupo;
            $grupoSF2_P4->torneo_id = $torneoId;
            $grupoSF2_P4->zona = $zona;
            $grupoSF2_P4->fecha = $getFecha($request->input('pareja_4_partido_1_dia'));
            $grupoSF2_P4->horario = $getHorario($request->input('pareja_4_partido_1_horario'));
            $grupoSF2_P4->jugador_1 = $request->pareja_4_idJugadorArriba;
            $grupoSF2_P4->jugador_2 = $request->pareja_4_idJugadorAbajo;
            $grupoSF2_P4->partido_id = $partidoSF2->id;
            $grupoSF2_P4->save();
            
            // Final: Ganador SF1 vs Ganador SF2 (se crea pero sin jugadores asignados aún)
            $partidoFinal = $this->crearPartido();
            $grupoFinal = new Grupo;
            $grupoFinal->torneo_id = $torneoId;
            $grupoFinal->zona = $zona;
            $grupoFinal->fecha = $getFecha($request->input('final_dia'));
            $grupoFinal->horario = $getHorario($request->input('final_horario'));
            $grupoFinal->jugador_1 = 0; // Se asignará después según resultados
            $grupoFinal->jugador_2 = 0;
            $grupoFinal->partido_id = $partidoFinal->id;
            $grupoFinal->save();
            
            // Consolación: Perdedor SF1 vs Perdedor SF2
            $partidoConsolacion = $this->crearPartido();
            $grupoConsolacion = new Grupo;
            $grupoConsolacion->torneo_id = $torneoId;
            $grupoConsolacion->zona = $zona;
            $grupoConsolacion->fecha = $getFecha($request->input('consolacion_dia'));
            $grupoConsolacion->horario = $getHorario($request->input('consolacion_horario'));
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
            $grupoA1->fecha = $getFecha($request->input('pareja_1_partido_1_dia'));
            $grupoA1->horario = $getHorario($request->input('pareja_1_partido_1_horario'));
            $grupoA1->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA1->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA1->partido_id = $partido1->id;
            $grupoA1->save();   
            
            $grupoA2 = new Grupo;
            $grupoA2->torneo_id = $torneoId;
            $grupoA2->zona = $zona;
            $grupoA2->fecha = $getFecha($request->input('pareja_1_partido_2_dia'));
            $grupoA2->horario = $getHorario($request->input('pareja_1_partido_2_horario'));
            $grupoA2->jugador_1 = $request->pareja_1_idJugadorArriba;
            $grupoA2->jugador_2 = $request->pareja_1_idJugadorAbajo;
            $grupoA2->partido_id = $partido2->id;
            $grupoA2->save();

            // PAREJA 2 ZONA Y PARTIDOS        
            $grupoA3 = new Grupo;
            $grupoA3->torneo_id = $torneoId;
            $grupoA3->zona = $zona;
            $grupoA3->fecha = $getFecha($request->input('pareja_2_partido_1_dia'));
            $grupoA3->horario = $getHorario($request->input('pareja_2_partido_1_horario'));
            $grupoA3->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA3->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA3->partido_id = $partido1->id;
            $grupoA3->save();   
            
            $grupoA4 = new Grupo;
            $grupoA4->torneo_id = $torneoId;
            $grupoA4->zona = $zona;
            $grupoA4->fecha = $getFecha($request->input('pareja_2_partido_2_dia'));
            $grupoA4->horario = $getHorario($request->input('pareja_2_partido_2_horario'));
            $grupoA4->jugador_1 = $request->pareja_2_idJugadorArriba;
            $grupoA4->jugador_2 = $request->pareja_2_idJugadorAbajo;
            $grupoA4->partido_id = $partido3->id;
            $grupoA4->save();

            // PAREJA 3 ZONA Y PARTIDOS        
            $grupoA5 = new Grupo;
            $grupoA5->torneo_id = $torneoId;
            $grupoA5->zona = $zona;
            $grupoA5->fecha = $getFecha($request->input('pareja_3_partido_1_dia'));
            $grupoA5->horario = $getHorario($request->input('pareja_3_partido_1_horario'));
            $grupoA5->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA5->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA5->partido_id = $partido2->id;
            $grupoA5->save();   
            
            $grupoA6 = new Grupo;
            $grupoA6->torneo_id = $torneoId;
            $grupoA6->zona = $zona;
            $grupoA6->fecha = $getFecha($request->input('pareja_3_partido_2_dia'));
            $grupoA6->horario = $getHorario($request->input('pareja_3_partido_2_horario'));
            $grupoA6->jugador_1 = $request->pareja_3_idJugadorArriba;
            $grupoA6->jugador_2 = $request->pareja_3_idJugadorAbajo;
            $grupoA6->partido_id = $partido3->id;
            $grupoA6->save();
            
            // NOTA: En un formato de 3 parejas, cada pareja juega 2 partidos:
            // - Pareja 1: partido 1 (vs Pareja 2) y partido 2 (vs Pareja 3)
            // - Pareja 2: partido 1 (vs Pareja 1) y partido 3 (vs Pareja 3)  
            // - Pareja 3: partido 2 (vs Pareja 1) y partido 3 (vs Pareja 2)
            // Los horarios de celda 3, 6 y 8 ya están siendo guardados correctamente arriba

            return response()->json(['success' => true, 'partidos' => [$partido1->id, $partido2->id, $partido3->id]]);
        }
    }

    public function obtenerDatosZona(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            $zona = $request->zona;
            
            if (!$torneoId || !$zona) {
                return response()->json(['success' => false, 'message' => 'Faltan parámetros'], 400);
            }
            
            // Obtener todos los grupos de esta zona (incluyendo "ganador X" y "perdedor X")
            $grupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where(function($query) use ($zona) {
                    $query->where('zona', $zona)
                          ->orWhere('zona', 'ganador ' . $zona)
                          ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->whereNotNull('grupos.jugador_1')
                          ->whereNotNull('grupos.jugador_2');
                    })->orWhere(function($q) {
                        // Incluir grupos con jugador_1 = 0 o jugador_2 = 0
                        $q->where(function($q2) {
                            $q2->where('grupos.jugador_1', 0)
                               ->whereNotNull('grupos.jugador_2');
                        })->orWhere(function($q2) {
                            $q2->whereNotNull('grupos.jugador_1')
                               ->where('grupos.jugador_2', 0);
                        });
                    });
                })
                ->select('grupos.id', 'grupos.torneo_id', 'grupos.zona', 'grupos.fecha', 
                        'grupos.horario', 'grupos.jugador_1', 'grupos.jugador_2', 'grupos.partido_id')
                ->orderBy('grupos.id')
                ->get();
            
            // Obtener información de jugadores
            $jugadoresIds = [];
            foreach ($grupos as $grupo) {
                if ($grupo->jugador_1 && $grupo->jugador_1 != 0) $jugadoresIds[] = $grupo->jugador_1;
                if ($grupo->jugador_2 && $grupo->jugador_2 != 0) $jugadoresIds[] = $grupo->jugador_2;
            }
            $jugadoresIds = array_unique($jugadoresIds);
            
            $jugadoresInfo = [];
            if (!empty($jugadoresIds)) {
                $jugadores = DB::table('jugadores')
                    ->whereIn('id', $jugadoresIds)
                    ->where('activo', 1)
                    ->get();
                
                foreach ($jugadores as $jugador) {
                    $foto = $jugador->foto ?? 'images/jugador_img.png';
                    if (!str_starts_with($foto, 'http') && !str_starts_with($foto, '/')) {
                        $foto = asset($foto);
                    } else if (str_starts_with($foto, 'images/')) {
                        $foto = asset($foto);
                    }
                    $jugadoresInfo[$jugador->id] = [
                        'id' => $jugador->id,
                        'nombre' => $jugador->nombre ?? '',
                        'apellido' => $jugador->apellido ?? '',
                        'foto' => $foto
                    ];
                }
            }
            
            // Procesar grupos para determinar estructura
            // Separar grupos con partido_id (partidos reales) de grupos sin partido_id (borradores)
            $gruposConPartidoId = [];
            $gruposSinPartidoId = [];
            $gruposLibres = [];
            
            foreach ($grupos as $grupo) {
                if (($grupo->jugador_1 == 0 || $grupo->jugador_1 === null) || 
                    ($grupo->jugador_2 == 0 || $grupo->jugador_2 === null)) {
                    $gruposLibres[] = $grupo;
                } else if ($grupo->partido_id) {
                    $gruposConPartidoId[] = $grupo;
                } else {
                    $gruposSinPartidoId[] = $grupo;
                }
            }
            
            // Agrupar por pareja
            $parejas = [];
            $parejasMap = [];
            
            // Primero procesar grupos con partido_id agrupados por partido_id
            $partidosMap = [];
            foreach ($gruposConPartidoId as $grupo) {
                if (!isset($partidosMap[$grupo->partido_id])) {
                    $partidosMap[$grupo->partido_id] = [];
                }
                $partidosMap[$grupo->partido_id][] = $grupo;
            }
            
            // Cada partido tiene 2 grupos (una pareja por grupo)
            foreach ($partidosMap as $partidoId => $gruposPartido) {
                foreach ($gruposPartido as $grupo) {
                    $key = min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                    if (!isset($parejasMap[$key])) {
                        $parejasMap[$key] = [];
                    }
                    $parejasMap[$key][] = $grupo;
                }
            }
            
            // Agregar grupos sin partido_id
            foreach ($gruposSinPartidoId as $grupo) {
                $key = min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasMap[$key])) {
                    $parejasMap[$key] = [];
                }
                $parejasMap[$key][] = $grupo;
            }
            
            // Convertir a array indexado
            $parejas = array_values($parejasMap);
            
            // Determinar si tiene 4 parejas
            $tieneCuatroParejas = count($parejas) >= 4;
            
            // Identificar grupos de ganador y perdedor
            // Buscar directamente por el nombre de la zona "ganador X" y "perdedor X"
            $grupoGanador = null;
            $grupoPerdedor = null;
            
            // Buscar grupo de ganador
            $gruposGanador = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'ganador ' . $zona)
                ->whereNotNull('partido_id')
                ->orderBy('id')
                ->get();
            
            if ($gruposGanador->count() > 0) {
                $grupoGanador = $gruposGanador->first();
            }
            
            // Buscar grupo de perdedor
            $gruposPerdedor = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'perdedor ' . $zona)
                ->whereNotNull('partido_id')
                ->orderBy('id')
                ->get();
            
            if ($gruposPerdedor->count() > 0) {
                $grupoPerdedor = $gruposPerdedor->first();
            }
            
            // Estructurar datos para respuesta
            $datos = [
                'zona' => $zona,
                'tieneCuatroParejas' => $tieneCuatroParejas,
                'tieneCuatroParejasEliminatoria' => ($grupoGanador && $grupoPerdedor) ? true : false,
                'parejas' => [],
                'gruposLibres' => $gruposLibres,
                'grupoGanador' => $grupoGanador ? [
                    'id' => $grupoGanador->id,
                    'fecha' => $grupoGanador->fecha,
                    'horario' => $grupoGanador->horario,
                    'partido_id' => $grupoGanador->partido_id
                ] : null,
                'grupoPerdedor' => $grupoPerdedor ? [
                    'id' => $grupoPerdedor->id,
                    'fecha' => $grupoPerdedor->fecha,
                    'horario' => $grupoPerdedor->horario,
                    'partido_id' => $grupoPerdedor->partido_id
                ] : null,
                'jugadores' => $jugadoresInfo
            ];
            
            // Procesar cada pareja
            foreach ($parejas as $index => $parejaGrupos) {
                if (empty($parejaGrupos)) continue;
                
                $primerGrupo = $parejaGrupos[0];
                $parejaData = [
                    'jugador_1' => $primerGrupo->jugador_1,
                    'jugador_2' => $primerGrupo->jugador_2,
                    'grupos' => []
                ];
                
                foreach ($parejaGrupos as $grupo) {
                    $parejaData['grupos'][] = [
                        'id' => $grupo->id,
                        'fecha' => $grupo->fecha,
                        'horario' => $grupo->horario,
                        'partido_id' => $grupo->partido_id
                    ];
                }
                
                $datos['parejas'][] = $parejaData;
            }
            
            return response()->json(['success' => true, 'datos' => $datos]);
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener datos de zona: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function verificarNumeroParejasZona(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            $zona = $request->zona;
            
            if (!$torneoId || !$zona) {
                return response()->json(['success' => false, 'message' => 'Faltan parámetros'], 400);
            }
            
            // Obtener todos los grupos de esta zona que tienen jugadores (excluyendo jugador 0)
            $grupos = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->whereNotNull('jugador_1')
                ->whereNotNull('jugador_2')
                ->where('jugador_1', '!=', 0)
                ->where('jugador_2', '!=', 0)
                ->select('jugador_1', 'jugador_2')
                ->get();
            
            // Si no hay grupos, devolver null
            if ($grupos->isEmpty()) {
                return response()->json(['success' => true, 'numParejas' => null]);
            }
            
            // Agrupar por pareja única
            $parejasMap = [];
            foreach ($grupos as $grupo) {
                $key = min($grupo->jugador_1, $grupo->jugador_2) . '_' . max($grupo->jugador_1, $grupo->jugador_2);
                if (!isset($parejasMap[$key])) {
                    $parejasMap[$key] = true;
                }
            }
            
            $numParejas = count($parejasMap);
            
            // Determinar si son 3 o 4 parejas
            if ($numParejas >= 4) {
                return response()->json(['success' => true, 'numParejas' => 4]);
            } else if ($numParejas >= 3) {
                return response()->json(['success' => true, 'numParejas' => 3]);
            } else {
                // Si hay menos de 3 parejas, devolver 3 por defecto
                return response()->json(['success' => true, 'numParejas' => 3]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al verificar número de parejas: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function obtenerTodasLasZonas(Request $request) {
        try {
            $torneoId = $request->torneo_id;

            if (!$torneoId) {
                return response()->json(['success' => false, 'message' => 'Falta torneo_id'], 400);
            }

            // Obtener todas las zonas únicas del torneo (excluyendo zonas de eliminatoria)
                    $zonas = DB::table('grupos')
                        ->where('torneo_id', $torneoId)
                        ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final'])
                        ->where('zona', 'not like', 'ganador %')
                        ->where('zona', 'not like', 'perdedor %')
                        ->select('zona')
                        ->distinct()
                        ->orderBy('zona')
                        ->pluck('zona')
                        ->toArray();

            // Si no hay zonas, retornar al menos 'A'
            if (empty($zonas)) {
                $zonas = ['A'];
            }

            return response()->json(['success' => true, 'zonas' => $zonas]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener todas las zonas: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
        $esBorrador = $request->es_borrador ?? 0; // 1 si es borrador, 0 si es guardado final
        
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
        
        // Si es borrador, actualizar el estado del torneo (si existe un campo para esto)
        // Por ahora, solo retornamos un mensaje diferente
        $mensaje = $esBorrador 
            ? 'Borrador guardado correctamente. Puede continuar editando.' 
            : 'Torneo americano guardado correctamente';
        
        return response()->json(['success' => true, 'message' => $mensaje, 'es_borrador' => $esBorrador]);
    }

    public function crearPartidosAmericano(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            
            $torneo = DB::table('torneos')
                            ->where('torneos.id', $torneoId)
                            ->where('torneos.activo', 1)
                            ->first();
            
            if (!$torneo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Torneo no encontrado'
                ], 404);
            }
            
            // Verificar si ya hay partidos creados y jugados
            $partidosExistentes = DB::table('grupos')
                                    ->where('grupos.torneo_id', $torneoId)
                                    ->whereNotNull('grupos.partido_id')
                                    ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                    ->count();
            
            // Si ya hay partidos creados, solo verificar que no falten partidos
            // No eliminar grupos iniciales si ya hay partidos jugados
            if ($partidosExistentes > 0) {
                // Verificar si hay grupos iniciales sin partido_id
                $gruposIniciales = DB::table('grupos')
                                    ->where('grupos.torneo_id', $torneoId)
                                    ->whereNull('grupos.partido_id')
                                    ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                    ->count();
                
                // Si hay grupos iniciales, crear los partidos faltantes
                if ($gruposIniciales > 0) {
                    // Continuar con la lógica de creación de partidos
                } else {
                    // Ya están todos los partidos creados, solo retornar éxito
                    return response()->json([
                        'success' => true,
                        'message' => 'Los partidos ya están creados',
                        'partidos_existentes' => true
                    ]);
                }
            }
            
            // Obtener solo los grupos iniciales del torneo (sin partido_id)
            // Estos son los grupos que se crearon al guardar el torneo, antes de crear los partidos
            $grupos = DB::table('grupos')
                            ->where('grupos.torneo_id', $torneoId)
                            ->whereNull('grupos.partido_id') // Solo grupos iniciales, sin partido_id
                            ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final']) // Excluir grupos de eliminatoria
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
        
            // Solo eliminar los grupos iniciales si no hay partidos jugados
            // Si ya hay partidos jugados, mantener los grupos iniciales por seguridad
            $partidosConResultados = DB::table('partidos')
                                        ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
                                        ->where('grupos.torneo_id', $torneoId)
                                        ->where(function($query) {
                                            $query->where('partidos.pareja_1_set_1', '>', 0)
                                                  ->orWhere('partidos.pareja_2_set_1', '>', 0);
                                        })
                                        ->count();
            
            // Solo eliminar grupos iniciales si no hay partidos con resultados
            if ($partidosConResultados == 0) {
                DB::table('grupos')
                    ->where('torneo_id', $torneoId)
                    ->whereNull('partido_id')
                    ->whereNotIn('zona', ['cuartos final', 'semifinal', 'final']) // No eliminar grupos de eliminatoria
                    ->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Partidos creados correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear partidos americano: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear los partidos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tvTorneoAmericano(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('index')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener todos los grupos del torneo (con partido_id) para identificar parejas y zonas
        // Después de crear los partidos, los grupos iniciales se eliminan, así que usamos los grupos con partido_id
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotNull('grupos.partido_id') // Solo grupos con partido_id (partidos creados)
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final']) // Excluir grupos de eliminatoria
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
                        'partido_id' => $grupo->partido_id
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
            
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2']
                ];
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
        
        // Calcular posiciones por zona
        $posicionesPorZona = [];
        $gruposCollection = collect($grupos);

        foreach (array_keys($partidosPorZona) as $zona) {
             $gruposZona = $gruposCollection->where('zona', $zona);
             
             $parejas = [];
             foreach ($gruposZona as $grupo) {
                if (!$grupo->jugador_1 || !$grupo->jugador_2) continue;
                
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

             // Iterar sobre partidos
             foreach ($partidosPorZona[$zona] as $p) {
                 if (!$p['partido_id']) continue;
                 $pid = $p['partido_id'];
                 if (!isset($partidosConResultados[$pid])) continue;
                 
                 $partido = $partidosConResultados[$pid];
                 
                 $gruposPartido = $gruposCollection->where('partido_id', $pid)->sortBy('id')->values();
                 if ($gruposPartido->count() < 2) continue;
                 
                 $g1 = $gruposPartido[0];
                 $paramPareja1 = $p['pareja_1'];
                 
                 $p1_key = $paramPareja1['jugador_1'] . '_' . $paramPareja1['jugador_2'];
                 $p2_key = $p['pareja_2']['jugador_1'] . '_' . $p['pareja_2']['jugador_2'];
                 
                 $set1 = $partido->pareja_1_set_1;
                 $set2 = $partido->pareja_2_set_1;
                 
                 $p1_score = 0; 
                 $p2_score = 0;
                 
                 // Verificar orden
                 if ($g1->jugador_1 == $paramPareja1['jugador_1'] && $g1->jugador_2 == $paramPareja1['jugador_2']) {
                     $p1_score = $set1;
                     $p2_score = $set2;
                 } else {
                     $p1_score = $set2;
                     $p2_score = $set1;
                 }
                 
                 if ($p1_score > 0 || $p2_score > 0) {
                      if ($p1_score > $p2_score) {
                          if(isset($parejas[$p1_key])) {
                              $parejas[$p1_key]['partidos_ganados']++;
                              $parejas[$p1_key]['puntos_ganados'] += $p1_score;
                              $parejas[$p1_key]['puntos_perdidos'] += $p2_score;
                              $parejas[$p1_key]['partidos_directos'][$p2_key] = ['ganado'=>true];
                          }
                          if(isset($parejas[$p2_key])) {
                              $parejas[$p2_key]['partidos_perdidos']++;
                              $parejas[$p2_key]['puntos_ganados'] += $p2_score;
                              $parejas[$p2_key]['puntos_perdidos'] += $p1_score;
                              $parejas[$p2_key]['partidos_directos'][$p1_key] = ['ganado'=>false];
                          }
                      } elseif ($p2_score > $p1_score) {
                          if(isset($parejas[$p2_key])) {
                              $parejas[$p2_key]['partidos_ganados']++;
                              $parejas[$p2_key]['puntos_ganados'] += $p2_score;
                              $parejas[$p2_key]['puntos_perdidos'] += $p1_score;
                              $parejas[$p2_key]['partidos_directos'][$p1_key] = ['ganado'=>true];
                          }
                          if(isset($parejas[$p1_key])) {
                              $parejas[$p1_key]['partidos_perdidos']++;
                              $parejas[$p1_key]['puntos_ganados'] += $p1_score;
                              $parejas[$p1_key]['puntos_perdidos'] += $p2_score;
                              $parejas[$p1_key]['partidos_directos'][$p2_key] = ['ganado'=>false];
                          }
                      }
                 }
             }
             
             // Calcular diferencia de games y agregar key
             foreach ($parejas as $key => $val) {
                 $parejas[$key]['key'] = $key;
                 $parejas[$key]['diferencia_games'] = ($val['puntos_ganados'] ?? 0) - ($val['puntos_perdidos'] ?? 0);
             }
             $posiciones = array_values($parejas);
             
             usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                // Usar diferencia de games en lugar de solo games ganados
                if ($a['diferencia_games'] != $b['diferencia_games']) {
                    return $b['diferencia_games'] - $a['diferencia_games'];
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
        
        return View('bahia_padel.tv.resultados')
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona)
                    ->with('jugadores', $jugadores)
                    ->with('partidosConResultados', $partidosConResultados)
                    ->with('posicionesPorZona', $posicionesPorZona);
    }
    
    public function tvTorneoAmericanoActualizar(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        
        // Obtener grupos y calcular posiciones (misma lógica que tvTorneoAmericano)
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotNull('grupos.partido_id')
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Obtener resultados de partidos
        $partidosIds = $grupos->pluck('partido_id')->unique()->filter();
        $partidosConResultados = [];
        if ($partidosIds->count() > 0) {
            $partidos = DB::table('partidos')
                            ->whereIn('id', $partidosIds)
                            ->get();
            foreach ($partidos as $partido) {
                $partidosConResultados[$partido->id] = $partido;
            }
        }
        
        // Calcular posiciones por zona
        $posicionesPorZona = [];
        $gruposCollection = collect($grupos);
        
        // Obtener zonas únicas
        $zonas = $grupos->pluck('zona')->unique();
        
        foreach ($zonas as $zona) {
            $gruposZona = $gruposCollection->where('zona', $zona);
            
            $parejas = [];
            foreach ($gruposZona as $grupo) {
                if (!$grupo->jugador_1 || !$grupo->jugador_2) continue;
                
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
            
            // Obtener partidos de esta zona
            $partidosZona = [];
            foreach ($gruposZona as $grupo) {
                if ($grupo->partido_id && isset($partidosConResultados[$grupo->partido_id])) {
                    $partidosZona[$grupo->partido_id] = $partidosConResultados[$grupo->partido_id];
                }
            }
            
            // Procesar partidos para calcular estadísticas
            foreach ($partidosZona as $partidoId => $partido) {
                $gruposPartido = $gruposZona->where('partido_id', $partidoId)->sortBy('id')->values();
                if ($gruposPartido->count() < 2) continue;
                
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                $key1 = $g1->jugador_1 . '_' . $g1->jugador_2;
                $key2 = $g2->jugador_1 . '_' . $g2->jugador_2;
                
                if (!isset($parejas[$key1]) || !isset($parejas[$key2])) continue;
                
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
                    } elseif ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    }
                }
            }
            
            // Calcular diferencia y ordenar
            foreach ($parejas as $key => $val) {
                $parejas[$key]['key'] = $key;
                $parejas[$key]['diferencia_games'] = ($val['puntos_ganados'] ?? 0) - ($val['puntos_perdidos'] ?? 0);
            }
            
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                if ($a['diferencia_games'] != $b['diferencia_games']) {
                    return $b['diferencia_games'] - $a['diferencia_games'];
                }
                return 0;
            });
            
            $posicionesPorZona[$zona] = $posiciones;
        }
        
        return response()->json([
            'success' => true,
            'posicionesPorZona' => $posicionesPorZona,
            'partidosConResultados' => $partidosConResultados
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
        
        // Obtener todos los grupos del torneo (con partido_id) para identificar parejas y zonas
        // Después de crear los partidos, los grupos iniciales se eliminan, así que usamos los grupos con partido_id
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotNull('grupos.partido_id') // Solo grupos con partido_id (partidos creados)
                        ->where(function($query) {
                            // Excluir grupos de eliminatoria: cuartos final (con o sin |), semifinal, final, ganador, perdedor
                            $query->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                                  ->where('grupos.zona', 'not like', 'cuartos final|%')
                                  ->where('grupos.zona', 'not like', 'ganador %')
                                  ->where('grupos.zona', 'not like', 'perdedor %');
                        })
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
                        'partido_id' => $grupo->partido_id
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
            
            foreach ($partidosOrdenados as $partido) {
                $partidosPorZona[$zona][] = [
                    'partido_id' => $partido['partido_id'],
                    'pareja_1' => $partido['pareja_1'],
                    'pareja_2' => $partido['pareja_2']
                ];
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
                    'puntos_ganados' => 0, // Suma de games ganados
                    'puntos_perdidos' => 0, // Suma de games perdidos
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
                    $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    $parejas[$key2]['partidos_perdidos']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                } else if ($puntosPareja2 > $puntosPareja1) {
                    $parejas[$key2]['partidos_ganados']++;
                    $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                    $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                    $parejas[$key1]['partidos_perdidos']++;
                    $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                    $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                    
                    // Guardar resultado del partido directo
                    $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true, 'puntos' => $puntosPareja2 . '-' . $puntosPareja1];
                    $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false, 'puntos' => $puntosPareja1 . '-' . $puntosPareja2];
                }
            }
        }
        
        // Agregar keys a cada pareja para poder comparar partidos directos
        // Calcular diferencia de games (ganados - perdidos)
        foreach ($parejas as $key => $pareja) {
            $parejas[$key]['key'] = $key;
            $parejas[$key]['diferencia_games'] = $pareja['puntos_ganados'] - $pareja['puntos_perdidos'];
        }
        
        // Convertir a array y ordenar por posición
        $posiciones = array_values($parejas);
        
        // Función de comparación con todos los criterios de desempate
        usort($posiciones, function($a, $b) {
            // 1. Primero por PARTIDOS GANADOS
            if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                return $b['partidos_ganados'] - $a['partidos_ganados'];
            }
            
            // 2. Si tienen los mismos partidos ganados, por DIFERENCIA DE GAMES (ganados - perdidos)
            if ($a['diferencia_games'] != $b['diferencia_games']) {
                return $b['diferencia_games'] - $a['diferencia_games'];
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
        // Los grupos de "ganador X" y "perdedor X" deben agruparse con la zona base "X"
        $partidosPorZona = [];
        foreach ($grupos as $grupo) {
            $zonaOriginal = $grupo->zona;
            $partidoId = $grupo->partido_id;
            
            // Validar que el partido_id existe
            if (!$partidoId || $partidoId === null) {
                continue; // Saltar grupos sin partido_id
            }
            
            // Determinar la zona base (si es "ganador A" o "perdedor A", usar "A")
            $zonaBase = $zonaOriginal;
            $esGanador = false;
            $esPerdedor = false;
            
            if (strpos($zonaOriginal, 'ganador ') === 0) {
                $zonaBase = substr($zonaOriginal, 8); // Quitar "ganador "
                $esGanador = true;
            } else if (strpos($zonaOriginal, 'perdedor ') === 0) {
                $zonaBase = substr($zonaOriginal, 9); // Quitar "perdedor "
                $esPerdedor = true;
            }
            
            // Excluir zonas especiales de eliminatoria
            if (in_array($zonaBase, ['cuartos final', 'semifinal', 'final'])) {
                continue;
            }
            
            if (!isset($partidosPorZona[$zonaBase])) {
                $partidosPorZona[$zonaBase] = [];
            }
            
            // Agrupar por partido_id único (usar partido_id como clave para preservarlo)
            if (!isset($partidosPorZona[$zonaBase][$partidoId])) {
                $partidosPorZona[$zonaBase][$partidoId] = [
                    'partido_id' => $partidoId,
                    'pareja_1' => null,
                    'pareja_2' => null,
                    'fecha' => $grupo->fecha,
                    'horario' => $grupo->horario,
                    'resultados' => $grupo,
                    'tipo' => $esGanador ? 'ganador' : ($esPerdedor ? 'perdedor' : 'normal')
                ];
            }
            
            // Asignar parejas (cada partido tiene 2 grupos con las dos parejas)
            // Incluir todos los grupos, incluso los que tienen jugador_1 = 0 y jugador_2 = 0 (partidos de Ganador/Perdedor)
            if (!$partidosPorZona[$zonaBase][$partidoId]['pareja_1']) {
                $partidosPorZona[$zonaBase][$partidoId]['pareja_1'] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2
                ];
            } else if (!$partidosPorZona[$zonaBase][$partidoId]['pareja_2']) {
                $partidosPorZona[$zonaBase][$partidoId]['pareja_2'] = [
                    'jugador_1' => $grupo->jugador_1,
                    'jugador_2' => $grupo->jugador_2
                ];
            }
        }
        
        // Ordenar los partidos por zona: primero los normales, luego ganador, luego perdedor
        // Mantener las claves originales (partido_id) para que el frontend pueda identificarlos
        foreach ($partidosPorZona as $zona => &$partidos) {
            // Separar partidos normales, ganador y perdedor
            $partidosNormales = [];
            $partidoGanador = null;
            $partidoPerdedor = null;
            
            foreach ($partidos as $partidoId => $partidoData) {
                if ($partidoData['tipo'] === 'ganador') {
                    $partidoGanador = [$partidoId => $partidoData];
                } else if ($partidoData['tipo'] === 'perdedor') {
                    $partidoPerdedor = [$partidoId => $partidoData];
                } else {
                    $partidosNormales[$partidoId] = $partidoData;
                }
            }
            
            // Reconstruir el array: normales primero, luego ganador, luego perdedor
            // Usar array_merge con claves preservadas usando el operador + para mantener las claves numéricas
            $partidos = $partidosNormales;
            if ($partidoGanador) {
                $partidos = $partidos + $partidoGanador; // Preservar claves
            }
            if ($partidoPerdedor) {
                $partidos = $partidos + $partidoPerdedor; // Preservar claves
            }
        }
        unset($partidos); // Liberar referencia
        
        return View('bahia_padel.admin.torneo.resultados_torneo')
                    ->with('jugadores', $jugadores)
                    ->with('torneo', $torneo)
                    ->with('partidosPorZona', $partidosPorZona); 
    }

    public function guardarResultadoPartido(Request $request) {
        try {
            $partidoId = $request->partido_id;
            
            \Log::info('=== Iniciando guardarResultadoPartido ===');
            \Log::info('Partido ID recibido: ' . $partidoId);
            \Log::info('Tipo de partido_id: ' . gettype($partidoId));
            \Log::info('Request data: ' . json_encode($request->all()));
            
            // Validar que partido_id existe y es válido
            if (!$partidoId || $partidoId === 'null' || $partidoId === '') {
                \Log::error('Partido ID inválido o vacío: ' . $partidoId);
                return response()->json(['success' => false, 'message' => 'Partido ID inválido']);
            }
            
            // Convertir a entero si es necesario
            $partidoIdInt = is_numeric($partidoId) ? (int)$partidoId : $partidoId;
            
            \Log::info('Buscando partido con ID: ' . $partidoIdInt);
            
            $partido = Partido::find($partidoIdInt);
            
            if (!$partido) {
                \Log::error('Partido no encontrado en BD con ID: ' . $partidoIdInt);
                // Intentar buscar directamente en la tabla
                $partidoDirecto = DB::table('partidos')->where('id', $partidoIdInt)->first();
                if ($partidoDirecto) {
                    \Log::info('Partido encontrado directamente en tabla, pero no con Eloquent');
                } else {
                    \Log::error('Partido tampoco encontrado directamente en tabla');
                }
                return response()->json(['success' => false, 'message' => 'Partido no encontrado']);
            }
            
            \Log::info('Partido encontrado: ID ' . $partido->id);
            
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
        
        // Verificar si es un partido eliminatorio y generar siguientes rondas si es necesario
        $grupo = DB::table('grupos')
                    ->where('partido_id', $partidoId)
                    ->first();
        
        if ($grupo && in_array($grupo->zona, ['cuartos final', 'semifinal', 'final'])) {
            if ($grupo->zona === 'cuartos final') {
                $this->crearSemifinalesPuntuable($grupo->torneo_id);
            } else if ($grupo->zona === 'semifinal') {
                $this->crearFinalPuntuable($grupo->torneo_id);
            }
        }
        
        // Si es una zona de 4 parejas eliminatoria, actualizar partidos de Ganador y Perdedor
        $recargar = false;
        $debugInfo = [];
        if ($grupo && !in_array($grupo->zona, ['cuartos final', 'semifinal', 'final'])) {
            $debugInfo['zona'] = $grupo->zona;
            $debugInfo['torneo_id'] = $grupo->torneo_id;
            $debugInfo['partido_id'] = $partidoId;
            $resultadoActualizacion = $this->actualizarPartidosGanadorPerdedor($partidoId, $partido, $grupo->torneo_id, $grupo->zona);
            
            // El resultado ahora es un array con 'actualizado' y 'debug'
            if (is_array($resultadoActualizacion)) {
                $recargar = $resultadoActualizacion['actualizado'];
                $debugInfo = array_merge($debugInfo, $resultadoActualizacion['debug']);
            } else {
                // Compatibilidad con versión anterior
                $recargar = $resultadoActualizacion;
            }
            
            $debugInfo['recargar'] = $recargar;
            $debugInfo['mensaje'] = $recargar ? 'Se actualizaron partidos de Ganador/Perdedor' : 'No se actualizaron partidos de Ganador/Perdedor';
        } else {
            $debugInfo['mensaje'] = 'No es una zona de 4 parejas eliminatoria o es fase eliminatoria';
            if ($grupo) {
                $debugInfo['zona_detectada'] = $grupo->zona;
            }
        }
        
            return response()->json([
                'success' => true, 
                'partido' => $partido, 
                'recargar' => $recargar,
                'debug' => $debugInfo
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en guardarResultadoPartido: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false, 
                'message' => 'Error al guardar el resultado: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualiza los partidos de Ganador y Perdedor cuando se guarda un resultado de Partido A o B
     */
    private function actualizarPartidosGanadorPerdedor($partidoId, $partido, $torneoId, $zona) {
        // Inicializar variables para evitar errores de "Undefined variable"
        $ganadorA = null;
        $ganadorB = null;
        $perdedorA = null;
        $perdedorB = null;
        $gruposGanador = collect();
        $gruposPerdedor = collect();
        
        $debugInfo = [];
        $debugInfo['inicio'] = 'Partido ID: ' . $partidoId . ', Torneo ID: ' . $torneoId . ', Zona: ' . $zona;
        
        \Log::info('=== Iniciando actualizarPartidosGanadorPerdedor ===');
        \Log::info('Partido ID: ' . $partidoId . ', Torneo ID: ' . $torneoId . ', Zona: ' . $zona);
        // Obtener los grupos de este partido para identificar las parejas
        $gruposPartido = DB::table('grupos')
            ->where('partido_id', $partidoId)
            ->where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->get();
        
        // Verificar si este partido tiene jugadores reales (no 0) - es Partido A o B
        $esPartidoAB = false;
        foreach ($gruposPartido as $gp) {
            if (($gp->jugador_1 != 0 && $gp->jugador_1 !== null) && 
                ($gp->jugador_2 != 0 && $gp->jugador_2 !== null)) {
                $esPartidoAB = true;
                break;
            }
        }
        
        if (!$esPartidoAB) {
            \Log::info('Partido ' . $partidoId . ' no es Partido A o B (no tiene jugadores reales)');
            return false; // No es Partido A o B
        }
        
        // Verificar que el partido tenga resultados para determinar ganador
        // Un partido tiene resultados si al menos un set tiene valores mayores a 0
        $tieneResultados = false;
        if (isset($partido->pareja_1_set_1) && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0)) {
            $tieneResultados = true;
        } else if (isset($partido->pareja_1_set_2) && ($partido->pareja_1_set_2 > 0 || $partido->pareja_2_set_2 > 0)) {
            $tieneResultados = true;
        } else if (isset($partido->pareja_1_set_3) && ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0)) {
            $tieneResultados = true;
        } else if (isset($partido->pareja_1_set_super_tie_break) && ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0)) {
            $tieneResultados = true;
        }
        
        if (!$tieneResultados) {
            \Log::info('Partido ' . $partidoId . ' no tiene resultados aún. Set1: ' . ($partido->pareja_1_set_1 ?? 'null') . '/' . ($partido->pareja_2_set_1 ?? 'null'));
            return false; // No tiene resultados, no actualizar
        }
        
        \Log::info('Partido ' . $partidoId . ' tiene resultados. Set1: ' . ($partido->pareja_1_set_1 ?? 0) . '/' . ($partido->pareja_2_set_1 ?? 0));
        
        // Determinar ganador y perdedor del partido
        $ganador = $this->determinarGanadorPartido($partido);
        $perdedor = $ganador === 1 ? 2 : 1;
        
        // Obtener las parejas (jugadores) del ganador y perdedor
        $gruposOrdenados = $gruposPartido->sortBy('id')->values();
        if ($gruposOrdenados->count() < 2) {
            \Log::warning('Partido ' . $partidoId . ' no tiene 2 grupos');
            return false;
        }
        
        $grupoGanador = $gruposOrdenados[$ganador - 1];
        $grupoPerdedor = $gruposOrdenados[$perdedor - 1];
        
        $ganadorJugador1 = $grupoGanador->jugador_1;
        $ganadorJugador2 = $grupoGanador->jugador_2;
        $perdedorJugador1 = $grupoPerdedor->jugador_1;
        $perdedorJugador2 = $grupoPerdedor->jugador_2;
        
        \Log::info('Partido ' . $partidoId . ' - Ganador: ' . $ganadorJugador1 . '/' . $ganadorJugador2 . ', Perdedor: ' . $perdedorJugador1 . '/' . $perdedorJugador2);
        
        // Identificar si es Partido A o Partido B
        // Partido A: Pareja 1 vs Pareja 2 (los primeros grupos creados)
        // Partido B: Pareja 3 vs Pareja 4 (los siguientes grupos)
        
        // Obtener todos los partidos de la zona con jugadores reales, ordenados por partido_id
        // Buscar partidos que tengan grupos con jugadores reales (no 0)
        $todosPartidos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->whereNotNull('partido_id')
            ->where('jugador_1', '!=', 0)
            ->where('jugador_1', '!=', null)
            ->where('jugador_2', '!=', 0)
            ->where('jugador_2', '!=', null)
            ->select('partido_id')
            ->distinct()
            ->orderBy('partido_id')
            ->get();
        
        $partidosConJugadores = $todosPartidos->pluck('partido_id')->unique()->values()->toArray();
        
        \Log::info('Partidos con jugadores reales en zona ' . $zona . ': ' . implode(', ', $partidosConJugadores));
        
        // Identificar Partido A y B por orden de partido_id
        $partidoAId = null;
        $partidoBId = null;
        
        if (count($partidosConJugadores) >= 1) {
            $partidoAId = $partidosConJugadores[0];
        }
        if (count($partidosConJugadores) >= 2) {
            $partidoBId = $partidosConJugadores[1];
        }
        
        // Determinar si el partido actual es A o B
        $esPartidoA = ($partidoId == $partidoAId);
        $esPartidoB = ($partidoId == $partidoBId);
        
        if (!$esPartidoA && !$esPartidoB) {
            \Log::info('Partido ' . $partidoId . ' no es Partido A ni B. Partidos con jugadores: ' . implode(', ', $partidosConJugadores));
            return false; // No es Partido A ni B
        }
        
        \Log::info('Partido identificado: ' . ($esPartidoA ? 'A' : 'B') . ' (ID: ' . $partidoId . ')');
        
        // Obtener los partidos de Ganador y Perdedor (tienen jugador_1 = 0 y jugador_2 = 0)
        // Buscar todos los partidos con jugador_1 = 0 y jugador_2 = 0 en esta zona
        // Usar whereRaw para manejar posibles problemas de tipo de dato
        $partidosGanadorPerdedor = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', $zona)
            ->whereRaw('(jugador_1 = 0 OR jugador_1 IS NULL)')
            ->whereRaw('(jugador_2 = 0 OR jugador_2 IS NULL)')
            ->whereNotNull('partido_id')
            ->select('partido_id')
            ->distinct()
            ->orderBy('partido_id')
            ->get();
        
        \Log::info('Partidos Ganador/Perdedor encontrados: ' . $partidosGanadorPerdedor->count());
        \Log::info('Partidos IDs: ' . json_encode($partidosGanadorPerdedor->pluck('partido_id')->toArray()));
        
        // Si no encontramos partidos con jugador_1=0 y jugador_2=0, buscar todos los partidos de la zona
        // y verificar cuáles tienen grupos con jugador_1=0 y jugador_2=0
        if ($partidosGanadorPerdedor->count() == 0) {
            \Log::info('No se encontraron partidos con jugador_1=0 y jugador_2=0. Buscando todos los partidos de la zona...');
            $todosPartidosZona = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where(function($q) use ($zona) {
                    $q->where('zona', $zona)
                      ->orWhere('zona', 'ganador ' . $zona)
                      ->orWhere('zona', 'perdedor ' . $zona);
                })
                ->whereNotNull('partido_id')
                ->select('partido_id', 'zona')
                ->distinct()
                ->orderBy('partido_id')
                ->get();
            
            \Log::info('Todos los partidos de la zona: ' . json_encode($todosPartidosZona->pluck('partido_id')->toArray()));
            
            // Verificar cada partido para ver si tiene grupos con jugador_1=0 y jugador_2=0
            foreach ($todosPartidosZona as $partidoInfo) {
                $gruposPartido = DB::table('grupos')
                    ->where('partido_id', $partidoInfo->partido_id)
                    ->where('torneo_id', $torneoId)
                    ->where(function($q) use ($zona) {
                        $q->where('zona', $zona)
                          ->orWhere('zona', 'ganador ' . $zona)
                          ->orWhere('zona', 'perdedor ' . $zona);
                    })
                    ->get();
                
                $tieneJugador0 = false;
                $zonaPartido = null;
                foreach ($gruposPartido as $grupo) {
                    if ($grupo->jugador_1 == 0 && $grupo->jugador_2 == 0) {
                        $tieneJugador0 = true;
                        $zonaPartido = $grupo->zona;
                        break;
                    }
                }
                
                if ($tieneJugador0) {
                    $partidosGanadorPerdedor->push((object)['partido_id' => $partidoInfo->partido_id, 'zona' => $zonaPartido]);
                    \Log::info('Encontrado partido con jugador 0: ' . $partidoInfo->partido_id . ' - Zona: ' . $zonaPartido);
                }
            }
            
            // Reordenar por partido_id
            $partidosGanadorPerdedor = $partidosGanadorPerdedor->sortBy('partido_id')->values();
            \Log::info('Partidos Ganador/Perdedor después de búsqueda alternativa: ' . $partidosGanadorPerdedor->count());
        }
        
        $partidoGanador = null;
        $partidoPerdedor = null;
        
        // Identificar Partido Ganador y Perdedor por el nombre de la zona
        foreach ($partidosGanadorPerdedor as $partidoInfo) {
            if (strpos($partidoInfo->zona, 'ganador') !== false && !$partidoGanador) {
                $partidoGanador = (object)['partido_id' => $partidoInfo->partido_id];
                \Log::info('Partido Ganador (Partido 3) ID: ' . $partidoGanador->partido_id . ' - Zona: ' . $partidoInfo->zona);
            } else if (strpos($partidoInfo->zona, 'perdedor') !== false && !$partidoPerdedor) {
                $partidoPerdedor = (object)['partido_id' => $partidoInfo->partido_id];
                \Log::info('Partido Perdedor (Partido 4) ID: ' . $partidoPerdedor->partido_id . ' - Zona: ' . $partidoInfo->zona);
            }
        }
        
        // Actualizar Partido Ganador
        if ($partidoGanador) {
            $gruposGanador = DB::table('grupos')
                ->where('partido_id', $partidoGanador->partido_id)
                ->where('torneo_id', $torneoId)
                ->where('zona', $zona)
                ->get();
            
            // Obtener ganadores de Partido A y B
            $ganadorA = null;
            $ganadorB = null;
            
            if ($esPartidoA) {
                $ganadorA = ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
            } else if ($esPartidoB) {
                $ganadorB = ['jugador_1' => $ganadorJugador1, 'jugador_2' => $ganadorJugador2];
            }
            
            // Intentar obtener el ganador del otro partido si aún no lo tenemos
            if (!$ganadorA || !$ganadorB) {
                $otroPartidoId = $esPartidoA ? $partidoBId : $partidoAId;
                if ($otroPartidoId) {
                    $otroPartido = DB::table('partidos')->where('id', $otroPartidoId)->first();
                    if ($otroPartido) {
                        // Verificar si el otro partido tiene resultados
                        $otroTieneResultados = ($otroPartido->pareja_1_set_1 > 0 || $otroPartido->pareja_2_set_1 > 0) ||
                                             ($otroPartido->pareja_1_set_2 > 0 || $otroPartido->pareja_2_set_2 > 0) ||
                                             ($otroPartido->pareja_1_set_3 > 0 || $otroPartido->pareja_2_set_3 > 0) ||
                                             ($otroPartido->pareja_1_set_super_tie_break > 0 || $otroPartido->pareja_2_set_super_tie_break > 0);
                        
                        if ($otroTieneResultados) {
                            $otroGanador = $this->determinarGanadorPartido($otroPartido);
                            if ($otroGanador) {
                                $otroGrupos = DB::table('grupos')
                                    ->where('partido_id', $otroPartidoId)
                                    ->where('torneo_id', $torneoId)
                                    ->where('zona', $zona)
                                    ->orderBy('id')
                                    ->get();
                                
                                if ($otroGrupos->count() >= 2) {
                                    $otroGrupoGanador = $otroGrupos[$otroGanador - 1];
                                    if ($esPartidoA && !$ganadorB) {
                                        $ganadorB = ['jugador_1' => $otroGrupoGanador->jugador_1, 'jugador_2' => $otroGrupoGanador->jugador_2];
                                        \Log::info('Obtenido ganador B del otro partido: ' . $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2']);
                                    } else if ($esPartidoB && !$ganadorA) {
                                        $ganadorA = ['jugador_1' => $otroGrupoGanador->jugador_1, 'jugador_2' => $otroGrupoGanador->jugador_2];
                                        \Log::info('Obtenido ganador A del otro partido: ' . $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2']);
                                    }
                                }
                            }
                        } else {
                            \Log::info('El otro partido (ID: ' . $otroPartidoId . ') aún no tiene resultados');
                        }
                    }
                }
            }
            
            // Actualizar grupos del partido Ganador
            if ($gruposGanador->count() >= 1) {
                $gruposGanadorArray = $gruposGanador->values();
                
                // Si solo hay 1 grupo y necesitamos 2, crear el segundo grupo
                if ($gruposGanador->count() == 1 && $ganadorB) {
                    // Crear el segundo grupo para el partido Ganador
                    $segundoGrupoGanador = DB::table('grupos')->insertGetId([
                        'torneo_id' => $torneoId,
                        'zona' => $zona,
                        'fecha' => $gruposGanadorArray[0]->fecha,
                        'horario' => $gruposGanadorArray[0]->horario,
                        'jugador_1' => 0,
                        'jugador_2' => 0,
                        'partido_id' => $partidoGanador->partido_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info('Creado segundo grupo Ganador: ID ' . $segundoGrupoGanador);
                    // Recargar los grupos
                    $gruposGanador = DB::table('grupos')
                        ->where('partido_id', $partidoGanador->partido_id)
                        ->where('torneo_id', $torneoId)
                        ->where('zona', 'ganador ' . $zona)
                        ->orderBy('id')
                        ->get();
                    $gruposGanadorArray = $gruposGanador->values();
                }
                
                // Actualizar el primer grupo si tenemos ganadorA (o si solo hay 1 grupo y es Partido A)
                if ($ganadorA) {
                    $grupoId = $gruposGanadorArray[0]->id;
                    $filasActualizadas = DB::table('grupos')
                        ->where('id', $grupoId)
                        ->update([
                            'jugador_1' => $ganadorA['jugador_1'],
                            'jugador_2' => $ganadorA['jugador_2']
                        ]);
                    \Log::info('Actualizado grupo Ganador (1): ID ' . $grupoId . ' con jugadores ' . $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2'] . ' - Filas actualizadas: ' . $filasActualizadas);
                    $debugInfo['actualizacionGanador1'] = [
                        'grupoId' => $grupoId,
                        'jugadores' => $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2'],
                        'filasActualizadas' => $filasActualizadas
                    ];
                }
                
                // Actualizar el segundo grupo si tenemos ganadorB (solo si hay 2 grupos)
                if ($ganadorB && $gruposGanadorArray->count() >= 2) {
                    $grupoId = $gruposGanadorArray[1]->id;
                    $filasActualizadas = DB::table('grupos')
                        ->where('id', $grupoId)
                        ->update([
                            'jugador_1' => $ganadorB['jugador_1'],
                            'jugador_2' => $ganadorB['jugador_2']
                        ]);
                    \Log::info('Actualizado grupo Ganador (2): ID ' . $grupoId . ' con jugadores ' . $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2'] . ' - Filas actualizadas: ' . $filasActualizadas);
                    $debugInfo['actualizacionGanador2'] = [
                        'grupoId' => $grupoId,
                        'jugadores' => $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2'],
                        'filasActualizadas' => $filasActualizadas
                    ];
                }
            }
        }
        
        // Actualizar Partido Perdedor (similar lógica)
        if ($partidoPerdedor) {
            $gruposPerdedor = DB::table('grupos')
                ->where('partido_id', $partidoPerdedor->partido_id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'perdedor ' . $zona)
                ->get();
            
            $perdedorA = null;
            $perdedorB = null;
            
            if ($esPartidoA) {
                $perdedorA = ['jugador_1' => $perdedorJugador1, 'jugador_2' => $perdedorJugador2];
            } else if ($esPartidoB) {
                $perdedorB = ['jugador_1' => $perdedorJugador1, 'jugador_2' => $perdedorJugador2];
            }
            
            // Intentar obtener el perdedor del otro partido si aún no lo tenemos
            if (!$perdedorA || !$perdedorB) {
                $otroPartidoId = $esPartidoA ? $partidoBId : $partidoAId;
                if ($otroPartidoId) {
                    $otroPartido = DB::table('partidos')->where('id', $otroPartidoId)->first();
                    if ($otroPartido) {
                        // Verificar si el otro partido tiene resultados
                        $otroTieneResultados = ($otroPartido->pareja_1_set_1 > 0 || $otroPartido->pareja_2_set_1 > 0) ||
                                             ($otroPartido->pareja_1_set_2 > 0 || $otroPartido->pareja_2_set_2 > 0) ||
                                             ($otroPartido->pareja_1_set_3 > 0 || $otroPartido->pareja_2_set_3 > 0) ||
                                             ($otroPartido->pareja_1_set_super_tie_break > 0 || $otroPartido->pareja_2_set_super_tie_break > 0);
                        
                        if ($otroTieneResultados) {
                            $otroGanador = $this->determinarGanadorPartido($otroPartido);
                            if ($otroGanador) {
                                $otroPerdedor = $otroGanador === 1 ? 2 : 1;
                                $otroGrupos = DB::table('grupos')
                                    ->where('partido_id', $otroPartidoId)
                                    ->where('torneo_id', $torneoId)
                                    ->where('zona', $zona)
                                    ->orderBy('id')
                                    ->get();
                                
                                if ($otroGrupos->count() >= 2) {
                                    $otroGrupoPerdedor = $otroGrupos[$otroPerdedor - 1];
                                    if ($esPartidoA && !$perdedorB) {
                                        $perdedorB = ['jugador_1' => $otroGrupoPerdedor->jugador_1, 'jugador_2' => $otroGrupoPerdedor->jugador_2];
                                        \Log::info('Obtenido perdedor B del otro partido: ' . $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2']);
                                    } else if ($esPartidoB && !$perdedorA) {
                                        $perdedorA = ['jugador_1' => $otroGrupoPerdedor->jugador_1, 'jugador_2' => $otroGrupoPerdedor->jugador_2];
                                        \Log::info('Obtenido perdedor A del otro partido: ' . $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2']);
                                    }
                                }
                            }
                        } else {
                            \Log::info('El otro partido (ID: ' . $otroPartidoId . ') aún no tiene resultados para perdedor');
                        }
                    }
                }
            }
            
            // Actualizar grupos del partido Perdedor
            if ($gruposPerdedor->count() >= 1) {
                $gruposPerdedorArray = $gruposPerdedor->values();
                
                // Si solo hay 1 grupo y necesitamos 2, crear el segundo grupo
                if ($gruposPerdedor->count() == 1 && $perdedorB) {
                    // Crear el segundo grupo para el partido Perdedor
                    $segundoGrupoPerdedor = DB::table('grupos')->insertGetId([
                        'torneo_id' => $torneoId,
                        'zona' => 'perdedor ' . $zona,
                        'fecha' => $gruposPerdedorArray[0]->fecha,
                        'horario' => $gruposPerdedorArray[0]->horario,
                        'jugador_1' => 0,
                        'jugador_2' => 0,
                        'partido_id' => $partidoPerdedor->partido_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    \Log::info('Creado segundo grupo Perdedor: ID ' . $segundoGrupoPerdedor);
                    // Recargar los grupos
                    $gruposPerdedor = DB::table('grupos')
                        ->where('partido_id', $partidoPerdedor->partido_id)
                        ->where('torneo_id', $torneoId)
                        ->where('zona', 'perdedor ' . $zona)
                        ->orderBy('id')
                        ->get();
                    $gruposPerdedorArray = $gruposPerdedor->values();
                }
                
                // Actualizar el primer grupo si tenemos perdedorA (o si solo hay 1 grupo y es Partido A)
                if ($perdedorA) {
                    $grupoId = $gruposPerdedorArray[0]->id;
                    $filasActualizadas = DB::table('grupos')
                        ->where('id', $grupoId)
                        ->update([
                            'jugador_1' => $perdedorA['jugador_1'],
                            'jugador_2' => $perdedorA['jugador_2']
                        ]);
                    \Log::info('Actualizado grupo Perdedor (1): ID ' . $grupoId . ' con jugadores ' . $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2'] . ' - Filas actualizadas: ' . $filasActualizadas);
                    $debugInfo['actualizacionPerdedor1'] = [
                        'grupoId' => $grupoId,
                        'jugadores' => $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2'],
                        'filasActualizadas' => $filasActualizadas
                    ];
                }
                
                // Actualizar el segundo grupo si tenemos perdedorB (solo si hay 2 grupos)
                if ($perdedorB && $gruposPerdedorArray->count() >= 2) {
                    $grupoId = $gruposPerdedorArray[1]->id;
                    $filasActualizadas = DB::table('grupos')
                        ->where('id', $grupoId)
                        ->update([
                            'jugador_1' => $perdedorB['jugador_1'],
                            'jugador_2' => $perdedorB['jugador_2']
                        ]);
                    \Log::info('Actualizado grupo Perdedor (2): ID ' . $grupoId . ' con jugadores ' . $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2'] . ' - Filas actualizadas: ' . $filasActualizadas);
                    $debugInfo['actualizacionPerdedor2'] = [
                        'grupoId' => $grupoId,
                        'jugadores' => $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2'],
                        'filasActualizadas' => $filasActualizadas
                    ];
                }
            }
        }
        
        // Recopilar información de debug
        $debugInfo = [];
        $debugInfo['esPartidoA'] = $esPartidoA;
        $debugInfo['esPartidoB'] = $esPartidoB;
        $debugInfo['partidoAId'] = $partidoAId;
        $debugInfo['partidoBId'] = $partidoBId;
        $debugInfo['ganadorJugadores'] = $ganadorJugador1 . '/' . $ganadorJugador2;
        $debugInfo['perdedorJugadores'] = $perdedorJugador1 . '/' . $perdedorJugador2;
        $debugInfo['partidoGanadorId'] = $partidoGanador ? $partidoGanador->partido_id : null;
        $debugInfo['partidoPerdedorId'] = $partidoPerdedor ? $partidoPerdedor->partido_id : null;
        $debugInfo['ganadorA'] = $ganadorA;
        $debugInfo['ganadorB'] = $ganadorB;
        $debugInfo['perdedorA'] = $perdedorA;
        $debugInfo['perdedorB'] = $perdedorB;
        $debugInfo['gruposGanadorCount'] = isset($gruposGanador) ? $gruposGanador->count() : 0;
        $debugInfo['gruposPerdedorCount'] = isset($gruposPerdedor) ? $gruposPerdedor->count() : 0;
        
        // Verificar qué se actualizó realmente
        $actualizacionesRealizadas = [];
        if (isset($gruposGanador) && $gruposGanador->count() >= 2) {
            $gruposGanadorArray = $gruposGanador->values();
            if ($ganadorA) {
                $actualizacionesRealizadas[] = 'Ganador grupo 1: ' . $ganadorA['jugador_1'] . '/' . $ganadorA['jugador_2'];
            }
            if ($ganadorB) {
                $actualizacionesRealizadas[] = 'Ganador grupo 2: ' . $ganadorB['jugador_1'] . '/' . $ganadorB['jugador_2'];
            }
        }
        if (isset($gruposPerdedor) && $gruposPerdedor->count() >= 2) {
            $gruposPerdedorArray = $gruposPerdedor->values();
            if ($perdedorA) {
                $actualizacionesRealizadas[] = 'Perdedor grupo 1: ' . $perdedorA['jugador_1'] . '/' . $perdedorA['jugador_2'];
            }
            if ($perdedorB) {
                $actualizacionesRealizadas[] = 'Perdedor grupo 2: ' . $perdedorB['jugador_1'] . '/' . $perdedorB['jugador_2'];
            }
        }
        $debugInfo['actualizacionesRealizadas'] = $actualizacionesRealizadas;
        
        // Retornar información de debug junto con el flag de recargar
        // Si se actualizó al menos un grupo, marcar como actualizado
        $seActualizo = false;
        if (isset($debugInfo['actualizacionGanador1']) || isset($debugInfo['actualizacionGanador2']) || 
            isset($debugInfo['actualizacionPerdedor1']) || isset($debugInfo['actualizacionPerdedor2'])) {
            $seActualizo = true;
        } else {
            // Fallback: verificar si tenemos ganadores/perdedores asignados
            $seActualizo = ($ganadorA || $ganadorB) || ($perdedorA || $perdedorB);
        }
        $debugInfo['seActualizo'] = $seActualizo;
        
        // Guardar debugInfo en el log también
        \Log::info('Debug info actualizarPartidosGanadorPerdedor: ' . json_encode($debugInfo));
        
        return ['actualizado' => $seActualizo, 'debug' => $debugInfo];
    }
    
    /**
     * Determina el ganador de un partido basándose en los sets
     * Retorna 1 si ganó pareja_1, 2 si ganó pareja_2
     */
    private function determinarGanadorPartido($partido) {
        $setsGanadosP1 = 0;
        $setsGanadosP2 = 0;
        
        // Set 1
        if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) {
            $setsGanadosP2++;
        }
        
        // Set 2
        if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) {
            $setsGanadosP1++;
        } else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) {
            $setsGanadosP2++;
        }
        
        // Set 3 (si existe)
        if ($partido->pareja_1_set_3 > 0 || $partido->pareja_2_set_3 > 0) {
            if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                $setsGanadosP1++;
            } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                $setsGanadosP2++;
            }
        }
        
        // Si hay empate en sets, usar super tie break
        if ($setsGanadosP1 == $setsGanadosP2) {
            if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                return 1;
            } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                return 2;
            }
        }
        
        return $setsGanadosP1 > $setsGanadosP2 ? 1 : 2;
    }
    
    private function crearSemifinalesPuntuable($torneoId) {
        // Obtener todos los partidos de cuartos con resultados completos
        $partidosCuartos = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'cuartos final')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0)
                      ->orWhere('partidos.pareja_1_set_super_tie_break', '>', 0)
                      ->orWhere('partidos.pareja_2_set_super_tie_break', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1', 
                    'partidos.pareja_1_set_2', 'partidos.pareja_2_set_2',
                    'partidos.pareja_1_set_3', 'partidos.pareja_2_set_3',
                    'partidos.pareja_1_set_super_tie_break', 'partidos.pareja_2_set_super_tie_break')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Verificar si ya existen semifinales
        $semifinalesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->count();
        
        if ($semifinalesExistentes > 0) {
            return; // Ya existen semifinales
        }
        
        // Para 12 parejas (4 grupos de 3): Semifinales = (1A/2C) vs (1C/2A) y (1B/2D) vs (1D/2B)
        // Obtener los ganadores de cada cuarto
        $ganadoresCuartos = [];
        foreach ($partidosCuartos as $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'cuartos final')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                // Determinar ganador basado en sets
                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;
                
                if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) $setsGanadosP1++;
                else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) $setsGanadosP1++;
                else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0) {
                    if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                        $setsGanadosP1 = 2;
                        $setsGanadosP2 = 1;
                    } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2;
                    }
                } else if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                    $setsGanadosP1++;
                } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                    $setsGanadosP2++;
                }
                
                $ganador = ($setsGanadosP1 > $setsGanadosP2) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                $ganadoresCuartos[] = $ganador;
            }
        }
        
        // Si tenemos 4 ganadores de cuartos, crear las semifinales
        // SF1: Ganador cuarto 1 (1A-2C) vs Ganador cuarto 3 (1C-2A)
        // SF2: Ganador cuarto 2 (1B-2D) vs Ganador cuarto 4 (1D-2B)
        if (count($ganadoresCuartos) == 4) {
            // Verificar si ya existen estas semifinales
            $existeSF1 = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->where(function($q) use ($ganadoresCuartos) {
                    $q->where(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[0]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[0]['jugador_2']);
                    })
                    ->orWhere(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[2]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[2]['jugador_2']);
                    });
                })
                ->exists();
            
            if (!$existeSF1) {
                // Crear Semifinal 1: Ganador cuarto 1 vs Ganador cuarto 3
                $partidoSF1 = $this->crearPartido();
                
                $grupoSF1_P1 = new Grupo;
                $grupoSF1_P1->torneo_id = $torneoId;
                $grupoSF1_P1->zona = 'semifinal';
                $grupoSF1_P1->fecha = '2000-01-01';
                $grupoSF1_P1->horario = '00:00';
                $grupoSF1_P1->jugador_1 = $ganadoresCuartos[0]['jugador_1'];
                $grupoSF1_P1->jugador_2 = $ganadoresCuartos[0]['jugador_2'];
                $grupoSF1_P1->partido_id = $partidoSF1->id;
                $grupoSF1_P1->save();
                
                $grupoSF1_P2 = new Grupo;
                $grupoSF1_P2->torneo_id = $torneoId;
                $grupoSF1_P2->zona = 'semifinal';
                $grupoSF1_P2->fecha = '2000-01-01';
                $grupoSF1_P2->horario = '00:00';
                $grupoSF1_P2->jugador_1 = $ganadoresCuartos[2]['jugador_1'];
                $grupoSF1_P2->jugador_2 = $ganadoresCuartos[2]['jugador_2'];
                $grupoSF1_P2->partido_id = $partidoSF1->id;
                $grupoSF1_P2->save();
            }
            
            // Verificar si ya existe SF2
            $existeSF2 = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->where(function($q) use ($ganadoresCuartos) {
                    $q->where(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[1]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[1]['jugador_2']);
                    })
                    ->orWhere(function($q2) use ($ganadoresCuartos) {
                        $q2->where('jugador_1', $ganadoresCuartos[3]['jugador_1'])
                           ->where('jugador_2', $ganadoresCuartos[3]['jugador_2']);
                    });
                })
                ->exists();
            
            if (!$existeSF2) {
                // Crear Semifinal 2: Ganador cuarto 2 vs Ganador cuarto 4
                $partidoSF2 = $this->crearPartido();
                
                $grupoSF2_P1 = new Grupo;
                $grupoSF2_P1->torneo_id = $torneoId;
                $grupoSF2_P1->zona = 'semifinal';
                $grupoSF2_P1->fecha = '2000-01-01';
                $grupoSF2_P1->horario = '00:00';
                $grupoSF2_P1->jugador_1 = $ganadoresCuartos[1]['jugador_1'];
                $grupoSF2_P1->jugador_2 = $ganadoresCuartos[1]['jugador_2'];
                $grupoSF2_P1->partido_id = $partidoSF2->id;
                $grupoSF2_P1->save();
                
                $grupoSF2_P2 = new Grupo;
                $grupoSF2_P2->torneo_id = $torneoId;
                $grupoSF2_P2->zona = 'semifinal';
                $grupoSF2_P2->fecha = '2000-01-01';
                $grupoSF2_P2->horario = '00:00';
                $grupoSF2_P2->jugador_1 = $ganadoresCuartos[3]['jugador_1'];
                $grupoSF2_P2->jugador_2 = $ganadoresCuartos[3]['jugador_2'];
                $grupoSF2_P2->partido_id = $partidoSF2->id;
                $grupoSF2_P2->save();
            }
        }
    }
    
    private function crearFinalPuntuable($torneoId) {
        // Obtener todos los partidos de semifinales con resultados completos
        $partidosSemifinales = DB::table('partidos')
            ->join('grupos', 'partidos.id', '=', 'grupos.partido_id')
            ->where('grupos.torneo_id', $torneoId)
            ->where('grupos.zona', 'semifinal')
            ->where(function($query) {
                $query->where('partidos.pareja_1_set_1', '>', 0)
                      ->orWhere('partidos.pareja_2_set_1', '>', 0)
                      ->orWhere('partidos.pareja_1_set_super_tie_break', '>', 0)
                      ->orWhere('partidos.pareja_2_set_super_tie_break', '>', 0);
            })
            ->select('partidos.id', 'partidos.pareja_1_set_1', 'partidos.pareja_2_set_1', 
                    'partidos.pareja_1_set_2', 'partidos.pareja_2_set_2',
                    'partidos.pareja_1_set_3', 'partidos.pareja_2_set_3',
                    'partidos.pareja_1_set_super_tie_break', 'partidos.pareja_2_set_super_tie_break')
            ->distinct()
            ->orderBy('partidos.id')
            ->get();
        
        // Verificar si ya existe la final
        $finalExiste = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'final')
            ->whereNotNull('partido_id')
            ->count();
        
        if ($finalExiste > 0) {
            return; // Ya existe la final
        }
        
        // Obtener los ganadores de cada semifinal
        $ganadoresSemifinales = [];
        foreach ($partidosSemifinales as $partido) {
            $gruposPartido = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'semifinal')
                ->orderBy('id')
                ->get();
            
            if ($gruposPartido->count() >= 2) {
                $g1 = $gruposPartido[0];
                $g2 = $gruposPartido[1];
                
                // Determinar ganador basado en sets
                $setsGanadosP1 = 0;
                $setsGanadosP2 = 0;
                
                if ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) $setsGanadosP1++;
                else if ($partido->pareja_2_set_1 > $partido->pareja_1_set_1) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_2 > $partido->pareja_2_set_2) $setsGanadosP1++;
                else if ($partido->pareja_2_set_2 > $partido->pareja_1_set_2) $setsGanadosP2++;
                
                if ($partido->pareja_1_set_super_tie_break > 0 || $partido->pareja_2_set_super_tie_break > 0) {
                    if ($partido->pareja_1_set_super_tie_break > $partido->pareja_2_set_super_tie_break) {
                        $setsGanadosP1 = 2;
                        $setsGanadosP2 = 1;
                    } else if ($partido->pareja_2_set_super_tie_break > $partido->pareja_1_set_super_tie_break) {
                        $setsGanadosP1 = 1;
                        $setsGanadosP2 = 2;
                    }
                } else if ($partido->pareja_1_set_3 > $partido->pareja_2_set_3) {
                    $setsGanadosP1++;
                } else if ($partido->pareja_2_set_3 > $partido->pareja_1_set_3) {
                    $setsGanadosP2++;
                }
                
                $ganador = ($setsGanadosP1 > $setsGanadosP2) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                $ganadoresSemifinales[] = $ganador;
            }
        }
        
        // Si tenemos 2 ganadores de semifinales, crear la final
        if (count($ganadoresSemifinales) == 2) {
            $partidoFinal = $this->crearPartido();
            
            $grupoFinal_P1 = new Grupo;
            $grupoFinal_P1->torneo_id = $torneoId;
            $grupoFinal_P1->zona = 'final';
            $grupoFinal_P1->fecha = '2000-01-01';
            $grupoFinal_P1->horario = '00:00';
            $grupoFinal_P1->jugador_1 = $ganadoresSemifinales[0]['jugador_1'];
            $grupoFinal_P1->jugador_2 = $ganadoresSemifinales[0]['jugador_2'];
            $grupoFinal_P1->partido_id = $partidoFinal->id;
            $grupoFinal_P1->save();
            
            $grupoFinal_P2 = new Grupo;
            $grupoFinal_P2->torneo_id = $torneoId;
            $grupoFinal_P2->zona = 'final';
            $grupoFinal_P2->fecha = '2000-01-01';
            $grupoFinal_P2->horario = '00:00';
            $grupoFinal_P2->jugador_1 = $ganadoresSemifinales[1]['jugador_1'];
            $grupoFinal_P2->jugador_2 = $ganadoresSemifinales[1]['jugador_2'];
            $grupoFinal_P2->partido_id = $partidoFinal->id;
            $grupoFinal_P2->save();
        }
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

    public function confirmarCruces(Request $request) {
        $torneoId = $request->torneo_id;
        $cruces = json_decode($request->cruces, true);
        
        if (!$cruces || !is_array($cruces)) {
            return response()->json(['success' => false, 'message' => 'Datos de cruces inválidos']);
        }
        
        // Eliminar cruces de cuartos existentes para este torneo
        $gruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->get();
        
        $partidosIds = $gruposCuartos->pluck('partido_id')->unique();
        if ($partidosIds->count() > 0) {
            DB::table('partidos')->whereIn('id', $partidosIds)->delete();
            DB::table('grupos')->whereIn('partido_id', $partidosIds)->delete();
        }
        
        // Crear los nuevos cruces de cuartos
        foreach ($cruces as $cruce) {
            if (!isset($cruce['pareja_1']) || !isset($cruce['pareja_2'])) {
                continue;
            }
            
            $pareja1 = $cruce['pareja_1'];
            $pareja2 = $cruce['pareja_2'];
            
            // Crear partido
            $partido = $this->crearPartido();
            
            // Crear grupo para pareja 1
            $grupo1 = new Grupo;
            $grupo1->torneo_id = $torneoId;
            $grupo1->zona = 'cuartos final';
            $grupo1->fecha = '2000-01-01';
            $grupo1->horario = '00:00';
            $grupo1->jugador_1 = $pareja1['jugador_1'];
            $grupo1->jugador_2 = $pareja1['jugador_2'];
            $grupo1->partido_id = $partido->id;
            $grupo1->save();
            
            // Crear grupo para pareja 2
            $grupo2 = new Grupo;
            $grupo2->torneo_id = $torneoId;
            $grupo2->zona = 'cuartos final';
            $grupo2->fecha = '2000-01-01';
            $grupo2->horario = '00:00';
            $grupo2->jugador_1 = $pareja2['jugador_1'];
            $grupo2->jugador_2 = $pareja2['jugador_2'];
            $grupo2->partido_id = $partido->id;
            $grupo2->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Cruces confirmados correctamente',
            'torneo_id' => $torneoId
        ]);
    }

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

    public function tvTorneoAmericanoCruces(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // PRIMERO: Obtener todos los partidos eliminatorios existentes directamente de la base de datos
        $cruces = [];
        $resultadosGuardados = [];
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Incluir zonas que comienzan con "cuartos final|" además de las exactas
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        // IMPORTANTE: Si hay múltiples grupos con el mismo partido_id, solo tomar los primeros 2
        // para evitar duplicados cuando se actualizan las parejas
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                // Normalizar la zona: si tiene "|", usar solo "cuartos final" para la agrupación
                $zonaNormalizada = $grupo->zona;
                if (strpos($zonaNormalizada, '|') !== false) {
                    $zonaNormalizada = 'cuartos final';
                }
                $partidosAgrupados[$partidoId] = [
                    'zona' => $zonaNormalizada,
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            // Solo agregar si no hay ya 2 grupos (pareja_1 y pareja_2)
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
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
        $crucesPorRonda = [
            'cuartos' => [],
            'semifinales' => [],
            'final' => []
        ];
        
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
                    'ronda' => $ronda
                ];
                
                $crucesPorRonda[$ronda][] = $cruce;
                $cruces[] = $cruce;
                
                // Guardar resultado si existe
                if ($partido && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0)) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruce['id'], // Agregar el ID del cruce para facilitar la búsqueda en la vista
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
        }
        
        // Calcular posiciones de cada zona para mostrar en la vista (necesario para clasificados)
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Calcular posiciones de cada zona y clasificados (siempre necesario para la vista)
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
        
        // Calcular clasificados para pasarlos a la vista (siempre necesario)
        $clasificados = [];
        $zonasArray = $zonas->toArray();
        
        // Clasificar los primeros de cada grupo
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
        
        // Obtener segundos y terceros por zona (necesario para completar clasificados)
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
        
        // Completar clasificados según el formato del torneo
        $zonasOrdenadasArray = $zonasArray;
        sort($zonasOrdenadasArray);
        if (count($zonasOrdenadasArray) == 3) {
            // 3 zonas: agregar A2, B2, C2 y 2 mejores terceros
            foreach ($zonasOrdenadasArray as $zona) {
                if (isset($segundosPorZona[$zona])) {
                    $clasificados[] = $segundosPorZona[$zona];
                }
            }
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
            for ($i = 0; $i < min(2, count($terceros)); $i++) {
                $clasificados[] = $terceros[$i];
            }
        } else {
            // Lógica estándar para otros casos
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
        
        // Si no hay cruces de cuartos en la base de datos, generarlos desde los clasificados
        if (count($crucesPorRonda['cuartos']) == 0) {
            // Armar los cruces según las reglas estándar
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
            
            usort($tercerosFinal, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            
            $crucesCuartos = [];
            $totalClasificados = count($clasificados);
            $zonasOrdenadasFinal = array_keys($primerosPorZonaFinal);
            sort($zonasOrdenadasFinal);
            
            // Caso especial: 6 clasificados
            if ($totalClasificados == 6) {
                $primeros = [];
                $resto = [];
                
                foreach ($clasificados as $clasificado) {
                    if ($clasificado['posicion'] == 1) {
                        $primeros[] = $clasificado;
                    } else {
                        $resto[] = $clasificado;
                    }
                }
                
                $segundosPorZona = [];
                $tercerosPorZona = [];
                
                foreach ($resto as $pareja) {
                    if ($pareja['posicion'] == 2) {
                        $segundosPorZona[$pareja['zona']] = $pareja;
                    } else if ($pareja['posicion'] == 3) {
                        $tercerosPorZona[$pareja['zona']] = $pareja;
                    }
                }
                
                $zonasArray = array_keys($segundosPorZona + $tercerosPorZona);
                sort($zonasArray);
                
                if (count($zonasArray) >= 2) {
                    $zona1 = $zonasArray[0];
                    $zona2 = $zonasArray[1];
                    
                    if (isset($segundosPorZona[$zona1]) && isset($tercerosPorZona[$zona2])) {
                        $crucesCuartos[] = [
                            'pareja_1' => $segundosPorZona[$zona1],
                            'pareja_2' => $tercerosPorZona[$zona2],
                            'ronda' => 'cuartos'
                        ];
                    }
                    
                    if (isset($segundosPorZona[$zona2]) && isset($tercerosPorZona[$zona1])) {
                        $crucesCuartos[] = [
                            'pareja_1' => $segundosPorZona[$zona2],
                            'pareja_2' => $tercerosPorZona[$zona1],
                            'ronda' => 'cuartos'
                        ];
                    }
                } else {
                    if (count($resto) >= 2) {
                        for ($i = 0; $i < count($resto) - 1; $i += 2) {
                            if (isset($resto[$i + 1])) {
                                $crucesCuartos[] = [
                                    'pareja_1' => $resto[$i],
                                    'pareja_2' => $resto[$i + 1],
                                    'ronda' => 'cuartos'
                                ];
                            }
                        }
                    }
                }
            } else if ($totalClasificados == 8 && count($zonasOrdenadasFinal) == 3) {
                $zonaA = $zonasOrdenadasFinal[0];
                $zonaB = $zonasOrdenadasFinal[1];
                $zonaC = $zonasOrdenadasFinal[2];
                
                if (isset($primerosPorZonaFinal[$zonaA]) && count($tercerosFinal) > 0) {
                    $crucesCuartos[] = [
                        'pareja_1' => $primerosPorZonaFinal[$zonaA],
                        'pareja_2' => $tercerosFinal[0],
                        'ronda' => 'cuartos'
                    ];
                }
                
                if (isset($primerosPorZonaFinal[$zonaB]) && count($tercerosFinal) > 1) {
                    $crucesCuartos[] = [
                        'pareja_1' => $primerosPorZonaFinal[$zonaB],
                        'pareja_2' => $tercerosFinal[1],
                        'ronda' => 'cuartos'
                    ];
                }
                
                if (isset($primerosPorZonaFinal[$zonaC]) && isset($segundosPorZonaFinal[$zonaA])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $primerosPorZonaFinal[$zonaC],
                        'pareja_2' => $segundosPorZonaFinal[$zonaA],
                        'ronda' => 'cuartos'
                    ];
                }
                
                if (isset($segundosPorZonaFinal[$zonaB]) && isset($segundosPorZonaFinal[$zonaC])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $segundosPorZonaFinal[$zonaB],
                        'pareja_2' => $segundosPorZonaFinal[$zonaC],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                $primeros = [];
                $resto = [];
                
                foreach ($clasificados as $clasificado) {
                    if ($clasificado['posicion'] == 1) {
                        $primeros[] = $clasificado;
                    } else {
                        $resto[] = $clasificado;
                    }
                }
                
                $primerosUsados = [];
                $restoUsados = [];
                
                $mitad = ceil(count($primeros) / 2);
                $primerosSuperior = array_slice($primeros, 0, $mitad);
                $primerosInferior = array_slice($primeros, $mitad);
                
                foreach ($primerosSuperior as $primero) {
                    $encontrado = false;
                    foreach ($resto as $index => $r) {
                        if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                            $crucesCuartos[] = [
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
                            $crucesCuartos[] = [
                                'pareja_1' => $primero,
                                'pareja_2' => $resto[$index],
                                'ronda' => 'cuartos'
                            ];
                            $restoUsados[] = $index;
                        }
                    }
                }
                
                foreach ($primerosInferior as $primero) {
                    $encontrado = false;
                    foreach ($resto as $index => $r) {
                        if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                            $crucesCuartos[] = [
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
                            $crucesCuartos[] = [
                                'pareja_1' => $primero,
                                'pareja_2' => $resto[$index],
                                'ronda' => 'cuartos'
                            ];
                            $restoUsados[] = $index;
                        }
                    }
                }
                
                $restantes = [];
                foreach ($resto as $index => $r) {
                    if (!in_array($index, $restoUsados)) {
                        $restantes[] = $r;
                    }
                }
                if (count($restantes) >= 2) {
                    for ($i = 0; $i < count($restantes) - 1; $i += 2) {
                        $crucesCuartos[] = [
                            'pareja_1' => $restantes[$i],
                            'pareja_2' => $restantes[$i + 1],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
            }
            
            // Agregar los cruces de cuartos generados a los cruces existentes
            $cruces = array_merge($crucesPorRonda['cuartos'], $crucesCuartos, $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        } else {
            // Si ya hay cruces de cuartos en la base de datos, usar todos los cruces existentes
            $cruces = array_merge($crucesPorRonda['cuartos'], $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        }
        
        // Separar primeros para pasarlos a la vista (necesario para el caso de 6 clasificados)
        $primerosClasificados = [];
        foreach ($clasificados as $clasificado) {
            if ($clasificado['posicion'] == 1) {
                $primerosClasificados[] = $clasificado;
            }
        }
        
        return View('bahia_padel.tv.cruces_americano')
                    ->with('torneo', $torneo)
                    ->with('jugadores', $jugadores)
                    ->with('clasificados', $clasificados)
                    ->with('cruces', $cruces)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('resultadosGuardados', $resultadosGuardados)
                    ->with('primerosClasificados', $primerosClasificados)
                    ->with('totalClasificados', count($clasificados));
    }
    
    public function tvTorneoAmericanoCrucesActualizar(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Incluir zonas que comienzan con "cuartos final|" además de las exactas
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        // IMPORTANTE: Si hay múltiples grupos con el mismo partido_id, solo tomar los primeros 2
        // para evitar duplicados cuando se actualizan las parejas
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                // Normalizar la zona: si tiene "|", usar solo "cuartos final" para la agrupación
                $zonaNormalizada = $grupo->zona;
                if (strpos($zonaNormalizada, '|') !== false) {
                    $zonaNormalizada = 'cuartos final';
                }
                $partidosAgrupados[$partidoId] = [
                    'zona' => $zonaNormalizada,
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            // Solo agregar si no hay ya 2 grupos (pareja_1 y pareja_2)
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
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
        
        // Construir resultados guardados
        $resultadosGuardados = [];
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2 && isset($partidos[$partidoId])) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId];
                
                // Determinar la ronda según la zona
                $ronda = 'cuartos';
                if ($datosPartido['zona'] === 'semifinal') {
                    $ronda = 'semifinales';
                } else if ($datosPartido['zona'] === 'final') {
                    $ronda = 'final';
                }
                
                $cruceId = $ronda . '_' . $partidoId;
                
                if ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruceId,
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
        }
        
        return response()->json([
            'success' => true,
            'resultadosGuardados' => $resultadosGuardados
        ]);
    }
    
    public function tvTorneoAmericanoSorteo(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('index')->with('error', 'Torneo no encontrado');
        }
        
        // Obtener grupos iniciales (sin partido_id) para mostrar el sorteo
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNull('grupos.partido_id') // Solo grupos iniciales
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->whereNotNull('grupos.jugador_1')
                        ->whereNotNull('grupos.jugador_2')
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Agrupar por zona
        $gruposPorZona = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($gruposPorZona[$zona])) {
                $gruposPorZona[$zona] = [];
            }
            $gruposPorZona[$zona][] = $grupo;
        }
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->keyBy('id');
        
        return View('bahia_padel.tv.sorteo_americano')
                    ->with('torneo', $torneo)
                    ->with('gruposPorZona', $gruposPorZona)
                    ->with('jugadores', $jugadores);
    }
    
    public function tvTorneoAmericanoSorteoActualizar(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return response()->json(['success' => false, 'message' => 'Torneo no encontrado'], 404);
        }
        
        // Obtener grupos iniciales (sin partido_id) para mostrar el sorteo
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNull('grupos.partido_id')
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->where(function($query) {
                            $query->whereNotNull('grupos.jugador_1')
                                  ->whereNotNull('grupos.jugador_2')
                                  ->where('grupos.jugador_1', '!=', 0)
                                  ->where('grupos.jugador_2', '!=', 0);
                        })
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Obtener todos los jugadores necesarios
        $jugadoresIds = [];
        foreach ($grupos as $grupo) {
            if ($grupo->jugador_1) $jugadoresIds[] = $grupo->jugador_1;
            if ($grupo->jugador_2) $jugadoresIds[] = $grupo->jugador_2;
        }
        $jugadoresIds = array_unique($jugadoresIds);
        
        $jugadores = [];
        if (count($jugadoresIds) > 0) {
            $jugadoresData = DB::table('jugadores')
                                ->whereIn('id', $jugadoresIds)
                                ->where('activo', 1)
                                ->get();
            
            foreach ($jugadoresData as $jugador) {
                $jugadores[$jugador->id] = [
                    'id' => $jugador->id,
                    'nombre' => $jugador->nombre,
                    'apellido' => $jugador->apellido,
                    'foto' => $jugador->foto
                ];
            }
        }
        
        // Agrupar por zona
        $gruposPorZona = [];
        foreach ($grupos as $grupo) {
            $zona = $grupo->zona;
            if (!isset($gruposPorZona[$zona])) {
                $gruposPorZona[$zona] = [];
            }
            $gruposPorZona[$zona][] = [
                'id' => $grupo->id,
                'zona' => $grupo->zona,
                'jugador_1' => $grupo->jugador_1,
                'jugador_2' => $grupo->jugador_2
            ];
        }
        
        return response()->json([
            'success' => true,
            'gruposPorZona' => $gruposPorZona,
            'jugadores' => $jugadores
        ]);
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
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get()
                        ->toArray();
        
        // PRIMERO: Obtener todos los partidos eliminatorios existentes directamente de la base de datos
        $cruces = [];
        $resultadosGuardados = [];
        
        // Obtener todos los grupos eliminatorios con sus partidos
        // Para cuartos, buscar también zonas que comiencen con "cuartos final"
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->orderBy('zona')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
        // Agrupar por partido_id
        // IMPORTANTE: Si hay múltiples grupos con el mismo partido_id, solo tomar los primeros 2
        // para evitar duplicados cuando se actualizan las parejas
        $partidosAgrupados = [];
        foreach ($gruposEliminatorios as $grupo) {
            $partidoId = $grupo->partido_id;
            if (!isset($partidosAgrupados[$partidoId])) {
                $partidosAgrupados[$partidoId] = [
                    'zona' => $grupo->zona, // Guardar zona completa con número de partido si existe
                    'partido_id' => $partidoId,
                    'grupos' => []
                ];
            }
            // Solo agregar si no hay ya 2 grupos (pareja_1 y pareja_2)
            if (count($partidosAgrupados[$partidoId]['grupos']) < 2) {
                $partidosAgrupados[$partidoId]['grupos'][] = $grupo;
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
        $crucesPorRonda = [
            'cuartos' => [],
            'semifinales' => [],
            'final' => []
        ];
        
        // Ordenar los partidos agrupados por partido_id para mantener orden consistente
        ksort($partidosAgrupados);
        
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                $partido = $partidos[$partidoId] ?? null;
                
                // Determinar la ronda según la zona
                $ronda = 'cuartos';
                if (strpos($datosPartido['zona'], 'semifinal') !== false) {
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
                    'ronda' => $ronda
                ];
                
                $crucesPorRonda[$ronda][] = $cruce;
                $cruces[] = $cruce;
                
                // Guardar resultado si existe
                if ($partido && ($partido->pareja_1_set_1 > 0 || $partido->pareja_2_set_1 > 0)) {
                    $resultadosGuardados[] = [
                        'partido_id' => $partidoId,
                        'cruce_id' => $cruce['id'], // Agregar el ID del cruce para facilitar la búsqueda en la vista
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
        }
        
        // Calcular posiciones de cada zona para mostrar en la vista (necesario para clasificados)
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
                        ->orderBy('grupos.zona')
                        ->orderBy('grupos.id')
                        ->get();
        
        // Calcular posiciones de cada zona y clasificados (siempre necesario para la vista)
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
        
        // Calcular clasificados para pasarlos a la vista (siempre necesario)
        $clasificados = [];
        $zonasArray = $zonas->toArray();
        
        // Verificar si hay grupos de 10 parejas totales: 2 grupos de 5 parejas cada uno
        $esGrupoDe10 = false;
        if (count($zonasArray) == 2) {
            $zona1 = $zonasArray[0];
            $zona2 = $zonasArray[1];
            // Dos zonas con 5 parejas cada una (total 10 parejas)
            if (isset($posicionesPorZona[$zona1]) && isset($posicionesPorZona[$zona2]) &&
                count($posicionesPorZona[$zona1]) == 5 && count($posicionesPorZona[$zona2]) == 5) {
                $esGrupoDe10 = true;
            }
            // O una zona con 10 parejas (cuando hay 2 zonas en total)
            elseif (isset($posicionesPorZona[$zona1]) && count($posicionesPorZona[$zona1]) == 10) {
                $esGrupoDe10 = true;
            }
        }
        
        // Si es grupo de 10 parejas totales con 2 zonas, clasificar los primeros 4 de cada grupo
        if ($esGrupoDe10 && count($zonasArray) == 2) {
            $zonasOrdenadasArray = $zonasArray;
            sort($zonasOrdenadasArray);
            
            foreach ($zonasOrdenadasArray as $zona) {
                if (isset($posicionesPorZona[$zona])) {
                    // Clasificar posiciones 1, 2, 3 y 4 (eliminar solo el último: posición 5 si es grupo de 5, o posición 10 si es grupo de 10)
                    for ($i = 0; $i < min(4, count($posicionesPorZona[$zona])); $i++) {
                        $clasificados[] = [
                            'zona' => $zona,
                            'posicion' => $i + 1,
                            'jugador_1' => $posicionesPorZona[$zona][$i]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][$i]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][$i]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][$i]['puntos_ganados']
                        ];
                    }
                }
            }
        } else {
            // Lógica original para otros casos
            // Clasificar los primeros de cada grupo
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
            
            // Obtener segundos y terceros por zona (necesario para completar clasificados)
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
            
            // Completar clasificados según el formato del torneo
            $zonasOrdenadasArray = $zonasArray;
            sort($zonasOrdenadasArray);
            if (count($zonasOrdenadasArray) == 3) {
                // 3 zonas: agregar A2, B2, C2 y 2 mejores terceros
                foreach ($zonasOrdenadasArray as $zona) {
                    if (isset($segundosPorZona[$zona])) {
                        $clasificados[] = $segundosPorZona[$zona];
                    }
                }
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
                for ($i = 0; $i < min(2, count($terceros)); $i++) {
                    $clasificados[] = $terceros[$i];
                }
            } else {
                // Lógica estándar para otros casos
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
        }
        
        // Si no hay cruces de cuartos en la base de datos, generarlos desde los clasificados
        if (count($crucesPorRonda['cuartos']) == 0) {
            // Verificar si es el caso especial de grupos de 10 parejas con 2 zonas (8 clasificados: 4 de cada zona)
            if ($esGrupoDe10 && count($zonasArray) == 2 && count($clasificados) == 8) {
                // Ordenar zonas (A y B)
                $zonasOrdenadasCruces = $zonasArray;
                sort($zonasOrdenadasCruces);
                $zonaA = $zonasOrdenadasCruces[0];
                $zonaB = $zonasOrdenadasCruces[1];
                
                // Organizar clasificados por zona y posición
                $clasificadosPorZonaPosicion = [];
                foreach ($clasificados as $clasificado) {
                    $clasificadosPorZonaPosicion[$clasificado['zona']][$clasificado['posicion']] = $clasificado;
                }
                
                // Crear cruces según el formato en este orden: A1-B4, B2-A3, A2-B3, B1-A4
                $crucesCuartos = [];
                // 1. A1 vs B4
                if (isset($clasificadosPorZonaPosicion[$zonaA][1]) && isset($clasificadosPorZonaPosicion[$zonaB][4])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaA][1],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaB][4],
                        'ronda' => 'cuartos'
                    ];
                }
                // 2. B2 vs A3
                if (isset($clasificadosPorZonaPosicion[$zonaB][2]) && isset($clasificadosPorZonaPosicion[$zonaA][3])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaB][2],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaA][3],
                        'ronda' => 'cuartos'
                    ];
                }
                // 3. A2 vs B3
                if (isset($clasificadosPorZonaPosicion[$zonaA][2]) && isset($clasificadosPorZonaPosicion[$zonaB][3])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaA][2],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaB][3],
                        'ronda' => 'cuartos'
                    ];
                }
                // 4. B1 vs A4
                if (isset($clasificadosPorZonaPosicion[$zonaB][1]) && isset($clasificadosPorZonaPosicion[$zonaA][4])) {
                    $crucesCuartos[] = [
                        'pareja_1' => $clasificadosPorZonaPosicion[$zonaB][1],
                        'pareja_2' => $clasificadosPorZonaPosicion[$zonaA][4],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                // Armar los cruces según las reglas estándar (lógica original)
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
                
                usort($tercerosFinal, function($a, $b) {
                    if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                        return $b['partidos_ganados'] - $a['partidos_ganados'];
                    }
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                });
                
                $crucesCuartos = [];
                $totalClasificados = count($clasificados);
                $zonasOrdenadasFinal = array_keys($primerosPorZonaFinal);
                sort($zonasOrdenadasFinal);
                
                // Caso especial: 6 clasificados
                if ($totalClasificados == 6) {
                    $primeros = [];
                    $resto = [];
                    
                    foreach ($clasificados as $clasificado) {
                        if ($clasificado['posicion'] == 1) {
                            $primeros[] = $clasificado;
                        } else {
                            $resto[] = $clasificado;
                        }
                    }
                    
                    $segundosPorZona = [];
                    $tercerosPorZona = [];
                    
                    foreach ($resto as $pareja) {
                        if ($pareja['posicion'] == 2) {
                            $segundosPorZona[$pareja['zona']] = $pareja;
                        } else if ($pareja['posicion'] == 3) {
                            $tercerosPorZona[$pareja['zona']] = $pareja;
                        }
                    }
                    
                    $zonasArray = array_keys($segundosPorZona + $tercerosPorZona);
                    sort($zonasArray);
                    
                    if (count($zonasArray) >= 2) {
                        $zona1 = $zonasArray[0];
                        $zona2 = $zonasArray[1];
                        
                        if (isset($segundosPorZona[$zona1]) && isset($tercerosPorZona[$zona2])) {
                            $crucesCuartos[] = [
                                'pareja_1' => $segundosPorZona[$zona1],
                                'pareja_2' => $tercerosPorZona[$zona2],
                                'ronda' => 'cuartos'
                            ];
                        }
                        
                        if (isset($segundosPorZona[$zona2]) && isset($tercerosPorZona[$zona1])) {
                            $crucesCuartos[] = [
                                'pareja_1' => $segundosPorZona[$zona2],
                                'pareja_2' => $tercerosPorZona[$zona1],
                                'ronda' => 'cuartos'
                            ];
                        }
                    } else {
                        if (count($resto) >= 2) {
                            for ($i = 0; $i < count($resto) - 1; $i += 2) {
                                if (isset($resto[$i + 1])) {
                                    $crucesCuartos[] = [
                                        'pareja_1' => $resto[$i],
                                        'pareja_2' => $resto[$i + 1],
                                        'ronda' => 'cuartos'
                                    ];
                                }
                            }
                        }
                    }
                } else if ($totalClasificados == 8 && count($zonasOrdenadasFinal) == 3) {
                    $zonaA = $zonasOrdenadasFinal[0];
                    $zonaB = $zonasOrdenadasFinal[1];
                    $zonaC = $zonasOrdenadasFinal[2];
                    
                    if (isset($primerosPorZonaFinal[$zonaA]) && count($tercerosFinal) > 0) {
                        $crucesCuartos[] = [
                            'pareja_1' => $primerosPorZonaFinal[$zonaA],
                            'pareja_2' => $tercerosFinal[0],
                            'ronda' => 'cuartos'
                        ];
                    }
                    
                    if (isset($primerosPorZonaFinal[$zonaB]) && count($tercerosFinal) > 1) {
                        $crucesCuartos[] = [
                            'pareja_1' => $primerosPorZonaFinal[$zonaB],
                            'pareja_2' => $tercerosFinal[1],
                            'ronda' => 'cuartos'
                        ];
                    }
                    
                    if (isset($primerosPorZonaFinal[$zonaC]) && isset($segundosPorZonaFinal[$zonaA])) {
                        $crucesCuartos[] = [
                            'pareja_1' => $primerosPorZonaFinal[$zonaC],
                            'pareja_2' => $segundosPorZonaFinal[$zonaA],
                            'ronda' => 'cuartos'
                        ];
                    }
                    
                    if (isset($segundosPorZonaFinal[$zonaB]) && isset($segundosPorZonaFinal[$zonaC])) {
                        $crucesCuartos[] = [
                            'pareja_1' => $segundosPorZonaFinal[$zonaB],
                            'pareja_2' => $segundosPorZonaFinal[$zonaC],
                            'ronda' => 'cuartos'
                        ];
                    }
                } else {
                    $primeros = [];
                    $resto = [];
                    
                    foreach ($clasificados as $clasificado) {
                        if ($clasificado['posicion'] == 1) {
                            $primeros[] = $clasificado;
                        } else {
                            $resto[] = $clasificado;
                        }
                    }
                    
                    $primerosUsados = [];
                    $restoUsados = [];
                    
                    $mitad = ceil(count($primeros) / 2);
                    $primerosSuperior = array_slice($primeros, 0, $mitad);
                    $primerosInferior = array_slice($primeros, $mitad);
                    
                    foreach ($primerosSuperior as $primero) {
                        $encontrado = false;
                        foreach ($resto as $index => $r) {
                            if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                                $crucesCuartos[] = [
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
                                $crucesCuartos[] = [
                                    'pareja_1' => $primero,
                                    'pareja_2' => $resto[$index],
                                    'ronda' => 'cuartos'
                                ];
                                $restoUsados[] = $index;
                            }
                        }
                    }
                    
                    foreach ($primerosInferior as $primero) {
                        $encontrado = false;
                        foreach ($resto as $index => $r) {
                            if (!in_array($index, $restoUsados) && $r['zona'] != $primero['zona']) {
                                $crucesCuartos[] = [
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
                                $crucesCuartos[] = [
                                    'pareja_1' => $primero,
                                    'pareja_2' => $resto[$index],
                                    'ronda' => 'cuartos'
                                ];
                                $restoUsados[] = $index;
                            }
                        }
                    }
                    
                    $restantes = [];
                    foreach ($resto as $index => $r) {
                        if (!in_array($index, $restoUsados)) {
                            $restantes[] = $r;
                        }
                    }
                    if (count($restantes) >= 2) {
                        for ($i = 0; $i < count($restantes) - 1; $i += 2) {
                            $crucesCuartos[] = [
                                'pareja_1' => $restantes[$i],
                                'pareja_2' => $restantes[$i + 1],
                                'ronda' => 'cuartos'
                            ];
                        }
                    }
                }
            }
            
            // Agregar los cruces de cuartos generados a los cruces existentes
            // Ordenar cada ronda por partido_id para mantener orden consistente
            usort($crucesPorRonda['cuartos'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            
            $cruces = array_merge($crucesPorRonda['cuartos'], $crucesCuartos, $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        } else {
            // Si ya hay cruces de cuartos en la base de datos, usar todos los cruces existentes
            // Ordenar cada ronda por partido_id para mantener orden consistente
            usort($crucesPorRonda['cuartos'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            usort($crucesPorRonda['semifinales'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            usort($crucesPorRonda['final'], function($a, $b) {
                return ($a['partido_id'] ?? 0) <=> ($b['partido_id'] ?? 0);
            });
            
            $cruces = array_merge($crucesPorRonda['cuartos'], $crucesPorRonda['semifinales'], $crucesPorRonda['final']);
        }
        
        // Verificar si todos los cuartos tienen resultados antes de mostrar semifinales
        // Buscar directamente en la base de datos todos los partidos de cuartos
        $gruposCuartos = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->where('zona', 'cuartos final')
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->select('partido_id')
            ->distinct()
            ->pluck('partido_id');
        
        $cuartosCompletos = true;
        
        if (count($gruposCuartos) > 0) {
            // Obtener todos los partidos de cuartos
            $partidosCuartos = DB::table('partidos')
                ->whereIn('id', $gruposCuartos)
                ->get();
            
            // Verificar que todos los partidos de cuartos tengan resultados (al menos un set > 0)
            foreach ($partidosCuartos as $partido) {
                $set1Pareja1 = $partido->pareja_1_set_1 ?? 0;
                $set1Pareja2 = $partido->pareja_2_set_1 ?? 0;
                
                // Si ambos sets son 0, el partido no tiene resultado
                if ($set1Pareja1 == 0 && $set1Pareja2 == 0) {
                    $cuartosCompletos = false;
                    break;
                }
            }
        } else {
            // Si no hay partidos de cuartos en la BD, no están completos
            $cuartosCompletos = false;
        }
        
        // Si los cuartos no están completos, filtrar semifinales y final de los cruces
        if (!$cuartosCompletos) {
            $cruces = array_filter($cruces, function($cruce) {
                return isset($cruce['ronda']) && $cruce['ronda'] === 'cuartos';
            });
            // Reindexar el array
            $cruces = array_values($cruces);
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
                    ->with('totalClasificados', count($clasificados))
                    ->with('cuartosCompletos', $cuartosCompletos);
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
        $semifinal = $request->semifinal ?? null; // 'Semifinal 1' o 'Semifinal 2'
        
        // Mapear ronda a nombre de zona
        $zonaRonda = '';
        if ($ronda === 'cuartos') {
            $zonaRonda = 'cuartos final';
        } else if ($ronda === 'semifinales') {
            $zonaRonda = 'semifinal';
        } else if ($ronda === 'final') {
            $zonaRonda = 'final';
        }
        
        // Buscar si ya existe un partido eliminatorio con estas parejas
        // Para cuartos, buscar también por número de partido si está disponible
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
        
        // Para cuartos, buscar por zona que comience con "cuartos final"
        if ($ronda === 'cuartos') {
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
            
            // Para cuartos, buscar por zona que comience con "cuartos final"
            if ($ronda === 'cuartos') {
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
                    if ($ronda === 'cuartos' && strpos($grupo1Encontrado->zona, '|') === false) {
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
        
        // Si se guardó un resultado de cuartos, verificar si se pueden crear las semifinales automáticamente
        if ($ronda === 'cuartos') {
            // Guardar el valor de semifinal en la zona del grupo para poder recuperarlo después
            if ($semifinal && $partido) {
                // Actualizar la zona de los grupos de este partido para incluir la información de semifinal
                $zonaActualizada = 'cuartos final|' . trim($semifinal);
                $filasActualizadas = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'like', 'cuartos final%')
                    ->update(['zona' => $zonaActualizada]);
                
                \Log::info('Actualizando zona del partido ' . $partido->id . ' a: ' . $zonaActualizada . ' (filas actualizadas: ' . $filasActualizadas . ')');
                
                // Verificar que se actualizó correctamente
                $gruposVerificados = DB::table('grupos')
                    ->where('partido_id', $partido->id)
                    ->where('torneo_id', $torneoId)
                    ->where('zona', 'like', 'cuartos final%')
                    ->get();
                
                foreach ($gruposVerificados as $grupo) {
                    \Log::info('Grupo ' . $grupo->id . ' del partido ' . $partido->id . ' tiene zona: ' . $grupo->zona);
                }
            }
            
            return $this->crearSemifinalesSiEsNecesario($torneoId, $semifinal);            
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
     * Crea las semifinales automáticamente cuando se completan los cuartos necesarios
     */
    private function crearSemifinalesSiEsNecesario($torneoId, $semifinalActual = null) {
        // Buscar todos los partidos de cuartos con resultados en la tabla grupos
        // Solo buscar en grupos donde zona es "cuartos final" (o "cuartos final|Partido X")
        
        // Obtener todos los partidos de cuartos con resultados
        // Primero obtener los IDs de partidos únicos
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
        
        foreach ($partidosCuartosOrdenados as $partido) {
            // Obtener ambos grupos del partido directamente
            $gruposCompletos = DB::table('grupos')
                ->where('partido_id', $partido->id)
                ->where('torneo_id', $torneoId)
                ->where('zona', 'like', 'cuartos final%')
                ->orderBy('id')
                ->get();
            
            if ($gruposCompletos->count() >= 2) {
                $g1 = $gruposCompletos[0];
                $g2 = $gruposCompletos[1];
                
                // Extraer la información de semifinal de la zona del primer grupo
                // (ambos grupos deberían tener la misma zona después de actualizar)
                $semifinalDelPartido = null;
                if (strpos($g1->zona, '|') !== false) {
                    $partes = explode('|', $g1->zona);
                    $semifinalDelPartido = isset($partes[1]) ? trim($partes[1]) : null;
                }
                
                \Log::info('Procesando partido ' . $partido->id . ', zona: ' . $g1->zona . ', semifinal extraída: ' . ($semifinalDelPartido ?? 'N/A'));
                
                // Determinar ganador según el resultado del partido
                $ganador = ($partido->pareja_1_set_1 > $partido->pareja_2_set_1) ? 
                    ['jugador_1' => $g1->jugador_1, 'jugador_2' => $g1->jugador_2] : 
                    ['jugador_1' => $g2->jugador_1, 'jugador_2' => $g2->jugador_2];
                
                // Agregar el ganador a la semifinal correspondiente
                // Priorizar la información de la zona sobre el valor actual
                if ($semifinalDelPartido && ($semifinalDelPartido == 'Semifinal 1' || $semifinalDelPartido == 'Semifinal 2')) {
                    $ganadoresPorSemifinal[$semifinalDelPartido][] = $ganador;
                    \Log::info('Ganador agregado a ' . $semifinalDelPartido . ' desde zona del partido');
                } else if ($semifinalActual && ($semifinalActual == 'Semifinal 1' || $semifinalActual == 'Semifinal 2')) {
                    // Si no hay información en la zona, usar el valor actual (solo para el partido que se acaba de guardar)
                    $ganadoresPorSemifinal[$semifinalActual][] = $ganador;
                    \Log::info('Ganador agregado a ' . $semifinalActual . ' desde parámetro actual');
                } else {
                    // Si no hay información de semifinal, no agregar el ganador
                    \Log::warning('No se pudo determinar la semifinal para el partido ' . $partido->id . '. Zona: ' . ($g1->zona ?? 'N/A') . ', Semifinal actual: ' . ($semifinalActual ?? 'N/A'));
                }
                
                \Log::info('Ganador cuarto (partido_id: ' . $partido->id . ', semifinal: ' . ($semifinalDelPartido ?? $semifinalActual ?? 'N/A') . '): ' . json_encode($ganador));
            }
        }
        
        \Log::info('=== RESUMEN DE GANADORES POR SEMIFINAL ===');
        \Log::info('Ganadores Semifinal 1: ' . count($ganadoresPorSemifinal['Semifinal 1']));
        foreach ($ganadoresPorSemifinal['Semifinal 1'] as $idx => $ganador) {
            \Log::info('  Semifinal 1 Ganador ' . ($idx + 1) . ': ' . json_encode($ganador));
        }
        \Log::info('Ganadores Semifinal 2: ' . count($ganadoresPorSemifinal['Semifinal 2']));
        foreach ($ganadoresPorSemifinal['Semifinal 2'] as $idx => $ganador) {
            \Log::info('  Semifinal 2 Ganador ' . ($idx + 1) . ': ' . json_encode($ganador));
        }
        \Log::info('==========================================');
        
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
        // Solo actualizar si hay al menos 2 ganadores de "Semifinal 1"
        if (count($ganadoresPorSemifinal['Semifinal 1']) >= 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 0) {
                $partidoIdSemifinal1 = $partidosIds[0];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal1]) && count($semifinalesPorPartido[$partidoIdSemifinal1]) >= 2) {
                    // Actualizar los grupos de la semifinal 1 SOLO con ganadores de "Semifinal 1"
                    // Solo actualizar el primer grupo si hay al menos 1 ganador
                    if (count($ganadoresPorSemifinal['Semifinal 1']) >= 1) {
                        DB::table('grupos')
                            ->where('id', $semifinalesPorPartido[$partidoIdSemifinal1][0]->id)
                            ->update([
                                'jugador_1' => $ganadoresPorSemifinal['Semifinal 1'][0]['jugador_1'],
                                'jugador_2' => $ganadoresPorSemifinal['Semifinal 1'][0]['jugador_2']
                            ]);
                    }
                    
                    // Solo actualizar el segundo grupo si hay al menos 2 ganadores
                    if (count($ganadoresPorSemifinal['Semifinal 1']) >= 2) {
                        DB::table('grupos')
                            ->where('id', $semifinalesPorPartido[$partidoIdSemifinal1][1]->id)
                            ->update([
                                'jugador_1' => $ganadoresPorSemifinal['Semifinal 1'][1]['jugador_1'],
                                'jugador_2' => $ganadoresPorSemifinal['Semifinal 1'][1]['jugador_2']
                            ]);
                    }
                    
                    \Log::info('Actualizando Semifinal 1 (partido_id: ' . $partidoIdSemifinal1 . ') con ' . count($ganadoresPorSemifinal['Semifinal 1']) . ' ganadores de Semifinal 1');
                    if (count($ganadoresPorSemifinal['Semifinal 1']) >= 1) {
                        \Log::info('Ganador 1 Semifinal 1: ' . json_encode($ganadoresPorSemifinal['Semifinal 1'][0]));
                    }
                    if (count($ganadoresPorSemifinal['Semifinal 1']) >= 2) {
                        \Log::info('Ganador 2 Semifinal 1: ' . json_encode($ganadoresPorSemifinal['Semifinal 1'][1]));
                    }
                }
            }
        }
        
        // Actualizar Semifinal 2 SOLO con los ganadores de "Semifinal 2"
        // Solo actualizar si hay al menos 2 ganadores de "Semifinal 2"
        if (count($ganadoresPorSemifinal['Semifinal 2']) >= 2) {
            $partidosIds = array_keys($semifinalesPorPartido);
            sort($partidosIds);
            
            if (count($partidosIds) > 1) {
                $partidoIdSemifinal2 = $partidosIds[1];
                if (isset($semifinalesPorPartido[$partidoIdSemifinal2]) && count($semifinalesPorPartido[$partidoIdSemifinal2]) >= 2) {
                    // Actualizar los grupos de la semifinal 2 SOLO con ganadores de "Semifinal 2"
                    // Solo actualizar el primer grupo si hay al menos 1 ganador
                    if (count($ganadoresPorSemifinal['Semifinal 2']) >= 1) {
                        DB::table('grupos')
                            ->where('id', $semifinalesPorPartido[$partidoIdSemifinal2][0]->id)
                            ->update([
                                'jugador_1' => $ganadoresPorSemifinal['Semifinal 2'][0]['jugador_1'],
                                'jugador_2' => $ganadoresPorSemifinal['Semifinal 2'][0]['jugador_2']
                            ]);
                    }
                    
                    // Solo actualizar el segundo grupo si hay al menos 2 ganadores
                    if (count($ganadoresPorSemifinal['Semifinal 2']) >= 2) {
                        DB::table('grupos')
                            ->where('id', $semifinalesPorPartido[$partidoIdSemifinal2][1]->id)
                            ->update([
                                'jugador_1' => $ganadoresPorSemifinal['Semifinal 2'][1]['jugador_1'],
                                'jugador_2' => $ganadoresPorSemifinal['Semifinal 2'][1]['jugador_2']
                            ]);
                    }
                    
                    \Log::info('Actualizando Semifinal 2 (partido_id: ' . $partidoIdSemifinal2 . ') con ' . count($ganadoresPorSemifinal['Semifinal 2']) . ' ganadores de Semifinal 2');
                    if (count($ganadoresPorSemifinal['Semifinal 2']) >= 1) {
                        \Log::info('Ganador 1 Semifinal 2: ' . json_encode($ganadoresPorSemifinal['Semifinal 2'][0]));
                    }
                    if (count($ganadoresPorSemifinal['Semifinal 2']) >= 2) {
                        \Log::info('Ganador 2 Semifinal 2: ' . json_encode($ganadoresPorSemifinal['Semifinal 2'][1]));
                    }
                }
            }
        }
        
        // Retornar respuesta JSON para que el frontend pueda recargar
        return response()->json([
            'success' => true,
            'message' => 'Semifinales actualizadas correctamente'
        ]);
    }
    
    /**
     * Crea las semifinales y final vacías si no existen
     */
    private function crearSemifinalesYFinalVacias($torneoId) {
        // Verificar si ya existen semifinales
        $semifinalesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'semifinal')
            ->whereNotNull('partido_id')
            ->count();
        
        // Si no hay semifinales, crear 2 semifinales vacías
        if ($semifinalesExistentes == 0) {
            // Crear Semifinal 1 vacía
            $parejaVacia = ['jugador_1' => 0, 'jugador_2' => 0];
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
            
            // Crear Semifinal 2 vacía
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'semifinales');
        }
        
        // Verificar si ya existe la final
        $finalExistente = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'final')
            ->whereNotNull('partido_id')
            ->count();
        
        // Si no hay final, crear 1 final vacía
        if ($finalExistente == 0) {
            $parejaVacia = ['jugador_1' => 0, 'jugador_2' => 0];
            $this->crearPartidoEliminatorio($torneoId, $parejaVacia, $parejaVacia, 'final');
        }
    }
    
    /**
     * Crea la final automáticamente cuando se completan las semifinales
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
                
                // Usar el índice del array para identificar la semifinal (0 o 1)
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
                
                \Log::info('Actualizando Final con ganadores de semifinales');
            } else {
                // Si no existe, crearla
                $this->crearPartidoEliminatorio($torneoId, $ganadoresSemifinales[0], $ganadoresSemifinales[1], 'final');
            }
        }
    }
    
    /**
     * Crea un partido eliminatorio (semifinal o final) en la base de datos
     */
    /**
     * Crea un partido eliminatorio (semifinal o final) en la base de datos
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
                ->distinct()
                ->count('partido_id');
            
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
                ->select('g1.partido_id')
                ->first();
            
            // Si ya existe, no crear otro
            if ($partidoExistente) {
                return;
            }
        }
        
        // Crear nuevo partido
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

    public function adminTorneoValidarCruces(Request $request) {
        $torneoId = $request->torneo_id;
        
        $torneo = DB::table('torneos')
                        ->where('torneos.id', $torneoId)
                        ->where('torneos.activo', 1)
                        ->first();
        
        if (!$torneo) {
            return redirect()->route('admintorneos')->with('error', 'Torneo no encontrado');
        }
        
        // Verificar si ya existen cruces armados (zonas que comienzan con "cuartos final|" o "cuartos final")
        $crucesExistentes = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where(function($query) {
                $query->where('zona', 'cuartos final')
                      ->orWhere('zona', 'like', 'cuartos final|%');
            })
            ->whereNotNull('partido_id')
            ->count();
        
        // Si ya existen cruces armados, redirigir directamente a la pantalla de cruces
        if ($crucesExistentes > 0) {
            return redirect()->route('admintorneoamericanocruces', ['torneo_id' => $torneoId]);
        }
        
        // Obtener información de los jugadores
        $jugadores = DB::table('jugadores')
                        ->where('jugadores.activo', 1)
                        ->get();
        
        // Calcular posiciones por zona (reutilizar lógica de adminTorneoAmericanoCruces)
        // Filtrar zonas internas: cuartos final (con o sin |), semifinal, final, ganador, perdedor
        $grupos = DB::table('grupos')
                        ->where('grupos.torneo_id', $torneoId)
                        ->where(function($query) {
                            $query->whereNotIn('grupos.zona', ['cuartos final', 'semifinal', 'final'])
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
            $partidos = DB::table('partidos')
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
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_perdidos']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => true];
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => false];
                    } else if ($puntosPareja2 > $puntosPareja1) {
                        $parejas[$key2]['partidos_ganados']++;
                        $parejas[$key2]['puntos_ganados'] += $puntosPareja2;
                        $parejas[$key2]['puntos_perdidos'] += $puntosPareja1;
                        $parejas[$key1]['partidos_perdidos']++;
                        $parejas[$key1]['puntos_ganados'] += $puntosPareja1;
                        $parejas[$key1]['puntos_perdidos'] += $puntosPareja2;
                        $parejas[$key2]['partidos_directos'][$key1] = ['ganado' => true];
                        $parejas[$key1]['partidos_directos'][$key2] = ['ganado' => false];
                    }
                }
            }
            
            // Agregar keys, calcular diferencia de games y ordenar
            foreach ($parejas as $key => $pareja) {
                $parejas[$key]['key'] = $key;
                $parejas[$key]['diferencia_games'] = ($pareja['puntos_ganados'] ?? 0) - ($pareja['puntos_perdidos'] ?? 0);
            }
            
            $posiciones = array_values($parejas);
            usort($posiciones, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                if ($a['diferencia_games'] != $b['diferencia_games']) {
                    return $b['diferencia_games'] - $a['diferencia_games'];
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
        
        // Identificar los dos mejores terceros para resaltarlos
        $mejoresTercerosIds = [];
        if (count($zonas->toArray()) == 3) {
            $tercerosArray = [];
            foreach ($zonas as $zona) {
                if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 3) {
                    $tercero = $posicionesPorZona[$zona][2];
                    $tercero['zona'] = $zona;
                    $tercerosArray[] = $tercero;
                }
            }
            
            // Ordenar terceros por partidos ganados y diferencia de games
            usort($tercerosArray, function($a, $b) {
                if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                    return $b['partidos_ganados'] - $a['partidos_ganados'];
                }
                $diffA = ($a['diferencia_games'] ?? 0);
                $diffB = ($b['diferencia_games'] ?? 0);
                if ($diffA != $diffB) {
                    return $diffB - $diffA;
                }
                return $b['puntos_ganados'] - $a['puntos_ganados'];
            });
            
            // Obtener los 2 mejores terceros
            $mejoresTerceros = array_slice($tercerosArray, 0, 2);
            foreach ($mejoresTerceros as $tercero) {
                $terceroId = $tercero['zona'] . '_' . $tercero['jugador_1'] . '_' . $tercero['jugador_2'];
                $mejoresTercerosIds[] = $terceroId;
            }
            \Log::info('Mejores terceros identificados: ' . json_encode($mejoresTercerosIds));
        }
        
        // Obtener solo cruces de cuartos de final
        $cruces = [];
        $gruposEliminatorios = DB::table('grupos')
            ->where('torneo_id', $torneoId)
            ->where('zona', 'cuartos final')
            ->whereNotNull('partido_id')
            ->orderBy('partido_id')
            ->orderBy('id')
            ->get();
        
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
        
        // Construir cruces de cuartos desde los partidos existentes
        foreach ($partidosAgrupados as $partidoId => $datosPartido) {
            if (count($datosPartido['grupos']) >= 2) {
                $g1 = $datosPartido['grupos'][0];
                $g2 = $datosPartido['grupos'][1];
                
                $cruce = [
                    'id' => 'cuartos_' . $partidoId,
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
                    'ronda' => 'cuartos'
                ];
                
                $cruces[] = $cruce;
            }
        }
        
        // Si no hay cruces de cuartos, generarlos desde los clasificados
        if (count($cruces) == 0) {
            $zonasArray = $zonas->toArray();
            sort($zonasArray);
            
            // Verificar si es caso de 12 parejas (3 zonas de 4 parejas cada una)
            $esGrupoDe12 = false;
            if (count($zonasArray) == 3) {
                $totalParejas = 0;
                foreach ($zonasArray as $zona) {
                    if (isset($posicionesPorZona[$zona])) {
                        $totalParejas += count($posicionesPorZona[$zona]);
                    }
                }
                if ($totalParejas == 12) {
                    $esGrupoDe12 = true;
                }
            }
            
            if ($esGrupoDe12 && count($zonasArray) == 3) {
                // Caso especial: 12 parejas (3 zonas de 4 parejas)
                $zonaA = $zonasArray[0];
                $zonaB = $zonasArray[1];
                $zonaC = $zonasArray[2];
                
                // Obtener primeros y segundos de cada zona
                $primeros = [];
                $segundos = [];
                $terceros = [];
                
                foreach ($zonasArray as $zona) {
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 1) {
                        $primeros[$zona] = [
                            'zona' => $zona,
                            'posicion' => 1,
                            'jugador_1' => $posicionesPorZona[$zona][0]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][0]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][0]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][0]['puntos_ganados']
                        ];
                    }
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 2) {
                        $segundos[$zona] = [
                            'zona' => $zona,
                            'posicion' => 2,
                            'jugador_1' => $posicionesPorZona[$zona][1]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][1]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][1]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][1]['puntos_ganados']
                        ];
                    }
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) >= 3) {
                        $terceros[$zona] = [
                            'zona' => $zona,
                            'posicion' => 3,
                            'jugador_1' => $posicionesPorZona[$zona][2]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][2]['jugador_2'],
                            'partidos_ganados' => $posicionesPorZona[$zona][2]['partidos_ganados'],
                            'puntos_ganados' => $posicionesPorZona[$zona][2]['puntos_ganados']
                        ];
                    }
                }
                
                // Seleccionar los 2 mejores terceros
                $tercerosArray = [];
                if (isset($terceros[$zonaA])) $tercerosArray[] = $terceros[$zonaA];
                if (isset($terceros[$zonaB])) $tercerosArray[] = $terceros[$zonaB];
                if (isset($terceros[$zonaC])) $tercerosArray[] = $terceros[$zonaC];
                
                // Ordenar terceros por partidos ganados y puntos
                usort($tercerosArray, function($a, $b) {
                    if ($a['partidos_ganados'] != $b['partidos_ganados']) {
                        return $b['partidos_ganados'] - $a['partidos_ganados'];
                    }
                    return $b['puntos_ganados'] - $a['puntos_ganados'];
                });
                
                $mejoresTerceros = array_slice($tercerosArray, 0, 2);
                $tercero1 = $mejoresTerceros[0] ?? null;
                $tercero2 = $mejoresTerceros[1] ?? null;
                
                // Generar cruces según la estructura especificada:
                // 1A vs 3C o 3B
                // 1B vs 3A o 3C
                // 1C vs 2A
                // 2B vs 2C
                
                $terceroPara1A = null;
                $terceroPara1B = null;
                
                // Cruce 1: 1A vs mejor tercero (3C o 3B)
                if (isset($primeros[$zonaA]) && ($tercero1 || $tercero2)) {
                    // Priorizar terceros de C o B
                    if ($tercero1 && ($tercero1['zona'] == $zonaC || $tercero1['zona'] == $zonaB)) {
                        $terceroPara1A = $tercero1;
                    } else if ($tercero2 && ($tercero2['zona'] == $zonaC || $tercero2['zona'] == $zonaB)) {
                        $terceroPara1A = $tercero2;
                    } else if ($tercero1) {
                        $terceroPara1A = $tercero1; // Usar el mejor disponible
                    } else if ($tercero2) {
                        $terceroPara1A = $tercero2;
                    }
                    
                    if ($terceroPara1A && isset($primeros[$zonaA])) {
                        $cruces[] = [
                            'id' => 'cuartos_1',
                            'partido_id' => null,
                            'pareja_1' => [
                                'jugador_1' => $primeros[$zonaA]['jugador_1'],
                                'jugador_2' => $primeros[$zonaA]['jugador_2'],
                                'zona' => $zonaA,
                                'posicion' => 1
                            ],
                            'pareja_2' => [
                                'jugador_1' => $terceroPara1A['jugador_1'],
                                'jugador_2' => $terceroPara1A['jugador_2'],
                                'zona' => $terceroPara1A['zona'],
                                'posicion' => 3
                            ],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
                
                // Cruce 2: 1B vs mejor tercero restante (3A o 3C)
                if (isset($primeros[$zonaB]) && ($tercero1 || $tercero2)) {
                    // Buscar el tercero que no se usó en el cruce 1 y que sea de A o C
                    if ($tercero1 && $tercero1['zona'] != ($terceroPara1A['zona'] ?? null) && ($tercero1['zona'] == $zonaA || $tercero1['zona'] == $zonaC)) {
                        $terceroPara1B = $tercero1;
                    } else if ($tercero2 && $tercero2['zona'] != ($terceroPara1A['zona'] ?? null) && ($tercero2['zona'] == $zonaA || $tercero2['zona'] == $zonaC)) {
                        $terceroPara1B = $tercero2;
                    } else if ($tercero1 && $tercero1['zona'] != ($terceroPara1A['zona'] ?? null)) {
                        $terceroPara1B = $tercero1;
                    } else if ($tercero2 && $tercero2['zona'] != ($terceroPara1A['zona'] ?? null)) {
                        $terceroPara1B = $tercero2;
                    }
                    
                    if ($terceroPara1B && isset($primeros[$zonaB])) {
                        $cruces[] = [
                            'id' => 'cuartos_2',
                            'partido_id' => null,
                            'pareja_1' => [
                                'jugador_1' => $primeros[$zonaB]['jugador_1'],
                                'jugador_2' => $primeros[$zonaB]['jugador_2'],
                                'zona' => $zonaB,
                                'posicion' => 1
                            ],
                            'pareja_2' => [
                                'jugador_1' => $terceroPara1B['jugador_1'],
                                'jugador_2' => $terceroPara1B['jugador_2'],
                                'zona' => $terceroPara1B['zona'],
                                'posicion' => 3
                            ],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
                
                // Cruce 3: 1C vs 2A
                if (isset($primeros[$zonaC]) && isset($segundos[$zonaA])) {
                    $cruces[] = [
                        'id' => 'cuartos_3',
                        'partido_id' => null,
                        'pareja_1' => [
                            'jugador_1' => $primeros[$zonaC]['jugador_1'],
                            'jugador_2' => $primeros[$zonaC]['jugador_2'],
                            'zona' => $zonaC,
                            'posicion' => 1
                        ],
                        'pareja_2' => [
                            'jugador_1' => $segundos[$zonaA]['jugador_1'],
                            'jugador_2' => $segundos[$zonaA]['jugador_2'],
                            'zona' => $zonaA,
                            'posicion' => 2
                        ],
                        'ronda' => 'cuartos'
                    ];
                }
                
                // Cruce 4: 2B vs 2C
                if (isset($segundos[$zonaB]) && isset($segundos[$zonaC])) {
                    $cruces[] = [
                        'id' => 'cuartos_4',
                        'partido_id' => null,
                        'pareja_1' => [
                            'jugador_1' => $segundos[$zonaB]['jugador_1'],
                            'jugador_2' => $segundos[$zonaB]['jugador_2'],
                            'zona' => $zonaB,
                            'posicion' => 2
                        ],
                        'pareja_2' => [
                            'jugador_1' => $segundos[$zonaC]['jugador_1'],
                            'jugador_2' => $segundos[$zonaC]['jugador_2'],
                            'zona' => $zonaC,
                            'posicion' => 2
                        ],
                        'ronda' => 'cuartos'
                    ];
                }
            } else {
                // Lógica para otros casos (reutilizar lógica existente)
                $clasificados = [];
                foreach ($zonasArray as $zona) {
                    if (isset($posicionesPorZona[$zona]) && count($posicionesPorZona[$zona]) > 0) {
                        $clasificados[] = [
                            'zona' => $zona,
                            'posicion' => 1,
                            'jugador_1' => $posicionesPorZona[$zona][0]['jugador_1'],
                            'jugador_2' => $posicionesPorZona[$zona][0]['jugador_2']
                        ];
                    }
                }
                
                // Generar cruces básicos
                if (count($clasificados) >= 2) {
                    for ($i = 0; $i < count($clasificados) - 1; $i += 2) {
                        $cruces[] = [
                            'id' => 'cuartos_' . ($i / 2),
                            'partido_id' => null,
                            'pareja_1' => [
                                'jugador_1' => $clasificados[$i]['jugador_1'],
                                'jugador_2' => $clasificados[$i]['jugador_2'],
                                'zona' => $clasificados[$i]['zona'],
                                'posicion' => $clasificados[$i]['posicion']
                            ],
                            'pareja_2' => [
                                'jugador_1' => $clasificados[$i + 1]['jugador_1'],
                                'jugador_2' => $clasificados[$i + 1]['jugador_2'],
                                'zona' => $clasificados[$i + 1]['zona'],
                                'posicion' => $clasificados[$i + 1]['posicion']
                            ],
                            'ronda' => 'cuartos'
                        ];
                    }
                }
            }
        }
        //return $cruces;
        return View('bahia_padel.admin.torneo.validar_cruces_americano')
                    ->with('jugadores', $jugadores)
                    ->with('torneo', $torneo)
                    ->with('cruces', $cruces)
                    ->with('posicionesPorZona', $posicionesPorZona)
                    ->with('mejoresTercerosIds', $mejoresTercerosIds);
    }

    public function guardarCrucesEditados(Request $request) {
        try {
            $torneoId = $request->torneo_id;
            $cruces = $request->cruces; // Array de cruces editados
            
            \Log::info('Guardando cruces editados para torneo: ' . $torneoId);
            \Log::info('Cruces recibidos: ' . json_encode($cruces));
            
            if (!$cruces || !is_array($cruces)) {
                return response()->json(['success' => false, 'message' => 'No se recibieron cruces válidos']);
            }
            
            // Eliminar cruces existentes para este torneo
            $gruposEliminatorios = DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->get();
            
            $partidosIds = $gruposEliminatorios->pluck('partido_id')->unique()->filter();
            
            // Eliminar partidos asociados
            if ($partidosIds->count() > 0) {
                DB::table('partidos')->whereIn('id', $partidosIds)->delete();
            }
            
            // Eliminar grupos eliminatorios
            DB::table('grupos')
                ->where('torneo_id', $torneoId)
                ->whereIn('zona', ['cuartos final', 'semifinal', 'final'])
                ->delete();
            
            // Crear nuevos cruces
            foreach ($cruces as $index => $cruce) {
                \Log::info('Procesando cruce ' . $index . ': ' . json_encode($cruce));
                
                // Validar estructura del cruce
                if (!isset($cruce['pareja_1']) || !isset($cruce['pareja_2'])) {
                    \Log::error('Cruce ' . $index . ' no tiene pareja_1 o pareja_2');
                    continue;
                }
                
                if (!isset($cruce['pareja_1']['jugador_1']) || !isset($cruce['pareja_1']['jugador_2']) ||
                    !isset($cruce['pareja_2']['jugador_1']) || !isset($cruce['pareja_2']['jugador_2'])) {
                    \Log::error('Cruce ' . $index . ' no tiene todos los jugadores requeridos');
                    continue;
                }
                
                $ronda = $cruce['ronda'] ?? 'cuartos';
                $zona = 'cuartos final';
                if ($ronda === 'semifinales') {
                    $zona = 'semifinal';
                } else if ($ronda === 'final') {
                    $zona = 'final';
                }
                
                // Validar que los jugadores no sean null o 0
                $jugador1_1 = $cruce['pareja_1']['jugador_1'];
                $jugador1_2 = $cruce['pareja_1']['jugador_2'];
                $jugador2_1 = $cruce['pareja_2']['jugador_1'];
                $jugador2_2 = $cruce['pareja_2']['jugador_2'];
                
                if (!$jugador1_1 || !$jugador1_2 || !$jugador2_1 || !$jugador2_2) {
                    \Log::error('Cruce ' . $index . ' tiene jugadores nulos o cero');
                    continue;
                }
                
                // Crear partido con todos los campos requeridos
                $partido = DB::table('partidos')->insertGetId([
                    'pareja_1_set_1' => 0,
                    'pareja_1_set_1_tie_break' => 0,
                    'pareja_2_set_1' => 0,
                    'pareja_2_set_1_tie_break' => 0,
                    'pareja_1_set_2' => 0,
                    'pareja_1_set_2_tie_break' => 0,
                    'pareja_2_set_2' => 0,
                    'pareja_2_set_2_tie_break' => 0,
                    'pareja_1_set_3' => 0,
                    'pareja_1_set_3_tie_break' => 0,
                    'pareja_2_set_3' => 0,
                    'pareja_2_set_3_tie_break' => 0,
                    'pareja_1_set_super_tie_break' => 0,
                    'pareja_2_set_super_tie_break' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Crear grupos para las dos parejas
                DB::table('grupos')->insert([
                    [
                        'torneo_id' => $torneoId,
                        'zona' => $zona,
                        'fecha' => '2000-01-01',
                        'horario' => '00:00',
                        'jugador_1' => $jugador1_1,
                        'jugador_2' => $jugador1_2,
                        'partido_id' => $partido,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'torneo_id' => $torneoId,
                        'zona' => $zona,
                        'fecha' => '2000-01-01',
                        'horario' => '00:00',
                        'jugador_1' => $jugador2_1,
                        'jugador_2' => $jugador2_2,
                        'partido_id' => $partido,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                ]);
            }
            
            return response()->json(['success' => true, 'message' => 'Cruces guardados correctamente']);
            
        } catch (\Exception $e) {
            \Log::error('Error al guardar cruces editados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

}


