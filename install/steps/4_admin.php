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

if(isset($_POST['finish_install'])) {
    $adminUser = trim($_POST['admin_user']);
    
    if(empty($adminUser) || !ctype_alnum($adminUser)) {
        $error = "El usuario admin debe ser alfanumérico.";
    } else {
        try {
    $config = [
        "system_active" => true,
        "error_reporting" => false,
        "website_template" => "canibal",
        "maintenance_page" => "https://muservermaker.com/",
        "website_forum_link" => "https://muservermaker.com/",
        "website_meta_description" => "Si siempre soñaste con tener tu propio servidor de Mu Online, contactate con MuServerMaker. Especialistas y profesionales en el ámbito. La mejor opción para tener tu primer server de MU. ",
        "website_meta_keywords" => "MuServerMaker, Mu Online, Private Mu Online Servers, MU Season 6, MU Season 5, MU Season 4, MU Season 3, MU Season 2, MU Season 2, MU Season 1, MU 99b, MU 97k, SSeMU, Louis, LayerGaming, MuServidores, ArmyredHost, MSPro, ",
        "admins" => [ $adminUser => 100 ],
        "SQL_DB_HOST" => $_SESSION['db_host'],
        "SQL_DB_NAME" => $_SESSION['db_name'],
        "SQL_DB_2_NAME" => null,
        "SQL_DB_USER" => $_SESSION['db_user'],
        "SQL_DB_PASS" => $_SESSION['db_pass'],
        "SQL_DB_PORT" => $_SESSION['db_port'],
        "SQL_USE_2_DB" => false,
        "SQL_PDO_DRIVER" => "1",
        "SQL_PASSWORD_ENCRYPTION" => "none",
        "SQL_SHA256_SALT" => "1234567890",
        "server_name" => "MuOnline",
        "web_title" => "MuOnline",
        "website_title" => "MuServerMaker CMS es un producto pensado para todos aquellos administradores que siempre soñaron con tener un servidor de MU, con una web con módulos 100% premium sin necesidad de tocar una sola línea de código.",
        "server_desc" => "Si siempre soñaste con tener tu propio servidor de Mu Online, contactate con MuServerMaker. Especialistas y profesionales en el ámbito. La mejor opción para tener tu primer server de MU. ",
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
        throw new Exception("No se pudo escribir en includes/config/. Verifique permisos CHMOD 777.");
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
                <div class="mb-4">
                    <label class="form-label fw-bold">Usuario (ID)</label>
                    <input type="text" name="admin_user" class="form-control form-control-lg" placeholder="Ej: Admin" required autocomplete="off" value="<?php echo $_POST['admin_user'] ?? ''; ?>">
                    <div class="form-text">
                        <i class="fa fa-info-circle"></i> Asegúrate de que esta cuenta ya exista o la vayas a crear en el juego.
                    </div>
                </div>

                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fa fa-cogs"></i> Configuración Automática</h5>
                    <p class="mb-0">Al finalizar, se generarán los archivos de configuración con los valores por defecto para <strong>MuOnline Season 20</strong>.</p>
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