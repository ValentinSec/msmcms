<?php
// Sidebar Module - Canibal Template

// 1. CARGA DE CONFIGURACIONES (Módulos)
$customSettingsPath = 'includes/config/custom_settings.json';
$cSettings = file_exists($customSettingsPath) ? json_decode(file_get_contents($customSettingsPath), true) : [];

// Helpers de Activación
$myCharActive = (isset($cSettings['module_mychar_active']) && $cSettings['module_mychar_active'] === 'on');
$vipModuleActive = (isset($cSettings['module_vip_active']) && $cSettings['module_vip_active'] === 'on');
$votesActive = (isset($cSettings['module_votes_active']) && $cSettings['module_votes_active'] === 'on');
$webshopActive = (isset($cSettings['module_webshop_active']) && $cSettings['module_webshop_active'] === 'on');
$casinoActive = (isset($cSettings['module_casino_active']) && $cSettings['module_casino_active'] === 'on');

// Configuración Donaciones
$donCfgFile = 'includes/config/donation_config.json';
$donCfg = file_exists($donCfgFile) ? json_decode(file_get_contents($donCfgFile), true) : [];
$donationModuleActive = (isset($donCfg['module_active']) && ($donCfg['module_active'] == 1 || $donCfg['module_active'] === 'on'));

// Configuración Black Market
$bmConfigFile = 'includes/config/black_market_config.json';
$bmData = file_exists($bmConfigFile) ? json_decode(file_get_contents($bmConfigFile), true) : [];
$blackMarketActive = (isset($bmData['module_active']) && $bmData['module_active'] === 'on');
?>

<style>
    /* Estilo para el botón activo (Hover Suave) */
    .btn-login-custom.active-sidebar {
        background-color: rgba(200, 161, 101, 0.15) !important;
        border-color: rgba(200, 161, 101, 0.5) !important;
        color: #c8a165 !important;
    }
</style>

<script>
    /* TRUCO AJAX: Login instantáneo en las sombras */
    let isSubmittingLogin = false;
    function doAjaxLogin(event, form) {
        if(isSubmittingLogin) return; 
        event.preventDefault(); // Frenamos el envío normal

        const btn = form.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fa fa-spinner fa-spin ms-2"></i> CONECTANDO...';
        btn.style.opacity = '0.7';
        isSubmittingLogin = true;

        const formData = new FormData(form);
        // MuServerMaker requiere este campo oculto para saber que se apretó el botón
        formData.append('msmLogin_submit', '1'); 

        fetch(form.action, {
            method: 'POST',
            body: formData,
            redirect: 'follow' // Seguimos a MuServerMaker en las sombras
        })
        .then(response => {
            // Si MuServerMaker nos redirigió al usercp internamente, ¡el login fue un éxito!
            if (response.url.indexOf('usercp') !== -1) {
                // Redirigimos al index.php de inmediato, carga limpia y rápida
                window.location.href = '<?php echo __BASE_URL__; ?>'; 
            } else {
                // Falló (mal usuario/pass). Enviamos el form de manera normal para que vea el error oficial
                form.onsubmit = null; 
                form.submit();
            }
        })
        .catch(error => {
            // Si hay error de red, fallback al sistema normal
            form.onsubmit = null;
            form.submit();
        });
    }
</script>

<?php if(!isLoggedIn()): ?>
    <div class="sidebar-box-login" id="canibal-account">
        <h6 class="sidebar-title">INGRESAR</h6>
        <form action="<?php echo __BASE_URL__; ?>login" method="post" style="width:100%;" onsubmit="doAjaxLogin(event, this);">
            <input type="text" name="msmLogin_user" class="login-input" placeholder="Usuario" required>
            <input type="password" name="msmLogin_pwd" class="login-input" placeholder="Contraseña" required>
            
            <button type="submit" name="msmLogin_submit" class="btn-login-custom" style="transition: all 0.3s ease;">
                INGRESAR <i class="fa fa-chevron-right ms-2"></i>
            </button>
            
            <a href="<?php echo __BASE_URL__; ?>forgotpassword" class="forgot-link">Recuperar Password</a>
            <a href="<?php echo __BASE_URL__; ?>register" class="create-acc-link">Crear Cuenta</a>
        </form>
    </div>
