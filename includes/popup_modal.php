<?php
/**
 * Frontend Popup Logic & Design (Sincronizado con AdminCP)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// 1. RUTAS DE CONFIGURACIÓN
$popCfgFile = 'includes/config/popup_config.json';
$socCfgFile = 'includes/config/custom_settings.json';

// 2. VERIFICACIONES DE SEGURIDAD
$canShowPopup = false;

if (file_exists($popCfgFile)) {
    $popData = json_decode(file_get_contents($popCfgFile), true);
    
    // Validar si está activo y no se ha visto (cookie)
    if (!empty($popData['active']) && $popData['active'] == 1 && !isset($_COOKIE['mu_popup_seen'])) {
        $canShowPopup = true;
        
        // Cargar redes para el Botón 2
        $socData = [];
        if (file_exists($socCfgFile)) {
            $socData = json_decode(file_get_contents($socCfgFile), true);
        }
        
        // Resolver Links
        $btn1_URL = isset($popData['btn1_link']) ? $popData['btn1_link'] : '#';
        
        $networkKey = isset($popData['btn2_network']) ? $popData['btn2_network'] : ''; 
        $btn2_URL = isset($socData[$networkKey]) ? $socData[$networkKey] : '#';
        
        // Imagen (Fallback)
        $bgImage = !empty($popData['image']) ? $popData['image'] : 'popup.jpg';
        $imgSrc = 'img/popup/' . $bgImage;
    }
}

if ($canShowPopup) {
    // Fuentes y Estilos
    echo '<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">';
    
    echo '<style>
        .mu-popup-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); z-index: 99999;
            display: flex; justify-content: center; align-items: center;
            opacity: 0; animation: popupFadeIn 0.5s forwards;
        }
        .mu-popup-container {
            position: relative;
            width: 500px; max-width: 95%; height: auto;
            border-radius: 10px;
            box-shadow: 0 0 50px rgba(0,0,0,0.9);
            overflow: hidden;
            animation: popupScaleUp 0.4s ease-out;
        }
        .mu-popup-bg { width: 100%; height: 100%; font-size: 0; }
        .mu-popup-bg img { width: 100%; height: auto; display: block; }
        .mu-popup-content {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; flex-direction: column;
            padding: 40px; box-sizing: border-box;
        }
        .mu-top-content { text-align: center; margin-top: 5px; }
        .mu-cinzel-bold {
            font-family: "Cinzel", serif; font-weight: 700; font-size: 32px;
            color: #e0d2ad; text-shadow: 0 4px 10px rgba(0,0,0,0.9);
            margin: 0; text-transform: uppercase; line-height: 1.1; text-align: center;
        }
        .mu-spacer-line { height: 35px; }
        .mu-cinzel-regular {
            font-family: "Cinzel", serif; font-weight: 400; font-size: 18px;
            color: #a89f91; margin: 0; text-transform: uppercase;
            letter-spacing: 2px; text-shadow: 0 2px 5px rgba(0,0,0,0.8); text-align: center;
        }
        .mu-mid-content { 
            flex-grow: 1; 
            display: flex; align-items: flex-start;
            justify-content: center; 
            padding-top: 105px;
        }
        .mu-cinzel-body {
            font-family: "Cinzel", serif; font-size: 16px; 
            color: #cccccc; line-height: 1.6; margin: 0; 
            text-shadow: 0 1px 3px rgba(0,0,0,1); text-align: center;
            max-width: 90%;
        }
        .mu-btn-overlay {
            position: absolute;
            bottom: 7.5%;
            width: 27%;
            height: 10%;
            display: flex; justify-content: center; align-items: center;
            font-family: "Cinzel", serif; font-weight: 700; font-size: 13px;
            text-transform: uppercase; text-decoration: none;
            cursor: pointer; transition: 0.3s;
            color: #cba660; text-shadow: 0 2px 5px rgba(0,0,0,0.8);
        }
        .mu-btn-overlay:hover { 
            color: #fff; text-shadow: 0 0 8px #cba660; 
        }
        .mu-btn-left { left: 17.3%; }
        .mu-btn-right { right: 17.3%; color: #a0b3ff; }
        .mu-btn-right:hover { text-shadow: 0 0 8px #5865F2; }
        .mu-popup-close {
            position: absolute; top: 15px; right: 20px;
            color: #888; font-family: sans-serif; font-size: 24px; font-weight: bold; cursor: pointer; z-index: 50;
        }
        .mu-popup-close:hover { color: #fff; }
        @keyframes popupFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popupScaleUp { from { transform: scale(0.9); } to { transform: scale(1); } }
        @media (max-width: 600px) {
            .mu-popup-container { width: 95%; }
            .mu-cinzel-bold { font-size: 20px; }
            .mu-cinzel-regular { font-size: 12px; }
            .mu-spacer-line { height: 15px; }
            .mu-cinzel-body { font-size: 11px; padding-top: 0; }
            .mu-mid-content { padding-top: 40px; }
            .mu-btn-overlay { font-size: 10px; bottom: 7%; height: 12%; }
        }
    </style>';

    echo '<div id="muWelcomePopup" class="mu-popup-overlay">';
    echo '    <div class="mu-popup-container">';
    echo '        <div class="mu-popup-bg">';
    echo '            <img src="' . $imgSrc . '?v=' . time() . '" alt="Welcome">';
    echo '        </div>';
    echo '        <div class="mu-popup-content">';
    echo '            <div class="mu-top-content">';
    echo '                <h2 class="mu-cinzel-bold">' . $popData['title'] . '</h2>';
    echo '                <div class="mu-spacer-line"></div>';
    echo '                <h4 class="mu-cinzel-regular">' . $popData['subtitle'] . '</h4>';
    echo '            </div>';
    echo '            <div class="mu-mid-content">';
    echo '                <p class="mu-cinzel-body">' . nl2br($popData['body']) . '</p>';
    echo '            </div>';
    
    if ($popData['btn1_active']) {
        echo '            <a href="' . $btn1_URL . '" class="mu-btn-overlay mu-btn-left">' . $popData['btn1_text'] . '</a>';
    }
    
    if ($popData['btn2_active']) {
        echo '            <a href="' . $btn2_URL . '" target="_blank" class="mu-btn-overlay mu-btn-right">' . $popData['btn2_text'] . '</a>';
    }
    
    echo '        </div>';
    echo '        <div class="mu-popup-close" onclick="closeMuPopup()">&times;</div>';
    echo '    </div>';
    echo '</div>';

    echo '<script>
    function closeMuPopup() {
        var popup = document.getElementById("muWelcomePopup");
        if(popup) {
            popup.style.opacity = "0";
            setTimeout(function() {
                popup.style.display = "none";
            }, 300);
        }
        var date = new Date();
        date.setTime(date.getTime() + (24 * 60 * 60 * 1000));
        document.cookie = "mu_popup_seen=1; expires=" + date.toUTCString() + "; path=/";
    }
    </script>';
}