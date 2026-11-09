<?php
/**
 * Main Navbar Module (Dynamic)
 */

// 1. Cargar Configuraci��n del AdminCP
$navConfigPath = __PATH_CONFIGS__ . 'navbar_config.json';
$navConfig = [
    'show_info' => 1,
    'show_downloads' => 1,
    'show_rankings' => 1,
    'show_guides' => 0,
    'show_news' => 0,
    'custom_links' => []
];

if (file_exists($navConfigPath)) {
    $loadedNav = json_decode(file_get_contents($navConfigPath), true);
    if(is_array($loadedNav)) {
        $navConfig = array_merge($navConfig, $loadedNav);
    }
}
?>

<nav class="navbar navbar-expand-lg fixed-top balduneta-navbar-fluid">
    <div class="container">
        <a class="navbar-brand" href="<?php echo __BASE_URL__; ?>">
            <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>logo.png" height="40">
        </a>
        
        <button class="navbar-toggler navbar-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse justify-content-center" id="navContent">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>">INICIO</a></li>
                
                <?php if($navConfig['show_news']): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>news">NOTICIAS</a></li>
                <?php endif; ?>

                <?php if($navConfig['show_info']): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>info">INFO</a></li>
                <?php endif; ?>

                <?php if($navConfig['show_downloads']): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>downloads">DESCARGAS</a></li>
                <?php endif; ?>

                <?php if($navConfig['show_rankings']): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>rankings">RANKINGS</a></li>
                <?php endif; ?>

                <?php if($navConfig['show_guides']): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>guides">GUÍAS<a></li>
                <?php endif; ?>

                <?php if(!empty($navConfig['custom_links'])): ?>
                    <?php foreach($navConfig['custom_links'] as $link): ?>
                        <?php 
                        // SI NO ESTÁ ACTIVO, SALTAR
                        if(isset($link['active']) && $link['active'] == 0) continue; 
                        
                        // Si es URL externa (empieza con http), no agregamos __BASE_URL__
                        $finalUrl = (strpos($link['route'], 'http') === 0) ? $link['route'] : __BASE_URL__ . $link['route'];
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($finalUrl); ?>">
                                <?php echo strtoupper(htmlspecialchars($link['title'])); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if(!isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo __BASE_URL__; ?>register">REGISTRO</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <?php if(isLoggedIn()): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp" class="btn btn-panel">
                    <i class="fa fa-user"></i> PANEL
                </a>
        
                <?php if(canAccessAdminCP($_SESSION['username'])): ?>
                    <a href="<?php echo __PATH_ADMINCP_HOME__; ?>" class="btn btn-admincp-nav">
                        <i class="fa fa-gear"></i> ADM
                    </a>
                <?php endif; ?>
                
            <?php else: ?>
                <a href="<?php echo __BASE_URL__; ?>register" class="btn btn-register-nav">Registrarse</a>
                <a href="<?php echo __BASE_URL__; ?>login" class="btn btn-login-nav">
                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>