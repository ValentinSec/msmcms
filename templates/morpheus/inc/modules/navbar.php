<?php
/**
 * Morpheus Template - Navbar Premium (Fixed)
 * Lógica: Carga dinámica desde navbar_config.json + Admin Check (muservermaker.json)
 */

// 1. Cargar configuración del Navbar
$navbarConfPath = __PATH_INCLUDES__ . 'config/navbar_config.json';
$navConf = file_exists($navbarConfPath) ? json_decode(file_get_contents($navbarConfPath), true) : [];

// 2. Valores por defecto
$showInfo = $navConf['show_info'] ?? 1;
$showDown = $navConf['show_downloads'] ?? 1;
$showRank = $navConf['show_rankings'] ?? 1;
$showGuide = $navConf['show_guides'] ?? 0;
$showNews = $navConf['show_news'] ?? 0;
$customLinks = $navConf['custom_links'] ?? [];

// 3. Detectar página actual
$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';

// 4. LÓGICA DE VERIFICACIÓN DE ADMIN (CORREGIDA FATAL ERROR)
$isMuserverAdmin = false;
if(isLoggedIn() && isset($_SESSION['username'])) {
    $msmPath = __PATH_INCLUDES__ . 'config/muservermaker.json';
    if(file_exists($msmPath)) {
        $msmConf = json_decode(file_get_contents($msmPath), true);
        if(isset($msmConf['admins']) && is_array($msmConf['admins'])) {
            // FIX: Validamos manualmente iterando sobre las llaves para evitar el TypeError de array_map
            $currentUser = strtolower($_SESSION['username']);
            foreach($msmConf['admins'] as $adminName => $adminData) {
                if(is_string($adminName) && strtolower($adminName) === $currentUser) {
                    $isMuserverAdmin = true;
                    break;
                }
            }
        }
    }
}
?>
<link href="<?php echo __BASE_URL__; ?>templates/morpheus/css/navbar.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">

<div class="morpheus-nav-wrapper">
    <div class="container h-100">
        <nav class="morpheus-nav">
            
            <ul class="nav-links">
                <li>
                    <a href="<?php echo __BASE_URL__; ?>" class="<?php echo (empty($currentPage)) ? 'active' : ''; ?>">
                        <span class="nav-text">Inicio</span>
                        <span class="nav-glow"></span>
                    </a>
                </li>

                <?php if($showNews): ?>
                    <li>
                        <a href="<?php echo __BASE_URL__; ?>news" class="<?php echo ($currentPage == 'news') ? 'active' : ''; ?>">
                            <span class="nav-text">Noticias</span>
                            <span class="nav-glow"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if($showInfo): ?>
                    <li>
                        <a href="<?php echo __BASE_URL__; ?>info" class="<?php echo ($currentPage == 'info') ? 'active' : ''; ?>">
                            <span class="nav-text">Info</span>
                            <span class="nav-glow"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if($showDown): ?>
                    <li>
                        <a href="<?php echo __BASE_URL__; ?>downloads" class="<?php echo ($currentPage == 'downloads') ? 'active' : ''; ?>">
                            <span class="nav-text">Descargas</span>
                            <span class="nav-glow"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if($showRank): ?>
                    <li>
                        <a href="<?php echo __BASE_URL__; ?>rankings" class="<?php echo ($currentPage == 'rankings') ? 'active' : ''; ?>">
                            <span class="nav-text">Rankings</span>
                            <span class="nav-glow"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if($showGuide): ?>
                    <li>
                        <a href="<?php echo __BASE_URL__; ?>guides" class="<?php echo ($currentPage == 'guides') ? 'active' : ''; ?>">
                            <span class="nav-text">Guías</span>
                            <span class="nav-glow"></span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if(!empty($customLinks)): ?>
                    <?php foreach($customLinks as $link): ?>
                        <?php if(isset($link['active']) && $link['active'] == 1): ?>
                            <li>
                                <a href="<?php echo (strpos($link['route'], 'http') === 0) ? $link['route'] : __BASE_URL__ . $link['route']; ?>" 
                                   target="<?php echo (strpos($link['route'], 'http') === 0) ? '_blank' : '_self'; ?>">
                                    <span class="nav-text"><?php echo $link['title']; ?></span>
                                    <span class="nav-glow"></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <div class="nav-auth">
                <?php if(isLoggedIn()): ?>

                    <a href="<?php echo __BASE_URL__; ?>usercp" class="auth-btn logged-in">
                        <span>PANEL</span>
                        <div class="auth-icon"><i class="fa fa-user"></i></div>
                    </a>
                    
                    <?php if(canAccessAdminCP($_SESSION['username'])): ?>
                        <a href="<?php echo __PATH_ADMINCP_HOME__; ?>" target="_blank" class="btn btn-admincp-nav">
                            <span>ADM</span>
                            <div class="auth-icon"><i class="fa fa-gear"></i></div>
                        </a>
                    <?php endif; ?>

                <?php else: ?>
                    <a href="javascript:void(0);" onclick="openLoginModal()" class="auth-btn">
                        <span>LOGIN</span>
                        <div class="auth-icon">
                            <i class="fa fa-key"></i>
                        </div>
                    </a>
                <?php endif; ?>
            </div>

        </nav>
    </div>
</div>