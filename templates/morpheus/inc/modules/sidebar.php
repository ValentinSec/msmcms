<div class="col-lg-4">
                        
    <?php
    // Comprobamos si el módulo de Donación está activo
    $donationConfigFile = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/donation_config.json';
    $isDonationActive = false;
    
    if (file_exists($donationConfigFile)) {
        $donationData = json_decode(file_get_contents($donationConfigFile), true);
        if (isset($donationData['module_active']) && $donationData['module_active'] == 1) {
            $isDonationActive = true;
        }
    }
    
    if ($isDonationActive): 
    ?>
    <div class="sidebar-panel donate-panel mb-4">
        <div class="donate-content">
            <h3 class="sb-title gold">DONACIONES</h3>
            <p class="sb-subtitle">Mantén el servidor activo!</p>
            <a href="<?php echo __BASE_URL__; ?>usercp" class="btn-donate-go">Dona ahora!</a>
        </div>
        <img src="<?php echo __BASE_URL__; ?>templates/morpheus/img/goblin.png" class="donate-char-img" alt="Donate">
    </div>
    <?php endif; ?>

    <div class="sidebar-panel info-panel mb-4">
        <div class="sb-header">
            <h3 class="sb-title">Información del servidor</h3>
            <a href="<?php echo __BASE_URL__; ?>info" style="text-decoration:none;">
                <i class="fa fa-plus sb-icon"></i>
            </a>
        </div>
        <div class="info-list">
            <div class="info-row"><span>Versión</span> <span class="val gold"><?php echo custom_config('server_version'); ?></span></div>
            <div class="info-row"><span>Exp</span> <span class="val"><?php echo custom_config('server_exp'); ?></span></div>
            <div class="info-row"><span>Drop</span> <span class="val"><?php echo custom_config('server_drop'); ?></span></div>
            
            <?php
            // Lógica dinámica para agregar info desde server_info.json -> "general" -> "data"
            $serverInfoPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/server_info.json';
            if (file_exists($serverInfoPath)) {
                $infoData = json_decode(file_get_contents($serverInfoPath), true);
                
                if (isset($infoData['general']['data']) && is_array($infoData['general']['data'])) {
                    foreach ($infoData['general']['data'] as $item) {
                        $label = isset($item['title']) ? trim($item['title']) : '';
                        $value = isset($item['desc']) ? trim($item['desc']) : '';
                        
                        if ($label !== '') {
                            echo '<div class="info-row"><span>'.htmlspecialchars($label).'</span> <span class="val">'.htmlspecialchars($value).'</span></div>';
                        }
                    }
                }
            }
            ?>

            <div class="info-row"><span>Cuentas</span> <span class="val"><?php echo number_format($stats['accounts']); ?></span></div>
            <div class="info-row"><span>Personajes</span> <span class="val"><?php echo number_format($stats['chars']); ?></span></div>
            <div class="info-row"><span>Guilds</span> <span class="val"><?php echo number_format($stats['guilds']); ?></span></div>
        </div>
    </div>

    <div class="sidebar-panel events-panel">
    <?php
    // --- 1. CARGA DE CONFIGURACIÓN ---
    $eventsConfigPath = __PATH_INCLUDES__ . 'config/server_events.json';
    $eventsList = [];
    $uniqueTypes = []; // Para guardar los tipos (Daily, Weekly, etc)

    if(file_exists($eventsConfigPath)) {
        $jsonContent = file_get_contents($eventsConfigPath);
        $decoded = json_decode($jsonContent, true);
        if(is_array($decoded)) {
            $eventsList = $decoded;
            // Extraer tipos únicos para el filtro
            foreach($eventsList as $ev) {
                if(isset($ev['type']) && !empty($ev['type'])) {
                    $uniqueTypes[$ev['type']] = $ev['type'];
                }
            }
        }
    }
    
    // Traducción de días para PHP (Español -> Inglés)
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
                    $evType = $ev['type'] ?? 'Daily'; // Daily por defecto
                    
                    // --- LÓGICA DE TIEMPO ---
                    $now = time();
                    $secondsLeft = -1; 
                    $statusClass = "offline";
                    $isActiveRange = false; // Para saber si estamos DENTRO del evento

                    // === CASO 1: WEEKLY (RANGO DE DÍAS) ===
                    if($evType == 'Weekly' && isset($ev['day_start'], $ev['time_start'])) {
                        
                        $dayStartEng = $dayMap[$ev['day_start']] ?? 'Monday';
                        $dayEndEng   = $dayMap[$ev['day_end']] ?? 'Sunday'; // Fallback
                        
                        // Calculamos timestamps de la SEMANA ACTUAL
                        // "this Monday 00:00"
                        $startTS = strtotime("this $dayStartEng " . $ev['time_start']);
                        $endTS   = strtotime("this $dayEndEng " . ($ev['time_end'] ?? '23:59'));

                        // Corrección: si hoy es Domingo y el evento es Lun-Vie, 'this Monday' podría ser mañana
                        // Usamos lógica relativa segura:
                        if($startTS > $endTS) { 
                            // Si inicio es mayor a fin (ej: Sábado a Lunes), sumamos semana al fin
                            $endTS = strtotime("+1 week", $endTS); 
                        }

                        // Lógica de Estado
                        if($now >= $startTS && $now <= $endTS) {
                            // 1. ESTAMOS DENTRO DEL EVENTO (ACTIVO)
                            $statusClass = "online";
                            $secondsLeft = $endTS - $now; // Cuenta regresiva hasta que TERMINE
                            $isActiveRange = true; // Flag para mostrar texto diferente
                        } elseif ($now < $startTS) {
                            // 2. TODAVÍA NO EMPEZÓ ESTA SEMANA
                            $secondsLeft = $startTS - $now;
                        } else {
                            // 3. YA TERMINÓ ESTA SEMANA -> Calcular para la próxima
                            $nextStartTS = strtotime("+1 week", $startTS);
                            $secondsLeft = $nextStartTS - $now;
                        }
                    } 
                    // === CASO 2: DAILY / SPECIAL (HORARIOS FIJOS) ===
                    else {
                        $rawTimes = $ev['event_times'] ?? $ev['times'] ?? [];
                        if(is_string($rawTimes)) $rawTimes = explode(',', $rawTimes);
                        
                        if(!empty($rawTimes) && is_array($rawTimes)) {
                            sort($rawTimes);
                            foreach($rawTimes as $t) {
                                $t = trim($t);
                                if(empty($t)) continue;
                                $checkTime = strtotime(date('Y-m-d') . ' ' . $t);
                                if($checkTime > $now) {
                                    $secondsLeft = $checkTime - $now;
                                    break; 
                                }
                            }
                            // Si no hay hoy, buscar mañana
                            if($secondsLeft === -1 && isset($rawTimes[0])) {
                                $firstTime = trim($rawTimes[0]);
                                if(!empty($firstTime)) {
                                    $nextRun = strtotime(date('Y-m-d', strtotime('+1 day')) . ' ' . $firstTime);
                                    $secondsLeft = $nextRun - $now;
                                }
                            }
                        }
                        // Online si falta menos de 10 min
                        if($secondsLeft > 0 && $secondsLeft <= 600) $statusClass = "online";
                    }
                ?>

                <div class="event-row" data-type="<?php echo $evType; ?>">
                    <div class="ev-status <?php echo $statusClass; ?>"></div>
                    <span class="ev-name"><?php echo $evName; ?></span>
                    
                    <span class="ev-timer js-countdown" 
                          data-seconds="<?php echo $secondsLeft; ?>"
                          data-active="<?php echo $isActiveRange ? '1' : '0'; ?>" 
                          style="color: #c8a165; font-weight: 700; font-size: 11px; font-family: monospace;">
                        --:--:--
                    </span>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-3 text-center text-muted" style="font-size:12px;">Sin eventos activos.</div>
        <?php endif; ?>
    </div>
