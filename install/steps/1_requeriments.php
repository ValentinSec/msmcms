<?php
/**
 * Paso 1: Requisitos del Servidor (PRODUCCIÓN)
 */

$minPhpVersion = '8.1.0';
$requirements = [
    'php' => [
        'label' => 'Versión de PHP ' . $minPhpVersion . '+',
        'status' => version_compare(PHP_VERSION, $minPhpVersion, '>='),
        'current' => PHP_VERSION
    ],
    'pdo' => [
        'label' => 'Extensión PDO',
        'status' => extension_loaded('pdo')
    ],
    'drivers' => [
        'label' => 'Drivers SQL Server (sqlsrv o dblib)',
        'status' => (extension_loaded('pdo_sqlsrv') || extension_loaded('pdo_dblib'))
    ],
    'openssl' => [
        'label' => 'Extensión OpenSSL',
        'status' => extension_loaded('openssl')
    ],
    'curl' => [
        'label' => 'Extensión cURL',
        'status' => extension_loaded('curl')
    ],
    'json' => [
        'label' => 'Extensión JSON',
        'status' => extension_loaded('json')
    ],
    'write_config' => [
        'label' => 'Permisos de Escritura: /includes/config/',
        'status' => is_writable(__DIR__ . '/../../includes/config/')
    ]
];

$canProceed = true;
foreach($requirements as $req) {
    if(!$req['status']) $canProceed = false;
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h4 class="mb-0"><i class="fa fa-server me-2"></i> 1. Verificación de Requisitos</h4>
    </div>
    <div class="card-body">
        <p class="text-muted">Antes de instalar <strong>MuServerMaker CMS</strong>, verifique que su hosting cumpla con lo siguiente:</p>
        
        <ul class="list-group mb-4">
            <?php foreach($requirements as $key => $req): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <?php echo $req['label']; ?>
                        <?php if(isset($req['current'])): ?>
                            <small class="text-muted ms-2">(Detectado: <?php echo $req['current']; ?>)</small>
                        <?php endif; ?>
                    </span>
                    
                    <?php if($req['status']): ?>
                        <span class="badge bg-success rounded-pill"><i class="fa fa-check"></i> OK</span>
                    <?php else: ?>
                        <span class="badge bg-danger rounded-pill"><i class="fa fa-times"></i> ERROR</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="text-end">
            <?php if($canProceed): ?>
                <a href="index.php?step=2" class="btn btn-primary btn-lg px-5">
                    Siguiente Paso <i class="fa fa-arrow-right ms-2"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary btn-lg px-5" disabled>
                    Corrija los errores para continuar
                </button>
                <div class="alert alert-danger mt-3 text-start">
                    <i class="fa fa-exclamation-triangle"></i> Por favor, instale las extensiones faltantes o corrija los permisos.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>