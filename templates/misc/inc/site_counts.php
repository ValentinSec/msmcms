<?php
/**
 * Totales del sitio (cuentas, personajes, guilds) con caché en archivo.
 * Lo usan templates/misc/sidebar.php y el home de los templates.
 */
if(!defined('access') or !access) die();

if(!function_exists('msm_cached_counts')) {
    function msm_cached_counts($ttl = 300) {
        $file = __PATH_INCLUDES__ . 'cache/site_counts.json';

        // 1. Caché vigente
        if(is_file($file) && (time() - filemtime($file)) < $ttl) {
            $cached = json_decode(@file_get_contents($file), true);
            if(is_array($cached) && isset($cached['accounts'], $cached['chars'], $cached['guilds'])) {
                return $cached;
            }
        }

        // 2. Consulta a la base
        $data = array('accounts' => 0, 'chars' => 0, 'guilds' => 0);
        try {
            // MEMB_INFO vive en la base de cuentas; Character y Guild en la del juego.
            // Con una sola base, 'Me_MuOnline' devuelve la misma conexión que 'MuOnline'.
            $dbAcc  = Connection::Database('Me_MuOnline');
            $dbGame = Connection::Database('MuOnline');

            $a = $dbAcc->query_fetch_single("SELECT COUNT(*) AS c FROM MEMB_INFO");
            $c = $dbGame->query_fetch_single("SELECT COUNT(*) AS c FROM Character");
            $g = $dbGame->query_fetch_single("SELECT COUNT(*) AS c FROM Guild");

            // Si alguna consulta falló (base caída), no se guarda nada
            if(!is_array($a) || !is_array($c) || !is_array($g)) return $data;

            $data = array(
                'accounts' => (int)$a['c'],
                'chars'    => (int)$c['c'],
                'guilds'   => (int)$g['c'],
            );
            @file_put_contents($file, json_encode($data), LOCK_EX);
        } catch(Exception $e) { }

        return $data;
    }
}