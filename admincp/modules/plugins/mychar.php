<?php
/**
 * Plugin: Mis Personajes (MyChar) - VISUAL ADAPTATION
 * PREPARED FOR OBFUSCATION (Pure PHP)
 */

// 1. RUTAS SEGURAS
$incPath = defined('__PATH_INCLUDES__') ? __PATH_INCLUDES__ : __DIR__ . '/../../../../includes/';
$myCharConfigPath = $incPath . 'config/mychar_config.json';

// 2. CARGAR CONFIGURACIÓN
$myCharConfig = [];
if (file_exists($myCharConfigPath)) {
    $myCharConfig = json_decode(file_get_contents($myCharConfigPath), true);
}

// 3. PROCESAR GUARDADO
$alertMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    
    // Contenedor 1: Funciones
    $myCharConfig['enable_reset'] = isset($_POST['enable_reset']) ? 1 : 0;
    $myCharConfig['enable_unstuck'] = isset($_POST['enable_unstuck']) ? 1 : 0;

    // Contenedor 2: Privacidad
    $myCharConfig['show_stats'] = isset($_POST['show_stats']) ? 1 : 0;
    $myCharConfig['show_inventory'] = isset($_POST['show_inventory']) ? 1 : 0;
    $myCharConfig['show_pvp_logs'] = isset($_POST['show_pvp_logs']) ? 1 : 0;
    
    if(file_put_contents($myCharConfigPath, json_encode($myCharConfig, JSON_PRETTY_PRINT))) {
        $alertMsg = '<div class="alert-success-custom">Configuración guardada correctamente.</div>';
    } else {
        $alertMsg = '<div class="alert alert-danger">Error al escribir el archivo JSON. Revisa permisos.</div>';
    }
}

// Estados de los interruptores
$resetActive = (isset($myCharConfig['enable_reset']) && $myCharConfig['enable_reset'] == 1) ? 'checked' : '';
$unstuckActive = (isset($myCharConfig['enable_unstuck']) && $myCharConfig['enable_unstuck'] == 1) ? 'checked' : '';

$statsActive = (!isset($myCharConfig['show_stats']) || $myCharConfig['show_stats'] == 1) ? 'checked' : '';
$inventoryActive = (!isset($myCharConfig['show_inventory']) || $myCharConfig['show_inventory'] == 1) ? 'checked' : '';
$pvpLogsActive = (!isset($myCharConfig['show_pvp_logs']) || $myCharConfig['show_pvp_logs'] == 1) ? 'checked' : '';

// --- RENDERIZADO HTML (PHP PURO) ---

// CSS
echo '<style>
    /* Layout */
    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*="col-"] { display: flex; flex-direction: column; margin-bottom: 30px; }
    
    /* Contenedores (Cards) */
    .settings-section { 
        flex: 1; margin-bottom: 0; display: flex; flex-direction: column; 
        background: #15171c; border: 1px solid #2d323e; border-radius: 8px; padding: 20px;
    }
    .settings-title {
        color: #fff; font-size: 18px; font-weight: 600; margin-bottom: 20px; 
        border-bottom: 1px solid #2d323e; padding-bottom: 15px; text-transform: uppercase;
    }
    .settings-label { display: block; color: #ccc; margin-bottom: 5px; font-weight: 600; }
    
    /* Switches */
    .switch-container { display: flex; align-items: center; min-height: 34px; margin-bottom: 15px; }
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin-right: 10px; margin-bottom: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #333; transition: .4s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #00d084; }
    input:checked + .slider:before { transform: translateX(20px); }
    .switch-label { color: #fff; font-weight: 500; }
    .switch-desc { display: block; font-size: 12px; color: #6c757d; margin-left: 50px; margin-top: -5px; margin-bottom: 15px; }

    /* Botón Guardar Verde */
    .btn-save-green {
        background-color: #00d084; color: #fff; border: none; padding: 10px 25px; 
        font-weight: 600; border-radius: 4px; cursor: pointer; transition: 0.3s;
    }
    .btn-save-green:hover { background-color: #00b371; }
    
    /* Alerta Custom */
    .alert-success-custom { 
        background: #1b1b28; border-left: 4px solid #00d084; color: #fff; 
        padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
    }
</style>';

// Container
echo '<div class="container-fluid">';
    
    // Alerta
    if(!empty($alertMsg)) {
        echo $alertMsg;
    }

    echo '<form method="post" action="index.php?module=my_plugins&load_plugin=mychar">';
        echo '<input type="hidden" name="action" value="save_config">';

        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Mis Personajes</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Grid Content
        echo '<div class="row row-flex">';
            
            // Columna 1: Funciones
            echo '<div class="col-md-6">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title"><i class="fa fa-cogs me-2"></i> Funciones de Panel</h3>';
                    
                    // Reset
                    echo '<div class="form-group">';
                        echo '<div class="switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="enable_reset" ' . $resetActive . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Habilitar Sistema de Resets</span>';
                        echo '</div>';
                        echo '<span class="switch-desc">Permite a los usuarios resetear sus personajes desde el panel.</span>';
                    echo '</div>';

                    // Unstuck
                    echo '<div class="form-group">';
                        echo '<div class="switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="enable_unstuck" ' . $unstuckActive . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Habilitar "Desatascar" (Unstuck)</span>';
                        echo '</div>';
                        echo '<span class="switch-desc">Mueve al personaje a Lorencia para solucionar bugs de posición.</span>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // Columna 2: Privacidad
            echo '<div class="col-md-6">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title"><i class="fa fa-eye me-2"></i> Privacidad y Visualización</h3>';
                    
                    // Stats
                    echo '<div class="form-group">';
                        echo '<div class="switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="show_stats" ' . $statsActive . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Mostrar Stats</span>';
                        echo '</div>';
                        echo '<span class="switch-desc">Si se desactiva, los stats dirán "Oculto" al público.</span>';
                    echo '</div>';

                    // Inventory
                    echo '<div class="form-group">';
                        echo '<div class="switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="show_inventory" ' . $inventoryActive . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Mostrar Inventario</span>';
                        echo '</div>';
                        echo '<span class="switch-desc">Si se desactiva, el equipamiento no será visible en el perfil.</span>';
                    echo '</div>';

                    // PVP Logs
                    echo '<div class="form-group">';
                        echo '<div class="switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="show_pvp_logs" ' . $pvpLogsActive . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Mostrar Historial PvP</span>';
                        echo '</div>';
                        echo '<span class="switch-desc">Si se desactiva, los logs de asesinatos estarán ocultos.</span>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row
    echo '</form>';
echo '</div>';
?>