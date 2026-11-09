<?php
/**
 * Paso 4: Configuración Final y Creación de Admin (PRODUCCIÓN)
 */

if(!isset($_SESSION['db_host'])) {
    echo '<script>window.location.href = "index.php?step=2";</script>';
    exit;
}

$error = '';
$success = false;

// --- Opciones válidas (listas blancas) ---
$validServerFiles = [
    'igcn'   => 'IGCN',
    'xteam'  => 'X-Team, MuEmu, Louis',
    'custom' => 'Custom (basado en IGCN)',
];
$validEncryption = [
    'none'   => 'Texto plano (none)',
    'wzmd5'  => 'WebZen MD5 / fn_md5 (wzmd5)',
    'phpmd5' => 'PHP MD5 (phpmd5)',
    'sha256' => 'SHA-256 (sha256)',
];

// Idiomas instalados (se detectan solos desde la carpeta de idiomas)
$validLanguages = [];
$langDir = __DIR__ . '/../../includes/languages/';
foreach(glob($langDir . '*', GLOB_ONLYDIR) as $dir) {
    if(file_exists($dir . '/language.php')) {
        $validLanguages[] = basename($dir);
    }
}
if(empty($validLanguages)) $validLanguages = ['en'];

if(isset($_POST['finish_install'])) {
    $adminUser   = trim($_POST['admin_user'] ?? '');
    $serverFiles = $_POST['server_files'] ?? '';
    $encryption  = $_POST['password_encryption'] ?? '';
    $language    = $_POST['language_default'] ?? '';
    $sha256salt  = trim($_POST['sha256_salt'] ?? '');

    if(empty($adminUser) || !ctype_alnum($adminUser)) {
        $error = "El usuario admin debe ser alfanumérico.";
    } elseif(!array_key_exists($serverFiles, $validServerFiles)) {
        $error = "Seleccioná un tipo de archivos de servidor válido.";
    } elseif(!array_key_exists($encryption, $validEncryption)) {
        $error = "Seleccioná un tipo de encriptación válido.";
    } elseif(!in_array($language, $validLanguages, true)) {
        $error = "Seleccioná un idioma válido.";
    } elseif($encryption === 'sha256' && $sha256salt === '') {
        $error = "Con encriptación SHA-256 debés indicar el salt que usa tu servidor de juego.";
    } else {
        try {
            $config = [
                // --- Claves de arranque obligatorias ---
                "muservermaker_cms_installed" => true,
                "server_files" => $serverFiles,
                "language_default" => $language,
				"username_min_len" => 4,
				"username_max_len" => 10,
				"password_min_len" => 4,
				"password_max_len" => 20,
				"plugins_system_enable": true,
				"language_switch_active": false,
				"language_debug": false,
				"player_profiles": true,
				"guild_profiles": true,
				"character_avatars_dir": "class",
                "system_active" => true,
                "error_reporting" => false,
                "website_template" => "canibal",
                "maintenance_page" => "https://muservermaker.com/",
                "website_forum_link" => "https://muservermaker.com/",
                "website_meta_description" => "Si siempre soñaste con tener tu propio servidor de Mu Online, contactate con MuServerMaker. Especialistas y profesionales en el ámbito. La mejor opción para tener tu primer server de MU. ",
                "website_meta_keywords" => "MuServerMaker, Mu Online, Private Mu Online Servers, MU Season 6, MU Season 5, MU Season 4, MU Season 3, MU Season 2, MU Season 1, MU 99b, MU 97k",
                "admins" => [ $adminUser => 100 ],
                "SQL_DB_HOST" => $_SESSION['db_host'],
                "SQL_DB_NAME" => $_SESSION['db_name'],
                "SQL_DB_2_NAME" => null,
                "SQL_DB_USER" => $_SESSION['db_user'],
                "SQL_DB_PASS" => $_SESSION['db_pass'],
                "SQL_DB_PORT" => $_SESSION['db_port'],
                "SQL_USE_2_DB" => false,
                "SQL_PDO_DRIVER" => "1",
                "SQL_PASSWORD_ENCRYPTION" => $encryption,
                "SQL_SHA256_SALT" => ($sha256salt !== '' ? $sha256salt : "0"),
                "server_name" => "MuOnline",
                "web_title" => "MuOnline",
                "website_title" => "Mi Servidor de Mu Online",
                "server_desc" => "Bienvenido a mi servidor de Mu Online.",
                "server_version" => "Season",
                "server_exp" => "500x",
                "server_exp_master" => "250x",
                "server_drop" => "50%",
                "server_max_users" => "500",
                "maximum_online" => "500",
                "config_max_stats" => "65535",
                "config_max_rank" => "100",
                "reset_points" => "500",
                "mr_points" => "10",
                "reset_level" => "400",
                "mr_level" => "400",
                "reset_money" => "10000000",
                "reset_type" => "keep",
                "reset_limit" => "1000",
                "fake_online" => "1",
                "fake_accounts" => "1",
                "social_fb" => "https://facebook.com/",
                "social_discord" => "https://discord.com/",
                "social_ig" => "https://instagram.com/",
                "social_wpp" => "",
                "social_yt" => "",
                "social_email" => "",
                "cs_day" => "7",
                "cs_time" => "20:00",
                "coin_1_column" => "WCoinC",
                "coin_2_column" => "WCoinP",
                "coin_3_column" => "GoblinPoint",
                "active_cs" => true,
                "active_social" => true,
                "active_coin_1" => true,
                "active_coin_2" => true,
                "active_coin_3" => true,
                "ip_block_system_enable" => false
            ];

            $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $filePath1 = __DIR__ . '/../../includes/config/muservermaker.json';

            $saved1 = @file_put_contents($filePath1, $jsonContent);

            if($saved1 !== false) {
                $success = true;
            } else {
                throw new Exception("No se pudo escribir en includes/config/. Verifique permisos (CHMOD 755/777).");
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h4 class="mb-0"><i class="fa fa-flag-checkered me-2"></i> 4. Finalizar Instalación</h4>
    </div>
    <div class="card-body">

        <?php if($success): ?>
            <div class="text-center py-5">
                <div class="text-success mb-3">
                    <i class="fa fa-check-circle fa-5x"></i>
                </div>
                <h2 class="text-success">Configuración Guardada</h2>
                <p class="lead">El administrador y los archivos de configuración han sido creados correctamente.</p>
                <hr>
                <a href="index.php?step=5" class="btn btn-primary btn-lg px-5">
                    Siguiente: Finalizar y Limpiar <i class="fa fa-arrow-right ms-2"></i>
                </a>
            </div>

        <?php else: ?>

            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <h3>Crear Cuenta Administradora</h3>
                <p class="text-muted">Esta cuenta tendrá acceso total al Panel de Administración (AdminCP).</p>
            </div>

            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label fw-bold">Usuario Admin (ID)</label>
                    <input type="text" name="admin_user" class="form-control form-control-lg" placeholder="Ej: Admin" required autocomplete="off" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? ''); ?>">
                    <div class="form-text">
                        <i class="fa fa-info-circle"></i> Esta cuenta debe existir (o vas a crearla) en el juego.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Archivos del Servidor</label>
                    <select name="server_files" class="form-control form-control-lg" required>
                        <?php foreach($validServerFiles as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo (($_POST['server_files'] ?? 'xteam') === $val ? 'selected' : ''); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Para archivos <b>Louis</b>, elegí <b>X-Team, MuEmu, Louis</b>.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Encriptación de Contraseñas</label>
                    <select name="password_encryption" class="form-control form-control-lg" required>
                        <?php foreach($validEncryption as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo (($_POST['password_encryption'] ?? 'wzmd5') === $val ? 'selected' : ''); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Debe coincidir con cómo guarda las contraseñas tu servidor. Louis suele ser <b>wzmd5</b>.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Salt SHA-256 (solo si elegís sha256)</label>
                    <input type="text" name="sha256_salt" class="form-control" placeholder="Dejar vacío si no usás SHA-256" value="<?php echo htmlspecialchars($_POST['sha256_salt'] ?? ''); ?>">
                    <div class="form-text">Debe ser el mismo salt que usa tu servidor de juego. No sirve uno aleatorio.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Idioma por Defecto</label>
                    <select name="language_default" class="form-control form-control-lg" required>
                        <?php foreach($validLanguages as $lng): ?>
                            <option value="<?php echo $lng; ?>" <?php echo (($_POST['language_default'] ?? 'es') === $lng ? 'selected' : ''); ?>><?php echo strtoupper($lng); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-5">
                    <a href="index.php?step=3" class="btn btn-outline-secondary">Atrás</a>
                    <button type="submit" name="finish_install" class="btn btn-success btn-lg px-5">
                        <i class="fa fa-save me-2"></i> Finalizar Instalación
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>