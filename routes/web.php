<?php

Route::get('/index_new', function () {	
	return View('home/index');   
});

Route::get('/clear-cache', function() {
   $exitCode = Artisan::call('cache:clear');
   // return what you want
});

Route::get('/config-cache', function() {
   $exitCode = Artisan::call('config:cache');
   // return what you want
});
Route::get('/config-clear', function() {
   $exitCode = Artisan::call('config:clear');
   // return what you want
});
Route::get('/migrate', function() {
   $exitCode = Artisan::call('migrate');
   // return what you want
});

Route::get("/tv_torneo_americano", "HomeController@tvTorneoAmericano")->name("tvtorneoamericano");
Route::get("/tv_torneo_americano_cruces", "HomeController@tvTorneoAmericanoCruces")->name("tvtorneoamericanocruces");
Route::get("/tv_torneo_americano_sorteo", "HomeController@tvTorneoAmericanoSorteo")->name("tvtorneoamericanosorteo");
Route::post("/tv_torneo_americano_actualizar", "HomeController@tvTorneoAmericanoActualizar")->name("tvtorneoamericanoactualizar");
Route::post("/tv_torneo_americano_cruces_actualizar", "HomeController@tvTorneoAmericanoCrucesActualizar")->name("tvtorneoamericanocrucesactualizar");
Route::post("/tv_torneo_americano_sorteo_actualizar", "HomeController@tvTorneoAmericanoSorteoActualizar")->name("tvtorneoamericanosorteoactualizar");

// ################################# estas rutas debo tocar para volver al mantenimiento ################
/*Route::get('/home_test', function () {	
	return View('home.index');    
}); */

Route::get('/', 'HomeFreeController@bahiaPadelHome')->name('index');	

Route::get('/bahia_padel', 'HomeFreeController@bahiaPadelHome')->name('bahiapadel');

Route::get('/bahia_padel_admin', 'HomeFreeController@bahiaPadelAdmin')->name('bahiapadeladmin');	

Route::get('/index2', 'Auth\LoginController@showLoginForm')->name('index2');	

// #################################################################################################

Route::group(['middleware' => ['auth', 'usuarioAdmin']], function () {
	
	Route::get('home_admin_2','UserController@admin');			
	Route::get('nuevo_usuario','UserController@nuevoUsuario');		

	Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
	
	Route::post('/registrar', 'Auth\RegisterController@registrar')->name('registrar');	
		
});
Route::group(['middleware' => ['auth', 'usuarioPadel']], function () {
	Route::get('bp_admin','HomeController@adminHomeBp')->name('bp_admin');	

	Route::get('home_admin','HomeController@adminHome')->name('home_admin');
});

