<?php
/**
 * General Configuration Module - Custom CMS
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */
if(!defined('access')) die();

// Definimos la ruta subiendo desde /admincp/modules/settings/ hasta la raíz
$myJsonFile = __DIR__ . '/../../../includes/config/custom_settings.json';
$tzFile = __DIR__ . '/../../../includes/config/timezone.php';

// --- LÓGICA DE GUARDADO ---
$alertMsg = '';
if(isset($_POST['settings_submit'])) {
    try {
        $currentSettings = [];
        if (file_exists($myJsonFile)) {
            $currentSettings = json_decode(file_get_contents($myJsonFile), true);
        }
        // Lista de campos del formulario
        $fields = [
            'server_name', 'web_title', 'server_desc', 'server_files', 'server_timezone',
            'website_language', 'server_version', 'server_exp', 'server_exp_master',
            'server_drop', 'server_max_users', 'config_max_stats', 'config_max_rank',
            'reset_points', 'mr_points', 'reset_level', 'mr_level', 'reset_money',
            'reset_type', 'reset_limit', 'fake_online', 'fake_accounts',
            'username_length', 'password_length', 'social_fb', 'social_discord',
            'social_ig', 'social_wpp', 'social_yt', 'cs_day', 'cs_time',
            'coin_1_column', 'coin_2_column', 'coin_3_column', 'social_email'
        ];

        // 2. ACTUALIZAR SOLO LOS CAMPOS DEL FORMULARIO
        foreach($fields as $f) {
            $currentSettings[$f] = isset($_POST[$f]) ? $_POST[$f] : "";
        }

        // 3. AGREGAR LOS CHECKBOXES (SWITCHES)
        $currentSettings['system_active'] = isset($_POST['system_active']);
        $currentSettings['active_cs'] = isset($_POST['active_cs']);
        $currentSettings['active_social'] = isset($_POST['active_social']);
        $currentSettings['active_coin_1'] = isset($_POST['active_coin_1']);
        $currentSettings['active_coin_2'] = isset($_POST['active_coin_2']);
        $currentSettings['active_coin_3'] = isset($_POST['active_coin_3']);
        
        // 5. GUARDAR DATOS
        $jsonData = json_encode($currentSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $save = file_put_contents($myJsonFile, $jsonData);
        
        if($save === false) {
            throw new Exception("Error: No se pudo escribir en el archivo JSON. Revisa permisos 777.");
        }

        // --- GUARDAR ZONA HORARIA EN TIMEZONE.PHP ---
        $selectedTz = isset($_POST['server_timezone']) ? $_POST['server_timezone'] : 'UTC';
        $tzContent = "<?php\n\ndate_default_timezone_set('" . addslashes($selectedTz) . "');\n";
        $saveTz = file_put_contents($tzFile, $tzContent);
        
        if($saveTz === false) {
            throw new Exception("Error: No se pudo modificar timezone.php. Revisa permisos 777.");
        }

        // --- CAMBIO ESTÉTICO: MENSAJE UNIFICADO ---
        $alertMsg = '<div class="alert-success-custom">Cambios guardados correctamente.</div>';

    } catch (Exception $e) {
        $alertMsg = '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
}

// Cargar valores actuales
$conf = [];
if(file_exists($myJsonFile)) {
    $conf = json_decode(file_get_contents($myJsonFile), true);
}
if(!is_array($conf)) $conf = [];

function getV($key, $data) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ""; }
function getC($key, $data) { return (isset($data[$key]) && $data[$key] === true) ? "checked" : ""; }

// --- RENDERIZADO HTML (PHP PURO) ---

// CSS
echo '<style>
    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*="col-"] { display: flex; flex-direction: column; margin-bottom: 30px; }
    .row-flex .settings-section { flex: 1; margin-bottom: 0; display: flex; flex-direction: column; }
    .switch-container { display: flex; align-items: center; min-height: 34px; }
    /* ESTILO MENSAJE ÉXITO CUSTOM */
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';

