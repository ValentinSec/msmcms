<?php
/**
 * SMTP Configuration Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */
if(!defined('access')) die();

// Definimos la ruta absoluta al archivo JSON
$myJsonFile = __PATH_CONFIGS__ . 'smtp_settings.json';

// --- LÓGICA DE GUARDADO ---
if(isset($_POST['save_smtp_settings'])) {
    try {
        $oldSmtp = file_exists($myJsonFile) ? json_decode(file_get_contents($myJsonFile), true) : [];
        $newPass = isset($_POST['smtp_pass']) ? trim($_POST['smtp_pass']) : '';
        $smtpConfig = [
            'smtp_host'   => trim($_POST['smtp_host']),
            'smtp_user'   => trim($_POST['smtp_user']),
            'smtp_pass'   => ($newPass !== '') ? $newPass : (isset($oldSmtp['smtp_pass']) ? $oldSmtp['smtp_pass'] : ''),
            'smtp_port'   => (int)$_POST['smtp_port'],
            'smtp_crypto' => trim($_POST['smtp_crypto']),
            'smtp_active' => isset($_POST['smtp_active']) ? true : false
        ];

        // Validaciones básicas
        if($smtpConfig['smtp_active'] && empty($smtpConfig['smtp_host'])) {
            throw new Exception("Si activas el SMTP, el Host es obligatorio.");
        }

        $jsonData = json_encode($smtpConfig, JSON_PRETTY_PRINT);
        
        // Verificar permisos de carpeta
        $dir = dirname($myJsonFile);
        if (!is_dir($dir)) { mkdir($dir, 0755, true); }

        if(file_put_contents($myJsonFile, $jsonData) === false) {
            throw new Exception("Error de escritura: No se pudo guardar en <code>/includes/config/</code>. Revisa permisos.");
        }
        
        echo '<div class="alert alert-success-custom"><i class="fa fa-check-circle"></i> Configuración SMTP guardada correctamente.</div>';
        
    } catch(Exception $e) {
        echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> '.$e->getMessage().'</div>';
    }
}

// --- LÓGICA DE PRUEBA ---
if(isset($_POST['test_smtp_email'])) {
    $target = $_POST['test_email_target'];
    if(filter_var($target, FILTER_VALIDATE_EMAIL)) {
        // Usamos la función nativa del CMS
        if(msm_send_mail($target, "Test de Conexión - " . config('server_name'), "<h1>Conexión Exitosa</h1><p>Si estás leyendo esto, tu SMTP está funcionando perfectamente.</p>")) {
            echo '<div class="alert alert-success-custom"><i class="fa fa-envelope"></i> Correo de prueba enviado exitosamente a <b>'.htmlspecialchars($target, ENT_QUOTES, 'UTF-8').'</b>.</div>';
        } else {
            echo '<div class="alert alert-danger"><i class="fa fa-times-circle"></i> Fallo en el envío. Revisa los logs de error de PHP o verifica credenciales.</div>';
        }
    } else {
        echo '<div class="alert alert-warning"><i class="fa fa-exclamation"></i> Por favor ingresa un email válido para la prueba.</div>';
    }
}