Route::group(['middleware' => ['auth', 'usuarioAdminPadel']], function () {

	Route::get('bp_admin','HomeController@adminHomeBp')->name('bp_admin');	

	Route::get('home_admin','HomeController@adminHome')->name('home_admin');

	Route::get('admin_jugadores','HomeController@adminJugadores')->name('adminjugadores');
	Route::get('admin_vivo','HomeController@adminVivo')->name('adminvivo');
	Route::get('admin_torneos','HomeController@adminTorneos')->name('admintorneos');
	Route::get('admin_fotos','HomeController@adminFotos')->name('adminfotos');
	Route::post('/registrar_torneo_admin', 'HomeController@registrarTorneo')->name('registrartorneoadmin');
	Route::post('/get_torneos', 'HomeController@getTorneos')->name('gettorneos');
	Route::post('/admin_torneo_selected', 'HomeController@adminTorneoSelected')->name('admintorneoselected');	
	Route::post('/admin_crear_jugador', 'HomeController@adminCrearJugador')->name('admincrearjugador');
	Route::post('/admin_editar_jugador', 'HomeController@adminEditarJugador')->name('admineditarjugador');
	Route::get('/get_jugadores_home', 'HomeController@getJugadores')->name('getjugadoreshome');
	Route::post('/admin_eliminar_jugador', 'HomeController@adminEliminarJugador')->name('admineliminarjugador');	
	Route::post('/guardar_fecha_admin_torneo', 'HomeController@guardarFechaAdminTorneo')->name('guardarfechaadmintorneo');
	Route::post('/obtener_datos_zona', 'HomeController@obtenerDatosZona')->name('obtenerdatoszona');
	Route::post('/verificar_numero_parejas_zona', 'HomeController@verificarNumeroParejasZona')->name('verificarnumeroparejaszona');
	Route::post('/obtener_todas_las_zonas', 'HomeController@obtenerTodasLasZonas')->name('obtenertodaslaszonas');
	Route::post('/guardar_torneo_americano', 'HomeController@guardarTorneoAmericano')->name('guardartorneoamericano');
	Route::post('/crear_partidos_americano', 'HomeController@crearPartidosAmericano')->name('crearpartidosamericano');
	Route::get('/admin_torneo_americano_partidos', 'HomeController@adminTorneoAmericanoPartidos')->name('admintorneoamericanopartidos');
	Route::post('/guardar_resultado_americano', 'HomeController@guardarResultadoAmericano')->name('guardarresultadoamericano');
	Route::post('/calcular_posiciones_americano', 'HomeController@calcularPosicionesAmericano')->name('calcularposicionesamericano');
	Route::get('/admin_torneo_americano_cruces', 'HomeController@adminTorneoAmericanoCruces')->name('admintorneoamericanocruces');
	Route::post('/guardar_resultado_cruce_americano', 'HomeController@guardarResultadoCruceAmericano')->name('guardarresultadocruceamericano');
	Route::get('/admin_torneo_resultados', 'HomeController@adminTorneoResultados')->name('admintorneoresultados');
	Route::post('/guardar_resultado_partido', 'HomeController@guardarResultadoPartido')->name('guardarresultadopartido');
	Route::post('/calcular_posiciones_zona', 'HomeController@calcularPosicionesZona')->name('calcularposicioneszona');
	Route::post('/verificar_partidos_completos', 'HomeController@verificarPartidosCompletos')->name('verificarpartidoscompletos');
	Route::get('/admin_torneo_validar_cruces', 'HomeController@adminTorneoValidarCruces')->name('admintorneovalidarcruces');
	Route::post('/confirmar_cruces', 'HomeController@confirmarCruces')->name('confirmarcruces');
	Route::get('/admin_torneo_puntuable_cruces', 'HomeController@adminTorneoPuntuableCruces')->name('admintorneopuntuablecruces');	
	



	Route::get('/home_admin_padel', 'AdminController@adminHome')->name('homeadminpadel');

	Route::get('/admin_jugador', 'AdminController@adminJugador')->name('adminjugador');

	Route::get('/admin_fecha', 'AdminController@adminFecha')->name('adminfecha');

	Route::get('/admin_torneo', 'AdminController@adminTorneo')->name('admintorneo');
	
	Route::get('/admin_tabla_general', 'AdminController@adminTablaGeneral')->name('admintablageneral');
	
	Route::post('/get_tabla_general', 'AdminController@getTablaGeneral')->name('gettablageneral');
	
	Route::post('/registrar_jugador', 'AdminController@registrarJugador')->name('registrarjugador');
	Route::post('/cargar_imagen_jugador', 'AdminController@cargarImagenJugador')->name('cargarimagenjugador');

	Route::post('/registrar_torneo', 'AdminController@registrarTorneo')->name('registrartorneo');
	
	Route::get('/modal_buscar_jugador_list', 'AdminController@modalBuscarJugadorList')->name('modalbuscarjugadorlist');
	
	Route::post('/get_jugador', 'AdminController@getJugador')->name('getjugador');
	Route::post('/get_jugadores', 'AdminController@getJugadores')->name('getjugadores');

	Route::post('/generar_fecha', 'AdminController@generarFecha')->name('generarfecha');

	Route::post('/comenzar_fecha', 'AdminController@comenzarFecha')->name('comenzarfecha');			
	
	Route::post('/get_partido_fecha', 'AdminController@getPartidoFecha')->name('getpartidofecha');			
	
	Route::post('/guardar_puntos', 'AdminController@guardarPuntos')->name('guardarpuntos');
				
	Route::post('/calcular_posiciones', 'AdminController@calcularPosiciones')->name('calcularposiciones');
	
	Route::post('/get_libres', 'AdminController@getLibres')->name('getlibres');	
	
	Route::post('/on_change_torneo', 'AdminController@onChangeTorneo')->name('onchangetorneo');	
	
	Route::post('/get_listado_fechas_previas', 'AdminController@getListadoFechasPrevias')->name('getlistadofechasprevias');	

	Route::get('/torneo/{torneo_id}/fecha/{fecha_id}', 'AdminController@getFecha')->name('ruta.fecha');
	
	Route::post('/guardar_puntos_fecha', 'AdminController@guardarPuntosFecha')->name('guardarpuntosfecha');	

	Route::post('/get_fechas_previas_jugadores', 'AdminController@getFechasPreviasJugadores')->name('getfechaspreviasjugadores');	
});


Route::group(['middleware' => ['auth', 'usuarioPadel']], function () {
	Route::get('/home_padel', 'HomeController@adminHome')->name('homepadel');
	
});

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Ruta pública para subir fotos de jugadores (mobile, sin autenticación)
Route::get('/subir-foto-jugador', [App\Http\Controllers\HomeController::class, 'mostrarSubirFotoJugador'])->name('subir.foto.jugador');
Route::post('/buscar-jugadores-publico', [App\Http\Controllers\HomeController::class, 'buscarJugadoresPublico'])->name('buscar.jugadores.publico');
Route::post('/subir-foto-jugador-publico', [App\Http\Controllers\HomeController::class, 'subirFotoJugadorPublico'])->name('subir.foto.jugador.publico');

Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

// Webhook para despliegue automático desde GitHub
Route::post('/deploy-webhook', [\App\Http\Controllers\DeployWebhookController::class, 'handle'])->name('deploy.webhook');

// Webhook configurado y funcionando - Test de despliegue automático
