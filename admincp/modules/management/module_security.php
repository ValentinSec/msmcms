<?php
/**
 * Security Configuration Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

$secFile = __PATH_INCLUDES__ . 'config/security_config.json';
$secConfig = file_exists($secFile) ? json_decode(file_get_contents($secFile), true) : [
    'recaptcha_active' => 0, 'site_key' => '', 'secret_key' => '',
    'analytics_active' => 0, 'analytics_key' => '', 'analytics_extra' => '',
    'ddos_active' => 0, 'ddos_limit' => 10, 'ddos_response' => 500,
    'blocked_ips' => ''
];

$successMsg = '';

if(isset($_POST['save_security'])) {
    $secConfig = [
        'recaptcha_active' => isset($_POST['recaptcha_active']) ? 1 : 0,
        'site_key' => trim($_POST['site_key']),
        'secret_key' => trim($_POST['secret_key']),
        'analytics_active' => isset($_POST['analytics_active']) ? 1 : 0,
        'analytics_key' => trim($_POST['analytics_key']),
        'analytics_extra' => trim($_POST['analytics_extra']),
        'ddos_active' => isset($_POST['ddos_active']) ? 1 : 0,
        'ddos_limit' => (int)$_POST['ddos_limit'],
        'ddos_response' => (int)$_POST['ddos_response'],
        'blocked_ips' => trim($_POST['blocked_ips'])
    ];
    file_put_contents($secFile, json_encode($secConfig, JSON_PRETTY_PRINT));
    
    $successMsg = '<div class="alert-success-custom">Configuración de seguridad actualizada correctamente.</div>';
}

// Datos para el grafico: contador propio del CMS (includes/visit.counter.php)
$labels = array(); $counts = array(); $viewCounts = array();
try {
    if(@include_once(__PATH_INCLUDES__ . 'visit.counter.php')) {
        foreach(msm_visits_read(7) as $day => $v) {
            $labels[] = date('d/m', strtotime($day));
            $counts[] = (int)$v['visits'];
            $viewCounts[] = (int)$v['views'];
        }
    }
} catch (Throwable $e) { }
if(empty($labels)) {
    for($i = 6; $i >= 0; $i--) { $labels[] = date('d/m', strtotime("-$i days")); $counts[] = 0; $viewCounts[] = 0; }
}

// Valores escapados solo para mostrarlos en el formulario (ya se guardaron sin escapar)
foreach(array('site_key', 'secret_key', 'analytics_key', 'analytics_extra', 'blocked_ips') as $__k) {
    $secConfig[$__k] = htmlspecialchars(isset($secConfig[$__k]) ? (string)$secConfig[$__k] : '', ENT_QUOTES, 'UTF-8');
}
foreach(array('ddos_limit', 'ddos_response') as $__k) {
    $secConfig[$__k] = isset($secConfig[$__k]) ? (int)$secConfig[$__k] : 0;
}
unset($__k);

// --- RENDERIZADO HTML (PHP PURO) ---

// Mensaje de éxito (si existe, se muestra antes del script para mantener flujo original o dentro del container)
if(!empty($successMsg)) {
    echo $successMsg;
}

echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

echo '<style>
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';

echo '<div class="container-fluid">';
    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">';
                    echo '<h3 style="margin:0; color:#fff; font-weight:600; font-size: 18px;"><i class="fa fa-shield-alt"></i> Panel de Seguridad</h3>';
                    echo '<button type="submit" name="save_security" class="btn-save-green">Guardar Cambios</button>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Settings Grid
        echo '<div class="row row-flex">';
            
            // Recaptcha
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Google Recaptcha</h3>';
                    echo '<div class="switch-list-item">';
                        echo '<label class="switch"><input type="checkbox" name="recaptcha_active" ' . ($secConfig['recaptcha_active'] ? 'checked' : '') . '><span class="slider"></span></label>';
                        echo '<span class="switch-label-text" style="color:#64748b; font-size:13px; margin-left:10px;">Activar (AntiBots & SQL Attacks)</span>';
                    echo '</div>';
                    echo '<label class="settings-label">Public Key</label>';
                    echo '<input type="text" name="site_key" class="form-control-dark" value="' . $secConfig['site_key'] . '">';
                    echo '<label class="settings-label" style="margin-top:10px;">Secret Key</label>';
                    echo '<input type="password" name="secret_key" class="form-control-dark" value="' . $secConfig['secret_key'] . '">';
                echo '</div>';
            echo '</div>';

            // Analytics
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Google Analytics</h3>';
                    echo '<div class="switch-list-item">';
                        echo '<label class="switch"><input type="checkbox" name="analytics_active" ' . ($secConfig['analytics_active'] ? 'checked' : '') . '><span class="slider"></span></label>';
                        echo '<span class="switch-label-text" style="color:#64748b; font-size:13px; margin-left:10px;">Activar (Traffic Control)</span>';
                    echo '</div>';
                    echo '<label class="settings-label">Analytics Key</label>';
                    echo '<input type="text" name="analytics_key" class="form-control-dark" value="' . $secConfig['analytics_key'] . '">';
                    echo '<label class="settings-label" style="margin-top:10px;">Extra Key (Optional)</label>';
                    echo '<input type="text" name="analytics_extra" class="form-control-dark" value="' . $secConfig['analytics_extra'] . '">';
                echo '</div>';
            echo '</div>';

            // DDoS
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">DDoS Protection</h3>';
                    echo '<div class="switch-list-item">';
                        echo '<label class="switch"><input type="checkbox" name="ddos_active" ' . ($secConfig['ddos_active'] ? 'checked' : '') . '><span class="slider"></span></label>';
                        echo '<span class="switch-label-text" style="color:#64748b; font-size:13px; margin-left:10px;">Activar DDoS Protection</span>';
                    echo '</div>';
                    echo '<label class="settings-label">Limit Page x IP</label>';
                    echo '<input type="number" name="ddos_limit" class="form-control-dark" value="' . $secConfig['ddos_limit'] . '">';
                    echo '<label class="settings-label" style="margin-top:10px;">Limit response time (ms)</label>';
                    echo '<input type="number" name="ddos_response" class="form-control-dark" value="' . $secConfig['ddos_response'] . '">';
                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row Flex

        // Blocked IPs
        echo '<div class="row" style="margin-top:25px;">';
            echo '<div class="col-lg-12">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Bloqueo por IP</h3>';
                    echo '<textarea name="blocked_ips" class="form-control-dark" style="height: 100px; font-family: monospace;" placeholder="Una IP por línea...">' . $secConfig['blocked_ips'] . '</textarea>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Traffic Chart
        echo '<div class="row" style="margin-top:25px;">';
            echo '<div class="col-lg-12">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Análisis de Tráfico Semanal</h3>';
                    echo '<div style="height:250px;"><canvas id="securityTrafficChart"></canvas></div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

    echo '</form>';
echo '</div>';

// JavaScript for Chart
echo '<script>
const ctx = document.getElementById("securityTrafficChart").getContext("2d");
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, "rgba(16, 185, 129, 0.2)");
gradient.addColorStop(1, "rgba(16, 185, 129, 0)");
new Chart(ctx, {
    type: "line",
    data: {
        labels: ' . json_encode($labels) . ',
        datasets: [{
            label: "Visitas",
            data: ' . json_encode($counts) . ',
            borderColor: "#10b981",
            backgroundColor: gradient,
            borderWidth: 2,
            pointRadius: 3,
            tension: 0.3,
            fill: true
        }, {
            label: "P\u00e1ginas vistas",
            data: ' . json_encode($viewCounts) . ',
            borderColor: "#60a5fa",
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 2,
            tension: 0.3,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, labels: { color: "#8b95a7", boxWidth: 12 } } },
        scales: {
            y: { beginAtZero: true, grid: { color: "rgba(255, 255, 255, 0.05)" }, ticks: { color: "#64748b", precision: 0 } },
            x: { grid: { display: false }, ticks: { color: "#64748b" } }
        }
    }
});
</script>';