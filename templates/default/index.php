<?php

$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_path, '/'));

if (isset($path_parts[0]) && !empty($path_parts[0])) {
    $_REQUEST['page'] = $path_parts[0];
}
if (isset($path_parts[1]) && !empty($path_parts[1])) {
    $_REQUEST['subpage'] = $path_parts[1];
}
// --- FIN PARCHE RUTAS AMIGABLES ---

if(!defined('access') or !access) die();
include('inc/template.functions.php');

// --- 0. CARGAR CONFIGURACIÓN DEL MÓDULO BLACK MARKET ---
$bmConfigFile = __PATH_INCLUDES__ . 'config/black_market_config.json';
$bmActive = false;
if (file_exists($bmConfigFile)) {
    $bmConf = json_decode(file_get_contents($bmConfigFile), true);
    if (isset($bmConf['module_active']) && $bmConf['module_active'] == 'on') {
        $bmActive = true;
    }
}

// --- INCLUIR PARSER DE ITEMS PARA TOOLTIPS DE INDEX ---
include_once('includes/config/items_manager.php'); 
if(!function_exists('bm_parse_item_index')) {
    function bm_parse_item_index($hex) {
        return BlackMarket::parseItemIndex($hex);
    }
}
// --- INICIO MÓDULO DE SEGURIDAD GLOBAL ---
$secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
if (file_exists($secConfigFile)) {
    $secConfig = json_decode(file_get_contents($secConfigFile), true);
    $userIp = $_SERVER['REMOTE_ADDR'];
    if (!empty($secConfig['blocked_ips'])) {
        $blockedList = explode("\n", str_replace("\r", "", $secConfig['blocked_ips']));
        if (in_array(trim($userIp), array_map('trim', $blockedList))) {
            header('HTTP/1.1 403 Forbidden');
            die("Access Denied");
        }
    }
    if (isset($secConfig['ddos_active']) && $secConfig['ddos_active'] == 1) {
        if (!session_id()) @session_start();
        $limit = $secConfig['ddos_limit'] ?? 10; 
        $responseTime = ($secConfig['ddos_response'] ?? 500) / 1000;
        $penaltyTime = 10; 
        $now = microtime(true);
        if (isset($_SESSION['ddos_penalty_until']) && $now < $_SESSION['ddos_penalty_until']) {
            header('HTTP/1.1 429 Too Many Requests');
            die("Access Restricted");
        }
        if (!isset($_SESSION['last_request_micro'])) {
            $_SESSION['last_request_micro'] = $now;
            $_SESSION['req_count'] = 1;
        } else {
            $timeDiff = $now - $_SESSION['last_request_micro'];
            if ($timeDiff < $responseTime) {
                $_SESSION['req_count']++;
                if ($_SESSION['req_count'] > $limit) {
                    $_SESSION['ddos_penalty_until'] = $now + $penaltyTime;
                    header('HTTP/1.1 429 Too Many Requests');
                    die("Access Restricted");
                }
            } else {
                $_SESSION['last_request_micro'] = $now;
                $_SESSION['req_count'] = 1;
            }
        }
    }
}

// --- FIX LECTURA DE CACHÉ ---
// Intentamos leer manualmente el archivo json generado por el cron nuevo
$cacheFilePath = __PATH_INCLUDES__ . 'cache/server_info.cache';
$realOnline = 0;

if(file_exists($cacheFilePath)) {
    $rawCache = file_get_contents($cacheFilePath);
    $cacheData = json_decode($rawCache, true);
    
    // Verificamos si tiene la estructura que guardamos [time, [data]]
    if(isset($cacheData[1][0])) {
        $srvInfo = explode("|", $cacheData[1][0]);
        // Index 3 es el online (cuentas|pjs|guilds|ONLINE)
        $realOnline = isset($srvInfo[3]) ? (int)$srvInfo[3] : 0;
    }
}
// --- FIN FIX ---

$multOnline = (float)custom_config('fake_online');
$displayOnline = round($realOnline * ($multOnline > 0 ? $multOnline : 1));
$maxOnline = (int)custom_config('server_max_users') ?: 1000; 
$porcentajeOnline = ($displayOnline / $maxOnline) * 100;
if($porcentajeOnline > 100) $porcentajeOnline = 100; 

if(!isset($_REQUEST['page'])) { $_REQUEST['page'] = ''; }
if(!isset($_REQUEST['subpage'])) { $_REQUEST['subpage'] = ''; }

if($_REQUEST['page'] == 'usercp') {
    define('USERCP_CLEAN', true); 
    include('modules/usercp.php');
    exit();
}

$muservermakerParams = muservermakerConfigs();
$activeTplName = $muservermakerParams['website_template']; 

