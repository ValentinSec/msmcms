<?php
/**
 * Global Configuration Helpers - PREPARED FOR OBFUSCATION
 * Pure PHP Version
 */

if (!function_exists('custom_config')) {
    function custom_config($key) {
        static $settings = null;   // se lee una sola vez por carga de página

        if ($settings === null) {
            $path = defined('__PATH_CONFIGS__') ? __PATH_CONFIGS__ : 'includes/config/';
            $configPath = $path . 'custom_settings.json';

            if (!file_exists($configPath)) {
                $settings = array();   // marca como "ya intentado", evita releer en cada llamada
                return "";
            }

            $configData = file_get_contents($configPath);
            $decoded = json_decode($configData, true);
            $settings = is_array($decoded) ? $decoded : array();
        }

        return (isset($settings[$key])) ? $settings[$key] : "";
    }
}

if (!function_exists('custom_config_active')) {
    function custom_config_active($key) {
        $val = custom_config($key);
        // Validaci贸n estricta para tipos booleanos, string 'on' o entero 1
        return ($val === true || $val === 'on' || $val === 1 || $val === '1');
    }
}