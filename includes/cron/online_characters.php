<?php
/**
 * Cron: Online Characters List
 * Modernized for Linux/DBLIB Compatibility
 */

// File Name
$file_name = basename(__FILE__);

// Usamos la conexión al JUEGO ($mu), porque ahí están MEMB_STAT y AccountCharacter
$mu = Connection::Database('MuOnline');

$result = array();

try {
    // Consulta directa uniendo MEMB_STAT (Online) con AccountCharacter (Último PJ)
    // Funciona en la mayoría de versiones S6+
    $sql = "SELECT ac.GameIDC 
            FROM MEMB_STAT ms
            JOIN AccountCharacter ac ON ms.memb___id = ac.Id
            WHERE ms.ConnectStat = 1 AND ac.GameIDC IS NOT NULL";

    $onlineList = $mu->query_fetch($sql);

    if(is_array($onlineList)) {
        foreach($onlineList as $char) {
            // Evitar duplicados y vacíos
            if(!empty($char['GameIDC']) && !in_array($char['GameIDC'], $result)) {
                $result[] = $char['GameIDC'];
            }
        }
    }

    // Guardar Caché (Usamos json_encode directo para estandarizar)
    // Formato MuServerMaker: [timestamp, [array_de_nombres]]
    $cacheData = [
        0 => time(),
        1 => $result
    ];
    
    $cacheFile = __PATH_INCLUDES__ . 'cache/online_characters.cache';
    $fp = fopen($cacheFile, 'w');
    if($fp) {
        fwrite($fp, json_encode($cacheData));
        fclose($fp);
    }

    // Actualizar registro de cron
    if(function_exists('updateCronLastRun')) updateCronLastRun($file_name);

} catch (Exception $e) {
    // Silencioso
}
?>