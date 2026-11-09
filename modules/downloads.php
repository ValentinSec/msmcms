<?php
/**
 * Balduneta MU - Downloads Module
 * FIXED VERSION: String Concatenation (Safe for Obfuscation)
 */

try {
    if(!mconfig('active')) throw new Exception(lang('error_47',true));
    
    $downloadCLIENTS = array();
    $downloadPATCHES = array();
    $downloadTOOLS = array();
    
    // Ruta absoluta
    $jsonPath = dirname(__FILE__) . '/../includes/config/modules/downloads.json';
    
    if(file_exists($jsonPath)) {
        $jsonContent = file_get_contents($jsonPath);
        $downloadsDATA = json_decode($jsonContent, true);
        
        if(is_array($downloadsDATA)) {
            foreach($downloadsDATA as $tempDownloadsData) {
                $type = isset($tempDownloadsData['download_type']) ? $tempDownloadsData['download_type'] : null;
                
                $data = [
                    'download_link' => isset($tempDownloadsData['download_link']) ? $tempDownloadsData['download_link'] : '',
                    'download_hosting' => isset($tempDownloadsData['download_hosting']) ? $tempDownloadsData['download_hosting'] : '',
                    'download_title' => isset($tempDownloadsData['download_title']) ? $tempDownloadsData['download_title'] : '1.0'
                ];

                if($type == 1) $downloadCLIENTS[] = $data;
                if($type == 2) $downloadPATCHES[] = $data;
                if($type == 3) $downloadTOOLS[] = $data;
            }
        }
    }

    // Variables de entorno
    $showLite = !empty($downloadCLIENTS);
    $showFull = !empty($downloadPATCHES);
    $colClass = ($showLite && $showFull) ? 'col-md-6' : 'col-md-12';
    $cssPath = __PATH_TEMPLATE_CSS__;
    $serverName = custom_config("server_name");

    // --- INICIO RENDERING (CONCATENACIÓN SEGURA) ---
    
    echo '<link href="' . $cssPath . 'downloads.css" rel="stylesheet">';
    echo '<div id="downloads-page-balduneta">';
    
    // Header
    echo '<div class="rank-header text-center mb-5">';
    echo '<h1 class="rank-title">DESCARGAS</h1>';
    echo '<p class="rank-subtitle">Descarga ' . $serverName . ' y comienza tu aventura hoy mismo.</p>';
    echo '</div>';

    echo '<div class="container-fluid g-4">';
    echo '<div class="row g-4 mb-5">';

    // 1. CLIENTE LITE
    if($showLite) {
        echo '<div class="' . $colClass . '">';
        echo '<div class="dl-box-standalone p-4 h-100 border-purple-glow">';
        echo '<span class="badge-new-yellow">CLIENTE SIN SONIDO</span>';
        echo '<div class="d-flex align-items-center mb-4 mt-2">';
        echo '<div class="icon-square bg-purple-soft me-3"><i class="fa fa-rocket"></i></div>';
        echo '<h2 class="text-white fw-bold mb-0">CLIENTE LITE</h2>';
        echo '</div>';
        echo '<div class="download-links-stack">';
        
        foreach($downloadCLIENTS as $dl) {
            echo '<a href="' . $dl['download_link'] . '" target="_blank" class="btn-dl-gradient purple mb-2">';
            echo '<span><i class="fa fa-download me-2"></i> ' . $dl['download_hosting'] . '</span>';
            echo '<small>v' . $dl['download_title'] . '</small>';
            echo '</a>';
        }
        
        echo '</div></div></div>';
    }

    // 2. CLIENTE FULL
    if($showFull) {
        echo '<div class="' . $colClass . '">';
        echo '<div class="dl-box-standalone p-4 h-100 border-orange-glow">';
        echo '<span class="badge-new-yellow">CLIENTE CON SONIDO</span>';
        echo '<div class="d-flex align-items-center mb-4 mt-2">';
        echo '<div class="icon-square bg-orange-soft me-3"><i class="fa fa-shield-halved"></i></div>';
        echo '<h2 class="text-white fw-bold mb-0">CLIENTE FULL</h2>';
        echo '</div>';
        echo '<div class="download-links-stack">';
        
        foreach($downloadPATCHES as $dl) {
            echo '<a href="' . $dl['download_link'] . '" target="_blank" class="btn-dl-gradient orange mb-2">';
            echo '<span><i class="fa fa-download me-2"></i> ' . $dl['download_hosting'] . '</span>';
            echo '<small>v' . $dl['download_title'] . '</small>';
            echo '</a>';
        }

        echo '</div></div></div>';
    }

    // 3. SIN CLIENTES
    if(!$showLite && !$showFull) {
        echo '<div class="col-12">';
        echo '<div class="dl-box-standalone p-5 text-center">';
        echo '<p class="text-muted m-0">No hay clientes de juego disponibles en este momento.</p>';
        echo '</div></div>';
    }

    echo '</div>'; // Cierre row superior

    // FILA INFERIOR
    echo '<div class="row g-4 d-flex align-items-stretch">';
    
    // COLUMNA IZQUIERDA (TOOLS / FEATURES)
    echo '<div class="col-md-6">';
    echo '<div class="dl-box-standalone p-5 h-100 d-flex flex-column">';
    
    if(!empty($downloadTOOLS)) {
        echo '<h3 class="text-orange-title text-center mb-5 text-uppercase">Herramientas Útiles</h3>';
        echo '<div class="download-links-stack flex-grow-1">';
        foreach($downloadTOOLS as $dl) {
            echo '<a href="' . $dl['download_link'] . '" target="_blank" class="btn-dl-gradient purple mb-2">';
            echo '<span><i class="fa fa-wrench me-2"></i> ' . $dl['download_hosting'] . '</span>';
            echo '<small>' . $dl['download_title'] . '</small>';
            echo '</a>';
        }
        echo '</div>';
    } else {
        echo '<h3 class="text-orange-title text-center mb-5 text-uppercase">¿Por qué elegir ' . $serverName . '?</h3>';
        echo '<ul class="list-unstyled dl-features text-white flex-grow-1">';
        echo '<li class="mb-4 d-flex align-items-center"><i class="fa fa-shield-virus text-orange me-3" style="font-size: 1.5rem;"></i> <span>Cliente seguro y optimizado a 60 FPS</span></li>';
        echo '<li class="mb-4 d-flex align-items-center"><i class="fa fa-bolt text-orange me-3" style="font-size: 1.5rem;"></i> <span>Sistema exclusivo de PvP y PvM</span></li>';
        echo '<li class="mb-4 d-flex align-items-center"><i class="fa fa-users text-orange me-3" style="font-size: 1.5rem;"></i> <span>Comunidad activa con miles de miembros</span></li>';
        echo '</ul>';
    }
    echo '</div></div>';

    // COLUMNA DERECHA (REQUISITOS)
    echo '<div class="col-md-6">';
    echo '<div class="dl-box-standalone p-5 h-100 d-flex flex-column">';
    echo '<h3 class="text-orange-title text-center mb-5 text-uppercase">Requisitos del Sistema</h3>';
    echo '<div class="requirements-table small text-white flex-grow-1">';
    
    // Header Tabla
    echo '<div class="row border-bottom border-secondary border-opacity-10 pb-2 mb-2 fw-bold text-orange text-uppercase">';
    echo '<div class="col-4"></div><div class="col-4 text-center">Mínimo</div><div class="col-4 text-center">Recomendado</div></div>';
    
    // SO
    echo '<div class="row mb-3 pb-1 border-bottom border-secondary border-opacity-10">';
    echo '<div class="col-4 fw-bold text-orange">SO:</div><div class="col-4 text-center text-white">Win 7/8/10</div><div class="col-4 text-center text-white">Win 10 64-bit</div></div>';

    // CPU
    echo '<div class="row mb-3 pb-1 border-bottom border-secondary border-opacity-10">';
    echo '<div class="col-4 fw-bold text-orange">CPU:</div><div class="col-4 text-center text-white">Intel i3</div><div class="col-4 text-center text-white">Intel i5</div></div>';

    // RAM
    echo '<div class="row mb-3 pb-1 border-bottom border-secondary border-opacity-10">';
    echo '<div class="col-4 fw-bold text-orange">RAM:</div><div class="col-4 text-center text-white">4GB</div><div class="col-4 text-center text-white">8GB</div></div>';
    
    // GPU
    echo '<div class="row mb-3 pb-1 border-bottom border-secondary border-opacity-10">';
    echo '<div class="col-4 fw-bold text-orange">GPU:</div><div class="col-4 text-center text-white">DirectX 9</div><div class="col-4 text-center text-white">DirectX 11</div></div>';

    // HDD
    echo '<div class="row mb-3 pb-1">';
    echo '<div class="col-4 fw-bold text-orange">ESPACIO:</div><div class="col-4 text-center text-white">2GB</div><div class="col-4 text-center text-white">4GB</div></div>';

    echo '</div></div></div>'; // Cierre requisitos y col
    echo '</div>'; // Cierre row inferior
    echo '</div>'; // Cierre container
    echo '</div>'; // Cierre ID principal

} catch(Exception $ex) {
    message('error', $ex->getMessage());
}
?>