// Container
echo '<div class="container-fluid">';
    
    // Alerta
    if(!empty($alertMsg)) {
        echo $alertMsg;
    }

    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración General</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="settings_submit" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Grid Content
        echo '<div class="row row-flex">';
            
            // Columna 1: Info Servidor
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Información del Servidor</h3>';
                    
                    // Ajax Active
                    echo '<div class="form-group switch-container">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="system_active" ' . getC('system_active', $conf) . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        echo '<span class="switch-label">Ajax Active</span>';
                    echo '</div>';

                    // Server Name
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Nombre del Servidor</label>';
                        echo '<input type="text" name="server_name" class="form-control-dark" value="' . getV('server_name', $conf) . '" placeholder="Ej: Thorus MU">';
                    echo '</div>';

                    // Web Title
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Título de la Web</label>';
                        echo '<input type="text" name="web_title" class="form-control-dark" value="' . getV('web_title', $conf) . '" placeholder="Ej: Bienvenido a Thorus MU - Season 6">';
                    echo '</div>';

                    // Description
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Descripción del Servidor</label>';
                        echo '<textarea name="server_desc" class="form-control-dark" style="height:80px;" placeholder="Ej: El servidor más estable con la mejor comunidad...">' . getV('server_desc', $conf) . '</textarea>';
                    echo '</div>';

                    // Server Files
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tipo de Archivos del Servidor</label>';
                        echo '<select name="server_files" class="form-control-dark">';
                            echo '<option value="SSEMU" ' . (getV('server_files', $conf) == "SSEMU" ? "selected" : "") . '>SSEMU</option>';
                            echo '<option value="IGCN" ' . (getV('server_files', $conf) == "IGCN" ? "selected" : "") . '>IGCN</option>';
                            echo '<option value="MUEmu" ' . (getV('server_files', $conf) == "MUEmu" ? "selected" : "") . '>MUEmu</option>';
                            echo '<option value="Louis" ' . (getV('server_files', $conf) == "Louis" ? "selected" : "") . '>Louis</option>';
                        echo '</select>';
                    echo '</div>';

                    // Server Timezone (NUEVO)
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Zona Horaria del Servidor</label>';
                        echo '<select name="server_timezone" class="form-control-dark">';
                            $tzlist = timezone_identifiers_list();
                            $currentTz = getV('server_timezone', $conf);
                            if(empty($currentTz)) $currentTz = 'UTC';
                            foreach($tzlist as $tz) {
                                $sel = ($currentTz == $tz) ? 'selected' : '';
                                echo '<option value="'.$tz.'" '.$sel.'>'.$tz.'</option>';
                            }
                        echo '</select>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // Columna 2: Lenguaje & Config
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Lenguaje & Config</h3>';
                    
                    // Language
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Lenguaje</label>';
                        echo '<select name="website_language" class="form-control-dark">';
                            echo '<option value="es" ' . (getV('website_language', $conf) == "es" ? "selected" : "") . '>Español</option>';
                            echo '<option value="en" ' . (getV('website_language', $conf) == "en" ? "selected" : "") . '>English</option>';
                        echo '</select>';
                    echo '</div>';
                    
                    // Version
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Versión del Servidor</label>';
                        echo '<select name="server_version" class="form-control-dark">';
                            $versiones = ["0.97", "0.99", "Season 1", "Season 2", "Season 3", "Season 4", "Season 5", "Season 6"];
                            $currentVer = getV('server_version', $conf);
                            foreach($versiones as $ver) {
                                $selected = ($currentVer == $ver) ? 'selected' : '';
                                echo '<option value="'.$ver.'" '.$selected.'>'.$ver.'</option>';
                            }
                        echo '</select>';
                    echo '</div>';

                    // Exp
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Experiencia</label>';
                        echo '<input type="text" name="server_exp" class="form-control-dark" value="' . getV('server_exp', $conf) . '" placeholder="Ej: 9999x">';
                    echo '</div>';
                    
                    // Master Exp
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Experiencia Master</label>';
                        echo '<input type="text" name="server_exp_master" class="form-control-dark" value="' . getV('server_exp_master', $conf) . '" placeholder="Ej: 500x">';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // Columna 3: Drop & Extras
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Drop & Extras</h3>';
                    
                    // Drop
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Drop Rate</label>';
                        echo '<input type="text" name="server_drop" class="form-control-dark" value="' . getV('server_drop', $conf) . '" placeholder="Ej: 40%">';
                    echo '</div>';
                    
                    // Max Users
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Usuarios Máximos</label>';
                        echo '<input type="text" name="server_max_users" class="form-control-dark" value="' . getV('server_max_users', $conf) . '" placeholder="Ej: 500">';
                    echo '</div>';
                    
                    // Max Stats
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Max Stats Points:</label>';
                        echo '<input type="text" name="config_max_stats" class="form-control-dark" value="' . getV('config_max_stats', $conf) . '" placeholder="Ej: 65535">';
                    echo '</div>';
                    
                    // Max Rank
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Max Characters in Ranking</label>';
                        echo '<input type="text" name="config_max_rank" class="form-control-dark" value="' . getV('config_max_rank', $conf) . '" placeholder="Ej: 100">';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row 1

        // Row 2: Resets & Stats Visuales & Coins & CS
        echo '<div class="row row-flex">';
            
            // Columna 1: Puntos por Reset
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Puntos por Reset</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Puntos por Reset</label>';
                        echo '<input type="text" name="reset_points" class="form-control-dark" value="' . getV('reset_points', $conf) . '" placeholder="Ej: 500">';
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Puntos por Master Reset</label>';
                        echo '<input type="text" name="mr_points" class="form-control-dark" value="' . getV('mr_points', $conf) . '" placeholder="Ej: 10">';
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Reset Level</label>';
                        echo '<input type="text" name="reset_level" class="form-control-dark" value="' . getV('reset_level', $conf) . '" placeholder="Ej: 400">';
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Costo en Zen</label>';
                        echo '<input type="text" name="reset_money" class="form-control-dark" value="' . getV('reset_money', $conf) . '" placeholder="Ej: 10000000">';
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tipo de Reset</label>';
                        echo '<select name="reset_type" class="form-control-dark">';
                            echo '<option value="keep" ' . (getV('reset_type', $conf) == "keep" ? "selected" : "") . '>Acumulativo</option>';
                            echo '<option value="reset" ' . (getV('reset_type', $conf) == "reset" ? "selected" : "") . '>Borra Stats</option>';
                        echo '</select>';
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Max. Resets:</label>';
                        echo '<input type="text" name="reset_limit" class="form-control-dark" value="' . getV('reset_limit', $conf) . '" placeholder="Ej: 1000">';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // Columna 2: Stats Visuales & Coins
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Estadísticas Visuales (Multiplicadores)</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Multiplicador Usuarios Online</label>';
                        echo '<input type="number" inputmode="decimal" name="fake_online" class="form-control-dark no-spinners" value="' . getV('fake_online', $conf) . '" placeholder="Ej: 2.0">';
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Multiplicador Cuentas Creadas</label>';
                        echo '<input type="number" inputmode="decimal" name="fake_accounts" class="form-control-dark no-spinners" value="' . getV('fake_accounts', $conf) . '" placeholder="Ej: 1.5">';
                    echo '</div>';
                    
                    echo '<hr style="border-color: #1e293b; margin: 20px 0;">';
                    echo '<h3 class="settings-title" style="border:none;"><i class="fa fa-laptop"></i> Configurar Monedas</h3>';
                    
                    for($i=1; $i<=3; $i++) {
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Columna Coins ' . $i . '</label>';
                            echo '<input type="text" name="coin_' . $i . '_column" class="form-control-dark" value="' . getV("coin_{$i}_column", $conf) . '" placeholder="Ej: WCoinC">';
                            echo '<div class="switch-container" style="margin-top: 5px;">';
                                echo '<label class="switch" style="transform: scale(0.8); margin-right: 5px;">';
                                    echo '<input type="checkbox" name="active_coin_' . $i . '" ' . getC("active_coin_{$i}", $conf) . '>';
                                    echo '<span class="slider"></span>';
                                echo '</label>';
                                echo '<span style="color:#64748b; font-size:12px;">Activar</span>';
                            echo '</div>';
                        echo '</div>';
                    }

                echo '</div>';
            echo '</div>';

            // Columna 3: CS & Social
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Castle Siege</h3>';
                    
                    echo '<div class="row">';
                        echo '<div class="col-xs-7">';
                            echo '<select name="cs_day" class="form-control-dark">';
                                echo '<option value="7" ' . (getV('cs_day', $conf) == "7" ? "selected" : "") . '>Sunday</option>';
                                echo '<option value="1" ' . (getV('cs_day', $conf) == "1" ? "selected" : "") . '>Monday</option>';
                                echo '<option value="2" ' . (getV('cs_day', $conf) == "2" ? "selected" : "") . '>Tuesday</option>';
                                echo '<option value="3" ' . (getV('cs_day', $conf) == "3" ? "selected" : "") . '>Wednesday</option>';
                                echo '<option value="4" ' . (getV('cs_day', $conf) == "4" ? "selected" : "") . '>Thursday</option>';
                                echo '<option value="5" ' . (getV('cs_day', $conf) == "5" ? "selected" : "") . '>Friday</option>';
                                echo '<option value="6" ' . (getV('cs_day', $conf) == "6" ? "selected" : "") . '>Saturday</option>';
                            echo '</select>';
                        echo '</div>';
                        echo '<div class="col-xs-5">';
                            echo '<input type="time" name="cs_time" class="form-control-dark" value="' . getV('cs_time', $conf) . '">';
                        echo '</div>';
                    echo '</div>';

                    echo '<div style="margin-top: 20px;">';
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="active_cs" ' . getC('active_cs', $conf) . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Activar Castle Siege</span>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div style="border:none; padding:0; margin-top:20px;">';
                        echo '<h3 class="settings-title">Social Media</h3>';
                        
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="active_social" ' . getC('active_social', $conf) . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Activar Redes</span>';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Facebook</label>';
                            echo '<input type="text" name="social_fb" class="form-control-dark" value="' . getV('social_fb', $conf) . '" placeholder="https://facebook.com/muservermaker">';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Discord</label>';
                            echo '<input type="text" name="social_discord" class="form-control-dark" value="' . getV('social_discord', $conf) . '" placeholder="https://discord.gg/muservermaker">';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">WhatsApp</label>';
                            echo '<input type="text" name="social_wpp" class="form-control-dark" value="' . getV('social_wpp', $conf) . '" placeholder="https://chat.whatsapp.com/muservermaker">';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">YouTube</label>';
                            echo '<input type="text" name="social_yt" class="form-control-dark" value="' . getV('social_yt', $conf) . '" placeholder="https://youtube.com/c/muservermakers">';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Email</label>';
                            echo '<input type="text" name="social_email" class="form-control-dark" value="' . getV('social_email', $conf) . '" placeholder="contacto@muservermaker.com">';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row 2

    echo '</form>';
echo '</div>';
?>