$templateConfigPath = $_SERVER['DOCUMENT_ROOT'] . '/templates/' . $activeTplName . '/config.json';
$tplData = ['hero_logo_width' => '300px'];
if(file_exists($templateConfigPath)) {
    $tplContent = file_get_contents($templateConfigPath);
    $tplData = json_decode($tplContent, true);
}

// --- AJAX HANDLER PARA EL MERCADO DEL INDEX (BLACK MARKET) ---
if(isset($_POST['market_ajax']) && $_POST['market_ajax'] == 'true') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        if($_POST['action'] == 'load_category') {
            $data = BlackMarket::getMarketItems($_POST['category']);
            echo json_encode(['status' => 'success', 'data' => $data]);
            exit;
        }

        if($_POST['action'] == 'buy_item') {
            if(!isLoggedIn()) throw new Exception("Debes iniciar sesión.");
            $result = BlackMarket::buyItem($_SESSION['username'], $_POST['id']);
            echo json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo custom_config('web_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
    // Versión de archivos estáticos = fecha de modificación (el navegador los guarda hasta que cambian)
    if(!function_exists('msm_v')) {
        function msm_v($relPath) { $t = @filemtime($relPath); return $t ? $t : 1; }
    }
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo __BASE_URL__; ?>templates/default/img/favicon.ico?v=<?php echo msm_v('templates/default/img/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo __BASE_URL__; ?>templates/misc/css/sidebar.css?v=<?php echo msm_v('templates/misc/css/sidebar.css'); ?>">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>main.css?v=<?php echo msm_v('templates/default/css/main.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/misc/css/main.css?v=<?php echo msm_v('templates/misc/css/main.css'); ?>" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>rankings.css?v=<?php echo msm_v('templates/default/css/rankings.css'); ?>" rel="stylesheet">
    <link href="<?php echo __BASE_URL__; ?>templates/misc/css/navbar.css?v=<?php echo msm_v('templates/misc/css/navbar.css'); ?>" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>login.css?v=<?php echo msm_v('templates/default/css/login.css'); ?>" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>footer.css?v=<?php echo msm_v('templates/default/css/footer.css'); ?>" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>info.css?v=<?php echo msm_v('templates/default/css/info.css'); ?>" rel="stylesheet">
    <link href="<?php echo __PATH_TEMPLATE_CSS__; ?>news.css?v=<?php echo msm_v('templates/default/css/news.css'); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <?php 
        $secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
        if (file_exists($secConfigFile)) {
            $secConfig = json_decode(file_get_contents($secConfigFile), true);
            if (isset($secConfig['recaptcha_active']) && $secConfig['recaptcha_active'] == 1) {
                // Esta es la librería que "dibuja" el recaptcha
                echo '<script src="https://www.google.com/recaptcha/api.js?hl=es" async defer></script>';
            }
        }
    ?>
    <?php 
        $secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
        if (file_exists($secConfigFile)) {
            $secConfig = json_decode(file_get_contents($secConfigFile), true);
            
            if (isset($secConfig['analytics_active']) && $secConfig['analytics_active'] == 1 && !empty($secConfig['analytics_key'])) {
                $gaKey = htmlspecialchars($secConfig['analytics_key']);
                ?>
                <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaKey; ?>"></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag('js', new Date());
                  gtag('config', '<?php echo $gaKey; ?>');
                </script>
                <?php 
            }
        }
    ?>
</head>
<body class="page-<?php echo empty($_REQUEST['page']) ? 'home' : preg_replace('/[^a-zA-Z0-9_-]/', '', $_REQUEST['page']); ?>">

    <div class="main-overlay"></div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/templates/misc/inc/modules/navbar.php'; ?>

    <?php if(empty($_REQUEST['page'])): ?>
    <header class="hero">
    <div class="video-background-container">
        <iframe src="https://player.vimeo.com/video/1143233657?h=468905b58b&badge=0&autopause=0&player_id=vimeo-background&app_id=58479&background=1&muted=1&loop=1&autoplay=1" 
                frameborder="0" 
                allow="autoplay; fullscreen" 
                class="vimeo-video-full"></iframe>
    </div>
    
    <div class="hero-video-overlay"></div>

    <div class="container text-center hero-content-wrapper">
    <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>logo.png" 
     style="width: <?php echo $tplData['hero_logo_width']; ?> !important; height: auto !important; max-width: 100%;" 
     alt="Logo">
    <h1 class="hero-title">BIENVENIDOS A <?php echo strtoupper(custom_config('server_name')); ?></h1>

    <div class="online-progress-wrapper mt-4 mb-2 mx-auto" style="max-width: 300px;">
        <div class="d-flex justify-content-between mb-2 px-1 small fw-bold text-uppercase" style="color: #E6D6C6; letter-spacing: 0.5px; font-size: 10px;">
            <span>Capacidad</span>
            <span><?php echo $displayOnline; ?> / <?php echo $maxOnline; ?></span>
        </div>
        <div class="progress custom-progress-bg">
            <div class="progress-bar progress-fill-gradient" 
                 role="progressbar" 
                 style="width: <?php echo $porcentajeOnline; ?>%;" 
                 aria-valuenow="<?php echo $porcentajeOnline; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center mt-5 g-3">
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="fas fa-users-cog stat-icon"></i>
                <span>Jugadores</span>
                <strong class="online-dot"><?php echo $displayOnline; ?></strong>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="stat-card">
                <i class="fas fa-bolt stat-icon"></i>
                <span>Estado</span>
                <strong style="color:#00ff88;">• Online</strong>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <a href="<?php echo __BASE_URL__; ?>info" class="stat-card-link">
                <div class="stat-card clickable">
                    <i class="far fa-calendar-alt stat-icon"></i>
                    <span>Rates</span>
                    <strong>Info</strong>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="<?php echo __BASE_URL__; ?>downloads" class="stat-card-link">
                <div class="stat-card clickable">
                    <i class="fas fa-download stat-icon"></i>
                    <span>Season</span>
                    <strong><?php echo custom_config('server_version'); ?></strong>
                </div>
            </a>
        </div>
    </div>

    <div class="hero-actions mt-5">
        <a href="<?php echo __BASE_URL__; ?>downloads" class="btn btn-download btn-lg">DESCARGAR CLIENTE</a>
        <a href="<?php echo custom_config('social_discord'); ?>" class="btn btn-discord btn-lg"><i class="fab fa-discord"></i> ÚNETE A DISCORD</a>
    </div>
    
    <div class="scroll-container">
        <span class="scroll-text">SCROLL</span>
        <div class="mouse-icon">
            <div class="wheel"></div>
        </div>
    </div>
</div>


</header>
    <?php else: ?>
        <div style="margin-top: 120px;"></div>
    <?php endif; ?>
    
    <main class="container my-5 content-section">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="content-wrapper-clean"> 
                    <?php 
                        if(!empty($_REQUEST['page'])) {
                            // Carga automática del sistema MuServerMaker para login, register, etc.
                            $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
                        } else {
                            $newsFile = __PATH_INCLUDES__ . 'config/server_news.json';
                            $settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
                            $newsData = file_exists($newsFile) ? json_decode(file_get_contents($newsFile), true) : [];
                            $conf = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
                            $layoutType = $conf['news_layout_type'] ?? 'cascada';

                            echo '<div class="news-header-container">
                                <h2 class="news-header-title">Últimas Noticias</h2>
                                <a href="'.__BASE_URL__.'news" class="news-header-button">
                                    <span>Ver todas las noticias</span>
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </div>';                                
                            // Definir rutas manuales si las constantes fallan en el index
                            $newsFilePath = 'includes/config/server_news.json';
                            $settingsFilePath = 'includes/config/custom_settings.json';
                            
                            $newsData = file_exists($newsFilePath) ? json_decode(file_get_contents($newsFilePath), true) : [];
                            $conf = file_exists($settingsFilePath) ? json_decode(file_get_contents($settingsFilePath), true) : [];
                            $layoutType = $conf['news_layout_type'] ?? 'cascada';
                        
                            echo '<div class="news-container">';
                            if(empty($newsData)) {
                                echo '<p class="text-center" style="color: #64748b;">No hay noticias publicadas.</p>';
                            } else {
                                // Ordenar por fecha (más reciente arriba)
                                usort($newsData, function($a, $b) {
                                    return strtotime($b['date']) - strtotime($a['date']);
                                });
                        
                                if($layoutType === 'cascada') {
                                    // Estilo CASCADA
                                    foreach($newsData as $news) {
                                        $newsUrl = __BASE_URL__ . 'news/' . ($news['slug'] ?? $news['id']) . '/';
                                        echo '<a href="'.$newsUrl.'" style="text-decoration:none; display:block; margin-bottom: 12px;">';
                                            echo '<div class="news-item" style="padding: 18px 25px; display:flex; justify-content:space-between; align-items:center; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px;">';
                                                echo '<h2 class="news-title-text" style="font-size: 16px; margin:0; color:#FF8A3B; font-family: Cinzel, serif;">'.htmlspecialchars($news['title']).'</h2>';
                                                echo '<span style="font-size: 12px; color: #818cf8; font-family: sans-serif; font-weight: 500;">'.$news['date'].'</span>';
                                            echo '</div>';
                                        echo '</a>';
                                    }
                                } elseif($layoutType === 'expandida') { 
                                    /* */
                                echo '<div class="news-grid-custom">';
                                    $displayNews = array_slice($newsData, 0, 3);
                                    foreach($displayNews as $index => $news) {
                                        $newsUrl = __BASE_URL__ . 'news/' . ($news['slug'] ?? $news['id']) . '/';
                                        $img = !empty($news['image']) ? __BASE_URL__ . $news['image'] : 'https://placehold.co/600x400/1e293b/FFF?text=MU';
                                        $short_desc = mb_substr(strip_tags($news['content']), 0, 120) . '...';
                                        
                                        // Asignamos una clase única a cada tarjeta según su posición
                                        $cardClass = ($index === 0) ? 'card-big' : 'card-small';
                                        
                                        echo '<a class="news-card '.$cardClass.'" href="'.$newsUrl.'">
                                                <img src="'.$img.'" class="bg-img">
                                                <div class="overlay-dark"></div>
                                                <div class="card-content">
                                                    '.($index === 0 ? '<div class="badge-destacado"><span>Destacado</span></div>' : '').'
                                                    <h3 class="card-title">'.htmlspecialchars($news['title']).'</h3>
                                                    <div class="card-date">'.date("d M Y", strtotime($news['date'])).'</div>
                                                    '.($index === 0 ? '<p class="card-desc">'.$short_desc.'</p>' : '').'
                                                    <div class="card-link">Leer más <i class="fa fa-chevron-right"></i></div>
                                                </div>
                                                </a>';
                                    }
                                echo '</div>';
                                } else {
                                    // Formato FULL (el estándar de MuServerMaker)
                                    foreach($newsData as $news) {
                                        $img = !empty($news['image']) ? __BASE_URL__ . $news['image'] : '';
                                        echo '<div class="news-item" style="margin-bottom:30px; background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">';
                                            echo '<h2 class="news-title-text" style="color:#FF8A3B; font-family: Cinzel, serif; margin-bottom: 15px;">'.htmlspecialchars($news['title']).'</h2>';
                                            if($img) echo '<img src="'.$img.'" style="width:100%; border-radius: 8px; margin-bottom: 15px;">';
                                            echo '<div class="news-body-text" style="color:#e6d6c6;">'.$news['content'].'</div>';
                                            echo '<div style="margin-top:15px; font-size:12px; color:#818cf8;">'.$news['date'].'</div>';
                                        echo '</div>';
                                    }
                                }
                            }
                            echo '</div>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <?php if(empty($_REQUEST['page'])): ?>
    <?php
// --- FILTRO DE CLASES POR VERSIÓN DEL SERVIDOR ---
if (!function_exists('is_class_allowed')) {
    function is_class_allowed($classCode) {
        $ver = strtolower(custom_config('server_version'));
        $cc = (int)$classCode;
        if (empty($ver)) $ver = "97";
        if (strpos($ver, '97') !== false || strpos($ver, '99') !== false) return ($cc < 48);
        if (strpos($ver, 'season 1') !== false || strpos($ver, 'season 2') !== false) return ($cc < 80);
        if (strpos($ver, 'season 3') !== false || strpos($ver, 'season 4') !== false || strpos($ver, 'season 5') !== false) return ($cc < 96);
        return true; // Season 6 en adelante: todas las clases
    }
}

// --- TOP 1: mejor personaje válido, sin administradores (CtlCode 32 o más) ---
$db = Connection::Database('MuOnline');
$topPlayer = null;
$candidates = $db->query_fetch("SELECT TOP 5 Name, Class, cLevel, ResetCount FROM Character WHERE ISNULL(CtlCode, 0) < 32 ORDER BY ResetCount DESC, cLevel DESC");
if (is_array($candidates)) {
    foreach ($candidates as $candidate) {
        if (is_class_allowed($candidate['Class'])) { $topPlayer = $candidate; break; }
    }
}

$topGuild = "Sin Guild";
$topAvatar = "avatar.jpg"; // avatar genérico (existe en character-avatars/)

if ($topPlayer) {
    $guildMember = $db->query_fetch_single("SELECT G_Name FROM GuildMember WHERE Name = ?", array($topPlayer['Name']));
    if (is_array($guildMember) && !empty($guildMember['G_Name'])) {
        $topGuild = $guildMember['G_Name'];
    }

    $cc = (int)$topPlayer['Class'];
    if ($cc < 16)       { $topAvatar = "dw.jpg"; }
    elseif ($cc < 32)   { $topAvatar = "dk.jpg"; }
    elseif ($cc < 48)   { $topAvatar = "elf.jpg"; }
    elseif ($cc < 64)   { $topAvatar = "mg.jpg"; }
    elseif ($cc < 80)   { $topAvatar = "dl.jpg"; }
    elseif ($cc < 96)   { $topAvatar = "sum.jpg"; }
    elseif ($cc < 112)  { $topAvatar = "rf.jpg"; }
    else                { $topAvatar = "gl.jpg"; }
}

// --- DUEÑO DEL CASTILLO ---
$csOwner = $db->query_fetch_single("SELECT OWNER_GUILD FROM MuCastle_DATA");
$csOwnerName = (is_array($csOwner) && !empty($csOwner['OWNER_GUILD'])) ? $csOwner['OWNER_GUILD'] : 'VACANTE';
?>

<section class="rank-highlight py-5 bg-dark-opacity">
    <div class="container text-center">
        <h2 class="section-title mb-5">LOS MEJORES EN <?php echo strtoupper(custom_config('server_name')); ?></h2>
        <div class="row g-4 justify-content-center">

            <div class="col-lg-4 col-md-6">
                <div class="rank-card gold-glow">
                    <div class="card-header-icon"><i class="fa fa-trophy"></i> TOP 1 RANKING</div>
                    <div class="rank-body">
                        <div class="player-info d-flex align-items-center justify-content-center mb-3">
                            <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>character-avatars/<?php echo $topAvatar; ?>" class="rank-avatar me-3" alt="" onerror="this.onerror=null;this.src='<?php echo __PATH_TEMPLATE_IMG__; ?>character-avatars/avatar.jpg';">
                            <div class="text-start">
                                <span class="player-name text-uppercase fw-bold" style="color: #FF8A3B; font-family: 'Trajan Pro', serif;">
                                    <?php echo $topPlayer ? htmlspecialchars($topPlayer['Name']) : 'Nadie a&uacute;n'; ?>
                                </span><br>
                                <span class="guild-name text-color small"><?php echo htmlspecialchars($topGuild); ?></span>
                            </div>
                        </div>
                        <div class="row rank-stats">
                            <div class="col-6">
                                <small class="text-color text-uppercase" style="font-size: 10px;">Resets</small><br>
                                <strong style="font-family: 'Roboto';"><?php echo $topPlayer ? number_format($topPlayer['ResetCount']) : '0'; ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-color text-uppercase" style="font-size: 10px;">Level</small><br>
                                <strong style="font-family: 'Roboto';"><?php echo $topPlayer ? $topPlayer['cLevel'] : '0'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="rank-card orange-glow">
                    <div class="card-header-icon"><i class="fa fa-shield-halved"></i> CASTLE SIEGE</div>
                    <div class="rank-body text-center d-flex flex-column justify-content-center" style="min-height: 115px;">
                        <i class="fa fa-chess-rook fa-2x mb-2 text-orange"></i>
                        <h4 class="guild-owner-name text-white text-uppercase" style="font-family: 'Trajan Pro'; letter-spacing: 1px;">
                            <?php echo htmlspecialchars($csOwnerName); ?>
                        </h4>
                        <p class="text-color small m-0">Due&ntilde;os del Castillo</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php if ($bmActive): ?>
<section class="market-section py-5" style="border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
    <div class="container text-center">
        <h2 class="section-subtitle" style="color: #FF8A3B; font-family: 'Cinzel', serif; letter-spacing: 2px;">MARKETPLACE</h2>
        <div class="title-divider mb-4" style="width: 50px; height: 3px; background: #FF8A3B; margin: 10px auto;"></div>
        
        <div class="market-ticker-wrap">
            <div class="market-ticker">
                <?php 
                $mk_db = Connection::Database('Me_MuOnline');
                // Use try-catch to avoid breaking page if table missing
                try {
                    // FIX: Convert varbinary column to varchar for proper PHP reading
                    $carousel_items = $mk_db->query_fetch("SELECT TOP 10 id, item_hex, item_img, price, currency, date_posted FROM MUSERVERMAKER_MARKET_SELL ORDER BY date_posted DESC");
                    
                    if (empty($carousel_items)) {
                         echo '<div class="text-center p-5 text-muted" style="width: 100%;">Ningún usuario está vendiendo sus items en este momento</div>';
                    } else {
                        if(count($carousel_items) > 0 && count($carousel_items) < 10) {
                            $carousel_items = array_merge($carousel_items, $carousel_items);
                        }

                        foreach($carousel_items as $cItem) {
                            // Convert binary to hex string if needed
                            $rawHex = $cItem['item_hex'];
                            if (!ctype_xdigit($rawHex)) {
                                $rawHex = bin2hex($rawHex);
                            }
                            
                            $cHtml = bm_parse_item_index($rawHex);
                            $cName = explode('+', strip_tags($cHtml))[0];
                            $safeTooltip = htmlspecialchars($cHtml, ENT_QUOTES, 'UTF-8');
                            $priceStr = number_format($cItem['price']).' '.$cItem['currency'];
                            
                            $itemId = $cItem['id'];
                            
                            echo '<div class="m-card-mini" 
                                       data-tooltip="'.$safeTooltip.'"
                                       onmouseenter="idx_show_tt(this)" 
                                       onmousemove="idx_move_tt(event)" 
                                       onmouseleave="idx_hide_tt()">
                                    <img src="'.__BASE_URL__.$cItem['item_img'].'">
                                    <div class="m-mini-info">
                                        <div class="m-mini-name">'.$cName.'</div>
                                        <div class="m-mini-price">'.$priceStr.'</div>
                                        <button class="m-btn-buy" onclick="openDirectBuy('.$itemId.', \''.$priceStr.'\')">COMPRAR</button>
                                    </div>
                                  </div>';
                        }
                    }
                } catch (Exception $e) {
                     echo '<div class="text-center p-5 text-muted">Ningún usuario está vendiendo sus items en este momento</div>';
                }
                ?>
            </div>
        </div>

        <div class="mt-4">
            <button onclick="openMarketModal()" class="btn btn-outline-light btn-sm" style="font-size: 11px; letter-spacing: 1px;">
                VER TODO EL MERCADO <i class="fa fa-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<div id="global-tt"></div>

<div id="wsMarketModal" class="ws-modal-overlay">
    <button class="btn-ws-close" onclick="closeMarketModal()" style="position:absolute; top:20px; right:30px; color:#fff; z-index:10001;"><i class="fa fa-times"></i></button>
    
    <div id="view_individual" class="ws-workspace">
        <div class="ws-sidebar-left">
            <div class="cat-header">Categorías</div>
            <div class="cat-list">
                <?php 
                $cats = [
                    -1=>['Ver Todos', 'fa-globe'],
                    0=>['Espadas', 'fa-khanda'], 1=>['Hachas', 'fa-gavel'], 2=>['Mazas', 'fa-hammer'], 
                    3=>['Lanzas', 'fa-pen-nib'], 4=>['Arcos', 'fa-bullseye'], 5=>['Bastones', 'fa-magic'], 
                    6=>['Escudos', 'fa-shield-alt'], 7=>['Cascos', 'fa-hard-hat'], 8=>['Armaduras', 'fa-tshirt'], 
                    9=>['Pantalones', 'fa-socks'], 10=>['Guantes', 'fa-mitten'], 11=>['Botas', 'fa-shoe-prints'], 
                    12=>['Alas/Orb', 'fa-feather'], 13=>['Joyas', 'fa-gem'], 14=>['Misc', 'fa-box-open'], 15=>['Scrolls', 'fa-scroll']
                ];
                foreach($cats as $id => $data) {
                    echo '<button class="cat-btn" onclick="wsLoadCat('.$id.', this)"><i class="fa-solid '.$data[1].'"></i> '.$data[0].'</button>';
                }
                ?>
            </div>
        </div>

        <div class="ws-center-panel">
            <div class="center-toolbar">
                <div class="search-wrapper">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" id="wsSearch" class="search-input" placeholder="Filtrar..." onkeyup="wsFilterGrid()">
                </div>
            </div>
            
            <div class="item-grid-container" id="wsItemGrid">
                <div style="grid-column: 1 / -1; text-align: center; margin-top: 150px; color: #444;">
                    <i class="fa fa-arrow-left" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i><br>
                    <p style="font-size: 14px;">Selecciona una categoría.</p>
                </div>
            </div>
            
            <div class="loading-overlay" id="wsLoader">
                <div class="spinner"></div><span style="color:#fff; font-size:12px; font-weight:600;">CARGANDO...</span>
            </div>
        </div>
    </div>
</div>

<div id="alertModal" class="alert-modal-overlay">
    <div class="alert-box">
        <h3 id="alertTitle" style="color:#FF8A3B; margin-bottom:10px;">Aviso</h3>
        <p id="alertBody" style="font-size:13px; color:#ccc;">Mensaje</p>
        <button class="alert-btn" onclick="document.getElementById('alertModal').style.display='none'">Aceptar</button>
    </div>
</div>

<div id="buyConfirmModal" class="confirm-modal-overlay">
    <div class="confirm-box">
        <div class="confirm-title">Confirmar Compra</div>
        <div class="confirm-body">
            ¿Comprar este item por <span id="buyPriceLabel" style="color:#ffd700; font-weight:bold;"></span>?
            <br><br>
            <small style="color:#e74c3c;">Tu baúl debe estar <b>VACÍO</b>.</small>
        </div>
        <div class="confirm-btns">
            <button class="btn-confirm-no" onclick="document.getElementById('buyConfirmModal').style.display='none'">Cancelar</button>
            <button class="btn-confirm-yes" id="btnDoBuy">COMPRAR</button>
        </div>
    </div>
</div>

<script>
    var bm_selected_id = -1;

    // --- TOOLTIP LOGIC ---
    function idx_show_tt(el) {
        var tt = document.getElementById('global-tt');
        tt.innerHTML = el.getAttribute('data-tooltip');
        tt.style.display = 'block';
    }
    function idx_move_tt(e) {
        var tt = document.getElementById('global-tt');
        tt.style.left = (e.clientX + 15) + 'px';
        tt.style.top = (e.clientY + 15) + 'px';
    }
    function idx_hide_tt() {
        document.getElementById('global-tt').style.display = 'none';
    }

    // --- MODAL WORKSPACE ---
    function openMarketModal() { document.getElementById('wsMarketModal').style.display = 'flex'; }
    function closeMarketModal() { document.getElementById('wsMarketModal').style.display = 'none'; }

    // --- ALERT SYSTEM ---
    function show_modal_alert(title, msg) {
        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertBody').innerHTML = msg;
        document.getElementById('alertModal').style.display = 'flex';
    }

    // --- BUY SYSTEM ---
    function openDirectBuy(id, priceLabel) {
        // Evita que el click se propague si viene del card wrapper
        if(window.event) window.event.stopPropagation();
        
        bm_selected_id = id;
        document.getElementById('buyPriceLabel').innerText = priceLabel;
        document.getElementById('btnDoBuy').onclick = function() { executeBuy(); };
        document.getElementById('buyConfirmModal').style.display = 'flex';
    }

function executeBuy() {
    document.getElementById('buyConfirmModal').style.display = 'none';

    // Mostrar loader mientras procesa
    showBuyLoader(true);

    var fd = new FormData();
    fd.append('market_ajax', 'true');
    fd.append('action', 'buy_item');
    fd.append('id', bm_selected_id);

    fetch(window.location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        showBuyLoader(false);
        if(res.status === 'success') {
            show_modal_alert("Éxito", "¡Compra realizada! Revisa tu baúl.");
            var activeCat = document.querySelector('.cat-btn.active');
            if(activeCat) activeCat.click();
            setTimeout(function(){ location.reload(); }, 2000);
        } else {
            show_modal_alert("Error", res.message || "No se pudo completar la compra.");
        }
    })
    .catch(function(err) {
        showBuyLoader(false);
        show_modal_alert("Error", "Ocurrió un problema de conexión. Intenta de nuevo.");
    });
}

// Loader de compra (overlay a pantalla completa)
function showBuyLoader(show) {
    var loader = document.getElementById('buyLoaderOverlay');
    if(!loader) {
        loader = document.createElement('div');
        loader.id = 'buyLoaderOverlay';
        loader.style.cssText = 'display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:100010; align-items:center; justify-content:center; flex-direction:column;';
        loader.innerHTML = '<div class="spinner" style="width:40px; height:40px; border:4px solid rgba(255,255,255,0.3); border-radius:50%; border-top-color:#FF8A3B; animation:spin 1s linear infinite;"></div><span style="color:#fff; margin-top:15px; font-size:13px; font-weight:600; letter-spacing:1px;">PROCESANDO COMPRA...</span>';
        document.body.appendChild(loader);
    }
    loader.style.display = show ? 'flex' : 'none';
}

    // --- CATEGORY LOADER (MODAL GRANDE) ---
    function wsLoadCat(catId, btn) {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        document.getElementById('wsLoader').style.display = 'flex';
        var grid = document.getElementById('wsItemGrid');
        grid.innerHTML = '';

        var fd = new FormData();
        fd.append('market_ajax', 'true');
        fd.append('action', 'load_category');
        fd.append('category', catId);

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            document.getElementById('wsLoader').style.display = 'none';
            if(res.status === 'success') {
                if(res.data.length === 0) {
                    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#666; margin-top:50px;">Categoría vacía.</div>';
                    return;
                }
                res.data.forEach(item => {
                    // Item Card HTML
                    var div = document.createElement('div');
                    div.className = 'ws-item-card';
                    div.setAttribute('data-name', item.clean_name.toLowerCase());
                    
                    // Tooltip data
                    var safeTt = item.tooltip.replace(/"/g, '&quot;');
                    
                    div.innerHTML = `
                        <div onmouseenter="idx_show_tt(this)" onmousemove="idx_move_tt(event)" onmouseleave="idx_hide_tt()" data-tooltip="${safeTt}">
                            <img src="${item.img}" class="ws-card-img">
                        </div>
                        <div class="ws-card-name">${item.clean_name}</div>
                        <div class="ws-card-price">${new Intl.NumberFormat().format(item.price)} ${item.currency}</div>
                        <button class="ws-card-btn" onclick="openDirectBuy(${item.id}, '${new Intl.NumberFormat().format(item.price)} ${item.currency}')">COMPRAR</button>
                    `;
                    grid.appendChild(div);
                });
            }
        });
    }

    function wsFilterGrid() {
        var term = document.getElementById('wsSearch').value.toLowerCase();
        document.querySelectorAll('.ws-item-card').forEach(slot => {
            var name = slot.getAttribute('data-name');
            slot.style.display = name.includes(term) ? 'flex' : 'none';
        });
    }
</script>

<section class="features-section py-5">
        <div class="container text-center">
            <h2 class="section-subtitle">¿POR QUÉ ELEGIRNOS?</h2>
            <div class="title-divider mb-5"></div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle bg-orange-grad"><i class="fa fa-bolt"></i></div>
                        <h4 class="feature-title">CLIENTE OPTIMIZADO</h4>
                        <p class="feature-text">60 FPS reales con fluidez única y exclusiva.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle bg-orange-grad"><i class="fa fa-bullseye"></i></div>
                        <h4 class="feature-title">EVENTOS EXCLUSIVOS</h4>
                        <p class="feature-text">Vive eventos únicos como Battlegrounds y Dungeons.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon-circle bg-orange-grad"><i class="fa fa-users"></i></div>
                        <h4 class="feature-title">COMUNIDAD ACTIVA</h4>
                        <p class="feature-text">Actividad constante las 24/7 con miles de usuarios.</p>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <?php if(!isLoggedIn()): ?>
                    <a href="<?php echo __BASE_URL__; ?>register" class="btn btn-register-now">REGÍSTRATE AHORA <i class="fa fa-arrow-right ms-2"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
<?php endif; ?>
<?php include(__PATH_TEMPLATE_ROOT__ . 'inc/modules/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/templates/misc/sidebar.php'); ?>

<?php

// LEER CONFIGURACIÓN DE STREAMS
$streamConfFile = 'includes/config/stream_config.json';
$stData = [];
$stActive = false;

if (file_exists($streamConfFile)) {
    $stData = json_decode(file_get_contents($streamConfFile), true);
    if (isset($stData['module_active']) && $stData['module_active'] == 'on') {
        $stActive = true;
    }
}

if ($stActive): 
?>
    <div id="streamWidgetRoot">
        
        <div id="streamsPanel" class="stream-panel-container">
            <div class="stream-panel-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
                Nuestros Streamers
            </div>
            
            <div class="stream-list">
                <?php if (!empty($stData['streamers'])): ?>
                    <?php foreach ($stData['streamers'] as $st): ?>
                        <div class="stream-item">
                            <div class="stream-user-label">
                                <span><?php echo htmlspecialchars($st['user']); ?></span>
                                <span style="text-transform: uppercase; font-size:10px; opacity:0.7;"><?php echo $st['platform']; ?></span>
                            </div>
                            <div class="stream-embed-responsive">
                                <?php 
                                    $domain = $_SERVER['HTTP_HOST'];
                                    // Removemos 'www.' si existe para evitar problemas con Twitch
                                    $domain = preg_replace('/^www\./', '', $domain);

                                    if ($st['platform'] == 'twitch') {
                                        echo '<iframe src="https://player.twitch.tv/?channel='.htmlspecialchars($st['user']).'&parent='.$domain.'&muted=true" allowfullscreen></iframe>';
                                    } elseif ($st['platform'] == 'kick') {
                                        echo '<iframe src="https://player.kick.com/'.htmlspecialchars($st['user']).'?muted=true" allowfullscreen></iframe>';
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #7e8299; font-size: 0.9rem;">
                        No hay streams configurados.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <button id="btnToggleStreams" class="stream-float-btn" onclick="toggleStreamPanel()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="15" x="2" y="7" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
            <span>Streams</span>
            <svg id="arrowIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.3s;"><path d="m18 15-6-6-6 6"></path></svg>
        </button>
    </div>

    <script>
    function toggleStreamPanel() {
        var panel = document.getElementById('streamsPanel');
        var arrow = document.getElementById('arrowIcon');
        
        if (panel.style.display === 'flex') {
            panel.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        } else {
            panel.style.display = 'flex';
            arrow.style.transform = 'rotate(180deg)';
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            // PHP inyecta la URL base y la Key automáticamente aquí:
            var cronUrl = "<?php echo __BASE_URL__; ?>api/cron.php";            
            // Ejecutamos el cron silenciosamente
            try {
                fetch(cronUrl, {mode: 'no-cors'});
                console.log("Auto-Cron ejecutado en: " + cronUrl);
            } catch(e) {
                console.error("Error auto-cron", e);
            }
        }, 3000);
    });
</script>
<?php endif; ?>
<?php include('includes/popup_modal.php'); ?>
<?php include 'templates/misc/countdown.php'; ?>
</body>
</html>