<?php
/**
 * Paso 5: Autodestrucción y Finalización (PRODUCCIÓN)
 */

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rrmdir($dir);
}

function rrmdir($dir) {
    if (is_dir($dir)) return rmdir($dir);
    return false;
}

if (isset($_POST['self_destruct'])) {
    $installerPath = realpath(__DIR__ . '/../');
    // Validar nombre de carpeta por seguridad
    if (basename($installerPath) === 'install' || basename($installerPath) === 'installer') {
        deleteDirectory($installerPath);
        header("Location: ../index.php");
        exit;
    } else {
        $error = "Por seguridad, la carpeta debe llamarse 'install' para auto-eliminarse.";
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h4 class="mb-0"><i class="fa fa-trash-alt me-2"></i> 5. Limpieza de Seguridad</h4>
    </div>
    <div class="card-body text-center">
        
        <div class="py-4">
            <i class="fa fa-shield-alt fa-5x text-success mb-3"></i>
            <h2 class="text-success">¡Instalación Exitosa!</h2>
            <p class="lead">Tu CMS <strong>MuServerMaker</strong> ya está configurado y listo para usar.</p>
        </div>

        <div class="alert alert-warning d-inline-block text-start" style="max-width: 600px;">
            <h5><i class="fa fa-exclamation-triangle"></i> ¡Atención!</h5>
            <p class="mb-0">
                Por razones de seguridad, es <strong>obligatorio</strong> eliminar el instalador antes de hacer público tu servidor. 
                Cualquier persona que acceda a esta carpeta podría reconfigurar tu base de datos.
            </p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="mt-5">
            <p class="text-muted">Selecciona una opción para finalizar:</p>
            
            <form method="post" class="d-grid gap-2 d-md-block">
                <button type="submit" name="self_destruct" class="btn btn-danger btn-lg px-4 me-md-2">
                    <i class="fa fa-bomb me-2"></i> Borrar Instalador e Ir a la Web
                </button>

                <a href="../admincp/" class="btn btn-outline-secondary btn-lg px-4">
                    Borraré manualmente (Ir al AdminCP)
                </a>
            </form>
        </div>
        
    </div>
</div>