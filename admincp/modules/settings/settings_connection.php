<?php
/**
 * Connection, System & Admin Settings Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

if(!defined('access')) die();

// Definimos la ruta absoluta al archivo JSON principal
$mainConfigFile = __PATH_CONFIGS__ . 'muservermaker.json';

// Valores validos de los desplegables
$encOptions = array(
    'none'   => 'None (texto plano)',
    'wzmd5'  => 'WZ_MD5',
    'phpmd5' => 'MD5 (PHP)',
    'sha256' => 'SHA-256',
);
$driverOptions = array('1' => 'DBLIB', '2' => 'SQLSRV', '3' => 'ODBC');

// --- LÓGICA DE GUARDADO ---
if(isset($_POST['save_connection_settings'])) {
    try {
        // 1. Cargar configuración actual. Si no se puede leer, NO se guarda nada.
        $currentConfig = null;
        if (file_exists($mainConfigFile)) {
            $currentConfig = json_decode((string)file_get_contents($mainConfigFile), true);
        }
        if(!is_array($currentConfig) || empty($currentConfig)) {
            throw new Exception("No se pudo leer muservermaker.json. No se guardó ningún cambio.");
        }

        // 2. Campos de texto
        $textFields = [
            'SQL_DB_HOST', 'SQL_DB_NAME', 'SQL_DB_2_NAME', 'SQL_DB_USER',
            'maintenance_page', 'website_forum_link',
            'website_meta_description', 'website_meta_keywords'
        ];
        foreach($textFields as $f) {
            if(isset($_POST[$f]) && is_string($_POST[$f])) $currentConfig[$f] = trim($_POST[$f]);
        }

        // Contraseña SQL: solo se cambia si se escribe una nueva
        if(isset($_POST['SQL_DB_PASS']) && is_string($_POST['SQL_DB_PASS']) && $_POST['SQL_DB_PASS'] !== '') {
            $currentConfig['SQL_DB_PASS'] = $_POST['SQL_DB_PASS'];
        }

        // Puerto: solo dígitos
        if(isset($_POST['SQL_DB_PORT']) && is_string($_POST['SQL_DB_PORT']) && ctype_digit($_POST['SQL_DB_PORT'])) {
            $currentConfig['SQL_DB_PORT'] = $_POST['SQL_DB_PORT'];
        }

        // Driver: lista blanca
        if(isset($_POST['SQL_PDO_DRIVER']) && is_string($_POST['SQL_PDO_DRIVER']) && isset($driverOptions[$_POST['SQL_PDO_DRIVER']])) {
            $currentConfig['SQL_PDO_DRIVER'] = $_POST['SQL_PDO_DRIVER'];
        }

        // Encriptación: lista blanca (o el valor que ya estaba guardado)
        if(isset($_POST['SQL_PASSWORD_ENCRYPTION']) && is_string($_POST['SQL_PASSWORD_ENCRYPTION'])) {
            $postedEnc = $_POST['SQL_PASSWORD_ENCRYPTION'];
            $storedEnc = isset($currentConfig['SQL_PASSWORD_ENCRYPTION']) ? (string)$currentConfig['SQL_PASSWORD_ENCRYPTION'] : '';
            if($postedEnc !== '' && (isset($encOptions[$postedEnc]) || $postedEnc === $storedEnc)) {
                $currentConfig['SQL_PASSWORD_ENCRYPTION'] = $postedEnc;
            }
        }

        $currentConfig['SQL_USE_2_DB'] = isset($_POST['SQL_USE_2_DB']);
        $currentConfig['system_active'] = isset($_POST['system_active']);
        $currentConfig['error_reporting'] = isset($_POST['error_reporting']);

        // 3. Subida de Imagen
        if(isset($_FILES['og_image']) && $_FILES['og_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['og_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if(in_array($ext, $allowed) && @getimagesize($_FILES['og_image']['tmp_name']) !== false) {
                $targetFile = __PATH_IMG__ . 'og_share.' . $ext;
                if(move_uploaded_file($_FILES['og_image']['tmp_name'], $targetFile)) {
                    $currentConfig['og_image_path'] = 'img/og_share.' . $ext;
                }
            }
        }

        // 4. LÓGICA DE ADMINISTRADORES (TABLA)
        $existingAdmins = (isset($currentConfig['admins']) && is_array($currentConfig['admins'])) ? $currentConfig['admins'] : [];
        $newAdmins = [];

        $validatePin = function($pin) {
            if(strlen($pin) < 6 || strlen($pin) > 64) throw new Exception("El PIN debe tener entre 6 y 64 caracteres.");
            if($pin === '123456') throw new Exception("El PIN 123456 no está permitido.");
        };

        // Procesar admins existentes
        if(isset($_POST['admin_level']) && is_array($_POST['admin_level'])) {
            foreach($_POST['admin_level'] as $user => $level) {
                $user = (string)$user;
                if(!ctype_alnum($user)) continue;
                if(!array_key_exists($user, $existingAdmins)) continue; // solo los que ya existen
                $level = (int)$level;

                // Si el nivel es 0, se elimina (skip)
                if($level <= 0) {
                    if(strcasecmp(trim($user), trim($_SESSION['username'])) === 0) throw new Exception("No puedes eliminar tu propia cuenta de administrador.");
                    continue;
                }

                // PIN: se conserva el guardado, salvo que se escriba uno nuevo
                $oldEntry = $existingAdmins[$user];
                $pin = (is_array($oldEntry) && isset($oldEntry['pin'])) ? (string)$oldEntry['pin'] : '';
                if(isset($_POST['admin_pin'][$user]) && is_string($_POST['admin_pin'][$user]) && $_POST['admin_pin'][$user] !== '') {
                    $validatePin($_POST['admin_pin'][$user]);
                    $pin = password_hash($_POST['admin_pin'][$user], PASSWORD_DEFAULT);
                }

                $newAdmins[$user] = ['level' => $level, 'pin' => $pin];
            }
        }

        // Procesar nuevo admin
        if(isset($_POST['new_admin_user']) && is_string($_POST['new_admin_user']) && trim($_POST['new_admin_user']) !== '') {
            $newUser = trim($_POST['new_admin_user']);
            $newLevel = isset($_POST['new_admin_level']) ? (int)$_POST['new_admin_level'] : 0;

            if(!ctype_alnum($newUser)) throw new Exception("El nombre del nuevo administrador solo puede tener letras y números.");
            foreach($newAdmins as $existingUser => $unused) {
                if(strcasecmp((string)$existingUser, $newUser) === 0) throw new Exception("El usuario " . htmlspecialchars($newUser) . " ya es administrador.");
            }
            if($newLevel <= 0) throw new Exception("El nuevo administrador necesita un nivel mayor a 0.");

            // PIN opcional: si queda vacío, el admin lo crea en su primer ingreso al panel
            $newPinHash = '';
            if(isset($_POST['new_admin_pin']) && is_string($_POST['new_admin_pin']) && $_POST['new_admin_pin'] !== '') {
                $validatePin($_POST['new_admin_pin']);
                $newPinHash = password_hash($_POST['new_admin_pin'], PASSWORD_DEFAULT);
            }

            $newAdmins[$newUser] = ['level' => $newLevel, 'pin' => $newPinHash];
        }

        // Protección: nunca guardar una lista donde no esté el admin que está operando
        $selfPresent = false;
        foreach($newAdmins as $adminUser => $unused) {
            if(strcasecmp(trim((string)$adminUser), trim($_SESSION['username'])) === 0) { $selfPresent = true; break; }
        }
        if(!$selfPresent) throw new Exception("La lista de administradores llegó incompleta. No se guardó ningún cambio.");

        $currentConfig['admins'] = $newAdmins;

        // 5. Guardar Archivo
        $jsonData = json_encode($currentConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if($jsonData === false || file_put_contents($mainConfigFile, $jsonData, LOCK_EX) === false) {
            throw new Exception("Error escribiendo muservermaker.json");
        }

        echo '<div class="alert-success-custom">Configuración guardada correctamente.</div>';

    } catch (Exception $e) {
        echo '<div class="alert alert-danger" style="margin-bottom:20px; background:#f64e60; border:none; color:#fff; padding:15px; border-radius:4px;">'.$e->getMessage().'</div>';
    }
}

// Cargar valores
$conf = file_exists($mainConfigFile) ? json_decode((string)file_get_contents($mainConfigFile), true) : [];
if(!is_array($conf)) $conf = [];

// Helpers
if(!function_exists('getV')) {
    function getV($key, $data) { return isset($data[$key]) ? htmlspecialchars((string)$data[$key], ENT_QUOTES, 'UTF-8') : ""; }
}
if(!function_exists('getC')) {
    function getC($key, $data) { return (isset($data[$key]) && $data[$key] === true) ? "checked" : ""; }
}

// Si el valor guardado no está en la lista, se muestra igual para no cambiarlo sin querer
$currentEnc = isset($conf['SQL_PASSWORD_ENCRYPTION']) ? (string)$conf['SQL_PASSWORD_ENCRYPTION'] : '';
if($currentEnc === '') {
    $encOptions = array('' => '— Sin definir (no se modifica) —') + $encOptions;
} elseif(!isset($encOptions[$currentEnc])) {
    $encOptions[$currentEnc] = $currentEnc . ' (valor actual)';
}
$currentDriver = isset($conf['SQL_PDO_DRIVER']) ? (string)$conf['SQL_PDO_DRIVER'] : '';

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
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { text-align: left; color: #7e8299; padding: 10px; border-bottom: 1px solid #2b2b40; font-size: 11px; text-transform: uppercase; }
    .admin-table td { padding: 10px; border-bottom: 1px solid #2b2b40; color: #fff; vertical-align: middle; }
    .admin-table tr:last-child td { border-bottom: none; }
    .pin-state { display: block; font-size: 10px; font-weight: 400; margin-top: 2px; }
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
    echo '<form action="" method="post" enctype="multipart/form-data" autocomplete="off">';
        
        // HEADER
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Conexión</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="save_connection_settings" class="btn-save-green">Guardar Cambios</button>';
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
                        echo '<input type="password" name="SQL_DB_PASS" class="form-control-dark" value="" placeholder="Dejar vacío para no cambiarla" autocomplete="new-password">';
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
                                foreach($driverOptions as $drvValue => $drvLabel) {
                                    echo '<option value="' . $drvValue . '" ' . ((string)$drvValue === $currentDriver ? 'selected' : '') . '>' . $drvLabel . '</option>';
                                }
                            echo '</select>';
                        echo '</div>';
                        echo '<div class="col-md-6">';
                            echo '<label class="settings-label">Encryption</label>';
                            echo '<select name="SQL_PASSWORD_ENCRYPTION" class="form-control-dark">';
                                foreach($encOptions as $encValue => $encLabel) {
                                    echo '<option value="' . htmlspecialchars((string)$encValue, ENT_QUOTES, 'UTF-8') . '" ' . ((string)$encValue === $currentEnc ? 'selected' : '') . '>' . htmlspecialchars($encLabel, ENT_QUOTES, 'UTF-8') . '</option>';
                                }
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
                    echo '<p style="color:#777; font-size:11px; margin-bottom:15px;">Nivel 0 elimina al administrador. El PIN no se muestra: dejá el campo vacío para conservarlo o escribí uno nuevo (mínimo 6 caracteres).</p>';
                    
                    echo '<div class="table-responsive">';
                        echo '<table class="admin-table">';
                            echo '<thead>';
                                echo '<tr>';
                                    echo '<th>Usuario</th>';
                                    echo '<th style="width:70px;">Nivel</th>';
                                    echo '<th style="width:120px;">PIN</th>';
                                echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                                
                                if(isset($conf['admins']) && is_array($conf['admins'])) {
                                    foreach($conf['admins'] as $user => $data) {
                                        // Compatibilidad con el formato antiguo (solo nivel)
                                        $lvl = is_array($data) ? (isset($data['level']) ? (int)$data['level'] : 1) : (int)$data;
                                        $storedPin = (is_array($data) && isset($data['pin'])) ? (string)$data['pin'] : '';
                                        $pinReady = ($storedPin !== '' && $storedPin !== '123456');
                                        $safeUser = htmlspecialchars((string)$user, ENT_QUOTES, 'UTF-8');

                                        echo '<tr>';
                                            echo '<td style="font-weight:600; color:#3699ff;">' . $safeUser;
                                                echo $pinReady
                                                    ? '<span class="pin-state" style="color:#00d084;">PIN configurado</span>'
                                                    : '<span class="pin-state" style="color:#f39c12;">PIN pendiente (lo crea al ingresar)</span>';
                                            echo '</td>';
                                            echo '<td><input type="number" name="admin_level[' . $safeUser . ']" value="' . $lvl . '" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                            echo '<td><input type="password" name="admin_pin[' . $safeUser . ']" value="" placeholder="Sin cambios" autocomplete="new-password" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                        echo '</tr>';
                                    }
                                }
                                
                                echo '<tr style="border-top: 2px solid #2b2b40;">';
                                    echo '<td><input type="text" name="new_admin_user" placeholder="Nuevo Usuario" class="form-control-dark" style="padding:5px;"></td>';
                                    echo '<td><input type="number" name="new_admin_level" placeholder="Lvl" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                    echo '<td><input type="password" name="new_admin_pin" placeholder="PIN (opcional)" autocomplete="new-password" class="form-control-dark" style="padding:5px; text-align:center;"></td>';
                                echo '</tr>';

                            echo '</tbody>';
                        echo '</table>';
                    echo '</div>';

                    echo '<br>';
                    
                    echo '<h3 class="settings-title" style="margin-top:10px;"><i class="fa fa-share-alt"></i> Imagen Social</h3>';
                    echo '<div class="form-group">';
                        echo '<div style="background: #151521; padding: 10px; border-radius: 4px; text-align: center; border: 1px dashed #2b2b40;">';
                            if(isset($conf['og_image_path']) && !empty($conf['og_image_path'])) {
                                echo '<img src="../' . htmlspecialchars((string)$conf['og_image_path'], ENT_QUOTES, 'UTF-8') . '?v=' . time() . '" style="max-width: 100%; max-height: 100px; margin-bottom: 10px; border-radius: 4px;">';
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