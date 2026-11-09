<?php
/**
 * MuServerMaker CMS - Cron Executor
 * File: includes/cron/cron.php
 * Fix: Paths, AdminCP Compatibility & Infinite Loop Protection
 */

// 1. FIX: Definir acceso solo si no viene definido del AdminCP
if(!defined('access')) {
    define('access', 'cron');
}

// 2. FIX RUTA: Cargar Núcleo de forma inteligente
// Esto soluciona el error de "failed to open stream" con doble includes
if(!class_exists('Connection')) {
    $currentDir = str_replace('\\', '/', __DIR__);
    // Estamos en /includes/cron/, subimos un nivel para buscar muservermaker.php
    $corePath = dirname($currentDir) . '/muservermaker.php';
    
    if(file_exists($corePath)) {
        require_once($corePath);
    } else {
        // Intento secundario por si la estructura varía
        $altPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/muservermaker.php';
        if(file_exists($altPath)) require_once($altPath);
        else die("Critical Error: Could not load muservermaker.php core.");
    }
}

// 3. Conexión a Base de Datos
global $db;
if(!is_object($db)) {
    try {
        $db = Connection::Database('Me_MuOnline');
    } catch (Exception $e) {
        if(access == 'cron') die("DB Connection Error: " . $e->getMessage());
        return;
    }
}

// 4. Asegurar ruta de crons
if(!defined('__PATH_CRON__')) define('__PATH_CRON__', __DIR__ . '/');

// 5. Obtener Crons Activos
try {
    $cronList = $db->query_fetch("SELECT * FROM MUSERVERMAKER_CRON WHERE cron_status = 1");
} catch (Exception $e) {
    return; // Tabla no existe, salimos
}

if(!is_array($cronList)) {
    if(access == 'cron') echo "No active crons found.";
    return;
}

// 6. Ejecutar Crons (Con protección anti-bucle)
foreach($cronList as $cron) {
    
    // [PROTECCIÓN CRÍTICA]
    // Si el archivo a ejecutar es este mismo script (cron.php), LO SALTAMOS.
    // Esto evita la recursión infinita que tumba la web.
    $scriptName = basename($cron['cron_file_run']);
    if($scriptName == 'cron.php' || $scriptName == 'api/cron.php') {
        continue;
    }

    // Calcular tiempos (Casting a int por seguridad PHP 8)
    $lastRun = (int)$cron['cron_last_run'];
    $interval = (int)$cron['cron_run_time'];
    $nextRun = $lastRun + $interval;
    $now = time();

    // Si ya pasó el tiempo, ejecutamos
    if($now >= $nextRun) {
        
        $filePath = __PATH_CRON__ . $scriptName;
        
        if(file_exists($filePath)) {
            // Ejecutar el archivo
            include($filePath);

            // Actualizar la última ejecución en la base de datos
            $db->query("UPDATE MUSERVERMAKER_CRON SET cron_last_run = ? WHERE cron_id = ?", [$now, $cron['cron_id']]);
            
            if(access == 'cron') echo "Executed: " . $scriptName . "<br>";
        } else {
            if(access == 'cron') echo "Error: File not found " . $filePath . "<br>";
        }
    }
}
?>