// Cargar valores actuales
$conf = file_exists($myJsonFile) ? json_decode(file_get_contents($myJsonFile), true) : [];
function getS($key, $data) { return isset($data[$key]) ? htmlspecialchars($data[$key]) : ""; }

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="container-fluid">';
    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row mb-4" style="align-items: center; display: flex;">';
            echo '<div class="col-xs-6">';
                echo '<h3 class="module-title">Configuración SMTP</h3>';
            echo '</div>';
            echo '<div class="col-xs-6 text-right">';
                echo '<button type="submit" name="save_smtp_settings" class="btn-save-green">';
                    echo '<i class="fa fa-save"></i> Guardar Cambios';
                echo '</button>';
            echo '</div>';
        echo '</div>';

        // Main Content
        echo '<div class="row row-flex">';
            
            // COLUMNA IZQUIERDA (CONFIGURACIÓN)
            echo '<div class="col-md-7">';
                echo '<div class="settings-section h-100">';
                    
                    // Section Header with Switch
                    echo '<div class="section-header">';
                        echo '<h3 class="settings-title"><i class="fa fa-server text-primary"></i> Servidor de Salida</h3>';
                        echo '<div class="switch-container-inline">';
                            echo '<label class="switch">';
                                $activeChecked = (isset($conf['smtp_active']) && $conf['smtp_active']) ? 'checked' : '';
                                echo '<input type="checkbox" name="smtp_active" ' . $activeChecked . '>';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                        echo '</div>';
                    echo '</div>';
                    
                    // Form Inputs
                    echo '<div class="form-grid mt-4">';
                        
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Mail Server (Host)</label>';
                            echo '<input type="text" name="smtp_host" class="form-control-dark" value="' . getS('smtp_host', $conf) . '" placeholder="Ej: smtp.gmail.com">';
                            echo '<small class="text-muted">Para Gmail use: <code>smtp.gmail.com</code></small>';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Usuario / Email</label>';
                            echo '<input type="email" name="smtp_user" class="form-control-dark" value="' . getS('smtp_user', $conf) . '" placeholder="tucorreo@gmail.com">';
                        echo '</div>';

                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Contraseña / App Password</label>';
                            echo '<input type="password" name="smtp_pass" class="form-control-dark" value="" placeholder="Dejar vacío para no cambiarla" autocomplete="new-password">';
                            echo '<small class="text-muted">Si usas Gmail, aquí va la <b>Contraseña de Aplicación</b>, no tu clave normal.</small>';
                        echo '</div>';

                        echo '<div class="row">';
                            echo '<div class="col-md-6">';
                                echo '<div class="form-group">';
                                    echo '<label class="settings-label">Puerto</label>';
                                    echo '<input type="text" name="smtp_port" class="form-control-dark" value="' . getS('smtp_port', $conf) . '" placeholder="587">';
                                echo '</div>';
                            echo '</div>';
                            echo '<div class="col-md-6">';
                                echo '<div class="form-group">';
                                    echo '<label class="settings-label">Cifrado</label>';
                                    echo '<select name="smtp_crypto" class="form-control-dark">';
                                        $selTls = (getS('smtp_crypto', $conf) == 'tls') ? 'selected' : '';
                                        $selSsl = (getS('smtp_crypto', $conf) == 'ssl') ? 'selected' : '';
                                        echo '<option value="tls" ' . $selTls . '>TLS (Recomendado)</option>';
                                        echo '<option value="ssl" ' . $selSsl . '>SSL</option>';
                                    echo '</select>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';

                    echo '</div>'; // End form-grid
                echo '</div>'; // End settings-section
            echo '</div>'; // End col-md-7

            // COLUMNA DERECHA (TEST & INFO)
            echo '<div class="col-md-5">';
                
                // Test Section
                echo '<div class="settings-section mb-4">';
                    echo '<h3 class="settings-title"><i class="fa fa-paper-plane text-warning"></i> Probar Conexión</h3>';
                    echo '<p class="text-muted small">Envía un correo de prueba para verificar que las credenciales sean correctas antes de habilitar el sistema.</p>';
                    
                    echo '<div class="form-group mt-3">';
                        echo '<label class="settings-label">Enviar email de test a:</label>';
                        echo '<div class="input-group">';
                            echo '<input type="email" name="test_email_target" class="form-control-dark" placeholder="destinatario@ejemplo.com">';
                            echo '<span class="input-group-btn">';
                                echo '<button type="submit" name="test_smtp_email" class="btn btn-primary" style="height: 38px; border-radius: 0 4px 4px 0; background: #3699ff; border:none;">';
                                    echo 'Enviar';
                                echo '</button>';
                            echo '</span>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';

                // Info Section
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title"><i class="fa fa-info-circle text-info"></i> Información</h3>';
                    echo '<ul class="info-list mt-3">';
                        echo '<li><b>Gmail:</b> Requiere activar "Verificación en 2 pasos" y generar una "Contraseña de Aplicación".</li>';
                        echo '<li><b>Puerto:</b> Usualmente 587 para TLS o 465 para SSL.</li>';
                        echo '<li><b>Error común:</b> Si usas hosting compartido, asegúrate de que no tengan bloqueados los puertos SMTP.</li>';
                    echo '</ul>';
                echo '</div>';

            echo '</div>'; // End col-md-5

        echo '</div>'; // End row-flex
    echo '</form>';
echo '</div>';

// CSS Styles
echo '<style>
    /* UI Unificada y Mejorada */
    .module-title { color:#fff; font-weight:700; font-size: 20px; margin:0; letter-spacing: -0.5px; }

    .settings-section { background: #1b1b28; border: 1px solid #2b2b40; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    .section-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #2b2b40; padding-bottom: 15px; margin-bottom: 20px; }
    .settings-title { color: #fff; font-size: 16px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }

    .settings-label { color: #7e8299; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: block; }
    .form-control-dark { background: #151521 !important; border: 1px solid #2b2b40 !important; color: #fff !important; padding: 10px 15px; font-size: 13px; border-radius: 6px; width: 100%; transition: border-color 0.3s; }
    .form-control-dark:focus { border-color: #3699ff !important; outline: none; }

    .btn-save-green { background: #00d084; color: #fff; border: none; padding: 10px 25px; border-radius: 6px; font-weight: 600; font-size: 13px; transition: 0.3s; cursor: pointer; }
    .btn-save-green:hover { background: #00b372; box-shadow: 0 4px 12px rgba(0, 208, 132, 0.3); }

    .alert-success-custom { background: rgba(0, 208, 132, 0.1); border: 1px solid #00d084; color: #00d084; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    .alert-danger { background: rgba(246, 78, 96, 0.1); border: 1px solid #f64e60; color: #f64e60; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    .alert-warning { background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; color: #ffc107; padding: 15px; border-radius: 6px; margin-bottom: 20px; }

    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li { color: #888; font-size: 13px; margin-bottom: 8px; padding-left: 15px; position: relative; }
    .info-list li::before { content: "•"; color: #3699ff; position: absolute; left: 0; font-weight: bold; }
    .text-muted { color: #565674 !important; }
    
    /* Switch Styles */
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin-bottom: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #2b2b40; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #00d084; }
    input:checked + .slider:before { transform: translateX(20px); }
</style>';
?>