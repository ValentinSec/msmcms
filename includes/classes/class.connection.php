<?php
/**
 * MuServerMaker CMS
 * https://muservermaker.com/
 * @version 1.0.0 (Safe Mode)
 * @author MuServerMaker
 * @copyright (c) 2026 MuServerMaker, All Rights Reserved
 */

class Connection {
    
    public static function Database($database='') {
        
        // Obtenemos configuración limpia del JSON
        $host   = self::_config('SQL_DB_HOST');
        $port   = self::_config('SQL_DB_PORT');
        $user   = self::_config('SQL_DB_USER');
        $pass   = self::_config('SQL_DB_PASS');
        $driver = self::_config('SQL_PDO_DRIVER');

        // DETECCIÓN INTELIGENTE (Opcional):
        // Si detectamos Linux, sugerimos 'dblib' (3) internamente, 
        // pero NO modificamos el host para no romper el AdminCP.
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Si el JSON dice 1 (sqlsrv), lo cambiamos a 'dblib' al vuelo
            // porque driver 1 es imposible en Linux.
            if($driver == 1 || $driver == 'sqlsrv') {
                $driver = 'dblib';
            }
        }

        switch($database) {
            case 'MuOnline':
                $dbName = self::_config('SQL_DB_NAME');
                return self::createInstance($host, $port, $dbName, $user, $pass, $driver);
                break;

            case 'Me_MuOnline':
                if(!self::_config('SQL_USE_2_DB')) return self::Database('MuOnline');
                
                $dbName2 = self::_config('SQL_DB_2_NAME');
                return self::createInstance($host, $port, $dbName2, $user, $pass, $driver);
                break;

            default:
                return null;
        }
    }
    
    // Función auxiliar para capturar errores FATALES (Pantalla Blanca)
    private static function createInstance($h, $p, $n, $u, $pw, $d) {
        try {
            // Instanciamos la clase dB original del CMS
            $db = new dB($h, $p, $n, $u, $pw, $d);
            
            // Verificamos si murió (Lógica original del CMS)
            if(isset($db->dead) && $db->dead) {
                // Si el reporte de errores está activo, lanzamos la excepción
                if(self::_config('error_reporting')) {
                    throw new Exception($db->error);
                }
                // Si no, lanzamos error genérico pero SIN romper todo el script
                return $db; // Devolvemos el objeto muerto para que el CMS maneje el error visualmente
            }
            return $db;

        } catch (Exception $e) {
            // ¡ESTO ES LO QUE TE SALVA DE LA PANTALLA BLANCA!
            // En lugar de morir, mostramos qué pasó.
            echo '<div style="background:#000; color:#ff5555; padding:20px; font-family:monospace; border:1px solid red; z-index:99999; position:relative;">';
            echo '<strong>[ERROR CRÍTICO DE CONEXIÓN]</strong><br>';
            echo 'Intentando conectar a: ' . $n . '<br>';
            echo 'Driver usado: ' . $d . '<br>';
            echo 'Error devuelto: ' . $e->getMessage();
            echo '</div>';
            die(); // Detenemos aquí para leer el error
        }
    }
    
    private static function _config($config) {
        if(!function_exists('muservermakerConfigs')) return null;
        $muservermakerConfig = muservermakerConfigs();
        if(!is_array($muservermakerConfig)) return null;
        return isset($muservermakerConfig[$config]) ? $muservermakerConfig[$config] : null;
    }
}