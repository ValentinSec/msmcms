<?php
/**
 * Módulo de Administración de Popup (Diseño Final Ajustado a popup.png)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// 1. RUTAS
$popupFile   = __PATH_INCLUDES__ . 'config/popup_config.json';
$socialFile  = __PATH_INCLUDES__ . 'config/custom_settings.json';
$uploadDir   = __ROOT_DIR__ . 'img/popup/';

// Crear carpeta
if (!file_exists($uploadDir)) { @mkdir($uploadDir, 0777, true); }

// 2. DEFAULT CONFIG
$popupData = [
    'active'      => 0,
    'title'       => '',
    'subtitle'    => '',
    'body'        => '',
    'image'       => 'popup.png', // Default image
    'btn1_active' => 0, 'btn1_text' => '', 'btn1_link' => '',
    'btn2_active' => 0, 'btn2_text' => '', 'btn2_network'=> 'social_discord'
];

if (file_exists($popupFile)) {
    $loaded = json_decode(file_get_contents($popupFile), true);
    if (is_array($loaded)) $popupData = array_merge($popupData, $loaded);
}

// Fallback de imagen
if (empty($popupData['image'])) { $popupData['image'] = 'popup.png'; }

// Cargar redes
$socialData = [];
if (file_exists($socialFile)) {
    $socialData = json_decode(file_get_contents($socialFile), true);
}

// 3. GUARDAR
if (isset($_POST['save_popup'])) {
    $saveData = $popupData;
    
    $saveData['active']      = isset($_POST['popup_active']) ? 1 : 0;
    $saveData['title']       = isset($_POST['popup_title']) ? $_POST['popup_title'] : '';
    $saveData['subtitle']    = isset($_POST['popup_subtitle']) ? $_POST['popup_subtitle'] : '';
    $saveData['body']        = isset($_POST['popup_body']) ? $_POST['popup_body'] : '';
    
    $saveData['btn1_active'] = isset($_POST['btn1_active']) ? 1 : 0;
    $saveData['btn1_text']   = isset($_POST['btn1_text']) ? $_POST['btn1_text'] : '';
    $saveData['btn1_link']   = isset($_POST['btn1_link']) ? $_POST['btn1_link'] : '';
    
    $saveData['btn2_active'] = isset($_POST['btn2_active']) ? 1 : 0;
    $saveData['btn2_text']   = isset($_POST['btn2_text']) ? $_POST['btn2_text'] : '';
    $saveData['btn2_network']= isset($_POST['btn2_network']) ? $_POST['btn2_network'] : '';

    if (isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['popup_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed)) {
            if (!empty($popupData['image']) && $popupData['image'] !== 'popup.png' && file_exists($uploadDir . $popupData['image'])) {
                @unlink($uploadDir . $popupData['image']);
            }
            $newFileName = 'popup_bg_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['popup_image']['tmp_name'], $uploadDir . $newFileName)) {
                $saveData['image'] = $newFileName;
            }
        }
    }

    file_put_contents($popupFile, json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: index.php?module=popup&success=1");
    exit;
}

function getV($k, $d) { return isset($d[$k]) ? htmlspecialchars($d[$k]) : ""; }
function getC($k, $d) { return (isset($d[$k]) && $d[$k] == 1) ? "checked" : ""; }

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">';

echo '<div class="container-fluid">';
    echo '<form action="" method="post" enctype="multipart/form-data">';
        
        // Alerta
        if(isset($_GET['success'])) {
            echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
        }

        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Popup</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="save_popup" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Grid
        echo '<div class="row row-flex">';
            
            // Columna Izquierda: Configuración
            echo '<div class="col-md-5">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Contenidos</h3>';
                    
                    // Switch Active
                    echo '<div class="form-group switch-container">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="popup_active" ' . getC('active', $popupData) . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        echo '<span class="switch-label">Estado del Popup</span>';
                    echo '</div>';

                    // Inputs Texto
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Título Principal (Arriba de la línea)</label>';
                        echo '<input type="text" id="in_title" name="popup_title" class="form-control-dark" value="' . getV('title', $popupData) . '" placeholder="Ej: ¡BIENVENIDOS!" oninput="updatePreview()">';
                    echo '</div>';

                    echo '<div class="form-group" style="margin-top: 1px;">';
                        echo '<label class="settings-label">Subtítulo (Debajo de la línea)</label>';
                        echo '<input type="text" id="in_subtitle" name="popup_subtitle" class="form-control-dark" value="' . getV('subtitle', $popupData) . '" placeholder="Ej: Nueva Apertura" oninput="updatePreview()">';
                    echo '</div>';

                    echo '<div class="form-group" style="margin-top: 15px;">';
                        echo '<label class="settings-label">Cuerpo del Mensaje</label>';
                        echo '<textarea id="in_body" name="popup_body" class="form-control-dark" placeholder="Escribe el mensaje aquí..." oninput="updatePreview()">' . getV('body', $popupData) . '</textarea>';
                    echo '</div>';

                    echo '<div class="divider-text">Botones (Texto dentro de los marcos)</div>';

                    // Botón 1
                    echo '<div class="btn-config-box">';
                        echo '<div class="form-group switch-container mb-2">';
                            echo '<label class="switch sm">';
                                echo '<input type="checkbox" id="check_btn1" name="btn1_active" ' . getC('btn1_active', $popupData) . ' onchange="updatePreview()">';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="text-gold">Botón Izquierdo (Manual)</span>';
                        echo '</div>';
                        echo '<div class="row">';
                            echo '<div class="col-md-6">';
                                echo '<input type="text" id="in_btn1_text" name="btn1_text" class="form-control-dark sm" value="' . getV('btn1_text', $popupData) . '" placeholder="Texto (ej: Registro)" oninput="updatePreview()">';
                            echo '</div>';
                            echo '<div class="col-md-6">';
                                echo '<input type="text" name="btn1_link" class="form-control-dark sm" value="' . getV('btn1_link', $popupData) . '" placeholder="URL">';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    // Botón 2
                    echo '<div class="btn-config-box mt-3">';
                        echo '<div class="form-group switch-container mb-2">';
                            echo '<label class="switch sm">';
                                echo '<input type="checkbox" id="check_btn2" name="btn2_active" ' . getC('btn2_active', $popupData) . ' onchange="updatePreview()">';
                                echo '<span class="slider"></span>';
                            echo '</label>';
                            echo '<span class="text-blue">Botón Derecho (Social)</span>';
                        echo '</div>';
                        echo '<div class="row">';
                            echo '<div class="col-md-6">';
                                echo '<input type="text" id="in_btn2_text" name="btn2_text" class="form-control-dark sm" value="' . getV('btn2_text', $popupData) . '" placeholder="Texto (ej: Discord)" oninput="updatePreview()">';
                            echo '</div>';
                            echo '<div class="col-md-6">';
                                echo '<select name="btn2_network" class="form-control-dark sm">';
                                    $networks = ['social_fb'=>'Facebook','social_discord'=>'Discord','social_wpp'=>'WhatsApp','social_yt'=>'YouTube','social_ig'=>'Instagram'];
                                    foreach($networks as $k => $v) {
                                        $sel = ($popupData['btn2_network'] == $k) ? 'selected' : '';
                                        echo "<option value='$k' $sel>$v</option>";
                                    }
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    // Imagen
                    echo '<div class="form-group" style="margin-top: 25px;">';
                        echo '<label class="settings-label">Imagen de Fondo</label>';
                        echo '<input type="file" name="popup_image" class="form-control-dark" accept="image/*">';
                        echo '<small style="color:#666; font-size:10px;">Default: <b>img/popup/popup.png</b></small>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // Columna Derecha: Preview
            echo '<div class="col-md-7">';
                echo '<div class="settings-section" style="min-height: 800px; display: flex; align-items: center; justify-content: center; background: #000; position: relative;">';
                    echo '<h3 class="settings-title" style="position: absolute; top: 20px; left: 25px; border:none; z-index: 10;">Previsualización en Vivo</h3>';
                    
                    echo '<div class="popup-preview-container">';
                        echo '<div class="popup-bg">';
                            $bgImage = !empty($popupData['image']) ? $popupData['image'] : 'popup.png';
                            $imgSrc = __BASE_URL__ . 'img/popup/' . $bgImage;
                            echo '<img src="' . $imgSrc . '?v=' . time() . '" alt="Popup BG">';
                        echo '</div>';

                        echo '<div class="popup-inner-content">';
                            
                            echo '<div class="top-content">';
                                echo '<h2 id="prev_title" class="cinzel-bold">TÍTULO AQUÍ</h2>';
                                echo '<div class="spacer-for-image-line"></div>';
                                echo '<h4 id="prev_subtitle" class="cinzel-regular">SUBTÍTULO AQUÍ</h4>';
                            echo '</div>';

                            echo '<div class="mid-content">';
                                echo '<p id="prev_body" class="cinzel-body">Escribe el contenido del mensaje a la izquierda...</p>';
                            echo '</div>';

                            echo '<a id="prev_btn1" class="btn-overlay btn-left">BOTÓN 1</a>';
                            echo '<a id="prev_btn2" class="btn-overlay btn-right">BOTÓN 2</a>';

                        echo '</div>';
                        
                        echo '<div class="close-x">&times;</div>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row
    echo '</form>';
echo '</div>';

// JavaScript
echo '<script>
    function updatePreview() {
        document.getElementById("prev_title").innerText = document.getElementById("in_title").value || "TÍTULO";
        document.getElementById("prev_subtitle").innerText = document.getElementById("in_subtitle").value || "SUBTÍTULO";
        document.getElementById("prev_body").innerText = document.getElementById("in_body").value || "Cuerpo del mensaje...";
        
        document.getElementById("prev_btn1").innerText = document.getElementById("in_btn1_text").value || "BOTÓN 1";
        document.getElementById("prev_btn2").innerText = document.getElementById("in_btn2_text").value || "BOTÓN 2";

        var showBtn1 = document.getElementById("check_btn1").checked;
        var showBtn2 = document.getElementById("check_btn2").checked;

        document.getElementById("prev_btn1").style.display = showBtn1 ? "flex" : "none";
        document.getElementById("prev_btn2").style.display = showBtn2 ? "flex" : "none";
    }

    document.addEventListener("DOMContentLoaded", function() {
        updatePreview();
    });
</script>';

// Styles
echo '<style>
    /* PANEL */
    .row-flex { display: flex; flex-wrap: wrap; }
    .settings-section { background: #0d1117; padding: 25px; border-radius: 8px; border: 1px solid #30363d; position: relative; }
    .settings-title { margin:0 0 20px 0; border-bottom: 1px solid #2d323e; padding-bottom: 15px; color: #fff; font-size: 18px; font-weight: 600; }
    .form-control-dark { background-color: #222; border: 1px solid #444; color: #eee; padding: 10px; width: 100%; border-radius: 4px; }
    textarea.form-control-dark { min-height: 120px; resize: vertical; }
    .btn-config-box { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #2d323e; }
    
    .switch-container { display: flex; align-items: center; }
    .switch { position: relative; display: inline-block; width: 50px; height: 24px; margin: 0 10px 0 0; }
    .switch.sm { transform: scale(0.8); margin-left: -5px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #383838; transition: .4s; border-radius: 34px; }
    .slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #28a745; }
    input:checked + .slider:before { transform: translateX(26px); }
    
    .btn-save-green { background: #1a4d2e; color: #00ff88; border: 1px solid #00ff88; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; }
    .text-gold { color: #f39c12; font-weight: bold; font-size: 12px; }
    .text-blue { color: #5865F2; font-weight: bold; font-size: 12px; }

    /* --- DISEÑO POPUP FINAL --- */
    .popup-preview-container {
        position: relative;
        width: 500px; 
        height: auto;
        border-radius: 10px; 
        box-shadow: 0 0 50px rgba(0,0,0,0.9);
        overflow: hidden;
    }
    
    .popup-bg { width: 100%; height: 100%; }
    .popup-bg img { width: 100%; height: auto; display: block; }

    .popup-inner-content {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        display: flex; flex-direction: column;
        padding: 40px; 
        box-sizing: border-box;
    }

    /* ZONA SUPERIOR */
    .top-content { text-align: center; margin-top: 5px; }
    
    .cinzel-bold {
        font-family: "Cinzel", serif; font-weight: 700; font-size: 32px;
        color: #e0d2ad; text-shadow: 0 4px 10px rgba(0,0,0,0.9);
        margin: 0; text-transform: uppercase; line-height: 1.1;
    }
    
    .spacer-for-image-line {
        height: 35px; 
    }

    .cinzel-regular {
        font-family: "Cinzel", serif; font-weight: 400; font-size: 18px;
        color: #a89f91; margin: 0; text-transform: uppercase;
        letter-spacing: 2px; text-shadow: 0 2px 5px rgba(0,0,0,0.8);
    }

    /* ZONA MEDIA (Cuerpo) */
    .mid-content {
        flex-grow: 1; 
        display: flex; 
        align-items: flex-start; 
        justify-content: center; 
        text-align: center;
        padding-top: 105px; 
    }
    .cinzel-body {
        font-family: "Cinzel", serif; 
        font-size: 16px; 
        color: #cccccc;
        line-height: 1.6; 
        margin: 0; 
        text-shadow: 0 1px 3px rgba(0,0,0,1);
        max-width: 90%; 
    }

    /* BOTONES INVISIBLES SOBRE LA IMAGEN */
    .btn-overlay {
        position: absolute;
        bottom: 7.5%; 
        width: 27%;   
        height: 10%;  
        display: flex; justify-content: center; align-items: center;
        
        font-family: "Cinzel", serif; font-weight: 700; font-size: 13px;
        text-transform: uppercase; text-decoration: none;
        cursor: pointer; transition: 0.3s;
        
        color: #cba660; 
        text-shadow: 0 2px 5px rgba(0,0,0,0.8);
    }
    
    .btn-overlay:hover {
        color: #fff;
        text-shadow: 0 0 8px #cba660;
    }

    /* Coordenadas Horizontales */
    .btn-left { left: 17.3%; }
    .btn-right { right: 17.3%; color: #a0b3ff; }
    .btn-right:hover { text-shadow: 0 0 8px #5865F2; }

    .close-x {
        position: absolute; top: 15px; right: 20px;
        color: #888; font-family: sans-serif; font-size: 24px; font-weight: bold; cursor: pointer; z-index: 20;
    }
    .close-x:hover { color: #fff; }
</style>';
?>