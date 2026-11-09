<?php
/**
 * Black Market Configuration Module
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */
if(!defined('access')) die();

// 1. RUTAS Y CONFIGURACIÓN
$basePath = __DIR__ . '/../../../includes/config/';
$configFile = $basePath . 'black_market_config.json';
$settingsFile = $basePath . 'custom_settings.json';

// Cargar configs actuales
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'module_active' => 'off',
    'tax_percent' => 10,
    'allowed_currency' => 'WCoinC',
    'market_title' => 'Mercado Negro'
];

$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

// 2. LÓGICA DE GUARDADO
if(isset($_POST['save_config'])) {
    try {
        $config['module_active'] = isset($_POST['module_active']) ? 'on' : 'off';
        $config['tax_percent'] = (int)$_POST['tax_percent'];
        $config['allowed_currency'] = $_POST['allowed_currency'];
        $config['market_title'] = $_POST['market_title'];
        
        $save = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if($save === false) {
            throw new Exception("Error: No se pudo escribir en el archivo JSON.");
        }
        
        echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
        
        // Recargar para mostrar cambios
        $config = json_decode(file_get_contents($configFile), true);

    } catch (Exception $e) {
        echo '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
}

// 3. OBTENER MONEDAS DISPONIBLES
$availableCurrencies = ['Credits' => 'Credits'];
for($i=1; $i<=3; $i++) {
    if(isset($settings["active_coin_$i"]) && $settings["active_coin_$i"] == 'on') {
        $col = $settings["coin_{$i}_column"];
        $availableCurrencies[$col] = $settings["coin_{$i}_name"];
    }
}

// Helpers visuales
function getV($key, $data) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ""; }
function getC($key, $data) { return (isset($data[$key]) && $data[$key] === 'on') ? "checked" : ""; }

// --- RENDERIZADO HTML (PHP PURO) ---

// CSS Styles
echo '<style>
.row-flex { display: flex; flex-wrap: wrap; }
.row-flex > [class*="col-"] { display: flex; flex-direction: column; margin-bottom: 30px; }
.row-flex .settings-section { flex: 1; margin-bottom: 0; display: flex; flex-direction: column; }
.switch-container { display: flex; align-items: center; min-height: 34px; margin-bottom: 15px; }
.alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';

echo '<div class="container-fluid">';
    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración Black Market</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="save_config" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Content Grid
        echo '<div class="row row-flex">';
            
            // Columna 1: Estado & General
            echo '<div class="col-md-6">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Estado & General</h3>';
                    
                    // Switch Activar
                    echo '<div class="form-group switch-container">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="module_active" ' . getC('module_active', $config) . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        echo '<span class="switch-label">Activar Módulo</span>';
                    echo '</div>';

                    // Input Título
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Título del Mercado</label>';
                        echo '<input type="text" name="market_title" class="form-control-dark" value="' . getV('market_title', $config) . '" placeholder="Ej: Mercado Negro">';
                        echo '<p style="color:#666; font-size:12px; margin-top:5px;">Nombre visible en el UserCP.</p>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // Columna 2: Economía & Impuestos
            echo '<div class="col-md-6">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Economía & Impuestos</h3>';
                    
                    // Select Moneda
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Moneda de Comercio</label>';
                        echo '<select name="allowed_currency" class="form-control-dark">';
                            foreach($availableCurrencies as $col => $name) {
                                $selected = ($config['allowed_currency'] == $col) ? 'selected' : '';
                                echo '<option value="' . $col . '" ' . $selected . '>' . $name . ' (' . $col . ')</option>';
                            }
                        echo '</select>';
                    echo '</div>';

                    // Input Tax
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tax / Fee (%)</label>';
                        echo '<input type="number" name="tax_percent" class="form-control-dark" value="' . getV('tax_percent', $config) . '" min="0" max="100">';
                        echo '<p style="color:#666; font-size:12px; margin-top:5px;">Porcentaje que se retiene (quema) en cada venta.</p>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row Flex
    echo '</form>';
echo '</div>'; // End Container
?>