</div>

<script>
// 1. FUNCIÓN DE FILTRADO (Dropdown)
function filterEventsByType(selectedType) {
    const rows = document.querySelectorAll('.event-row');
    rows.forEach(row => {
        if (selectedType === 'all' || row.getAttribute('data-type') === selectedType) {
            row.style.display = 'flex'; // O el display que use tu CSS original
        } else {
            row.style.display = 'none';
        }
    });
}

// 2. FUNCIÓN DE CUENTA REGRESIVA
document.addEventListener('DOMContentLoaded', function() {
    const timers = document.querySelectorAll('.js-countdown');

    function updateTimers() {
        timers.forEach(timer => {
            let seconds = parseInt(timer.getAttribute('data-seconds'));
            let isActive = timer.getAttribute('data-active') === '1';
            
            if(seconds === -1) {
                timer.innerHTML = "--:--:--";
                return;
            }

            if(seconds > 0) {
                seconds--;
                timer.setAttribute('data-seconds', seconds);
            }

            // Visualización
            if(seconds <= 0 && !isActive) {
                // Si llegó a 0 y NO era un rango (es decir, un daily que inicia)
                timer.innerHTML = "<span style='color:#0f0; animation: pulse 1s infinite;'>¡Iniciando!</span>";
                let row = timer.closest('.event-row');
                if(row) row.querySelector('.ev-status').className = 'ev-status online';
            } else {
                // Formatear HH:MM:SS
                // Si son muchas horas (días), mostramos días
                let days = Math.floor(seconds / (3600 * 24));
                let h = Math.floor((seconds % (3600 * 24)) / 3600);
                let m = Math.floor((seconds % 3600) / 60);
                let s = Math.floor(seconds % 60);

                let hStr = h < 10 ? "0" + h : h;
                let mStr = m < 10 ? "0" + m : m;
                let sStr = s < 10 ? "0" + s : s;

                // Si está ACTIVO (Weekly en curso), lo mostramos en Verde indicando cuánto falta para cerrar
                if(isActive) {
                     // Formato: "Cierra en 2d 10:00:00" o solo tiempo
                     let prefix = days > 0 ? days + "d " : "";
                     timer.innerHTML = `<span style="color:#4caf50;">Termina: ${prefix}${hStr}:${mStr}:${sStr}</span>`;
                } else {
                    // Cuenta normal para iniciar
                    if(days > 0) {
                        timer.innerHTML = `${days}d ${hStr}:${mStr}:${sStr}`;
                    } else {
                        timer.innerHTML = `${hStr}:${mStr}:${sStr}`;
                    }
                }
                
                // Color online para Daily próximos (10 min)
                if(!isActive && seconds < 600 && seconds > 0) {
                    let row = timer.closest('.event-row');
                    if(row) row.querySelector('.ev-status').className = 'ev-status online';
                }
            }
        });
    }

    updateTimers();
    setInterval(updateTimers, 1000);
});
</script>

<style>
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
.ev-timer { font-variant-numeric: tabular-nums; }
</style>

</div>