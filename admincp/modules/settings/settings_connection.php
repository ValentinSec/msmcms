<?php
/**
 * Connection, System & Admin Settings Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

if(!defined('access')) die();

// Definimos la ruta absoluta al archivo JSON principal
$mainConfigFile = __PATH_CONFIGS__ . 'muservermaker.json';

// --- LÓGICA DE GUARDADO ---
if(isset($_POST['save_connection_settings'])) {
    try {
        // 1. Cargar configuración actual
        $currentConfig = [];
        if (file_exists($mainConfigFile)) {
            $currentConfig = json_decode(file_get_contents($mainConfigFile), true);
        }

        // 2. Guardar Configuración General (SQL, SEO, ETC)
        $fields = [
            'SQL_DB_HOST', 'SQL_DB_NAME', 'SQL_DB_2_NAME', 'SQL_DB_USER', 
            'SQL_DB_PASS', 'SQL_DB_PORT', 'SQL_PDO_DRIVER', 'SQL_PASSWORD_ENCRYPTION',
            'maintenance_page', 'website_forum_link',
            'website_meta_description', 'website_meta_keywords'
        ];

        foreach($fields as $f) {
            if(isset($_POST[$f])) $currentConfig[$f] = $_POST[$f];
        }

        $currentConfig['SQL_USE_2_DB'] = isset($_POST['SQL_USE_2_DB']);
        $currentConfig['system_active'] = isset($_POST['system_active']);
        $currentConfig['error_reporting'] = isset($_POST['error_reporting']);

        // 3. Subida de Imagen
        if(isset($_FILES['og_image']) && $_FILES['og_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['og_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if(in_array($ext, $allowed)) {
                $targetFile = __PATH_IMG__ . 'og_share.' . $ext;
                if(move_uploaded_file($_FILES['og_image']['tmp_name'], $targetFile)) {
                    $currentConfig['og_image_path'] = 'img/og_share.' . $ext;
                }
            }
        }

        // 4. LÓGICA DE ADMINISTRADORES (TABLA)
        $newAdmins = [];
        
        // Procesar admins existentes
        if(isset($_POST['admin_level']) && is_array($_POST['admin_level'])) {
            foreach($_POST['admin_level'] as $user => $level) {
                // Filtros básicos
                if(!ctype_alnum($user)) continue;
                $level = (int)$level;
                $pin = isset($_POST['admin_pin'][$user]) ? $_POST['admin_pin'][$user] : '123456';

                // Si el nivel es 0, se elimina (skip)
                if($level == 0) {
                    if($user == $_SESSION['username']) throw new Exception("No puedes eliminar tu propia cuenta de administrador.");
                    continue; 
                }

                // Guardamos estructura nueva con PIN
                $newAdmins[$user] = [
                    'level' => $level,
                    'pin'   => $pin
                ];
            }
        }

        // Procesar nuevo admin
        if(!empty($_POST['new_admin_user'])) {
            $newUser = $_POST['new_admin_user'];
            $newLevel = (int)$_POST['new_admin_level'];
            $newPin = !empty($_POST['new_admin_pin']) ? $_POST['new_admin_pin'] : '123456';

            if(array_key_exists($newUser, $newAdmins)) throw new Exception("El usuario $newUser ya es administrador.");
            if($newLevel > 0) {
                $newAdmins[$newUser] = [
                    'level' => $newLevel,
                    'pin'   => $newPin
                ];
            }
        }

        // Guardar admins en el config
        $currentConfig['admins'] = $newAdmins;

        // 5. Guardar Archivo
        $jsonData = json_encode($currentConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if(file_put_contents($mainConfigFile, $jsonData) === false) {
            throw new Exception("Error escribiendo muservermaker.json");
        }

        echo '<div class="alert-success-custom">Configuración guardada correctamente.</div>';

    } catch (Exception $e) {
        echo '<div class="alert alert-danger" style="margin-bottom:20px; background:#f64e60; border:none; color:#fff; padding:15px; border-radius:4px;">'.$e->getMessage().'</div>';
    }
}

// Cargar valores
$conf = file_exists($mainConfigFile) ? json_decode(file_get_contents($mainConfigFile), true) : [];

// Helpers
function getV($key, $data) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ""; }
function getC($key, $data) { return (isset($data[$key]) && $data[$key] === true) ? "checked" : ""; }

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<style>
    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*="col-"] { display: flex; flex-direction: column; margin-bottom: 30px; }
    .row-flex .settings-section { flex: 1; margin-bottom: 0; display: flex; flex-direction: column; background: #1b1b28; border: 1px solid #2b2b40; padding: 25px; border-radius: 8px; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .settings-title { color: #fff; font-size: 15px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #2b2b40; }
    .settings-label { color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .form-control-dark { width: 100%; background: #151521; border: 1px solid #2b2b40; color: #fff; padding: 10px; font-size: 13px; border-radius: 4px; outline: none; }
    .form-control-dark:focus { border-color: #3699ff; }
    .btn-save-green { background: #00d084; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .btn-save-green:hover { background: #00b372; }
    
    /* Admin Table */
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { text-align: left; color: #7e8299; padding: 10px; border-bottom: 1px solid #2b2b40; font-size: 11px; text-transform: uppercase; }
    .admin-table td { padding: 10px; border-bottom: 1px solid #2b2b40; color: #fff; vertical-align: middle; }
    .admin-table tr:last-child td { border-bottom: none; }
    
    /* Switch */
    .switch-container { display: flex; align-items: center; margin-bottom: 15px; }
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin-right: 10px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #2b2b40; transition: .4s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #3699ff; }
    input:checked + .slider:before { transform: translateX(20px); }
    .switch-label { color: #fff; font-size: 13px; }
</style>';

echo '<div class="container-fluid">';
    echo '<form action="" method="post" enctype="multipart/form-data">';
        
        // HEADER
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Conexión</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="save_connection_settings" class="btn-save-green">Guardar Todo</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // MAIN CONTENT
        echo '<div class="row row-flex">';
            
            // COLUMNA 1: SQL
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title"><i class="fa fa-database"></i> SQL Connection</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">SQL Host / IP</label>';
                        echo '<input type="text" name="SQL_DB_HOST" class="form-control-dark" value="' . getV('SQL_DB_HOST', $conf) . '" required>';
                    echo '</div>';

                    echo '<div class="row">';
                        echo '<div class="col-md-8">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Database Name</label>';
                                echo '<input type="text" name="SQL_DB_NAME" class="form-control-dark" value="' . getV('SQL_DB_NAME', $conf) . '" required>';
                            echo '</div>';
                        echo '</div>';
                        echo '<div class="col-md-4">';
                            echo '<div class="form-group">';
                                echo '<label class="settings-label">Port</label>';
                                echo '<input type="number" name="SQL_DB_PORT" class="form-control-dark" value="' . getV('SQL_DB_PORT', $conf) . '">';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">SQL User</label>';
                        echo '<input type="text" name="SQL_DB_USER" class="form-control-dark" value="' . getV('SQL_DB_USER', $conf) . '" required>';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">SQL Password</label>';
                        echo '<input type="password" name="SQL_DB_PASS" class="form-control-dark" value="' . getV('SQL_DB_PASS', $conf) . '" required>';
                    echo '</div>';

                    // DB 2 & Switch
                    echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #2b2b40;">';
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch">';
                                echo '<input type="checkbox" name="SQL_USE_2_DB" ' . getC('SQL_USE_2_DB', $conf) . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="switch-label">Usar Me_MuOnline (DB 2)</span>';
                        echo '</div>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Database 2 Name</label>';
                            echo '<input type="text" name="SQL_DB_2_NAME" class="form-control-dark" value="' . getV('SQL_DB_2_NAME', $conf) . '">';
                        echo '</div>';
                    echo '</div>';
                    
                    // Driver & Encryption
                    echo '<div class="row mt-2">';
                        echo '<div class="col-md-6">';
                            echo '<label class="settings-label">Driver</label>';
                            echo '<select name="SQL_PDO_DRIVER" class="form-control-dark">';
                                echo '<option value="1" ' . (getV('SQL_PDO_DRIVER', $conf) == 1 ? 'selected' : '') . '>DBLIB</option>';
                                echo '<option value="2" ' . (getV('SQL_PDO_DRIVER', $conf) == 2 ? 'selected' : '') . '>SQLSRV</option>';
                                echo '<option value="3" ' . (getV('SQL_PDO_DRIVER', $conf) == 3 ? 'selected' : '') . '>ODBC</option>';
                            echo '</select>';
                        echo '</div>';
                        echo '<div class="col-md-6">';
                            echo '<label class="settings-label">Encryption</label>';
                            echo '<select name="SQL_PASSWORD_ENCRYPTION" class="form-control-dark">';
                                echo '<option value="none" ' . (getV('SQL_PASSWORD_ENCRYPTION', $conf) == 'none' ? 'selected' : '') . '>None</option>';
                                echo '<option value="md5" ' . (getV('SQL_PASSWORD_ENCRYPTION', $conf) == 'md5' ? 'selected' : '') . '>MD5</option>';
                                echo '<option value="sha256" ' . (getV('SQL_PASSWORD_ENCRYPTION', $conf) == 'sha256' ? 'selected' : '') . '>SHA256</option>';
                            echo '</select>';
                        echo '</div>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // COLUMNA 2: SEO & SYSTEM
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title"><i class="fa fa-globe"></i> SEO & System</h3>';
                    
                    echo '<div class="form-group switch-container">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="system_active" ' . getC('system_active', $conf) . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        echo '<span class="switch-label">Sitio Web Activo</span>';
                    echo '</div>';

                    echo '<div class="form-group switch-container">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="error_reporting" ' . getC('error_reporting', $conf) . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        echo '<span class="switch-label">Debug Mode</span>';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Maintenance URL</label>';
                        echo '<input type="text" name="maintenance_page" class="form-control-dark" value="' . getV('maintenance_page', $conf) . '">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Forum URL</label>';
                        echo '<input type="text" name="website_forum_link" class="form-control-dark" value="' . getV('website_forum_link', $conf) . '">';
                    echo '</div>';

                    echo '<hr style="border-color: #2b2b40;">';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Meta Description</label>';
                        echo '<textarea name="website_meta_description" class="form-control-dark" style="height: 80px;">' . getV('website_meta_description', $conf) . '</textarea>';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Meta Keywords</label>';
                        echo '<textarea name="website_meta_keywords" class="form-control-dark" style="height: 60px;">' . getV('website_meta_keywords', $conf) . '</textarea>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // COLUMNA 3: ADMINS & SOCIAL
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    
                    echo '<h3 class="settings-title"><i class="fa fa-user-shield"></i> Administradores</h3>';
                    echo '<p style="color:#777; font-size:11px; margin-bottom:15px;">Establece Nivel 0 para eliminar a un administrador.</p>';
                    
                    echo '<div class="table-responsive">';
                        echo '<table class="admin-table">';
                            echo '<thead>';
                                echo '<tr>';
                                    echo '<th>Usuario</th>';
                                    echo '<th style="width:70px;">Nivel</th>';
                                    echo '<th style="width:100px;">PIN 2FA</th>';
                                echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                                
                                if(isset($conf['admins']) && is_array($conf['admins'])) {
                                    foreach($conf['admins'] as $user => $data) {
                                        // Compatibilidad
                                        $lvl = is_array($data) ? $data['level'] : $data;
                                        $pin = is_array($data) && isset($data['pin']) ? $data['pin'] : '123456';

                                        echo '<tr>';
                                            echo '<td style="font-weight:600; color:#3699ff;">' . $user . '</td>';
                                            echo '<td><input type="number" name="admin_level[' . $user . ']" value="' . $lvl . '" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                            echo '<td><input type="text" name="admin_pin[' . $user . ']" value="' . $pin . '" class="form-control-dark" style="padding:5px; text-align:center; letter-spacing:1px;"></td>';
                                        echo '</tr>';
                                    }
                                }
                                
                                echo '<tr style="border-top: 2px solid #2b2b40;">';
                                    echo '<td><input type="text" name="new_admin_user" placeholder="Nuevo Usuario" class="form-control-dark" style="padding:5px;"></td>';
                                    echo '<td><input type="number" name="new_admin_level" placeholder="Lvl" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                    echo '<td><input type="text" name="new_admin_pin" placeholder="PIN" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                echo '</tr>';

                            echo '</tbody>';
                        echo '</table>';
                    echo '</div>';

                    echo '<br>';
                    
                    echo '<h3 class="settings-title" style="margin-top:10px;"><i class="fa fa-share-alt"></i> Imagen Social</h3>';
                    echo '<div class="form-group">';
                        echo '<div style="background: #151521; padding: 10px; border-radius: 4px; text-align: center; border: 1px dashed #2b2b40;">';
                            if(isset($conf['og_image_path']) && !empty($conf['og_image_path'])) {
                                echo '<img src="../' . $conf['og_image_path'] . '?v=' . time() . '" style="max-width: 100%; max-height: 100px; margin-bottom: 10px; border-radius: 4px;">';
                            } else {
                                echo '<p style="font-size: 11px; color: #666;">Sin imagen</p>';
                            }
                            echo '<input type="file" name="og_image" class="form-control-dark" style="padding: 5px;">';
                        echo '</div>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row Flex
    echo '</form>';
echo '</div>';
?>
        // 2. Guardar Configuración General (SQL, SEO, ETC)
        $fields = [
            'SQL_DB_HOST', 'SQL_DB_NAME', 'SQL_DB_2_NAME', 'SQL_DB_USER', 
            'SQL_DB_PASS', 'SQL_DB_PORT', 'SQL_PDO_DRIVER', 'SQL_PASSWORD_ENCRYPTION',
            'maintenance_page', 'website_forum_link',
            'website_meta_description', 'website_meta_keywords'
        ];

        foreach($fields as $f) {
            if(isset($_POST[$f])) $currentConfig[$f] = $_POST[$f];
        }

        $currentConfig['SQL_USE_2_DB'] = isset($_POST['SQL_USE_2_DB']);
        $currentConfig['system_active'] = isset($_POST['system_active']);
        $currentConfig['error_reporting'] = isset($_POST['error_reporting']);

        // 3. Subida de Imagen
        if(isset($_FILES['og_image']) && $_FILES['og_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['og_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if(in_array($ext, $allowed)) {
                $targetFile = __PATH_IMG__ . 'og_share.' . $ext;
                if(move_uploaded_file($_FILES['og_image']['tmp_name'], $targetFile)) {
                    $currentConfig['og_image_path'] = 'img/og_share.' . $ext;
                }
            }
        }

        // 4. LÓGICA DE ADMINISTRADORES (TABLA)
        $newAdmins = [];
        
        // Procesar admins existentes
        if(isset($_POST['admin_level']) && is_array($_POST['admin_level'])) {
            foreach($_POST['admin_level'] as $user => $level) {
                // Filtros básicos
                if(!ctype_alnum($user)) continue;
                $level = (int)$level;
                $pin = isset($_POST['admin_pin'][$user]) ? $_POST['admin_pin'][$user] : '123456';

                // Si el nivel es 0, se elimina (skip)
                if($level == 0) {
                    if($user == $_SESSION['username']) throw new Exception("No puedes eliminar tu propia cuenta de administrador.");
                    continue; 
                }

                // Guardamos estructura nueva con PIN
                $newAdmins[$user] = [
                    'level' => $level,
                    'pin'   => $pin
                ];
            }
        }

        // Procesar nuevo admin
        if(!empty($_POST['new_admin_user'])) {
            $newUser = $_POST['new_admin_user'];
            $newLevel = (int)$_POST['new_admin_level'];
            $newPin = !empty($_POST['new_admin_pin']) ? $_POST['new_admin_pin'] : '123456';

            if(array_key_exists($newUser, $newAdmins)) throw new Exception("El usuario $newUser ya es administrador.");
            if($newLevel > 0) {
                $newAdmins[$newUser] = [
                    'level' => $newLevel,
                    'pin'   => $newPin
                ];
            }
        }

        // Guardar admins en el config
        $currentConfig['admins'] = $newAdmins;

        // 5. Guardar Archivo
        $jsonData = json_encode($currentConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if(file_put_contents($mainConfigFile, $jsonData) === false) {
            throw new Exception("Error escribiendo muservermaker.json");
        }

        echo '<div class="alert-success-custom">Configuración guardada correctamente.</div>';

    } catch (Exception $e) {
        echo '<div class="alert alert-danger" style="margin-bottom:20px; background:#f64e60; border:none; color:#fff; padding:15px; border-radius:4px;">'.$e->getMessage().'</div>';
    }
}

// Cargar valores
$conf = file_exists($mainConfigFile) ? json_decode(file_get_contents($mainConfigFile), true) : [];

// Helpers
function getV($key, $data) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ""; }
function getC($key, $data) { return (isset($data[$key]) && $data[$key] === true) ? "checked" : ""; }
?>

<style>
    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; margin-bottom: 30px; }
    .row-flex .settings-section { flex: 1; margin-bottom: 0; display: flex; flex-direction: column; background: #1b1b28; border: 1px solid #2b2b40; padding: 25px; border-radius: 8px; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .settings-title { color: #fff; font-size: 15px; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #2b2b40; }
    .settings-label { color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .form-control-dark { width: 100%; background: #151521; border: 1px solid #2b2b40; color: #fff; padding: 10px; font-size: 13px; border-radius: 4px; outline: none; }
    .form-control-dark:focus { border-color: #3699ff; }
    .btn-save-green { background: #00d084; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .btn-save-green:hover { background: #00b372; }
    
    /* Admin Table */
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { text-align: left; color: #7e8299; padding: 10px; border-bottom: 1px solid #2b2b40; font-size: 11px; text-transform: uppercase; }
    .admin-table td { padding: 10px; border-bottom: 1px solid #2b2b40; color: #fff; vertical-align: middle; }
    .admin-table tr:last-child td { border-bottom: none; }
    
    /* Switch */
    .switch-container { display: flex; align-items: center; margin-bottom: 15px; }
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin-right: 10px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #2b2b40; transition: .4s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #3699ff; }
    input:checked + .slider:before { transform: translateX(20px); }
    .switch-label { color: #fff; font-size: 13px; }
</style>

<div class="container-fluid">
    <form action="" method="post" enctype="multipart/form-data">
        
        <div class="row">
            <div class="col-lg-12">
                <div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">
                    <div class="col-xs-6">
                        <h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Conexión</h3>
                    </div>
                    <div class="col-xs-6 text-right">
                        <button type="submit" name="save_connection_settings" class="btn-save-green">Guardar Todo</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-flex">
            
            <div class="col-md-4">
                <div class="settings-section">
                    <h3 class="settings-title"><i class="fa fa-database"></i> SQL Connection</h3>
                    
                    <div class="form-group">
                        <label class="settings-label">SQL Host / IP</label>
                        <input type="text" name="SQL_DB_HOST" class="form-control-dark" value="<?php echo getV('SQL_DB_HOST', $conf); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="settings-label">Database Name</label>
                                <input type="text" name="SQL_DB_NAME" class="form-control-dark" value="<?php echo getV('SQL_DB_NAME', $conf); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="settings-label">Port</label>
                                <input type="number" name="SQL_DB_PORT" class="form-control-dark" value="<?php echo getV('SQL_DB_PORT', $conf); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="settings-label">SQL User</label>
                        <input type="text" name="SQL_DB_USER" class="form-control-dark" value="<?php echo getV('SQL_DB_USER', $conf); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="settings-label">SQL Password</label>
                        <input type="password" name="SQL_DB_PASS" class="form-control-dark" value="<?php echo getV('SQL_DB_PASS', $conf); ?>" required>
                    </div>

                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #2b2b40;">
                        <div class="form-group switch-container">
                            <label class="switch">
                                <input type="checkbox" name="SQL_USE_2_DB" <?php echo getC('SQL_USE_2_DB', $conf); ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="switch-label">Usar Me_MuOnline (DB 2)</span>
                        </div>
                        <div class="form-group">
                            <label class="settings-label">Database 2 Name</label>
                            <input type="text" name="SQL_DB_2_NAME" class="form-control-dark" value="<?php echo getV('SQL_DB_2_NAME', $conf); ?>">
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="settings-label">Driver</label>
                            <select name="SQL_PDO_DRIVER" class="form-control-dark">
                                <option value="1" <?php if(getV('SQL_PDO_DRIVER', $conf) == 1) echo 'selected'; ?>>DBLIB</option>
                                <option value="2" <?php if(getV('SQL_PDO_DRIVER', $conf) == 2) echo 'selected'; ?>>SQLSRV</option>
                                <option value="3" <?php if(getV('SQL_PDO_DRIVER', $conf) == 3) echo 'selected'; ?>>ODBC</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="settings-label">Encryption</label>
                            <select name="SQL_PASSWORD_ENCRYPTION" class="form-control-dark">
                                <option value="none" <?php if(getV('SQL_PASSWORD_ENCRYPTION', $conf) == 'none') echo 'selected'; ?>>None</option>
                                <option value="md5" <?php if(getV('SQL_PASSWORD_ENCRYPTION', $conf) == 'md5') echo 'selected'; ?>>MD5</option>
                                <option value="sha256" <?php if(getV('SQL_PASSWORD_ENCRYPTION', $conf) == 'sha256') echo 'selected'; ?>>SHA256</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-md-4">
                <div class="settings-section">
                    <h3 class="settings-title"><i class="fa fa-globe"></i> SEO & System</h3>
                    
                    <div class="form-group switch-container">
                        <label class="switch">
                            <input type="checkbox" name="system_active" <?php echo getC('system_active', $conf); ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label">Sitio Web Activo</span>
                    </div>

                    <div class="form-group switch-container">
                        <label class="switch">
                            <input type="checkbox" name="error_reporting" <?php echo getC('error_reporting', $conf); ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label">Debug Mode</span>
                    </div>

                    <div class="form-group">
                        <label class="settings-label">Maintenance URL</label>
                        <input type="text" name="maintenance_page" class="form-control-dark" value="<?php echo getV('maintenance_page', $conf); ?>">
                    </div>

                    <div class="form-group">
                        <label class="settings-label">Forum URL</label>
                        <input type="text" name="website_forum_link" class="form-control-dark" value="<?php echo getV('website_forum_link', $conf); ?>">
                    </div>

                    <hr style="border-color: #2b2b40;">

                    <div class="form-group">
                        <label class="settings-label">Meta Description</label>
                        <textarea name="website_meta_description" class="form-control-dark" style="height: 80px;"><?php echo getV('website_meta_description', $conf); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="settings-label">Meta Keywords</label>
                        <textarea name="website_meta_keywords" class="form-control-dark" style="height: 60px;"><?php echo getV('website_meta_keywords', $conf); ?></textarea>
                    </div>

                </div>
            </div>

            <div class="col-md-4">
                <div class="settings-section">
                    
                    <h3 class="settings-title"><i class="fa fa-user-shield"></i> Administradores</h3>
                    <p style="color:#777; font-size:11px; margin-bottom:15px;">Establece Nivel 0 para eliminar a un administrador.</p>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th style="width:70px;">Nivel</th>
                                    <th style="width:100px;">PIN 2FA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(isset($conf['admins']) && is_array($conf['admins'])): 
                                    foreach($conf['admins'] as $user => $data):
                                        // Compatibilidad con formato antiguo (solo numero) y nuevo (array)
                                        $lvl = is_array($data) ? $data['level'] : $data;
                                        $pin = is_array($data) && isset($data['pin']) ? $data['pin'] : '123456';
                                ?>
                                <tr>
                                    <td style="font-weight:600; color:#3699ff;"><?php echo $user; ?></td>
                                    <td>
                                        <input type="number" name="admin_level[<?php echo $user; ?>]" value="<?php echo $lvl; ?>" class="form-control-dark" style="padding:5px; text-align:center;">
                                    </td>
                                    <td>
                                        <input type="text" name="admin_pin[<?php echo $user; ?>]" value="<?php echo $pin; ?>" class="form-control-dark" style="padding:5px; text-align:center; letter-spacing:1px;">
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                                
                                <tr style="border-top: 2px solid #2b2b40;">
                                    <td><input type="text" name="new_admin_user" placeholder="Nuevo Usuario" class="form-control-dark" style="padding:5px;"></td>
                                    <td><input type="number" name="new_admin_level" placeholder="Lvl" class="form-control-dark" style="padding:5px; text-align:center;"></td>
                                    <td><input type="text" name="new_admin_pin" placeholder="PIN" class="form-control-dark" style="padding:5px; text-align:center;"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <br>
                    
                    <h3 class="settings-title" style="margin-top:10px;"><i class="fa fa-share-alt"></i> Imagen Social</h3>
                    <div class="form-group">
                        <div style="background: #151521; padding: 10px; border-radius: 4px; text-align: center; border: 1px dashed #2b2b40;">
                            <?php if(isset($conf['og_image_path']) && !empty($conf['og_image_path'])): ?>
                                <img src="../<?php echo $conf['og_image_path']; ?>?v=<?php echo time(); ?>" style="max-width: 100%; max-height: 100px; margin-bottom: 10px; border-radius: 4px;">
                            <?php else: ?>
                                <p style="font-size: 11px; color: #666;">Sin imagen</p>
                            <?php endif; ?>
                            <input type="file" name="og_image" class="form-control-dark" style="padding: 5px;">
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>