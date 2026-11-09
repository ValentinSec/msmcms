<?php
/**
 * Global Floating Countdown Module (Versión LEFT SIDE + TITÁN)
 * Ubicación: templates/misc/countdown.php
 */

// 1. CARGAR CONFIGURACIÓN
$countConfigPath = __PATH_INCLUDES__ . 'config/countdown_config.json';
$countData = [];

if (file_exists($countConfigPath)) {
    $countData = json_decode(file_get_contents($countConfigPath), true);
}

// Validar estado activo
if (empty($countData) || !isset($countData['active']) || $countData['active'] != 1) {
    return;
}

// 2. PREPARAR DATOS
$targetDateStr = $countData['target_date'];
$targetTimestamp = strtotime($targetDateStr);
$serverTime = time();

if ($serverTime >= $targetTimestamp && $countData['finish_action'] == 'none') {
    return;
}

$jsConfig = [
    'target' => $targetTimestamp * 1000,
    'now'    => $serverTime * 1000,
    'action' => $countData['finish_action'],
    'text'   => $countData['finish_text'],
    'url'    => $countData['finish_url']
];

$baseUrl = defined('__BASE_URL__') ? __BASE_URL__ : './';
?>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Orbitron:wght@400;700&family=Rajdhani:wght@500;700&family=Montserrat:wght@300;700&display=swap" rel="stylesheet">

