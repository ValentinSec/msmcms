<?php
/**
 * Edit Template Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// --- 1. CÁLCULO DE RUTAS ---
$rootPath = realpath(__DIR__ . '/../../../') . '/';
$mainConfigPath = $rootPath . 'includes/config/muservermaker.json';

// Detectar template activo
$serverActiveTemplate = 'default';
if (file_exists($mainConfigPath)) {
    $mainConfig = json_decode(file_get_contents($mainConfigPath), true);
    if (isset($mainConfig['website_template'])) $serverActiveTemplate = $mainConfig['website_template'];
}

// --- 2. ESCANEO Y SELECCIÓN ---
$templatesDir = $rootPath . 'templates/';
$availableTemplates = [];
if (is_dir($templatesDir)) {
    $dirs = scandir($templatesDir);
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && $dir !== 'misc' && is_dir($templatesDir . $dir)) {
            $availableTemplates[] = $dir;
        }
    }
}
$targetTemplate = isset($_POST['target_template']) ? $_POST['target_template'] : $serverActiveTemplate;
if (!in_array($targetTemplate, $availableTemplates)) { $targetTemplate = $availableTemplates[0]; }

// Rutas
$templatePath       = $templatesDir . $targetTemplate . '/';
$templateImgPath    = $templatePath . 'img/';
$templateConfigPath = $templatePath . 'config.json';
$webImgPath         = defined('__BASE_URL__') ? __BASE_URL__ . 'templates/' . $targetTemplate . '/img/' : '/templates/' . $targetTemplate . '/img/';
$placeholderImg     = "https://placehold.co/400x200/222/666?text=Sin+Imagen";

// --- 3. GUARDADO ---
$msgContent = '';
if(isset($_POST['save_template_settings'])) {
    try {
        $currentConfig = [];
        if(file_exists($templateConfigPath)) {
            $currentConfig = json_decode(file_get_contents($templateConfigPath), true);
        }
        if (!file_exists($templateImgPath)) { @mkdir($templateImgPath, 0755, true); }

        $processDelete = function($flagName, $configKey, $defaultFile, &$config, $path) {
            if (isset($_POST[$flagName]) && $_POST[$flagName] == '1') {
                $fileToDelete = isset($config[$configKey]) ? $config[$configKey] : $defaultFile;
                if (!empty($fileToDelete) && file_exists($path . $fileToDelete)) { @unlink($path . $fileToDelete); }
                $config[$configKey] = ''; 
            }
        };
        
        // Extension tomada del contenido real de la imagen, nunca del nombre del archivo subido
        $safeImgExt = function($fileKey) {
            $map = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');
            $info = @getimagesize($_FILES[$fileKey]['tmp_name']);
            if(!is_array($info) || !isset($map[$info[2]])) {
                throw new Exception('Uno de los archivos subidos no es una imagen v&aacute;lida (JPG, PNG, GIF o WEBP). No se guard&oacute; ning&uacute;n cambio de configuraci&oacute;n.');
            }
            return $map[$info[2]];
        };

        // Ejecutar borrados (Generales)
        $processDelete('delete_favicon', 'favicon', 'favicon.ico', $currentConfig, $templateImgPath);
        $processDelete('delete_logo', 'logo_img', 'logo.png', $currentConfig, $templateImgPath);
        $processDelete('delete_hero', 'hero_image', '', $currentConfig, $templateImgPath);
        $processDelete('delete_bg', 'background_image', '', $currentConfig, $templateImgPath);
        
        // Ejecutar borrados (Morpheus)
        if($targetTemplate == 'morpheus') {
            $processDelete('delete_s1', 'slider_img_1', 'slider1.jpg', $currentConfig, $templateImgPath);
            $processDelete('delete_s2', 'slider_img_2', 'slider2.jpg', $currentConfig, $templateImgPath);
        }

        // Ejecutar borrados (Canibal)
        if($targetTemplate == 'canibal') {
            $processDelete('delete_bg_top', 'bg_top_img', 'bg-top.jpg', $currentConfig, $templateImgPath);
            $processDelete('delete_bg_bottom', 'bg_bottom_img', 'bg-bottom.jpg', $currentConfig, $templateImgPath);
            $processDelete('delete_soc_fb', 'soc_fb_img', 'social/facebook.jpg', $currentConfig, $templateImgPath);
            $processDelete('delete_soc_dc', 'soc_dc_img', 'social/discord.jpg', $currentConfig, $templateImgPath);
            $processDelete('delete_soc_wp', 'soc_wp_img', 'social/whatsapp.jpg', $currentConfig, $templateImgPath);
            $processDelete('delete_soc_yt', 'soc_yt_img', 'social/youtube.jpg', $currentConfig, $templateImgPath);
        }

        // Subidas Generales
        if(!empty($_FILES['favicon_upload']['name']) && $_FILES['favicon_upload']['error'] === UPLOAD_ERR_OK) {
            move_uploaded_file($_FILES['favicon_upload']['tmp_name'], $templateImgPath . 'favicon.ico');
            $currentConfig['favicon'] = 'favicon.ico'; // Forzamos guardar en json por si acaso
        }
        if(!empty($_FILES['logo_upload']['name']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
            move_uploaded_file($_FILES['logo_upload']['tmp_name'], $templateImgPath . 'logo.png');
            $currentConfig['logo_img'] = 'logo.png';
        }
        if(!empty($_FILES['hero_upload']['name']) && $_FILES['hero_upload']['error'] === UPLOAD_ERR_OK) {
            $ext = $safeImgExt('hero_upload');
            $heroName = 'hero_custom_' . time() . '.' . $ext; 
            if(move_uploaded_file($_FILES['hero_upload']['tmp_name'], $templateImgPath . $heroName)) {
                if(isset($currentConfig['hero_image']) && file_exists($templateImgPath.$currentConfig['hero_image'])) { @unlink($templateImgPath.$currentConfig['hero_image']); }
                $currentConfig['hero_image'] = $heroName;
            }
        }
        if(!empty($_FILES['background_upload']['name']) && $_FILES['background_upload']['error'] === UPLOAD_ERR_OK) {
            $ext = $safeImgExt('background_upload');
            $bgName = 'bg_custom_' . time() . '.' . $ext;
            if(move_uploaded_file($_FILES['background_upload']['tmp_name'], $templateImgPath . $bgName)) {
                if(isset($currentConfig['background_image']) && file_exists($templateImgPath.$currentConfig['background_image'])) { @unlink($templateImgPath.$currentConfig['background_image']); }
                $currentConfig['background_image'] = $bgName;
            }
        }

        // Subidas Específicas: CANIBAL
        if($targetTemplate == 'canibal') {
            // Fondos
            if(!empty($_FILES['bg_top_upload']['name']) && $_FILES['bg_top_upload']['error'] === UPLOAD_ERR_OK) {
                $ext = $safeImgExt('bg_top_upload');
                $bgTopName = 'bg_top_' . time() . '.' . $ext; 
                if(move_uploaded_file($_FILES['bg_top_upload']['tmp_name'], $templateImgPath . $bgTopName)) {
                    if(isset($currentConfig['bg_top_img']) && file_exists($templateImgPath.$currentConfig['bg_top_img'])) { @unlink($templateImgPath.$currentConfig['bg_top_img']); }
                    $currentConfig['bg_top_img'] = $bgTopName;
                }
            }
            if(!empty($_FILES['bg_bottom_upload']['name']) && $_FILES['bg_bottom_upload']['error'] === UPLOAD_ERR_OK) {
                $ext = $safeImgExt('bg_bottom_upload');
                $bgBotName = 'bg_bottom_' . time() . '.' . $ext; 
                if(move_uploaded_file($_FILES['bg_bottom_upload']['tmp_name'], $templateImgPath . $bgBotName)) {
                    if(isset($currentConfig['bg_bottom_img']) && file_exists($templateImgPath.$currentConfig['bg_bottom_img'])) { @unlink($templateImgPath.$currentConfig['bg_bottom_img']); }
                    $currentConfig['bg_bottom_img'] = $bgBotName;
                }
            }

            // Imágenes Sociales (Se guardan en /img/social/ de la plantilla para no sobreescribir las globales)
            $socialPath = $templateImgPath . 'social/';
            if (!file_exists($socialPath)) { 
                if(!@mkdir($socialPath, 0755, true)) {
                    throw new Exception("No se pudo crear la carpeta: " . $socialPath);
                }
            }

            $handleSocialUpload = function($fileKey, $configKey, $prefix) use ($socialPath, &$currentConfig, $templateImgPath) {
                if(isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                    
                    // Validar extensiones permitidas
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if(!in_array($ext, $allowed)) return;

                    $fName = $prefix . '_' . time() . '.' . $ext;
                    
                    if(move_uploaded_file($_FILES[$fileKey]['tmp_name'], $socialPath . $fName)) {
                        // Borrar imagen anterior si existe para no llenar el disco
                        if(isset($currentConfig[$configKey]) && !empty($currentConfig[$configKey])) {
                            $oldFile = $templateImgPath . $currentConfig[$configKey];
                            if(file_exists($oldFile)) { @unlink($oldFile); }
                        }
                        // GUARDAR LA RUTA RELATIVA
                        $currentConfig[$configKey] = 'social/' . $fName;
                    }
                }
            };

            $handleSocialUpload('soc_fb_upload', 'soc_fb_img', 'fb');
            $handleSocialUpload('soc_ig_upload', 'soc_ig_img', 'ig');
            $handleSocialUpload('soc_dc_upload', 'soc_dc_img', 'dc');
            $handleSocialUpload('soc_wp_upload', 'soc_wp_img', 'wp');
            $handleSocialUpload('soc_yt_upload', 'soc_yt_img', 'yt');
        }

        // Tamano del logo: Canibal usa su propio control; el resto, el general
        $widthField = ($targetTemplate == 'canibal' && isset($_POST['hero_logo_width_canibal'])) ? 'hero_logo_width_canibal' : 'hero_logo_width';
        if(isset($_POST[$widthField]) && is_string($_POST[$widthField])) {
            $cleanWidth = (int)preg_replace('/[^0-9]/', '', $_POST[$widthField]);
            if($cleanWidth < 50 || $cleanWidth > 1000) $cleanWidth = 300;
            $currentConfig['hero_logo_width'] = $cleanWidth . 'px';
        }
        if(isset($_POST['hero_video_url']) && is_string($_POST['hero_video_url'])) {
            $currentConfig['hero_video_url'] = trim($_POST['hero_video_url']);
        }

        // Slider (Aplica a Morpheus)
        if($targetTemplate == 'morpheus') {
            $currentConfig['slider_1_title'] = $_POST['slider_1_title'];
            $currentConfig['slider_1_btn']   = $_POST['slider_1_btn'];
            $currentConfig['slider_1_link']  = $_POST['slider_1_link'];
            $currentConfig['slider_2_title'] = $_POST['slider_2_title'];
            $currentConfig['slider_2_btn']   = $_POST['slider_2_btn'];
            $currentConfig['slider_2_link']  = $_POST['slider_2_link'];
            
            if(!empty($_FILES['s1_img']['name']) && $_FILES['s1_img']['error'] == 0) {
                move_uploaded_file($_FILES['s1_img']['tmp_name'], $templateImgPath . 'slider1.jpg');
            }
            if(!empty($_FILES['s2_img']['name']) && $_FILES['s2_img']['error'] == 0) {
                move_uploaded_file($_FILES['s2_img']['tmp_name'], $templateImgPath . 'slider2.jpg');
            }
        }

                $jsonOut = json_encode($currentConfig, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        if($jsonOut === false) throw new Exception('No se pudo generar la configuraci&oacute;n del template.');
        if(@file_put_contents($templateConfigPath, $jsonOut, LOCK_EX) === false) {
            throw new Exception('No se pudo escribir templates/' . htmlspecialchars($targetTemplate) . '/config.json. Revis&aacute; los permisos de ese archivo.');
        }
        $savedWidth = isset($currentConfig['hero_logo_width']) ? htmlspecialchars((string)$currentConfig['hero_logo_width']) : '-';
        $msgContent = '<div class="alert-success-custom">Cambios guardados correctamente en <b>' . htmlspecialchars(ucfirst($targetTemplate)) . '</b>. Tama&ntilde;o del logo: <b>' . $savedWidth . '</b>.</div>';
    } catch (Exception $e) {
        $msgContent = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    }
}

// --- 5. LECTURA ---
$tplConfig = [];
if(file_exists($templateConfigPath)) {
    $tplConfig = json_decode(file_get_contents($templateConfigPath), true);
}

function getImgUrl($path, $file, $webPath, $placeholder) {
    if(!empty($file) && file_exists($path . $file)) {
        return $webPath . $file . '?v=' . time();
    }
    return $placeholder;
}

$currWidth = isset($tplConfig['hero_logo_width']) ? intval($tplConfig['hero_logo_width']) : 300;
$currVideo = isset($tplConfig['hero_video_url']) ? $tplConfig['hero_video_url'] : '';

$heroFile = isset($tplConfig['hero_image']) ? $tplConfig['hero_image'] : 'hero_default.png';
$bgFile   = isset($tplConfig['background_image']) ? $tplConfig['background_image'] : 'bg_default.jpg';

$urlHero   = getImgUrl($templateImgPath, $heroFile, $webImgPath, $placeholderImg);
$urlBg     = getImgUrl($templateImgPath, $bgFile, $webImgPath, $placeholderImg);
$urlFavicon= getImgUrl($templateImgPath, 'favicon.ico', $webImgPath, $placeholderImg);
$urlLogo   = getImgUrl($templateImgPath, 'logo.png', $webImgPath, $placeholderImg);

// Slider Params (Morpheus)
$urlSlide1 = getImgUrl($templateImgPath, 'slider1.jpg', $webImgPath, $placeholderImg);
$urlSlide2 = getImgUrl($templateImgPath, 'slider2.jpg', $webImgPath, $placeholderImg);
$s1_title  = isset($tplConfig['slider_1_title']) ? $tplConfig['slider_1_title'] : 'Play Now';
$s1_btn    = isset($tplConfig['slider_1_btn']) ? $tplConfig['slider_1_btn'] : 'Register';
$s1_link   = isset($tplConfig['slider_1_link']) ? $tplConfig['slider_1_link'] : 'register';
$s2_title  = isset($tplConfig['slider_2_title']) ? $tplConfig['slider_2_title'] : 'New Season';
$s2_btn    = isset($tplConfig['slider_2_btn']) ? $tplConfig['slider_2_btn'] : 'Download';
$s2_link   = isset($tplConfig['slider_2_link']) ? $tplConfig['slider_2_link'] : 'downloads';

// Params Canibal
$bgTopFile = isset($tplConfig['bg_top_img']) ? $tplConfig['bg_top_img'] : 'bg-top.jpg';
$bgBotFile = isset($tplConfig['bg_bottom_img']) ? $tplConfig['bg_bottom_img'] : 'bg-bottom.jpg';
$urlBgTop  = getImgUrl($templateImgPath, $bgTopFile, $webImgPath, $placeholderImg);
$urlBgBot  = getImgUrl($templateImgPath, $bgBotFile, $webImgPath, $placeholderImg);

$sfFile_fb = isset($tplConfig['soc_fb_img']) ? $tplConfig['soc_fb_img'] : 'social/facebook.jpg';
$sfFile_dc = isset($tplConfig['soc_dc_img']) ? $tplConfig['soc_dc_img'] : 'social/discord.jpg';
$sfFile_wp = isset($tplConfig['soc_wp_img']) ? $tplConfig['soc_wp_img'] : 'social/whatsapp.jpg';
$sfFile_yt = isset($tplConfig['soc_yt_img']) ? $tplConfig['soc_yt_img'] : 'social/youtube.jpg';

$urlSocFb = getImgUrl($templateImgPath, $sfFile_fb, $webImgPath, $placeholderImg);
$urlSocDc = getImgUrl($templateImgPath, $sfFile_dc, $webImgPath, $placeholderImg);
$urlSocWp = getImgUrl($templateImgPath, $sfFile_wp, $webImgPath, $placeholderImg);
$urlSocYt = getImgUrl($templateImgPath, $sfFile_yt, $webImgPath, $placeholderImg);


// --- RENDERIZADO HTML (PHP PURO) ---

// CSS
echo '<style>
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .dynamic-group { display: none; }
    .config-card { background: #151521; padding: 25px; border-radius: 8px; border: 1px solid #333; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .section-header { color: #fff; border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 20px; font-weight: bold; text-transform: uppercase; font-size: 0.95rem; letter-spacing: 1px; }
    .img-preview-box { width: 100%; height: 120px; background: #080808; border: 1px dashed #444; border-radius: 6px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px; position: relative; }
    .img-preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .btn-delete-img { position: absolute; top: 5px; right: 5px; background: rgba(220, 53, 69, 0.9); border: none; color: #fff; width: 25px; height: 25px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; transition: 0.2s; z-index: 10; }
    .btn-delete-img:hover { background: #ff0000; transform: scale(1.1); }
    .settings-label { color: #8898aa; font-size: 13px; margin-bottom: 8px; display: block; font-weight: 500; }
    .form-control-dark:focus { border-color: #3699ff; box-shadow: 0 0 0 2px rgba(54, 153, 255, 0.2); }
    h3, h5, h6 { font-family: "Poppins", sans-serif; }
</style>';

// Container
echo '<div class="container-fluid">';
    
    // Alerta
    echo '<div class="row mb-3">' . $msgContent . '</div>';

    echo '<form action="" method="post" enctype="multipart/form-data" id="templateForm">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-12 mb-4">';
                echo '<div style="background: #1e1e2d; padding: 20px; border-radius: 8px; border-left: 5px solid #3699ff; display: flex; align-items: center; justify-content: space-between;">';
                    echo '<div>';
                        echo '<h3 style="margin:0; color: #fff; font-size: 20px;">Editor Visual de Templates</h3>';
                        echo '<small style="color: #666;">Selecciona el template que deseas configurar.</small>';
                    echo '</div>';
                    echo '<div style="display: flex; align-items: center; gap: 15px;">';
                        echo '<span style="color: #aaa; font-size: 14px;">Editando:</span>';
                        echo '<select name="target_template" id="templateSelector" class="form-control-dark" style="width: 200px; font-weight: bold; background: #000;" onchange="changeTemplate()">';
                            foreach($availableTemplates as $tplName) {
                                $sel = ($targetTemplate == $tplName) ? 'selected' : '';
                                echo '<option value="' . $tplName . '" ' . $sel . '>' . ucfirst($tplName) . '</option>';
                            }
                        echo '</select>';
                                                echo '<button type="submit" name="save_template_settings" class="btn-save-green"><i class="fa fa-save"></i> Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Grid
        echo '<div class="row">';
            
            // Columna Izquierda (Forms)
            echo '<div class="col-lg-8">';
                
                // Card 1: Identidad (Todos los Templates)
                echo '<div class="config-card">';
                    echo '<div class="section-header">Identidad General (Favicon y Barra Navegación)</div>';
                    echo '<div class="row">';
                        
                        // Favicon
                        echo '<div class="col-md-3">';
                            echo '<label class="settings-label">Favicon (.ico)</label>';
                            echo '<div class="img-preview-box" style="height: 80px;">';
                                echo '<img src="' . $urlFavicon . '" id="prev_favicon" onerror="this.src=\'' . $placeholderImg . '\'">';
                                echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'favicon\', \'prev_favicon\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                            echo '</div>';
                            echo '<input type="hidden" name="delete_favicon" id="delete_favicon" value="0">';
                            echo '<input type="file" name="favicon_upload" id="input_favicon" class="form-control-file-dark" accept=".ico,.png" onchange="previewImage(this, \'prev_favicon\', \'delete_favicon\')">';
                        echo '</div>';

                        // Logo
                        echo '<div class="col-md-9">';
                            echo '<label class="settings-label">Logo Barra de Navegación (.png)</label>';
                            echo '<div class="row">';
                                echo '<div class="col-md-4">';
                                    echo '<div class="img-preview-box" style="height: 80px;">';
                                        echo '<img src="' . $urlLogo . '" id="prev_logo" onerror="this.src=\'' . $placeholderImg . '\'">';
                                        echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'logo\', \'prev_logo\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<input type="hidden" name="delete_logo" id="delete_logo" value="0">';
                                echo '</div>';
                                echo '<div class="col-md-8">';
                                    echo '<input type="file" name="logo_upload" id="input_logo" class="form-control-file-dark mb-3" accept=".png,.jpg" onchange="previewImage(this, \'prev_logo\', \'delete_logo\')">';
                                    echo '<small class="text-muted dynamic-group group-general group-morpheus group-default">Este es el logo pequeño que va en la barra superior.</small>';
                                    echo '<small class="text-muted dynamic-group group-canibal" style="color:#c8a165!important;">En Canibal, este es el logo grande flotante del cabezal.</small>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';

                // ==========================================
                // BLOQUE GENERAL (No Canibal)
                // ==========================================
                echo '<div class="dynamic-group group-general group-morpheus group-default">';
                    echo '<div class="config-card">';
                        echo '<div class="section-header">Configuración Visual (Cabecera / Hero)</div>';
                        
                        echo '<div class="form-group mb-4">';
                            echo '<div style="display:flex; justify-content:space-between;">';
                                echo '<label class="settings-label" id="lbl_hero" style="font-size: 15px; color: #fff;">Imagen Principal:</label>';
                                echo '<label class="settings-label">Tamaño: <span id="widthVal" style="color:#00ff88;">' . $currWidth . 'px</span></label>';
                            echo '</div>';
                            
                            echo '<div class="row">';
                                echo '<div class="col-md-5">';
                                    echo '<div class="img-preview-box" style="height: 180px;">';
                                        echo '<img src="' . $urlHero . '" id="prev_hero" onerror="this.src=\'' . $placeholderImg . '\'" style="width: ' . $currWidth . 'px; transition: width 0.2s;">';
                                        echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'hero\', \'prev_hero\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<input type="hidden" name="delete_hero" id="delete_hero" value="0">';
                                echo '</div>';
                                echo '<div class="col-md-7">';
                                    echo '<input type="file" name="hero_upload" id="input_hero" class="form-control-file-dark mb-3" accept=".png,.jpg,.gif" onchange="previewImage(this, \'prev_hero\', \'delete_hero\')">';
                                    echo '<label class="settings-label">Ajustar Tamaño de Visualización:</label>';
                                    echo '<input type="range" name="hero_logo_width" min="50" max="1000" value="' . $currWidth . '" class="form-control" oninput="updateLogoSize(this.value)">';
                                    echo '<p class="text-muted mt-2" style="font-size: 12px;" id="desc_hero">...</p>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';

                        // Background Estático
                        echo '<div class="form-group mb-4 dynamic-group group-classic group-morpheus group-general">';
                            echo '<label class="settings-label" style="font-size: 15px; color: #fff;">Imagen de Fondo (Background):</label>';
                            echo '<div class="row">';
                                echo '<div class="col-md-5">';
                                    echo '<div class="img-preview-box" style="height: 120px;">';
                                        echo '<img src="' . $urlBg . '" id="prev_bg" onerror="this.src=\'' . $placeholderImg . '\'" style="object-fit: cover;">';
                                        echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'bg\', \'prev_bg\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<input type="hidden" name="delete_bg" id="delete_bg" value="0">';
                                echo '</div>';
                                echo '<div class="col-md-7">';
                                    echo '<input type="file" name="background_upload" id="input_bg" class="form-control-file-dark" accept=".jpg,.png,.jpeg" onchange="previewImage(this, \'prev_bg\', \'delete_bg\')">';
                                    echo '<p class="text-muted mt-2" style="font-size: 12px;">Imagen grande que ocupa todo el fondo del sitio.</p>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';

                        // Video (Default)
                        echo '<div class="form-group mb-4 dynamic-group group-default">';
                            echo '<label class="settings-label" style="color: #3699ff;">Video de Fondo (URL):</label>';
                            echo '<input type="text" name="hero_video_url" class="form-control-dark" value="' . $currVideo . '" placeholder="Ej: https://player.vimeo.com/video/...">';
                            echo '<p class="text-muted mt-1" style="font-size: 12px;">URL del iframe de Vimeo o ruta del MP4.</p>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';

                // ==========================================
                // BLOQUE CANIBAL
                // ==========================================
                echo '<div class="dynamic-group group-canibal">';
                    echo '<div class="config-card" style="border-left: 4px solid #c8a165;">';
                        echo '<div class="section-header" style="color:#c8a165;">Diseño de Fondos (Canibal)</div>';
                        
                        // Tamaño Logo Canibal (Usa el mismo slider pero aislado visualmente)
                        echo '<div class="form-group mb-4">';
                            echo '<div style="display:flex; justify-content:space-between;">';
                                echo '<label class="settings-label" style="font-size: 15px; color: #fff;">Ajuste Tamaño Logo Central:</label>';
                                echo '<label class="settings-label">Tamaño: <span id="widthValCanibal" style="color:#c8a165;">' . $currWidth . 'px</span></label>';
                            echo '</div>';
                            echo '<input type="range" id="hero_logo_width_canibal" name="hero_logo_width_canibal" min="50" max="1000" value="' . $currWidth . '" class="form-control" oninput="updateLogoSizeCanibal(this.value)">';
                        echo '</div>';

                        echo '<hr style="border-color:#333; margin: 25px 0;">';

                        // Background TOP
                        echo '<div class="form-group mb-4">';
                            echo '<label class="settings-label" style="font-size: 15px; color: #fff;">Background Superior (Top):</label>';
                            echo '<div class="row">';
                                echo '<div class="col-md-5">';
                                    echo '<div class="img-preview-box" style="height: 120px;">';
                                        echo '<img src="' . $urlBgTop . '" id="prev_bg_top" onerror="this.src=\'' . $placeholderImg . '\'" style="object-fit: cover;">';
                                        echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'bg_top\', \'prev_bg_top\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<input type="hidden" name="delete_bg_top" id="delete_bg_top" value="0">';
                                echo '</div>';
                                echo '<div class="col-md-7">';
                                    echo '<input type="file" name="bg_top_upload" id="input_bg_top" class="form-control-file-dark" accept=".jpg,.png,.jpeg" onchange="previewImage(this, \'prev_bg_top\', \'delete_bg_top\')">';
                                    echo '<p class="text-muted mt-2" style="font-size: 12px;">Parte alta del sitio.</p>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';

                        // Background BOTTOM
                        echo '<div class="form-group mb-0">';
                            echo '<label class="settings-label" style="font-size: 15px; color: #fff;">Background Inferior (Bottom):</label>';
                            echo '<div class="row">';
                                echo '<div class="col-md-5">';
                                    echo '<div class="img-preview-box" style="height: 120px;">';
                                        echo '<img src="' . $urlBgBot . '" id="prev_bg_bottom" onerror="this.src=\'' . $placeholderImg . '\'" style="object-fit: cover;">';
                                        echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'bg_bottom\', \'prev_bg_bottom\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<input type="hidden" name="delete_bg_bottom" id="delete_bg_bottom" value="0">';
                                echo '</div>';
                                echo '<div class="col-md-7">';
                                    echo '<input type="file" name="bg_bottom_upload" id="input_bg_bottom" class="form-control-file-dark" accept=".jpg,.png,.jpeg" onchange="previewImage(this, \'prev_bg_bottom\', \'delete_bg_bottom\')">';
                                    echo '<p class="text-muted mt-2" style="font-size: 12px;">Parte baja del sitio (Footer).</p>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    // Imágenes Redes Sociales
                    echo '<div class="config-card" style="border-left: 4px solid #c8a165;">';
                        echo '<div class="section-header" style="color:#c8a165;">Imágenes de Redes Sociales (Index)</div>';
                        echo '<p class="text-muted mb-4" style="font-size:12px;">Sube las imágenes que se muestran en los cuadros de redes de la página principal.</p>';
                        
                        echo '<div class="row">';
                            $socArr = [
                                ['id' => 'fb', 'name' => 'Facebook', 'url' => $urlSocFb],
                                ['id' => 'dc', 'name' => 'Discord', 'url' => $urlSocDc],
                                ['id' => 'wp', 'name' => 'WhatsApp', 'url' => $urlSocWp],
                                ['id' => 'yt', 'name' => 'YouTube', 'url' => $urlSocYt]
                            ];

                            foreach($socArr as $s) {
                                echo '<div class="col-md-4 mb-4">';
                                    echo '<label class="settings-label text-center d-block">'.$s['name'].'</label>';
                                    echo '<div class="img-preview-box" style="height: 80px;">';
                                        echo '<img src="' . $s['url'] . '" id="prev_soc_'.$s['id'].'" onerror="this.src=\'' . $placeholderImg . '\'" style="object-fit: cover;">';
                                        echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'soc_'.$s['id'].'\', \'prev_soc_'.$s['id'].'\')" title="Borrar"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<input type="hidden" name="delete_soc_'.$s['id'].'" id="delete_soc_'.$s['id'].'" value="0">';
                                    echo '<input type="file" name="soc_'.$s['id'].'_upload" id="input_soc_'.$s['id'].'" class="form-control-file-dark" accept=".jpg,.png" onchange="previewImage(this, \'prev_soc_'.$s['id'].'\', \'delete_soc_'.$s['id'].'\')" style="font-size:11px; padding:5px;">';
                                echo '</div>';
                            }
                        echo '</div>';
                    echo '</div>';
                echo '</div>';

                // ==========================================
                // BLOQUE MORPHEUS
                // ==========================================
                echo '<div class="config-card dynamic-group group-morpheus" style="border-left: 4px solid #f64e60;">';
                    echo '<div class="section-header" style="color:#f64e60;">Gestor de Slider (Morpheus)</div>';
                    echo '<div class="row">';
                        
                        // Slide 1
                        echo '<div class="col-md-6 mb-3">';
                            echo '<div style="background: #000; padding: 10px; border-radius: 6px; border: 1px solid #333;">';
                                echo '<h6 style="color:#fff; margin-bottom: 10px;">Slide 1</h6>';
                                echo '<div class="img-preview-box" style="height: 100px;">';
                                    echo '<img src="' . $urlSlide1 . '" id="prev_s1" onerror="this.src=\'' . $placeholderImg . '\'" style="object-fit: cover;">';
                                    echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'s1\', \'prev_s1\')"><i class="fa fa-trash"></i></button>';
                                echo '</div>';
                                echo '<input type="hidden" name="delete_s1" id="delete_s1" value="0">';
                                echo '<input type="file" name="s1_img" id="input_s1" class="form-control-file-dark mb-2" onchange="previewImage(this, \'prev_s1\', \'delete_s1\')">';
                                echo '<input type="text" name="slider_1_title" class="form-control-dark mb-2" value="' . $s1_title . '" placeholder="Título">';
                                echo '<input type="text" name="slider_1_btn" class="form-control-dark mb-2" value="' . $s1_btn . '" placeholder="Texto Botón">';
                                echo '<input type="text" name="slider_1_link" class="form-control-dark" value="' . $s1_link . '" placeholder="Enlace">';
                            echo '</div>';
                        echo '</div>';

                        // Slide 2
                        echo '<div class="col-md-6 mb-3">';
                            echo '<div style="background: #000; padding: 10px; border-radius: 6px; border: 1px solid #333;">';
                                echo '<h6 style="color:#fff; margin-bottom: 10px;">Slide 2</h6>';
                                echo '<div class="img-preview-box" style="height: 100px;">';
                                    echo '<img src="' . $urlSlide2 . '" id="prev_s2" onerror="this.src=\'' . $placeholderImg . '\'" style="object-fit: cover;">';
                                    echo '<button type="button" class="btn-delete-img" onclick="triggerDelete(\'s2\', \'prev_s2\')"><i class="fa fa-trash"></i></button>';
                                echo '</div>';
                                echo '<input type="hidden" name="delete_s2" id="delete_s2" value="0">';
                                echo '<input type="file" name="s2_img" id="input_s2" class="form-control-file-dark mb-2" onchange="previewImage(this, \'prev_s2\', \'delete_s2\')">';
                                echo '<input type="text" name="slider_2_title" class="form-control-dark mb-2" value="' . $s2_title . '" placeholder="Título">';
                                echo '<input type="text" name="slider_2_btn" class="form-control-dark mb-2" value="' . $s2_btn . '" placeholder="Texto Botón">';
                                echo '<input type="text" name="slider_2_link" class="form-control-dark" value="' . $s2_link . '" placeholder="Enlace">';
                            echo '</div>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';

                // Submit
                echo '<button type="submit" name="save_template_settings" class="btn-save-green btn-block" style="width: 100%; padding: 15px; font-size: 1.1rem; font-weight: bold; margin-bottom: 50px;">';
                    echo '<i class="fa fa-save"></i> GUARDAR CAMBIOS';
                echo '</button>';

            echo '</div>'; // End Col Left

            // Columna Derecha (Info)
            echo '<div class="col-lg-4">';
                echo '<div class="config-card">';
                    echo '<h5 style="color:#fff;">Información</h5>';
                    echo '<p style="color:#888; font-size: 0.9rem;">Editando: <b style="color:#fff;">' . ucfirst($targetTemplate) . '</b></p>';
                    echo '<hr style="border-color:#333;">';
                    
                    echo '<div id="help_default" class="dynamic-group group-default">';
                        echo '<small style="color:#3699ff; font-weight:bold;">TEMPLATE DEFAULT</small>';
                        echo '<p style="color:#666; font-size: 12px; margin-top:5px;">Este diseño utiliza video de fondo. La "Imagen Principal" actúa como un logo flotante.</p>';
                    echo '</div>';

                    echo '<div id="help_morpheus" class="dynamic-group group-morpheus">';
                        echo '<small style="color:#f64e60; font-weight:bold;">TEMPLATE MORPHEUS</small>';
                        echo '<p style="color:#666; font-size: 12px; margin-top:5px;">Diseño moderno con Slider. La "Imagen Principal" es el logo central grande.</p>';
                    echo '</div>';

                    echo '<div id="help_general" class="dynamic-group group-general">';
                        echo '<small style="color:#ffa800; font-weight:bold;">TEMPLATE ESTÁNDAR</small>';
                        echo '<p style="color:#666; font-size: 12px; margin-top:5px;">Configuración clásica. Fondo estático y personaje lateral.</p>';
                    echo '</div>';

                    echo '<div id="help_canibal" class="dynamic-group group-canibal">';
                        echo '<small style="color:#c8a165; font-weight:bold;">TEMPLATE CANIBAL</small>';
                        echo '<p style="color:#666; font-size: 12px; margin-top:5px;">Diseño oscuro en 2 capas (Top/Bottom). Sube las imágenes de redes sociales para el Index.</p>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row
    echo '</form>';
echo '</div>';

// JavaScript
echo '<script>
var placeholderImg = "' . $placeholderImg . '";

function triggerDelete(type, imgId) {
    if(!confirm("¿Quitar esta imagen? Se aplicará al Guardar.")) return;
    document.getElementById("delete_" + type).value = "1";
    var fileInput = document.getElementById("input_" + type);
    if(fileInput) fileInput.value = "";
    document.getElementById(imgId).src = placeholderImg;
}

function previewImage(input, imgId, deleteInputId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(imgId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
        if(deleteInputId) document.getElementById(deleteInputId).value = "0";
    }
}

function changeTemplate() { document.getElementById("templateForm").submit(); }

function updateUiBasedOnTemplate() {
    var tpl = "' . $targetTemplate . '";
    document.querySelectorAll(".dynamic-group").forEach(el => el.style.display = "none");

    if(tpl == "canibal") {
        document.querySelectorAll(".group-canibal").forEach(el => el.style.display = "block");
        var prevLogo = document.getElementById("prev_logo");
		if(prevLogo) prevLogo.style.width = document.querySelector("input[name=\'hero_logo_width\']").value + "px";	
    } else if(tpl == "default") {
        document.querySelectorAll(".group-default").forEach(el => el.style.display = "block");
		document.querySelectorAll(".group-general").forEach(el => el.style.display = "block");																					  
    } else if(tpl == "morpheus") {
        document.querySelectorAll(".group-morpheus").forEach(el => el.style.display = "block");
        document.querySelectorAll(".group-general").forEach(el => el.style.display = "block");
    } else {
        document.querySelectorAll(".group-classic").forEach(el => el.style.display = "block");
        document.querySelectorAll(".group-general").forEach(el => el.style.display = "block");
    }

    var lblHero = document.getElementById("lbl_hero");
    var descHero = document.getElementById("desc_hero");

    if(tpl == "default") {
        lblHero.innerText = "Logo Central Flotante:";
        descHero.innerText = "Aparece sobre el video. PNG Transparente recomendado.";
    } else if(tpl == "morpheus") {
        lblHero.innerText = "Logo Central Grande:";
        descHero.innerText = "Aparece arriba del slider. Usa el slider de tamaño para ajustarlo.";
    } else if(tpl != "canibal") {
        lblHero.innerText = "Personaje Hero (Lateral):";
        descHero.innerText = "Aparece a la derecha del encabezado.";
    }
}

function updateLogoSize(val) {
    document.getElementById("widthVal").innerText = val + "px";
    var prev = document.getElementById("prev_hero");
    if(prev) prev.style.width = val + "px";
}

function updateLogoSizeCanibal(val) {
    document.getElementById("widthValCanibal").innerText = val + "px";
    var prev = document.getElementById("prev_logo");
    if(prev) prev.style.width = val + "px";
    
    // Sincronizamos el input principal real para que se guarde correctamente en config.json
    var ogInput = document.querySelector("input[name=\'hero_logo_width\']");
    if(ogInput) ogInput.value = val;
}

document.addEventListener("DOMContentLoaded", function() {
    updateUiBasedOnTemplate();
});
</script>';
?>