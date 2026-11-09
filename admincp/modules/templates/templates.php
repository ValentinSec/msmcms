<?php
/**
 * Templates Manager Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

// --- 1. CONFIGURACIÓN DE RUTAS (CORREGIDO) ---
$jsonPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/muservermaker.json';
$templatesPath = $_SERVER['DOCUMENT_ROOT'] . '/templates/';

// --- 2. LÓGICA DE ACTIVACIÓN ---
$errorMessage = '';
if(isset($_GET['action']) && $_GET['action'] == 'activate' && isset($_GET['id'])) {
    $newTemplate = $_GET['id'];
    
    try {
        if(!file_exists($jsonPath)) throw new Exception("No se encontró el archivo muservermaker.json");
        if(!is_writable($jsonPath)) throw new Exception("El archivo muservermaker.json no tiene permisos de escritura (CHMOD 777).");

        // Leer el archivo actual
        $jsonContent = file_get_contents($jsonPath);
        $config = json_decode($jsonContent, true);

        if(!is_array($config)) throw new Exception("Error al decodificar el archivo de configuración.");

        // Cambiar la línea website_template
        $config['website_template'] = $newTemplate;

        // Guardar los cambios
        $updatedJson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if(file_put_contents($jsonPath, $updatedJson)) {
            // Éxito: Redirigir
            header("Location: index.php?module=templates&status=success");
            exit;
        } else {
            throw new Exception("No se pudo escribir en el archivo JSON.");
        }

    } catch (Exception $ex) {
        $errorMessage = $ex->getMessage();
    }
}

// --- 3. LÓGICA DE LECTURA DE CARPETAS ---
$currentTemplate = 'default';
if(file_exists($jsonPath)) {
    $tmpCfg = json_decode(file_get_contents($jsonPath), true);
    if(isset($tmpCfg['website_template'])) $currentTemplate = $tmpCfg['website_template'];
}

$templates = [];

if (is_dir($templatesPath)) {
    // Escaneamos todas las carpetas en /templates/
    $dirs = glob($templatesPath . '*', GLOB_ONLYDIR);
    
    foreach ($dirs as $dir) {
        $folderName = basename($dir); // Nombre de la carpeta (ej: "default")
        
        // --- EXCLUSIÓN DE CARPETA MISC ---
        if ($folderName == 'misc') continue;
        
        $previewFile = "";
        
        // --- LÓGICA DE IMÁGENES (PRIORIDAD: /img/templates/) ---
        
        // 1. Buscar en la carpeta global /img/templates/
        $globalImgPathPng = $_SERVER['DOCUMENT_ROOT'] . '/img/templates/' . $folderName . '.png';
        $globalImgPathJpg = $_SERVER['DOCUMENT_ROOT'] . '/img/templates/' . $folderName . '.jpg';

        if (file_exists($globalImgPathPng)) {
            $previewFile = __BASE_URL__ . 'img/templates/' . $folderName . '.png';
        } elseif (file_exists($globalImgPathJpg)) {
            $previewFile = __BASE_URL__ . 'img/templates/' . $folderName . '.jpg';
        } 
        else {
            // 2. Fallback: Buscar dentro de la carpeta del template si no está en la global
            $localPaths = [
                $dir . '/preview.png' => __BASE_URL__ . 'templates/' . $folderName . '/preview.png',
                $dir . '/preview.jpg' => __BASE_URL__ . 'templates/' . $folderName . '/preview.jpg',
                $dir . '/img/preview.png' => __BASE_URL__ . 'templates/' . $folderName . '/img/preview.png',
                $dir . '/img/preview.jpg' => __BASE_URL__ . 'templates/' . $folderName . '/img/preview.jpg'
            ];

            foreach($localPaths as $path => $url) {
                if(file_exists($path)) {
                    $previewFile = $url;
                    break;
                }
            }
        }

        // Agregamos al array de visualización
        $templates[] = [
            'id'     => $folderName,
            'name'   => strtoupper($folderName),
            'image'  => $previewFile,
            'active' => ($folderName == $currentTemplate) ? true : false
        ];
    }
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="container-fluid">';
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            echo '<div class="templates-header">';
                echo '<h2 class="templates-title" style="color:#fff; font-weight:600;">Gestión de Templates</h2>';
                echo '<p class="templates-subtitle">Tienes <strong>' . count($templates) . '</strong> templates disponibles para elegir.</p>';
                
                // Mensaje de Éxito
                if(isset($_GET['status']) && $_GET['status'] == 'success') {
                    echo '<div class="alert alert-success" style="background: #1a4d2e; color: #00ff88; border: 1px solid #00ff88;">';
                        echo '<i class="fa fa-check"></i> ¡Template actualizado correctamente!';
                    echo '</div>';
                }

                // Mensaje de Error
                if(!empty($errorMessage)) {
                    echo '<div class="alert alert-danger" style="background: #4d1a1a; color: #ff4d4d; border: 1px solid #ff4d4d;">';
                        echo '<i class="fa fa-times"></i> ' . $errorMessage;
                    echo '</div>';
                }
            echo '</div>';
        echo '</div>';
    echo '</div>';

    // Grid de Templates
    echo '<div class="row" style="margin-top: 20px;">';
        foreach($templates as $tpl) {
            $activeClass = $tpl['active'] ? 'active-border' : '';
            
            echo '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">';
                echo '<div class="template-card ' . $activeClass . '">';
                    
                    // Thumbnail Container
                    echo '<div class="template-thumb-container" style="background: #000; height: 180px; overflow: hidden; position: relative;">';
                        if(!empty($tpl['image'])) {
                            echo '<img src="' . $tpl['image'] . '?v=' . time() . '" class="template-thumb" style="width: 100%; height: 100%; object-fit: cover; object-position: top;">';
                        } else {
                            echo '<div class="no-preview" style="color: #444; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">';
                                echo '<i class="fa fa-image fa-3x"></i>';
                                echo '<span style="font-size: 10px; margin-top: 5px;">SIN PREVIEW</span>';
                            echo '</div>';
                        }
                        
                        // Badge Activo
                        if($tpl['active']) {
                            echo '<div style="position: absolute; top: 10px; right: 10px; background: #00ff88; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.5);">ACTIVO</div>';
                        }
                    echo '</div>';
                    
                    // Body Card
                    echo '<div style="padding: 15px;">';
                        echo '<h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;">' . $tpl['name'] . '</h4>';
                        
                        if($tpl['active']) {
                            echo '<button type="button" class="btn-active" disabled style="width: 100%; background: transparent; border: 1px solid #444; color: #888; padding: 8px; border-radius: 4px; cursor: not-allowed;">';
                                echo '<i class="fa fa-check"></i> Seleccionado';
                            echo '</button>';
                        } else {
                            echo '<a href="index.php?module=templates&action=activate&id=' . $tpl['id'] . '" class="btn-activate-link" style="width: 100%; display: block; text-align: center; background: #007bff; color: #fff; padding: 8px; border-radius: 4px; text-decoration: none; font-size: 12px; transition: 0.3s;">';
                                echo 'ACTIVAR TEMPLATE';
                            echo '</a>';
                        }
                    echo '</div>';

                echo '</div>';
            echo '</div>';
        }
    echo '</div>'; // End Row Grid

echo '</div>'; // End Container Fluid

// Styles
echo '<style>
    .template-card { background: #1a1a1c; border: 1px solid #2d2d30; border-radius: 4px; margin-bottom: 25px; }
    .active-border { border-color: #00ff88 !important; }
    .btn-activate-link:hover { background: #0056b3 !important; }
</style>';
?>