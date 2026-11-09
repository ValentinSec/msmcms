<?php
/**
 * MuServerMaker AdminCP - Index
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// access
define('access', 'admincp');

// --- DEBUGGING TEMPORAL ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ----------------------------------------

try {
    
    // Load WebENGINE
    if(!@include_once('../includes/muservermaker.php')) throw new Exception('Could not load MuServerMaker CMS.');

    // Check if user is logged in
    if(!isLoggedIn()) { redirect(); }

    // Check if user has access
    if(!canAccessAdminCP($_SESSION['username'])) { redirect(); }

    // =================================================================================
    // INICIO SISTEMA DE SEGURIDAD AVANZADO (PIN INDIVIDUAL + AUTO-LOCK)
    // =================================================================================
    
    $inactive_limit = 600; // 600 segundos = 10 Minutos
    $pinError = '';

    // 1. Lógica para "Bloquear" manualmente
    if (isset($_GET['action']) && $_GET['action'] == 'lock') {
        unset($_SESSION['admin_pin_verified']);
        unset($_SESSION['admin_last_activity']);
        header("Location: index.php");
        exit();
    }

    // 2. CHECK DE INACTIVIDAD
    if (isset($_SESSION['admin_pin_verified']) && $_SESSION['admin_pin_verified'] === true) {
        if (isset($_SESSION['admin_last_activity'])) {
            $seconds_inactive = time() - $_SESSION['admin_last_activity'];
            if ($seconds_inactive > $inactive_limit) {
                unset($_SESSION['admin_pin_verified']);
                unset($_SESSION['admin_last_activity']);
                $pinError = 'Sesión expirada por inactividad (10 min).';
            }
        }
        $_SESSION['admin_last_activity'] = time();
    }

    // 3. PROCESAR DESBLOQUEO
    if (isset($_POST['unlock_pin'])) {
        $adminsList = config('admins', true);
        $currentUser = $_SESSION['username'];
        $userPin = '123456'; 

        if(isset($adminsList[$currentUser])) {
            $userData = $adminsList[$currentUser];
            if(is_array($userData) && isset($userData['pin'])) {
                $userPin = $userData['pin'];
            } 
        }

        if ($_POST['unlock_pin'] === (string)$userPin) {
            $_SESSION['admin_pin_verified'] = true;
            $_SESSION['admin_last_activity'] = time();
            header("Location: index.php");
            exit();
        } else {
            $pinError = 'PIN Incorrecto.';
        }
    }

    // 4. EL GATEKEEPER (PANTALLA DE BLOQUEO EN PHP PURO)
    if (!isset($_SESSION['admin_pin_verified']) || $_SESSION['admin_pin_verified'] !== true) {
        $baseUrl = __BASE_URL__;
        $userNameUpper = strtoupper($_SESSION['username']);
        $errorHtml = '';
        
        if(!empty($pinError)) {
            $errorHtml = '<div class="error-msg"><i class="fa fa-clock me-2"></i> ' . $pinError . '</div>';
        }

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Seguridad AdminCP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body { background: #0f1217; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; overflow: hidden; }
                .lock-wrapper { width: 100%; max-width: 380px; padding: 20px; animation: fadeIn 0.5s ease; }
                .lock-card { background: rgba(21, 23, 28, 0.95); padding: 40px 30px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); text-align: center; box-shadow: 0 0 50px rgba(0,0,0,0.5); position: relative; overflow: hidden; }
                .lock-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #f39c12, #e67e22); }
                .avatar-box { width: 90px; height: 90px; margin: 0 auto 20px; border-radius: 50%; background: #1a1d24; display: flex; align-items: center; justify-content: center; border: 2px solid #2d323e; color: #f39c12; font-size: 32px; box-shadow: 0 0 20px rgba(0,0,0,0.3); }
                h4 { color: #fff; font-weight: 700; margin-bottom: 5px; letter-spacing: 1px; }
                p { color: #8a8d93; font-size: 13px; margin-bottom: 30px; }
                .form-control-pin { background: rgba(0,0,0,0.3); border: 1px solid #2d323e; color: #fff; text-align: center; font-size: 24px; letter-spacing: 10px; height: 50px; border-radius: 8px; margin-bottom: 20px; transition: 0.3s; }
                .form-control-pin:focus { background: rgba(0,0,0,0.5); border-color: #f39c12; box-shadow: 0 0 10px rgba(243, 156, 18, 0.2); outline: none; color: #f39c12; }
                .btn-unlock { width: 100%; padding: 12px; border-radius: 8px; background: linear-gradient(135deg, #f39c12 0%, #d35400 100%); border: none; color: #fff; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 14px; transition: 0.3s; }
                .btn-unlock:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3); color: #fff; }
                .error-msg { color: #e74c3c; font-size: 13px; margin-bottom: 15px; background: rgba(231, 76, 60, 0.1); padding: 8px; border-radius: 5px; }
                .back-link { display: block; margin-top: 20px; color: #555; font-size: 12px; text-decoration: none; transition: 0.3s; }
                .back-link:hover { color: #888; }
                @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            </style>
        </head>
        <body>
            <div class="lock-wrapper">
                <div class="lock-card">
                    <div class="avatar-box"><i class="fa fa-user-shield"></i></div>
                    <h4>{$userNameUpper}</h4>
                    <p>Ingrese su PIN de seguridad personal.</p>
                    {$errorHtml}
                    <form method="post" action="">
                        <input type="password" name="unlock_pin" class="form-control form-control-pin" placeholder="••••••" maxlength="20" autofocus autocomplete="off">
                        <button type="submit" class="btn btn-unlock">Desbloquear <i class="fa fa-arrow-right ms-2"></i></button>
                    </form>
                    <a href="{$baseUrl}" class="back-link"><i class="fa fa-arrow-left me-1"></i> Volver al sitio web</a>
                </div>
            </div>
        </body>
        </html>
HTML;
        die(); // Matamos el script aquí
    }
    // =================================================================================
    // FIN SISTEMA DE SEGURIDAD
    // =================================================================================

    // Load AdminCP Tools
    if(!@include_once(__PATH_ADMINCP_INC__ . 'functions.php')) throw new Exception('Could not load AdminCP functions.');
    
    // Check Configurations
    if(!@include_once(__PATH_ADMINCP_INC__ . 'check.php')) throw new Exception('Could not load AdminCP configuration check.');
    
} catch (Exception $ex) {
    if(file_exists('../includes/error.html')) {
        $errorPage = file_get_contents('../includes/error.html');
        echo str_replace("{ERROR_MESSAGE}", $ex->getMessage(), $errorPage);
    } else {
        echo '<div style="font-family:sans-serif; background:#ffebeb; border:2px solid #ff0000; color:#333; padding:20px; margin:20px; border-radius:5px;">';
        echo '<h2 style="color:#cc0000; margin-top:0;">🛑 Error del Sistema</h2>';
        echo '<p style="font-size:16px;">' . $ex->getMessage() . '</p>';
        echo '</div>';
    }
    die();
}

// Load Sidebar Modules
include('modules_list.php');

// Preparación de variables para el HTML principal
$adminCpHome = __PATH_ADMINCP_HOME__;
$baseUrl = __BASE_URL__;
$adminName = admincp_base();
$usernameInitial = strtoupper(substr($_SESSION['username'], 0, 1));

// --- RENDERIZADO DEL PANEL PRINCIPAL (PHP PURO) ---

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MuServerMaker AdminCP 1.0">
    <meta name="author" content="MuServerMaker">
    <title>MuServerMaker - AdminCP</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <link rel="stylesheet" href="{$adminCpHome}css/metisMenu.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@3.3.7/dist/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tinymce@7.7.0/skins/ui/oxide/content.min.css">
    <link rel="stylesheet" href="css/webengine.css">
    
    <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>

    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <div id="wrapper">

        <header class="top-header">
            <div class="header-left">
                <button id="sidebarToggle" class="toggle-btn">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
            
            <div class="header-right">
                <div class="header-search">
                    <i class="fa fa-search"></i>
                    <input type="text" id="playerSearchInput" placeholder="Buscar Jugador..." autocomplete="off">
                    <div id="playerSearchResults" class="search-results-overlay"></div>
                </div>

                <a href="{$baseUrl}" target="_blank" class="header-btn" title="Ir a la web">
                    <i class="fa fa-home"></i>
                </a>

                <div class="user-profile">
                    <button class="user-profile-btn">
                        <div class="user-avatar">
                            {$usernameInitial}
                        </div>
                    </button>
                </div>
            </div>
        </header>

        <div class="navbar-default sidebar" role="navigation">
            <div class="sidebar-nav">
                
                <div class="sidebar-logo-area">
                    <a href="{$adminName}">
                        <img id="sidebar-logo" src="img/logo.png" alt="MuServerMaker Logo" />
                    </a>
                </div>

                <div class="sidebar-menu-container">
                    <ul class="nav" id="side-menu">
HTML;

                        // --- LOGICA DEL SIDEBAR (LOOP EN PHP) ---
                        foreach($admincpSidebar as $sidebarItem) {
                            $active = '';
                            if(isset($_GET['module'])) {
                                if(is_array($sidebarItem[1])) {
                                    if(array_key_exists($_GET['module'], $sidebarItem[1])) {
                                        $active = ' class="active"';
                                    }
                                } elseif($sidebarItem[1] == $_GET['module']) {
                                    $active = ' class="active"';
                                }
                            } elseif(!isset($_GET['module']) && $sidebarItem[1] == 'home') {
                                 $active = ' class="active"';
                            }
                            
                            echo '<li'.$active.'>';
                                $itemIcon = (check_value($sidebarItem[2]) ? '<i class="fa '.$sidebarItem[2].' fa-fw"></i>' : '');
                                
                                $titleText = '<span class="nav-link-text">'.$sidebarItem[0].'</span>';

                                if(is_array($sidebarItem[1])) {
                                    echo '<a href="#">'.$itemIcon.$titleText.'<span class="fa arrow"></span></a>';
                                    echo '<ul class="nav nav-second-level">';
                                        foreach($sidebarItem[1] as $sidebarSubItemModule => $sidebarSubItemTitle) {
                                            if(check_value($sidebarSubItemTitle)) echo '<li><a href="'.admincp_base($sidebarSubItemModule).'">'.$sidebarSubItemTitle.'</a></li>';
                                        }
                                    echo '</ul>';
                                } else {
                                    echo '<a href="'.admincp_base($sidebarItem[1]).'">'.$itemIcon.$titleText.'</a>';
                                }
                            echo '</li>';
                        }
                        
                        if(isset($extra_admincp_sidebar)) {
                            if(is_array($extra_admincp_sidebar)) {
                                echo '<li>';
                                    echo '<a href="#"><i class="fa fa-square fa-fw"></i><span class="nav-link-text">Active Plugins</span><span class="fa arrow"></span></a>';
                                    echo '<ul class="nav nav-second-level">';
                                        foreach($extra_admincp_sidebar as $pluginSidebarItem) {
                                            if(is_array($pluginSidebarItem) && is_array($pluginSidebarItem[1])) {
                                                echo '<li>';
                                                    echo '<a href="#">'.$pluginSidebarItem[0].' <span class="fa arrow"></span></a>';
                                                    echo '<ul class="nav nav-third-level collapse" aria-expanded="false" style="height: 0px;">';
                                                        foreach($pluginSidebarItem[1] as $pluginSidebarSubItem) {
                                                            echo '<li><a href="'.admincp_base($pluginSidebarSubItem[1]).'">'.$pluginSidebarSubItem[0].'</a></li>';
                                                        }
                                                    echo '</ul>';
                                                echo '</li>';
                                            }
                                        }
                                    echo '</ul>';
                                echo '</li>';
                            }
                        }

echo <<<HTML
                    </ul>
                </div>
            </div>
        </div>

        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
HTML;

                        // --- ROUTER INTELIGENTE ---
                        $req = isset($_REQUEST['module']) ? $_REQUEST['module'] : 'home';
                        $req = preg_replace('/[^a-zA-Z0-9_]/', '', $req); 

                        $base_modules_path = 'modules/';
                        $file_to_load = '';

                        if($req == 'home') {
                            $file_to_load = $base_modules_path . 'home.php';
                        } else {
                            if (strpos($req, 'settings') !== false) {
                                $file_to_load = $base_modules_path . 'settings/' . $req . '.php';
                            } elseif (strpos($req, 'module_') === 0) {
                                $file_to_load = $base_modules_path . 'management/' . $req . '.php'; 
                            } elseif ($req == 'editchar') {
                                $file_to_load = $base_modules_path . 'tools/tools_character/editchar.php';
                            } elseif (strpos($req, 'tools_') === 0) {
                                $file_to_load = $base_modules_path . 'tools/' . $req . '.php';
                            } elseif (strpos($req, 'template') !== false) {
                                $file_to_load = $base_modules_path . 'templates/' . $req . '.php';
                            } elseif (strpos($req, 'plugin') !== false) {
                                $file_to_load = $base_modules_path . 'plugins/' . $req . '.php';
                            } elseif ($req == 'vip') {
                                $file_to_load = $base_modules_path . 'plugins/vip.php';
                            } elseif ($req == 'popup') { 
                                $file_to_load = $base_modules_path . 'templates/popup.php';
                            } else {
                                $file_to_load = $base_modules_path . $req . '.php';
                            }
                        }

                        // Carga y Verificación
                        if (file_exists($file_to_load)) {
                            include($file_to_load);
                        } else {
                            if($req == 'home') {
                                echo '<h1 class="page-header">Dashboard</h1>';
                            } else {
                                echo "<div class='alert alert-danger' style='margin-top:20px;'>
                                        <strong>Error 404:</strong> El archivo del módulo no se encuentra.<br>
                                        El sistema intentó buscar en: <code>$file_to_load</code>
                                      </div>";
                            }
                        }

echo <<<HTML
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>
    <script src="{$adminCpHome}js/metisMenu.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@3.3.7/dist/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.7.0/tinymce.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $("#sidebarToggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
            var logo = $("#sidebar-logo");
            if ($("#wrapper").hasClass("toggled")) {
                logo.attr("src", "img/colapse.png");
                logo.css("max-width", "40px"); 
            } else {
                logo.attr("src", "img/logo.png");
                logo.css("max-width", "100%"); 
            }
        });
    });
    </script>
</body>
</html>
HTML;
?>