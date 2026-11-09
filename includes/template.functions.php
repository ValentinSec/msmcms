<?php
/**
 * Global Configuration Helpers - PREPARED FOR OBFUSCATION
 * Pure PHP Version
 */

if (!function_exists('custom_config')) {
    function custom_config($key) {
        $path = defined('__PATH_CONFIGS__') ? __PATH_CONFIGS__ : 'includes/config/';
        $configPath = $path . 'custom_settings.json';
        
        if (!file_exists($configPath)) {
            return "";
        }
        
        $configData = file_get_contents($configPath);
        $settings = json_decode($configData, true);
        
        return (isset($settings[$key])) ? $settings[$key] : "";
    }
}

if (!function_exists('custom_config_active')) {
    function custom_config_active($key) {
        $val = custom_config($key);
        // Validación estricta para tipos booleanos, string 'on' o entero 1
        return ($val === true || $val === 'on' || $val === 1 || $val === '1');
    }
}