<style>
    /* =========================================
       FLOATING COUNTDOWN (IZQUIERDA)
       ========================================= */
    .floating-countdown {
        position: fixed;
        top: 15%; 
        left: 0; /* AHORA A LA IZQUIERDA */
        z-index: 99999;
        
        /* ANCHO MASIVO */
        width: 600px !important; 
        
        /* Transición de la propiedad LEFT */
        transition: left 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        animation: slideInLeft 1s forwards;
        font-family: sans-serif;
    }

    @keyframes slideInLeft {
        from { left: -600px; }
        to { left: 0; }
    }

    /* AL CERRAR, SE VA A LA IZQUIERDA NEGATIVA */
    .floating-countdown.collapsed {
        left: -600px !important;
    }

    /* LA FLECHA (Ahora a la derecha del widget) */
    .toggle-btn {
        position: absolute;
        top: 50%;
        /* Se pega a la DERECHA del contenedor (sobresale) */
        right: -50px; 
        transform: translateY(-50%);
        
        width: 50px;
        height: 100px;
        
        background: #000;
        border: 2px solid rgba(255,255,255,0.2);
        
        /* Borde izquierdo plano para pegar con el widget */
        border-left: none; 
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
        
        color: #cba660; 
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        box-shadow: 5px 0 15px rgba(0,0,0,0.5); /* Sombra hacia la derecha */
        z-index: 100;
        font-size: 24px; 
    }

    /* Rotar flecha al cerrar */
    .floating-countdown.collapsed .toggle-btn i {
        transform: rotate(180deg);
    }
    .toggle-btn:hover { background: #333; color: #fff; }

    /* CONTENEDOR INTERNO */
    .countdown-wrapper {
        position: relative;
        padding: 40px 30px;
        
        /* Bordes redondeados a la DERECHA solamente */
        border-top-right-radius: 20px;
        border-bottom-right-radius: 20px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        
        /* Borde izquierdo eliminado */
        border-left: none !important;
        
        text-align: center;
        box-shadow: 10px 10px 40px rgba(0,0,0,0.9);
        background: #111; 
    }

    /* --- TEXTOS GIGANTES (IGUAL QUE ANTES) --- */
    .event-title { margin: 0 0 30px 0; text-transform: uppercase; letter-spacing: 3px; line-height: 1.2; font-size: 36px !important; }
    .timer-flex { display: flex; justify-content: center; align-items: center; gap: 20px; }
    .timer-box { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 100px; }
    .time-val { line-height: 1; font-size: 70px !important; font-weight: 800; display: block; }
    .time-label { font-size: 14px !important; text-transform: uppercase; margin-top: 10px; font-weight: bold; letter-spacing: 1px; }
    .timer-separator { font-size: 50px !important; font-weight: bold; margin-top: -30px; }
    .finish-msg h3 { margin: 0; font-size: 28px; text-transform: uppercase; color: #fff; text-shadow: 0 0 10px rgba(255,255,255,0.5); animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 0.8; } 50% { opacity: 1; transform: scale(1.05); } 100% { opacity: 0.8; } }

    /* --- THEMES (Adaptados para Borde Izquierdo Plano) --- */
    /* En todos los themes cambiamos border-right: none por border-left: none */
    
    .theme-classic { background: rgba(0, 0, 0, 0.95); border: 3px solid #cba660; border-left: none; }
    .theme-classic .event-title { font-family: 'Cinzel', serif; color: #cba660; font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.8); }
    .theme-classic .time-val { font-family: 'Cinzel', serif; color: #fff; }
    .theme-classic .time-label { color: #cba660; font-family: 'Cinzel', serif; }
    .theme-classic .timer-separator { color: #666; }

    .theme-modern { background: rgba(10, 25, 47, 0.95); border: 2px solid #00f3ff; border-left: none; }
    .theme-modern .event-title { font-family: 'Orbitron', sans-serif; color: #00f3ff; text-shadow: 0 0 15px #00f3ff; }
    .theme-modern .time-val { font-family: 'Orbitron', sans-serif; color: #fff; text-shadow: 0 0 10px #fff; }
    .theme-modern .time-label { color: #00f3ff; font-family: sans-serif; }
    .theme-modern .timer-separator { color: #00f3ff; opacity: 0.8; }

    .theme-fire { background: linear-gradient(to bottom, #2b0a0a, #000); border: 2px solid #ff4d4d; border-left: none; }
    .theme-fire .event-title { font-family: 'Cinzel', serif; color: #ff4d4d; text-shadow: 0 0 15px #ff0000; font-weight: 700; }
    .theme-fire .time-val { font-family: 'Cinzel', serif; color: #fff; text-shadow: 0 0 10px #ff4d4d; }
    .theme-fire .time-label { color: #ffae00; }
    .theme-fire .timer-separator { color: #ff4d4d; }

    .theme-ice { background: rgba(10, 20, 30, 0.95); border: 2px solid #aaddff; border-left: none; backdrop-filter: blur(10px); }
    .theme-ice .event-title { font-family: 'Cinzel', serif; color: #e6f7ff; text-shadow: 0 0 20px #0099ff; font-weight: 700; }
    .theme-ice .time-val { font-family: 'Cinzel', serif; color: #fff; text-shadow: 0 0 15px #00ccff; }
    .theme-ice .time-label { color: #aaddff; }
    .theme-ice .timer-separator { color: #aaddff; }

    .theme-nature { background: rgba(18, 41, 19, 0.95); border: 2px solid #4caf50; border-left: none; }
    .theme-nature .event-title { font-family: 'Cinzel', serif; color: #81c784; text-shadow: 0 2px 5px #000; }
    .theme-nature .time-val { font-family: 'Cinzel', serif; color: #fff; }
    .theme-nature .time-label { color: #a5d6a7; }
    .theme-nature .timer-separator { color: #4caf50; }

    .theme-minimal { background: rgba(0,0,0,0.9); border-bottom: 2px solid rgba(255,255,255,0.3); border-radius: 0; }
    .theme-minimal .event-title { font-family: 'Montserrat', sans-serif; font-weight: 300; letter-spacing: 5px; color: #fff; }
    .theme-minimal .time-val { font-family: 'Montserrat', sans-serif; font-weight: 100; color: #fff; }
    .theme-minimal .time-label { color: #aaa; font-weight: 400; }
    .theme-minimal .timer-separator { color: #666; }

    .theme-cards-dark .timer-separator { display: none; }
    .theme-cards-dark .event-title { font-family: 'Rajdhani', sans-serif; color: #eee; font-weight: 700; }
    .theme-cards-dark .timer-box { background: #151515; border: 2px solid #333; padding: 10px; border-radius: 10px; min-width: 90px; }
    .theme-cards-dark .time-val { font-family: 'Rajdhani', sans-serif; color: #fff; font-weight: 700; }
    .theme-cards-dark .time-label { color: #888; font-weight: 600; }

    .theme-cards-glass { background: rgba(0,0,0,0.7); backdrop-filter: blur(15px); border:none; }
    .theme-cards-glass .timer-separator { display: none; }
    .theme-cards-glass .event-title { font-family: 'Montserrat', sans-serif; font-weight: 700; color: #fff; }
    .theme-cards-glass .timer-box { background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 12px; padding: 10px; min-width: 90px; }
    .theme-cards-glass .time-val { font-family: 'Montserrat', sans-serif; color: #fff; font-weight: 700; }
    .theme-cards-glass .time-label { color: rgba(255,255,255,0.9); }

    .theme-cards-gold .timer-separator { display: none; }
    .theme-cards-gold .event-title { font-family: 'Cinzel', serif; color: #ffdb58; text-shadow: 0 2px 15px #000; font-weight: 700; }
    .theme-cards-gold .timer-box { background: linear-gradient(to bottom, #332812, #1a1408); border: 2px solid #bfa058; border-radius: 8px; padding: 10px; min-width: 90px; }
    .theme-cards-gold .time-val { font-family: 'Cinzel', serif; color: #fff; text-shadow: 0 0 10px #bfa058; }
    .theme-cards-gold .time-label { color: #bfa058; font-size: 10px; }

    .theme-cards-cyber .timer-separator { display: none; }
    .theme-cards-cyber .event-title { font-family: 'Orbitron', sans-serif; color: #fcee0a; letter-spacing: 4px; }
    .theme-cards-cyber .timer-box { background: #000; border: 2px solid #fcee0a; padding: 10px; min-width: 90px; }
    .theme-cards-cyber .time-val { font-family: 'Orbitron', sans-serif; color: #fcee0a; }
    .theme-cards-cyber .time-label { background: #fcee0a; color: #000; width: 100%; font-weight: 800; margin-top: 5px; }

    /* RESPONSIVE MÓVIL */
    @media (max-width: 768px) {
        .floating-countdown {
            width: 100% !important;
            top: auto; bottom: 0; left: 0;
            transform: translateY(100%); 
            animation: slideUp 1s forwards;
        }
        .floating-countdown.collapsed {
            transform: translateY(100%);
            left: 0 !important;
        }
        .countdown-wrapper {
            border-radius: 0; border: none; border-top: 3px solid #444; padding: 20px;
        }
        .toggle-btn {
            top: -40px; left: 50%; transform: translateX(-50%) rotate(-90deg);
            border-radius: 10px 10px 0 0; width: 80px; height: 40px;
            border: 1px solid #444; border-bottom: none;
            /* Reset de estilos de escritorio */
            right: auto; border-left: 1px solid #444;
        }
        .time-val { font-size: 32px !important; }
        @keyframes slideUp { to { transform: translateY(0); } }
    }
</style>

<div id="global_countdown_container" class="floating-countdown">
    
    <div id="cd_toggle_btn" class="toggle-btn" title="Mostrar/Ocultar">
        <i class="fa fa-chevron-left"></i>
    </div>

    <div id="global_countdown_wrapper" class="countdown-wrapper theme-<?php echo $countData['theme']; ?>">
        
        <h2 class="event-title"><?php echo htmlspecialchars($countData['event_name']); ?></h2>
        
        <div id="timer_display" class="timer-flex">
            <div class="timer-box">
                <span id="cd_days" class="time-val">00</span>
                <span class="time-label">DÍAS</span>
            </div>
            <div class="timer-separator">:</div>
            <div class="timer-box">
                <span id="cd_hours" class="time-val">00</span>
                <span class="time-label">HS</span>
            </div>
            <div class="timer-separator">:</div>
            <div class="timer-box">
                <span id="cd_min" class="time-val">00</span>
                <span class="time-label">MIN</span>
            </div>
            <div class="timer-separator">:</div>
            <div class="timer-box">
                <span id="cd_sec" class="time-val">00</span>
                <span class="time-label">SEG</span>
            </div>
        </div>

        <div id="finish_message" class="finish-msg" style="display:none;">
            <h3><?php echo htmlspecialchars($countData['finish_text']); ?></h3>
        </div>

    </div>
</div>

<script>
    window.countdownConfig = <?php echo json_encode($jsConfig); ?>;
    
    (function() {
        if(typeof window.countdownConfig === 'undefined') return;
        var config = window.countdownConfig;
        
        var targetTime = config.target;
        var serverTimeNow = config.now;
        var clientTimeNow = new Date().getTime();
        var timeOffset = serverTimeNow - clientTimeNow;

        var timerDisplay = document.getElementById("timer_display");
        var finishMsg = document.getElementById("finish_message");
        var container = document.getElementById("global_countdown_container");
        var mainWrapper = document.getElementById("global_countdown_wrapper");
        var toggleBtn = document.getElementById("cd_toggle_btn");

        // TOGGLE LOGIC (LEFT SIDE)
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                container.classList.toggle('collapsed');
                // Rotar flecha
                var icon = this.querySelector('i');
                if(container.classList.contains('collapsed')) {
                    // Cerrado: queremos flecha derecha (para abrir)
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                } else {
                    // Abierto: queremos flecha izquierda (para cerrar)
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                }
            });
        }

        function updateTimer() {
            var now = new Date().getTime() + timeOffset; 
            var distance = targetTime - now;

            if (distance < 0) {
                if(timerDisplay) timerDisplay.style.display = "none";
                if (config.action === 'message') {
                    if(finishMsg) finishMsg.style.display = "block";
                } else if (config.action === 'redirect' && config.url) {
                    window.location.href = config.url;
                } else {
                    if(container) container.style.display = "none";
                }
                clearInterval(timerInterval);
                return;
            }

            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            var elD = document.getElementById("cd_days");
            var elH = document.getElementById("cd_hours");
            var elM = document.getElementById("cd_min");
            var elS = document.getElementById("cd_sec");

            if(elD) elD.innerText = (days < 10 ? "0" : "") + days;
            if(elH) elH.innerText = (hours < 10 ? "0" : "") + hours;
            if(elM) elM.innerText = (minutes < 10 ? "0" : "") + minutes;
            if(elS) elS.innerText = (seconds < 10 ? "0" : "") + seconds;
        }

        var timerInterval = setInterval(updateTimer, 1000);
        updateTimer(); 
    })();
</script>