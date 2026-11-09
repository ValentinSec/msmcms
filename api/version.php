<?php
/**
 * MuServerMaker CMS
 * * @version 1.0.0
 * @author MuServerMaker Team
 * @copyright (c) 2026 MuServerMaker, All Rights Reserved
 */

define('access', 'api');
header('Content-Type: application/json');

try {
	
	// 1. Cargar el Kernel de MuServerMaker (Reemplaza a WebEngine)
    // Busca en la carpeta 'includes' el archivo muservermaker.php
	if(!@include_once(rtrim(str_replace('\\','/', dirname(__DIR__)), '/') . '/includes/muservermaker.php')) {
        throw new Exception('Could not load MuServerMaker Kernel.');
    }
	
	// Helper para versi贸n de Apache (Se mantiene igual, es utilidad)
	if(!function_exists('apache_get_version')) {
		function apache_get_version() {
			if(!isset($_SERVER['SERVER_SOFTWARE']) || strlen($_SERVER['SERVER_SOFTWARE']) == 0) {
				return '';
			}
			return $_SERVER['SERVER_SOFTWARE'];
		}
	}
	
	// Inicializar Handler (Clase cargada por muservermaker.php)
	$handler = new Handler();
	
	// Respuesta JSON
	http_response_code(200);
	echo json_encode(array(
        'code' => 200, 
        'apache' => apache_get_version(), 
        'php' => phpversion(), 
        'muservermaker' => __MUSERVERMAKER_VERSION__ // Constante definida en tu includes/muservermaker.php
    ), JSON_PRETTY_PRINT);
	
} catch(Exception $ex) {
	http_response_code(500);
	echo json_encode(array('code' => 500, 'error' => $ex->getMessage()), JSON_PRETTY_PRINT);
}
?>