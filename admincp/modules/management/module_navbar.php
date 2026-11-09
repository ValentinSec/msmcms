<?php
/**
 * Navbar Configuration Module (Dynamic Sync)
 * PREPARED FOR OBFUSCATION (Pure PHP)
 */

// 1. Rutas
$jsonPath = __PATH_CONFIGS__ . 'navbar_config.json';

// Configuración por defecto
$defaultConfig = [
    'show_info' => 1,
    'show_downloads' => 1,
    'show_rankings' => 1,
    'show_guides' => 0,
    'show_news' => 0,
    'custom_links' => []
];

// Cargar configuración actual
$currentConfig = $defaultConfig;
if (file_exists($jsonPath)) {
    $loaded = json_decode(file_get_contents($jsonPath), true);
    if(is_array($loaded)) {
        $currentConfig = array_merge($defaultConfig, $loaded);
    }
}

// 2. Procesar Guardado
if(isset($_POST['submit_navbar'])) {
    try {
        $newConfig = [
            'show_info' => isset($_POST['show_info']) ? 1 : 0,
            'show_downloads' => isset($_POST['show_downloads']) ? 1 : 0,
            'show_rankings' => isset($_POST['show_rankings']) ? 1 : 0,
            'show_guides' => isset($_POST['show_guides']) ? 1 : 0,
            'show_news' => isset($_POST['show_news']) ? 1 : 0,
            'custom_links' => []
        ];

        // Procesar Enlaces Personalizados
        if(isset($_POST['custom_title']) && is_array($_POST['custom_title'])) {
            foreach($_POST['custom_title'] as $index => $title) {
                $title = trim($title);
                $route = isset($_POST['custom_route'][$index]) ? trim($_POST['custom_route'][$index]) : '';
                
                // Leer flag hidden
                $isActive = (isset($_POST['link_active_flag'][$index]) && $_POST['link_active_flag'][$index] == "1") ? 1 : 0;

                if(!empty($title) && !empty($route)) {
                    $newConfig['custom_links'][] = [
                        'title' => $title,
                        'route' => $route,
                        'active' => $isActive
                    ];
                }
            }
        }

        // Guardar JSON
        if(!file_put_contents($jsonPath, json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            throw new Exception("Error al escribir navbar_config.json. Revisa permisos.");
        }

        header("Location: index.php?module=module_navbar&success=1");
        exit;

    } catch (Exception $ex) {
        echo '<div class="alert alert-danger"><b>Error:</b> '.$ex->getMessage().'</div>';
    }
}

// --- RENDERIZADO HTML (PHP PURO) ---

// Estilos CSS
echo '<style>
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .settings-section { background-color: #0f1115; border: 1px solid #1e293b; border-radius: 8px; padding: 25px; margin-bottom: 30px; height: 100%; }
    .settings-title { color: #fff; font-size: 16px; font-weight: 600; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 10px; }
    .btn-save-green { background-color: #10b981; color: #fff; font-weight: 600; padding: 10px 25px; border: none; border-radius: 6px; transition: background 0.2s; }
    .btn-save-green:hover { background-color: #059669; color: #fff; }
    .switch-container { display: flex; align-items: center; margin-bottom: 15px; justify-content: space-between; }
    .switch { position: relative; display: inline-block; width: 40px; height: 22px; margin-bottom: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #10b981; }
    input:checked + .slider:before { transform: translateX(18px); }
    .switch-label { color: #fff; font-size: 13px; font-weight: 500; }
    .form-control-dark { background-color: #1e293b; border: 1px solid #334155; color: #fff; border-radius: 4px; height: 38px; padding: 6px 12px; width: 100%; font-size: 13px; }
    .form-control-dark:focus { border-color: #10b981; outline: none; }
    .btn-add-link { background-color: #3b82f6; color: #fff; border: none; padding: 8px 15px; border-radius: 4px; font-size: 12px; cursor: pointer; }
    .btn-remove-link { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); width: 30px; height: 30px; border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
    .btn-remove-link:hover { background-color: #ef4444; color: #fff; }
    .switch-separator { border-top: 1px dashed #334155; margin: 15px 0; position: relative; }
    .switch-separator span { position: absolute; top: -10px; left: 0; background: #0f1115; padding-right: 10px; font-size: 11px; color: #64748b; text-transform: uppercase; }
</style>';

echo '<div class="container-fluid">';
    
    // Mensaje Éxito
    if(isset($_GET['success'])) {
        echo '<div class="alert-success-custom">Configuración guardada correctamente.</div>';
    }

    echo '<form action="" method="post" id="navbarForm">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6"><h3 style="margin:0; color:#fff; font-weight:600;">Gestor de Navegación</h3></div>';
                    echo '<div class="col-xs-6 text-right"><button type="submit" name="submit_navbar" class="btn-save-green">Guardar Cambios</button></div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Content
        echo '<div class="row display-flex">';
            
            // COLUMNA IZQUIERDA (SWITCHES)
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Módulos Estándar</h3>';
                    echo '<p style="color:#94a3b8; font-size:12px; margin-bottom:20px;">Activa o desactiva las secciones predeterminadas.</p>';
                    
                    // Standard Switches
                    $stdModules = [
                        'show_news' => 'Noticias',
                        'show_info' => 'Información',
                        'show_downloads' => 'Descargas',
                        'show_rankings' => 'Rankings',
                        'show_guides' => 'Guías'
                    ];

                    foreach($stdModules as $key => $label) {
                        $checked = ($currentConfig[$key]) ? 'checked' : '';
                        echo '<div class="form-group switch-container">';
                            echo '<span class="switch-label">' . $label . '</span>';
                            echo '<label class="switch"><input type="checkbox" name="' . $key . '" ' . $checked . '><span class="slider"></span></label>';
                        echo '</div>';
                    }

                    echo '<div class="switch-separator"><span>Personalizados</span></div>';
                    echo '<div id="leftPanelSwitches">';
                        
                        if(!empty($currentConfig['custom_links'])) {
                            foreach($currentConfig['custom_links'] as $idx => $link) {
                                $uid = 'link_' . $idx;
                                $isActive = (!isset($link['active']) || $link['active'] == 1);
                                $chk = $isActive ? 'checked' : '';
                                $val = $isActive ? '1' : '0';
                                
                                echo '<div class="form-group switch-container" id="switch_row_' . $uid . '">';
                                    echo '<span class="switch-label" style="color:#3b82f6;" id="switch_label_' . $uid . '">' . htmlspecialchars($link['title']) . '</span>';
                                    echo '<label class="switch">';
                                        echo '<input type="hidden" name="link_active_flag[' . $idx . ']" id="hidden_active_' . $uid . '" value="' . $val . '">';
                                        echo '<input type="checkbox" onchange="document.getElementById(\'hidden_active_' . $uid . '\').value = this.checked ? 1 : 0;" ' . $chk . '>';
                                        echo '<span class="slider"></span>';
                                    echo '</label>';
                                echo '</div>';
                            }
                        }

                    echo '</div>'; // End leftPanelSwitches
                echo '</div>';
            echo '</div>';

            // COLUMNA DERECHA (INPUTS)
            echo '<div class="col-md-8">';
                echo '<div class="settings-section">';
                    echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #1e293b; padding-bottom:10px;">';
                        echo '<h3 style="color:#fff; font-size:16px; font-weight:600; margin:0;">Enlaces Personalizados</h3>';
                        echo '<button type="button" class="btn-add-link" onclick="addCustomLink()"><i class="fa fa-plus"></i> Agregar Nuevo</button>';
                    echo '</div>';
                    
                    echo '<div id="customLinksContainer">';
                        if(!empty($currentConfig['custom_links'])) {
                            foreach($currentConfig['custom_links'] as $idx => $link) {
                                $uid = 'link_' . $idx;
                                echo '<div class="row custom-link-row" id="input_row_' . $uid . '" style="margin-bottom:15px; border-bottom:1px dashed #2d3035; padding-bottom:15px;">';
                                    echo '<div class="col-xs-4">';
                                        echo '<label style="font-size:11px; color:#64748b; margin-bottom:2px;">Nombre del Módulo</label>';
                                        echo '<input type="text" name="custom_title[' . $idx . ']" class="form-control-dark" placeholder="Ej: TIENDA" value="' . htmlspecialchars($link['title']) . '" onkeyup="syncTitle(\'' . $uid . '\', this)" required>';
                                    echo '</div>';
                                    echo '<div class="col-xs-7">';
                                        echo '<label style="font-size:11px; color:#64748b; margin-bottom:2px;">Redireccionamiento (Módulo o URL)</label>';
                                        echo '<input type="text" name="custom_route[' . $idx . ']" class="form-control-dark" placeholder="Ej: shop/items ó https://google.com" value="' . htmlspecialchars($link['route']) . '" required>';
                                    echo '</div>';
                                    echo '<div class="col-xs-1 text-right" style="padding-top:20px;">';
                                        echo '<button type="button" class="btn-remove-link" onclick="removeLink(\'' . $uid . '\')"><i class="fa fa-trash"></i></button>';
                                    echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p id="noLinksMsg" style="color:#64748b; text-align:center; padding:20px;">No hay enlaces personalizados configurados.</p>';
                        }
                    echo '</div>'; // End customLinksContainer
                echo '</div>';
            echo '</div>';

        echo '</div>'; // End row display-flex
    echo '</form>';
echo '</div>';

// JavaScript
echo '<script>
function generateUUID() {
    return "new_" + Math.random().toString(36).substr(2, 9);
}

function addCustomLink() {
    var noMsg = document.getElementById("noLinksMsg");
    if(noMsg) noMsg.style.display = "none";

    var uid = generateUUID();
    var index = Date.now(); 

    // 1. Agregar Inputs (Derecha)
    var container = document.getElementById("customLinksContainer");
    var div = document.createElement("div");
    div.className = "row custom-link-row";
    div.id = "input_row_" + uid;
    div.style.cssText = "margin-bottom:15px; border-bottom:1px dashed #2d3035; padding-bottom:15px;";
    
    var htmlInput = \'<div class="col-xs-4">\';
    htmlInput += \'<label style="font-size:11px; color:#64748b; margin-bottom:2px;">Nombre del Módulo</label>\';
    htmlInput += \'<input type="text" name="custom_title[\' + index + \']" class="form-control-dark" placeholder="Ej: FORO" onkeyup="syncTitle(\\\'\' + uid + \'\\\', this)" required>\';
    htmlInput += \'</div>\';
    htmlInput += \'<div class="col-xs-7">\';
    htmlInput += \'<label style="font-size:11px; color:#64748b; margin-bottom:2px;">Redireccionamiento (Módulo o URL)</label>\';
    htmlInput += \'<input type="text" name="custom_route[\' + index + \']" class="form-control-dark" placeholder="Ej: usercp/vip ó https://miforo.com" required>\';
    htmlInput += \'</div>\';
    htmlInput += \'<div class="col-xs-1 text-right" style="padding-top:20px;">\';
    htmlInput += \'<button type="button" class="btn-remove-link" onclick="removeLink(\\\'\' + uid + \'\\\')"><i class="fa fa-trash"></i></button>\';
    htmlInput += \'</div>\';
    
    div.innerHTML = htmlInput;
    container.appendChild(div);

    // 2. Agregar Switch (Izquierda)
    var leftPanel = document.getElementById("leftPanelSwitches");
    var switchDiv = document.createElement("div");
    switchDiv.className = "form-group switch-container";
    switchDiv.id = "switch_row_" + uid;
    
    var htmlSwitch = \'<span class="switch-label" style="color:#3b82f6;" id="switch_label_\' + uid + \'"> (Nuevo) </span>\';
    htmlSwitch += \'<label class="switch">\';
    htmlSwitch += \'<input type="hidden" name="link_active_flag[\' + index + \']" id="hidden_active_\' + uid + \'" value="1">\';
    htmlSwitch += \'<input type="checkbox" onchange="document.getElementById(\\\'hidden_active_\' + uid + \'\\\').value = this.checked ? 1 : 0;" checked>\';
    htmlSwitch += \'<span class="slider"></span>\';
    htmlSwitch += \'</label>\';

    switchDiv.innerHTML = htmlSwitch;
    leftPanel.appendChild(switchDiv);
}

function removeLink(uid) {
    var inputRow = document.getElementById("input_row_" + uid);
    if(inputRow) inputRow.remove();
    
    var switchRow = document.getElementById("switch_row_" + uid);
    if(switchRow) switchRow.remove();

    var container = document.getElementById("customLinksContainer");
    if(container.children.length === 0 || (container.children.length === 1 && container.children[0].id === "noLinksMsg")) {
        if(document.getElementById("noLinksMsg")) document.getElementById("noLinksMsg").style.display = "block";
    }
}

function syncTitle(uid, input) {
    var label = document.getElementById("switch_label_" + uid);
    if(label) label.innerText = input.value || "(Sin Nombre)";
}
</script>';
?>