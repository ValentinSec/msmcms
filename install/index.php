<?php
session_start();

// --- 0. PREPARACIÓN ---
// Capturamos el paso al inicio para usarlo en la validación de seguridad
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// --- 1. SEGURIDAD: VERIFICAR SI YA ESTÁ INSTALADO ---
// Ruta al archivo de configuración generado
$configFile = __DIR__ . '/../includes/config/muservermaker.json';
$fileExists = file_exists($configFile);

// LÓGICA DE BLOQUEO:
// Bloqueamos solo si el archivo existe Y NO estamos en el paso final (5)
$isLocked = ($fileExists && $step != 5);

// --- 2. LÓGICA DE FONDO DINÁMICO ---
$bgImages = [];
$baseDir = __DIR__ . '/assets/img/';
for ($i = 1; $i <= 14; $i++) {
    foreach (['jpg', 'jpeg', 'png'] as $ext) {
        if (file_exists($baseDir . "img{$i}.{$ext}")) {
            $bgImages[] = "assets/img/img{$i}.{$ext}";
            break; 
        }
    }
}

// --- 3. DATOS DE LOS PASOS ---
// $step ya se definió arriba
$totalSteps = 5;

$stepTitles = [
    1 => 'Requisitos',
    2 => 'Base de Datos',
    3 => 'Instalación',
    4 => 'Configuración',
    5 => 'Finalizar'
];

$progress = ($step / $totalSteps) * 100;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación MuServerMaker CMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/installer.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="bg-darker">

    <div class="bg-slideshow">
        <div class="bg-overlay"></div>
        <?php foreach($bgImages as $index => $img): ?>
            <div class="bg-slide" id="slide-<?php echo $index; ?>" 
                 style="background-image: url('<?php echo $img; ?>');">
            </div>
        <?php endforeach; ?>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                
                <div class="text-center mb-5 logo-brand">
                    <img src="assets/img/logo.png" alt="MuServerMaker Logo">
                    <p>CMS INSTALLER WIZARD</p>
                </div>

                <?php if ($isLocked): ?>
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white text-center py-3">
                            <h4 class="mb-0"><i class="fa fa-lock me-2"></i> Instalación Bloqueada</h4>
                        </div>
                        <div class="card-body text-center py-5">
                            <i class="fa fa-shield-alt fa-5x text-danger mb-4"></i>
                            <h2 class="text-white mb-3">El CMS ya está instalado</h2>
                            <p class="lead text-muted mb-4">
                                El sistema ha detectado un archivo de configuración existente. 
                                Por seguridad, el instalador ha sido deshabilitado.
                            </p>
                            
                            <div class="alert alert-warning d-inline-block text-start">
                                <strong>¿Quieres reinstalar?</strong><br>
                                Debes eliminar el archivo <code>includes/config/muservermaker.json</code> 
                                o borrar la base de datos manualmente antes de continuar.
                            </div>
                            
                            <div class="mt-5">
                                <a href="../index.php" class="btn btn-outline-light px-4">
                                    <i class="fa fa-home me-2"></i> Volver a la Web
                                </a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="progress mb-4" style="height: 6px; background-color: rgba(255,255,255,0.1);">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progress; ?>%; box-shadow: 0 0 10px #3b82f6;"></div>
                    </div>

                    <div class="d-flex justify-content-between mb-4 text-white position-relative step-indicator">
                        <?php foreach($stepTitles as $sNum => $sTitle): 
                            $isActive = ($step >= $sNum);
                            $activeClass = $isActive ? 'text-primary fw-bold' : 'text-muted';
                            $iconClass = ($step > $sNum) ? 'fa-check-circle' : 'fa-circle';
                            if($step == $sNum) $iconClass = 'fa-dot-circle';
                        ?>
                            <div class="text-center" style="font-size: 0.85rem; z-index: 2;">
                                <div class="mb-1"><i class="fa <?php echo $iconClass; ?> <?php echo $activeClass; ?>"></i></div>
                                <span class="<?php echo $activeClass; ?>"><?php echo $sTitle; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="installer-content">
                        <?php
                        $files = glob(__DIR__ . "/steps/{$step}_*.php");
                        if ($files && file_exists($files[0])) {
                            include $files[0];
                        } else {
                            echo '<div class="alert alert-danger shadow-lg">Error Crítico: No se encontró el archivo para el paso '.$step.'</div>';
                        }
                        ?>
                    </div>

                <?php endif; ?>

                <div class="text-center mt-5 text-muted small" style="opacity: 0.7;">
                    &copy; <?php echo date('Y'); ?> MuServerMaker CMS. Todos los derechos reservados.
                </div>

            </div>
        </div>
    </div>
    
    <div id="global-loader">
        <div class="gta-spinner"></div>
        <div class="loader-title">Procesando...</div>
        <div class="loader-subtitle" id="loader-tip">Iniciando motores...</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* LOGICA DEL SLIDESHOW */
        const totalSlides = <?php echo count($bgImages); ?>;
        let currentSlide = parseInt(sessionStorage.getItem('msm_bg_index')) || 0;
        if (currentSlide >= totalSlides) currentSlide = 0;

        function showCurrentSlide() {
            document.querySelectorAll('.bg-slide').forEach(el => el.classList.remove('active'));
            const activeEl = document.getElementById('slide-' + currentSlide);
            if(activeEl) activeEl.classList.add('active');
        }

        function nextSlide() {
            if(totalSlides <= 1) return;
            currentSlide = (currentSlide + 1) % totalSlides;
            sessionStorage.setItem('msm_bg_index', currentSlide);
            showCurrentSlide();
        }
        showCurrentSlide();
        if(totalSlides > 1) setInterval(nextSlide, 8000);

        /* LOGICA DEL LOADER */
        const loadingTips = [
            "Conectando con el Continente de MU...", "Afilando las Chaos Weapons...",
            "Configurando puertos...", "Invocando al Dark Lord...",
            "Verificando integridad...", "Limpiando logs antiguos...",
            "Cargando módulos...", "Estableciendo conexión SQL..."
        ];

        const loader = document.getElementById('global-loader');
        const tipElement = document.getElementById('loader-tip');
        let tipInterval;

        function showLoader() {
            if(!loader) return;
            loader.style.display = 'flex';
            changeTip();
            tipInterval = setInterval(changeTip, 2500);
        }

        function changeTip() {
            if(!tipElement) return;
            const randomIndex = Math.floor(Math.random() * loadingTips.length);
            tipElement.style.opacity = 0; 
            setTimeout(() => { tipElement.innerText = loadingTips[randomIndex]; tipElement.style.opacity = 1; }, 300);
        }

        // Solo activar loader si NO está bloqueado
        <?php if (!$isLocked): ?>
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) { if (this.checkValidity()) showLoader(); });
            });
            document.querySelectorAll('a.btn-primary, a.btn-success, a.btn-warning').forEach(btn => {
                btn.addEventListener('click', function(e) { if (!this.getAttribute('target')) showLoader(); });
            });
        <?php endif; ?>
    </script>
</body>
</html>