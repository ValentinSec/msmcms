<?php
/**
 * Módulo de Administración de Countdown (Ruta Dinámica Fix + 10 Diseños)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// 1. RUTAS Y CONFIGURACIÓN
$configFile = __PATH_INCLUDES__ . 'config/countdown_config.json';

// Diseños disponibles (Themes)
$themes = [
    'classic'      => 'Clásico (Dorado Mu)',
    'modern'       => 'Cyber Neon (Azul)',
    'fire'         => 'Infernal (Rojo Fuego)',
    'ice'          => 'Frozen (Hielo/Blanco)',
    'nature'       => 'Elven (Verde/Naturaleza)',
    'minimal'      => 'Minimalista (Líneas)',
    'cards-dark'   => 'Tarjetas Dark (Cajas Individuales)',
    'cards-glass'  => 'Glassmorphism (Transparente)',
    'cards-gold'   => 'Placas Doradas (Premium)',
    'cards-cyber'  => 'Cyberpunk (Amarillo/Negro)'
];

// 2. DEFAULT CONFIG
$countData = [
    'active'      => 0,
    'event_name'  => 'Gran Apertura',
    'target_date' => date('Y-m-d\TH:i'),
    'theme'       => 'classic',
    'finish_action' => 'message',
    'finish_text'   => '¡El servidor está online!',
    'finish_url'    => ''
];

if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if (is_array($loaded)) $countData = array_merge($countData, $loaded);
}

// 3. GUARDAR CAMBIOS (FIX DEFINITIVO DE RUTA)
if (isset($_POST['save_countdown'])) {
    $saveData = $countData;
    
    $saveData['active']        = isset($_POST['count_active']) ? 1 : 0;
    $saveData['event_name']    = isset($_POST['count_name']) ? $_POST['count_name'] : 'Evento';
    $saveData['target_date']   = isset($_POST['count_date']) ? $_POST['count_date'] : '';
    $saveData['theme']         = isset($_POST['count_theme']) ? $_POST['count_theme'] : 'classic';
    $saveData['finish_action'] = isset($_POST['count_action']) ? $_POST['count_action'] : 'message';
    $saveData['finish_text']   = isset($_POST['count_finish_text']) ? $_POST['count_finish_text'] : '';
    $saveData['finish_url']    = isset($_POST['count_finish_url']) ? $_POST['count_finish_url'] : '';

    file_put_contents($configFile, json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Detectamos el módulo actual desde la URL
    $currentModule = isset($_GET['module']) ? $_GET['module'] : 'tools_countdown';
    header("Location: index.php?module=" . $currentModule . "&success=1");
    exit;
}

function getVal($k, $d) { return isset($d[$k]) ? htmlspecialchars($d[$k]) : ""; }
function getChk($k, $d) { return (isset($d[$k]) && $d[$k] == 1) ? "checked" : ""; }

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&family=Montserrat:wght@300;700&display=swap" rel="stylesheet">';

echo '<div class="container-fluid">';
    echo '<form action="" method="post">';
        
        // Alerta Éxito
        if(isset($_GET['success'])) {
            echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
        }

        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Countdown</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" name="save_countdown" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Grid Content
        echo '<div class="row row-flex">';
            
            // Columna Izquierda: Parámetros
            echo '<div class="col-md-5">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Parámetros</h3>';
                    
                    // Switch Active
                    echo '<div class="form-group switch-container">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="count_active" ' . getChk('active', $countData) . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        echo '<span class="switch-label">Activar Contador</span>';
                    echo '</div>';

                    // Inputs
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Nombre del Evento</label>';
                        echo '<input type="text" id="in_name" name="count_name" class="form-control-dark" value="' . getVal('event_name', $countData) . '" placeholder="Ej: Gran Apertura" oninput="updatePreview()">';
                    echo '</div>';

                    echo '<div class="form-group" style="margin-top: 15px;">';
                        echo '<label class="settings-label">Fecha y Hora Objetivo</label>';
                        echo '<input type="datetime-local" name="count_date" class="form-control-dark" value="' . getVal('target_date', $countData) . '">';
                        echo '<small style="color:#666;">Hora del Servidor</small>';
                    echo '</div>';

                    echo '<div class="form-group" style="margin-top: 15px;">';
                        echo '<label class="settings-label">Diseño Visual (10 Estilos)</label>';
                        echo '<select id="in_theme" name="count_theme" class="form-control-dark" onchange="updatePreview()">';
                            foreach($themes as $key => $name) {
                                $sel = ($countData['theme'] == $key) ? 'selected' : '';
                                echo '<option value="' . $key . '" ' . $sel . '>' . $name . '</option>';
                            }
                        echo '</select>';
                    echo '</div>';

                    echo '<div class="divider-text">Acción al Finalizar</div>';

                    echo '<div class="btn-config-box">';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Cuando el tiempo llegue a 0:</label>';
                            echo '<select id="in_action" name="count_action" class="form-control-dark sm" onchange="toggleActionFields()">';
                                echo '<option value="message" ' . ($countData['finish_action'] == 'message' ? 'selected' : '') . '>Mostrar Mensaje</option>';
                                echo '<option value="redirect" ' . ($countData['finish_action'] == 'redirect' ? 'selected' : '') . '>Redireccionar URL</option>';
                                echo '<option value="none" ' . ($countData['finish_action'] == 'none' ? 'selected' : '') . '>Nada (Queda en 00:00:00)</option>';
                            echo '</select>';
                        echo '</div>';

                        echo '<div id="field_message" class="form-group mt-2" style="display:none;">';
                            echo '<input type="text" name="count_finish_text" class="form-control-dark sm" value="' . getVal('finish_text', $countData) . '" placeholder="Mensaje final (Ej: ¡Online!)">';
                        echo '</div>';

                        echo '<div id="field_redirect" class="form-group mt-2" style="display:none;">';
                            echo '<input type="text" name="count_finish_url" class="form-control-dark sm" value="' . getVal('finish_url', $countData) . '" placeholder="URL (Ej: https://miservidor.com)">';
                        echo '</div>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

            // Columna Derecha: Preview
            echo '<div class="col-md-7">';
                echo '<div class="settings-section" style="min-height: 500px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #000; position: relative; overflow: hidden;">';
                    echo '<h3 class="settings-title" style="position: relative; align-self: flex-start; border:none; z-index: 10; margin: 0 0 20px 0;">Previsualización</h3>';
                    
                    echo '<div class="preview-bg"></div>';

                    echo '<div id="preview_container" class="countdown-wrapper">';
                        echo '<h2 id="prev_event_name" class="event-title">GRAN APERTURA</h2>';
                        
                        echo '<div class="timer-flex">';
                            // Días
                            echo '<div class="timer-box">';
                                echo '<span class="time-val">13</span>';
                                echo '<span class="time-label">DÍAS</span>';
                            echo '</div>';
                            echo '<div class="timer-separator">:</div>';
                            // Horas
                            echo '<div class="timer-box">';
                                echo '<span class="time-val">12</span>';
                                echo '<span class="time-label">HS</span>';
                            echo '</div>';
                            echo '<div class="timer-separator">:</div>';
                            // Minutos
                            echo '<div class="timer-box">';
                                echo '<span class="time-val">23</span>';
                                echo '<span class="time-label">MIN</span>';
                            echo '</div>';
                            echo '<div class="timer-separator">:</div>';
                            // Segundos
                            echo '<div class="timer-box">';
                                echo '<span class="time-val">50</span>';
                                echo '<span class="time-label">SEG</span>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row
    echo '</form>';
echo '</div>';

// JavaScript
echo '<script>
    function updatePreview() {
        // Actualizar Texto
        document.getElementById("prev_event_name").innerText = document.getElementById("in_name").value || "EVENTO";
        
        // Actualizar Tema
        const theme = document.getElementById("in_theme").value;
        const container = document.getElementById("preview_container");
        
        // Limpiar todas las clases anteriores y poner la nueva
        container.className = "countdown-wrapper theme-" + theme;
    }

    function toggleActionFields() {
        const action = document.getElementById("in_action").value;
        document.getElementById("field_message").style.display = (action === "message") ? "block" : "none";
        document.getElementById("field_redirect").style.display = (action === "redirect") ? "block" : "none";
    }

    document.addEventListener("DOMContentLoaded", function() {
        updatePreview();
        toggleActionFields();
    });
</script>';

// Styles
echo '<style>
    /* ESTILOS GENERALES PANEL */
    .row-flex { display: flex; flex-wrap: wrap; }
    .settings-section { background: #0d1117; padding: 25px; border-radius: 8px; border: 1px solid #30363d; position: relative; }
    .settings-title { margin:0 0 20px 0; border-bottom: 1px solid #2d323e; padding-bottom: 15px; color: #fff; font-size: 18px; font-weight: 600; }
    .form-control-dark { background-color: #222; border: 1px solid #444; color: #eee; padding: 10px; width: 100%; border-radius: 4px; }
    .btn-config-box { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #2d323e; }
    
    .switch-container { display: flex; align-items: center; }
    .switch { position: relative; display: inline-block; width: 50px; height: 24px; margin: 0 10px 0 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #383838; transition: .4s; border-radius: 34px; }
    .slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #28a745; }
    input:checked + .slider:before { transform: translateX(26px); }
    
    /* ESTÉTICA POPUP */
    .btn-save-green { background: #1a4d2e; color: #00ff88; border: 1px solid #00ff88; padding: 8px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }

    /* --- PREVIEW BG --- */
    .preview-bg {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: url("../img/popup/popup.png") center/cover no-repeat;
        opacity: 0.3; filter: blur(5px);
    }

    /* --- ESTRUCTURA BASE --- */
    .countdown-wrapper {
        position: relative; z-index: 5; text-align: center; padding: 40px;
        border-radius: 15px; transition: all 0.5s ease; width: 85%;
    }
    .event-title { margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
    .timer-flex { display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap;}
    .timer-box { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 80px; }
    .time-val { line-height: 1; }
    .time-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
    .timer-separator { font-size: 30px; font-weight: bold; margin-top: -15px; }

    /* ========================== THEMES =========================== */

    /* 1. CLASSIC */
    .theme-classic { background: rgba(0, 0, 0, 0.8); border: 2px solid #cba660; box-shadow: 0 0 30px rgba(203, 166, 96, 0.2); }
    .theme-classic .event-title { font-family: "Cinzel", serif; color: #cba660; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.8); }
    .theme-classic .time-val { font-family: "Cinzel", serif; font-size: 36px; color: #fff; }
    .theme-classic .time-label { color: #cba660; font-family: "Cinzel", serif; }
    .theme-classic .timer-separator { color: #666; }

    /* 2. MODERN */
    .theme-modern { background: rgba(10, 25, 47, 0.9); border: 1px solid #00f3ff; box-shadow: 0 0 20px #00f3ff, inset 0 0 20px rgba(0, 243, 255, 0.2); }
    .theme-modern .event-title { font-family: "Orbitron", sans-serif; color: #00f3ff; text-shadow: 0 0 10px #00f3ff; }
    .theme-modern .time-val { font-family: "Orbitron", sans-serif; font-size: 36px; color: #fff; text-shadow: 0 0 5px #fff; }
    .theme-modern .time-label { color: #00f3ff; font-family: sans-serif; }
    .theme-modern .timer-separator { color: #00f3ff; opacity: 0.5; }

    /* 3. FIRE */
    .theme-fire { background: linear-gradient(to bottom, #2b0a0a, #000); border: 2px solid #ff4d4d; box-shadow: 0 0 30px #ff0000; }
    .theme-fire .event-title { font-family: "Cinzel", serif; color: #ff4d4d; text-shadow: 0 0 10px #ff0000; font-weight: 700; }
    .theme-fire .time-val { font-family: "Cinzel", serif; font-size: 36px; color: #fff; text-shadow: 0 0 5px #ff4d4d; }
    .theme-fire .time-label { color: #ffae00; }
    .theme-fire .timer-separator { color: #ff4d4d; }

    /* 4. ICE */
    .theme-ice { background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(0, 162, 255, 0.1)); border: 1px solid #aaddff; box-shadow: 0 0 20px #aaddff; backdrop-filter: blur(5px); }
    .theme-ice .event-title { font-family: "Cinzel", serif; color: #e6f7ff; text-shadow: 0 0 15px #0099ff; font-weight: 700; }
    .theme-ice .time-val { font-family: "Cinzel", serif; font-size: 36px; color: #fff; text-shadow: 0 0 10px #00ccff; }
    .theme-ice .time-label { color: #aaddff; }
    .theme-ice .timer-separator { color: #aaddff; }

    /* 5. NATURE */
    .theme-nature { background: rgba(18, 41, 19, 0.9); border: 2px solid #4caf50; box-shadow: 0 0 20px #4caf50; }
    .theme-nature .event-title { font-family: "Cinzel", serif; color: #81c784; text-shadow: 0 2px 5px #000; }
    .theme-nature .time-val { font-family: "Cinzel", serif; font-size: 36px; color: #fff; }
    .theme-nature .time-label { color: #a5d6a7; }
    .theme-nature .timer-separator { color: #4caf50; }

    /* 6. MINIMAL */
    .theme-minimal { background: transparent; border-bottom: 1px solid rgba(255,255,255,0.2); border-radius: 0; padding: 20px 0; }
    .theme-minimal .event-title { font-family: "Montserrat", sans-serif; font-weight: 300; letter-spacing: 5px; color: #fff; }
    .theme-minimal .time-val { font-family: "Montserrat", sans-serif; font-weight: 100; font-size: 48px; color: #fff; }
    .theme-minimal .time-label { color: #888; font-weight: 400; }
    .theme-minimal .timer-separator { color: #444; }

    /* 7. CARDS DARK */
    .theme-cards-dark .timer-separator { display: none; }
    .theme-cards-dark .timer-flex { gap: 20px; }
    .theme-cards-dark .event-title { font-family: "Rajdhani", sans-serif; color: #eee; font-weight: 700; letter-spacing: 2px; }
    .theme-cards-dark .timer-box { 
        background: #151515; border: 1px solid #333; padding: 15px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); 
        min-width: 90px;
    }
    .theme-cards-dark .time-val { font-family: "Rajdhani", sans-serif; font-size: 38px; color: #fff; font-weight: 700; }
    .theme-cards-dark .time-label { color: #666; font-weight: 600; }

    /* 8. CARDS GLASS */
    .theme-cards-glass { background: transparent; box-shadow: none; border: none; }
    .theme-cards-glass .timer-separator { display: none; }
    .theme-cards-glass .event-title { font-family: "Montserrat", sans-serif; font-weight: 700; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
    .theme-cards-glass .timer-box {
        background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 12px; padding: 15px; min-width: 90px;
    }
    .theme-cards-glass .time-val { font-family: "Montserrat", sans-serif; font-size: 36px; color: #fff; font-weight: 700; }
    .theme-cards-glass .time-label { color: rgba(255,255,255,0.7); }

    /* 9. CARDS GOLD */
    .theme-cards-gold .timer-separator { display: none; }
    .theme-cards-gold .event-title { font-family: "Cinzel", serif; color: #ffdb58; text-shadow: 0 2px 10px #000; font-weight: 700; }
    .theme-cards-gold .timer-box {
        background: linear-gradient(to bottom, #332812, #1a1408);
        border: 2px solid #bfa058; border-radius: 4px; padding: 10px; min-width: 90px;
        box-shadow: inset 0 0 10px rgba(0,0,0,0.8), 0 0 10px rgba(191, 160, 88, 0.3);
    }
    .theme-cards-gold .time-val { font-family: "Cinzel", serif; font-size: 32px; color: #fff; text-shadow: 0 0 5px #bfa058; }
    .theme-cards-gold .time-label { color: #bfa058; font-size: 9px; }

    /* 10. CARDS CYBER */
    .theme-cards-cyber .timer-separator { display: none; }
    .theme-cards-cyber .event-title { font-family: "Orbitron", sans-serif; color: #fcee0a; text-transform: uppercase; letter-spacing: 3px; }
    .theme-cards-cyber .timer-box {
        background: #000; border: 2px solid #fcee0a; padding: 10px; min-width: 90px;
        clip-path: polygon(10% 0, 100% 0, 100% 90%, 90% 100%, 0 100%, 0 10%); 
    }
    .theme-cards-cyber .time-val { font-family: "Orbitron", sans-serif; font-size: 34px; color: #fcee0a; }
    .theme-cards-cyber .time-label { background: #fcee0a; color: #000; width: 100%; font-weight: 800; margin-top: 5px; }

</style>';
?>