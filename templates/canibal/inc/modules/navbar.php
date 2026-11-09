<?php
/**
 * Dynamic Navbar - Canibal Template
 * Basado en la lógica de navbar_config.json
 */

// 1. Cargar Configuración del AdminCP
$navConfigPath = __PATH_CONFIGS__ . 'navbar_config.json';
$navConfig = [
    'show_info' => 1,
    'show_downloads' => 1,
    'show_rankings' => 1,
    'show_guides' => 1,
    'show_news' => 1,
    'custom_links' => []
];

if (file_exists($navConfigPath)) {
    $loadedNav = json_decode(file_get_contents($navConfigPath), true);
    if(is_array($loadedNav)) {
        $navConfig = array_merge($navConfig, $loadedNav);
    }
}
?>

<nav class="canibal-navbar-structure">
    <div class="canibal-nav-links">
        
        <a href="<?php echo __BASE_URL__; ?>" class="nav-btn-custom">
            <i class="fa fa-home"></i> INICIO
        </a>

        <?php if($navConfig['show_news']): ?>
            <a href="<?php echo __BASE_URL__; ?>news" class="nav-btn-custom">
                <i class="fa fa-newspaper"></i> NOTICIAS
            </a>
        <?php endif; ?>

        <?php if($navConfig['show_info']): ?>
            <a href="<?php echo __BASE_URL__; ?>info" class="nav-btn-custom">
                <i class="fa fa-info-circle"></i> INFO
            </a>
        <?php endif; ?>

        <?php if($navConfig['show_downloads']): ?>
            <a href="<?php echo __BASE_URL__; ?>downloads" class="nav-btn-custom">
                <i class="fa fa-download"></i> DESCARGAS
            </a>
        <?php endif; ?>

        <?php if($navConfig['show_rankings']): ?>
            <a href="<?php echo __BASE_URL__; ?>rankings" class="nav-btn-custom">
                <i class="fa fa-trophy"></i> RANKINGS
            </a>
        <?php endif; ?>

        <?php if($navConfig['show_guides']): ?>
            <a href="<?php echo __BASE_URL__; ?>guides" class="nav-btn-custom">
                <i class="fa fa-book"></i> GUÍAS
            </a>
        <?php endif; ?>

        <?php if(!empty($navConfig['custom_links'])): ?>
            <?php foreach($navConfig['custom_links'] as $link): ?>
                <?php 
                // Saltar si no está activo
                if(isset($link['active']) && $link['active'] == 0) continue; 
                
                // Determinar URL (Interna o Externa)
                $finalUrl = (strpos($link['route'], 'http') === 0) ? $link['route'] : __BASE_URL__ . $link['route'];
                ?>
                <a href="<?php echo htmlspecialchars($finalUrl); ?>" class="nav-btn-custom">
                    <i class="fa fa-link"></i> <?php echo strtoupper(htmlspecialchars($link['title'])); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if(!isLoggedIn()): ?>
            <a href="<?php echo __BASE_URL__; ?>register" class="nav-btn-custom">
                <i class="fa fa-user-plus"></i> REGISTRO
            </a>
        <?php endif; ?>

    </div>
</nav>