<?php else: ?>
    <div class="sidebar-box-login" id="canibal-account" style="align-items:center; text-align:center; height: auto !important;">
        <h6 class="text-white mb-3" style="font-family:'Cinzel'; letter-spacing:1px;"><?php echo $_SESSION['username']; ?></h6>
        
        <?php if(canAccessAdminCP($_SESSION['username'])): ?>
            <a href="<?php echo __PATH_ADMINCP_HOME__; ?>" class="btn-login-custom" style="border-color: #f1c40f; color: #f1c40f; margin-bottom: 6px;">
                <i class="fa fa-gear me-2"></i> ADMINISTRACIÓN
            </a>
        <?php endif; ?>

        <div style="width:100%; display:flex; flex-direction:column; gap:6px;">
            <a href="<?php echo __BASE_URL__; ?>usercp/config" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/config') !== false) echo 'active-sidebar'; ?>">
                <i class="fa fa-user-gear me-2"></i> MI CUENTA
            </a>

            <?php if($myCharActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/characters" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/characters') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-users me-2"></i> MIS PERSONAJES
                </a>
            <?php endif; ?>

            <?php if($donationModuleActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/donate" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/donate') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-credit-card me-2"></i> DONAR
                </a>
            <?php endif; ?>

            <?php if($vipModuleActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/vip" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/vip') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-crown me-2"></i> RECARGAR VIP
                </a>
            <?php endif; ?>

            <?php if($webshopActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/webshop" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/webshop') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-cart-shopping me-2"></i> WEBSHOP
                </a>
            <?php endif; ?>

            <?php if($casinoActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/casino" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/casino') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-clover me-2"></i> CASINO GOBLIN
                </a>
            <?php endif; ?>

            <?php if($blackMarketActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/black_market" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/black_market') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-mask me-2"></i> BLACK MARKET
                </a>
            <?php endif; ?>

            <?php if($votesActive): ?>
                <a href="<?php echo __BASE_URL__; ?>usercp/vote" class="btn-login-custom <?php if(strpos($_SERVER['REQUEST_URI'], 'usercp/vote') !== false) echo 'active-sidebar'; ?>">
                    <i class="fa fa-check-to-slot me-2"></i> VOTOS
                </a>
            <?php endif; ?>
        </div>

        <hr style="width:100%; border-color:rgba(255,255,255,0.1); margin: 15px 0 10px 0;">
        <a href="<?php echo __BASE_URL__; ?>logout" class="forgot-link text-danger logout-btn" style="font-weight:bold;">
            <i class="fa fa-power-off"></i> CERRAR SESIÓN
        </a>
    </div>
<?php endif; ?>

<div class="sidebar-box-search">
    <h6 class="sidebar-title">BUSCAR PJ</h6>
    <form onsubmit="event.preventDefault(); var pj = this.name.value.trim(); if(pj) window.location.href = '<?php echo __BASE_URL__; ?>profile/player/req/' + encodeURIComponent(pj);">
        <input type="text" name="name" class="login-input" placeholder="Nombre del personaje...">
        <button type="submit" class="btn-login-custom">BUSCAR</button>
    </form>
</div>

<div class="sidebar-box-generic" style="padding: 0 !important; overflow: hidden;">
    <?php
    // --- LÓGICA DE CARGA DE EVENTOS ---
    $eventsList = [];
    $uniqueTypes = []; 
    
    // Intento 1: Ruta relativa (Estándar)
    $eventsConfigPath = 'includes/config/server_events.json';
    // Intento 2: Ruta absoluta (Fallback)
    if(!file_exists($eventsConfigPath)) {
        $eventsConfigPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/server_events.json';
    }

    if(file_exists($eventsConfigPath)) {
        // Leemos el JSON directo sin regex porque es un archivo .json puro
        $jsonContent = file_get_contents($eventsConfigPath);
        $decoded = json_decode($jsonContent, true);
        
        if(is_array($decoded)) {
            $eventsList = $decoded;
            // Extraer tipos para filtro
            foreach($eventsList as $ev) {
                if(isset($ev['type']) && !empty($ev['type'])) {
                    $uniqueTypes[$ev['type']] = $ev['type'];
                }
            }
        }
    }
    
    $dayMap = [
        'Lunes' => 'Monday', 'Martes' => 'Tuesday', 'Miércoles' => 'Wednesday', 
        'Miercoles' => 'Wednesday', 'Jueves' => 'Thursday', 'Viernes' => 'Friday', 
        'Sábado' => 'Saturday', 'Sabado' => 'Saturday', 'Domingo' => 'Sunday'
    ];
    ?>

    <div class="sb-header">
        <h3 class="sb-title">Eventos</h3>
        <i class="fa fa-calendar-alt sb-icon"></i>
    </div>
    
    <?php if(!empty($uniqueTypes)): ?>
    <div class="sb-filter-container" style="padding: 0 15px 15px 15px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <select id="eventFilterSelect" class="form-control-dark" style="width: 100%; font-size: 12px; height: 30px; padding: 0 10px;" onchange="filterEventsByType(this.value)">
            <option value="all">Todos los Eventos</option>
            <?php foreach($uniqueTypes as $type): ?>
                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <div class="events-list" id="eventsListContainer">
        <?php if(!empty($eventsList)): ?>
            <?php foreach($eventsList as $ev): ?>
                <?php 
                    $evName = $ev['event_name'] ?? $ev['name'] ?? 'Evento';
                    $evType = $ev['type'] ?? 'Daily';
                    
                    $now = time();
                    $secondsLeft = 0; 
                    $statusClass = "offline";
                    $isActiveRange = false;

                    // A. Eventos Semanales
                    if($evType == 'Weekly' && isset($ev['day_start'], $ev['time_start'])) {
                        $dayStartEng = $dayMap[$ev['day_start']] ?? 'Monday';
                        $dayEndEng   = $dayMap[$ev['day_end']] ?? 'Sunday';
                        
                        $startTS = strtotime("this $dayStartEng " . $ev['time_start']);
                        $endTS   = strtotime("this $dayEndEng " . ($ev['time_end'] ?? '23:59'));

                        if($startTS > $endTS) $endTS = strtotime("+1 week", $endTS); 

                        if($now >= $startTS && $now <= $endTS) {
                            $statusClass = "online";
                            $secondsLeft = $endTS - $now;
                            $isActiveRange = true;
                        } elseif ($now < $startTS) {
                            $secondsLeft = $startTS - $now;
                        } else {
                            $nextStartTS = strtotime("+1 week", $startTS);
                            $secondsLeft = $nextStartTS - $now;
                        }
                    } 
                    // B. Eventos Diarios (Daily / Special)
                    else {
                        // Soporte para llave 'event_times' o 'times'
                        $rawTimes = $ev['event_times'] ?? $ev['times'] ?? [];
                        if(is_string($rawTimes)) $rawTimes = explode(',', $rawTimes);
                        
                        $nextRunTimestamp = -1;
                        if(!empty($rawTimes) && is_array($rawTimes)) {
                            sort($rawTimes); 
                            $todayDate = date('Y-m-d');
                            $tomorrowDate = date('Y-m-d', strtotime('+1 day'));

                            foreach($rawTimes as $t) {
                                $t = trim($t);
                                if(empty($t)) continue;
                                $tsToday = strtotime("$todayDate $t");
                                if($tsToday > $now) {
                                    $nextRunTimestamp = $tsToday;
                                    break;
                                }
                            }
                            // Si no hay más hoy, buscar mañana
                            if($nextRunTimestamp == -1 && isset($rawTimes[0])) {
                                $firstTime = trim($rawTimes[0]);
                                if(!empty($firstTime)) {
                                    $nextRunTimestamp = strtotime("$tomorrowDate $firstTime");
                                }
                            }
                        }

                        if($nextRunTimestamp != -1) {
                            $secondsLeft = $nextRunTimestamp - $now;
                            if($secondsLeft <= 600) $statusClass = "online";
                        }
                    }
                ?>

                <div class="event-row" data-type="<?php echo $evType; ?>">
                    <div class="ev-status <?php echo $statusClass; ?>"></div>
                    <span class="ev-name"><?php echo $evName; ?></span>
                    <span class="ev-timer js-countdown" 
                          data-seconds="<?php echo max(0, $secondsLeft); ?>"
                          data-active="<?php echo $isActiveRange ? '1' : '0'; ?>" 
                          style="color: #c8a165; font-weight: 700; font-size: 11px; font-family: monospace;">
                        --:--:--
                    </span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-3 text-center text-muted" style="font-size:12px;">
                Cargando eventos... <br>
                <small style="font-size:9px; color:#555;">(No se encontró server_events.json)</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Filtro de Categorias
    if(typeof filterEventsByType !== 'function') {
        function filterEventsByType(type) {
            const rows = document.querySelectorAll('.event-row');
            rows.forEach(row => {
                row.style.display = (type === 'all' || row.getAttribute('data-type') === type) ? 'flex' : 'none';
            });
        }
    }

    // Timer Global
    function sbUpdateCountdowns() {
        const timers = document.querySelectorAll('.js-countdown');
        timers.forEach(timer => {
            let seconds = parseInt(timer.getAttribute('data-seconds'));
            if (isNaN(seconds)) seconds = 0; // Prevenir error NaN

            const isActive = timer.getAttribute('data-active') === '1';

            if (seconds > 0) {
                seconds--;
                timer.setAttribute('data-seconds', seconds);

                const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
                const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');

                if (isActive) {
                    timer.innerHTML = "ACTIVO";
                    timer.style.color = "#2bdf49"; 
                } else {
                    timer.innerHTML = h + ":" + m + ":" + s;
                    timer.style.color = "#c8a165"; 
                }
            } else {
                // Tiempo agotado o no calculado
                timer.innerHTML = isActive ? "FINALIZADO" : "INICIANDO";
                timer.style.color = isActive ? "#aaa" : "#2bdf49";
            }
        });
    }

    // Iniciar intervalo único para el sidebar
    if (window.sbEventInterval) clearInterval(window.sbEventInterval);
    window.sbEventInterval = setInterval(sbUpdateCountdowns, 1000);
    // Ejecución inicial inmediata
    sbUpdateCountdowns();
</script>

<?php
// Barra superior de cuenta (solo celular). El script la mueve al inicio del <body>
// y traslada el cuadro #canibal-account dentro de su panel desplegable.
$cmbLabel = isLoggedIn() ? htmlspecialchars($_SESSION['username']) : 'INGRESAR';
?>
<div id="cmbBar" class="cmb-bar">
    <a href="<?php echo __BASE_URL__; ?>" class="cmb-logo">
        <img src="<?php echo __BASE_URL__; ?>templates/canibal/img/logo.png" alt="Inicio">
    </a>
    <button type="button" class="cmb-toggle" id="cmbToggle" aria-expanded="false" aria-controls="cmbPanel">
        <i class="fa fa-user"></i>
        <span class="cmb-label"><?php echo $cmbLabel; ?></span>
        <i class="fa fa-chevron-down cmb-chev"></i>
    </button>
    <div class="cmb-panel" id="cmbPanel"></div>
</div>

<script>
(function () {
    var bar = document.getElementById('cmbBar');
    var account = document.getElementById('canibal-account');
    if (!bar || !account) return;

    var panel = document.getElementById('cmbPanel');
    var toggle = document.getElementById('cmbToggle');
    var homeParent = account.parentNode;          // columna lateral
    var homeNext = account.nextElementSibling;    // caja "Buscar PJ"

    // Fuera del contenedor del template, para que quede por encima de todo
    document.body.insertBefore(bar, document.body.firstChild);

    var mq = window.matchMedia('(max-width: 767.98px)');

    function setOpen(open) {
        bar.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function place() {
        if (mq.matches) {
            panel.appendChild(account);
            document.body.classList.add('has-cmb');
        } else {
            homeParent.insertBefore(account, homeNext);
            document.body.classList.remove('has-cmb');
            setOpen(false);
        }
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        setOpen(!bar.classList.contains('is-open'));
    });
    document.addEventListener('click', function (e) {
        if (bar.classList.contains('is-open') && !bar.contains(e.target)) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });

    if (mq.addEventListener) mq.addEventListener('change', place); else mq.addListener(place);
    place();
})();
</script>