<?php
/**
 * Cron: Server Statistics Info (Versión Estable)
 * File: includes/cron/server_info.php
 */

// Evitar acceso directo
if(!defined('access')) {
    die("Access Denied");
}

try {
    // 1. Configuración Manual del Multiplicador
    // Cambia el 1 por 1.5, 2, etc. si quieres inflar los números.
    $onlineMultiplier = 1; 

    // 2. Conexiones (Usando la clase Connection que ya arreglamos)
    $mu = Connection::Database('MuOnline');
    $me = Connection::Database('Me_MuOnline');

    // 3. Consultas (Protegidas con intval para evitar errores de tipo)
    
    // ONLINE: Usamos >= 1 para ser más tolerantes con el estado
    // A veces MEMB_STAT usa estados diferentes al 1.
    $onData = $mu->query_fetch_single("SELECT COUNT(*) as t FROM MEMB_STAT WHERE ConnectStat >= 1");
    $totalOnline = ($onData && isset($onData['t'])) ? intval($onData['t']) : 0;
    
    // Aplicar multiplicador
    $totalOnline = floor($totalOnline * $onlineMultiplier);

    // CUENTAS
    $accData = $me->query_fetch_single("SELECT COUNT(*) as t FROM MEMB_INFO");
    $totalAccounts = ($accData && isset($accData['t'])) ? intval($accData['t']) : 0;

    // PERSONAJES
    $charData = $mu->query_fetch_single("SELECT COUNT(*) as t FROM Character");
    $totalCharacters = ($charData && isset($charData['t'])) ? intval($charData['t']) : 0;

    // GUILDS
    $guildData = $mu->query_fetch_single("SELECT COUNT(*) as t FROM Guild");
    $totalGuilds = ($guildData && isset($guildData['t'])) ? intval($guildData['t']) : 0;

    // 4. Preparar Datos
    // Formato string clásico de MuServerMaker: "Cuentas|Pjs|Guilds|Online"
    // Esto es lo que espera leer tu módulo del home.
    $dataString = "$totalAccounts|$totalCharacters|$totalGuilds|$totalOnline";
    
    // Estructura caché MuServerMaker
    // MuServerMaker a veces espera un array serializado o un json, pero la función UpdateCache original
    // solía guardar solo el string o un array simple. Vamos a guardar el formato seguro.
    $cacheData = [
        0 => time(),        // Timestamp
        1 => [$dataString]  // Datos
    ];

    // 5. Guardar Caché
    // Usamos ruta absoluta segura
    $cacheFile = __PATH_INCLUDES__ . 'cache/server_info.cache';
    
    $fp = @fopen($cacheFile, 'w'); // Arroba para suprimir error visual si falla permisos
    if($fp) {
        fwrite($fp, json_encode($cacheData));
        fclose($fp);
    } else {
        // Intento fallback (formato antiguo) por si el json falla en el frontend
        // Solo descomentar si el Home muestra error de "Array" o vacío
        /*
        $fp = @fopen($cacheFile, 'w');
        fwrite($fp, serialize($serverInfo));
        fclose($fp);
        */
    }

} catch (Exception $e) {
    // Si falla algo, no hacemos nada (silencioso) para no dar Error 500 en la API
    // El cron simplemente no actualizará esta vez.
}
?>