<?php
/**
 * Paso 3: Instalación de Tablas (PRODUCCIÓN)
 */

// Verificar sesión
if(!isset($_SESSION['db_host'])) {
    echo '<script>window.location.href = "index.php?step=2";</script>';
    exit;
}

$prefix = "MUSERVERMAKER_";

// LISTA DE TABLAS A INSTALAR
$tables = [
    // Tablas Básicas y Logs
    'MSM_BANS.txt'               => 'BANS',
    'MSM_BAN_LOG.txt'            => 'BAN_LOG',
    'MSM_BLOCKED_IP.txt'         => 'BLOCKED_IP',
    'MSM_FLA.txt'                => 'FLA',
    
    // Sistema de Créditos y Pagos
    'MSM_CREDITS_CONFIG.txt'     => 'CREDITS_CONFIG',
    'MSM_CREDITS_LOGS.txt'       => 'CREDITS_LOGS',
    'MSM_PAYPAL.txt'             => 'PAYPAL_TRANSACTIONS',
    'MSM_MERCADOPAGO.txt'        => 'MERCADOPAGO_TRANSACTIONS',
    
    // Sistema de Cuentas
    'MSM_PASSCHANGE.txt'         => 'PASSCHANGE_REQUEST',
    'MSM_REGISTER.txt'           => 'REGISTER_ACCOUNT',
    'MSM_ACCOUNT_COUNTRY.txt'    => 'ACCOUNT_COUNTRY', 
    
    // [NUEVO] Columnas de Referidos (ALTER TABLE MEMB_INFO)
    'MSM_REFERRALS.txt'          => 'REFERRALS_DATA',
    
    // Sistema de Juego y Rankings
    'MSM_VOTES.txt'              => 'VOTES',
    'MSM_PVP_SYSTEM.txt'         => 'PVP_SYSTEM',
    'MSM_PVP_LOGS.txt'           => 'PVP_LOGS',
    'MSM_MONSTER_DIE.txt'        => 'MONSTER_DIE',
    'MSM_BOSS_RANKING.txt'       => 'BOSS_RANKING',
    
    // Sistema Web (Noticias, Plugins, Cron)
    'MSM_CRON.txt'               => 'CRON',
    
    // Sistema WebShop
    'MSM_WEBSHOP_ADMIN_LOG.txt'      => 'WEBSHOP_ADMIN_LOG',
    'MSM_WEBSHOP_LOG.txt'            => 'WEBSHOP_LOG',
    
    // Sistema Black Market
    'MSM_MARKET.txt'                 => 'MARKET_SELL',
    
    // Sistema Casino
    'MSM_CASINO_LOG.txt'             => 'CASINO_LOG',
	
	'MSM_CASINO_PENDING.txt'         => 'CASINO_PENDING',

    // Sistema de Cuentas: Recordarme
    'MSM_REMEMBER.txt'               => 'REMEMBER',
];

$procedures = [
    'MSM_BOSS_RANKING_PROC.txt'  => 'WZ_Custom_AddBossKill'
];

$logs = [];
$completed = false;

if(isset($_POST['run_install'])) {
    try {
        $dsn = "sqlsrv:Server=".$_SESSION['db_host'].",".$_SESSION['db_port'].";Database=".$_SESSION['db_name'];
        if(!in_array('sqlsrv', PDO::getAvailableDrivers())) {
            $dsn = "dblib:host=".$_SESSION['db_host'].":".$_SESSION['db_port'].";dbname=".$_SESSION['db_name'];
        }
        
        $conn = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Instalar Tablas
        foreach($tables as $file => $suffix) {
            $tableName = $prefix . $suffix;
            $filePath = __DIR__ . '/../sql/' . $file;

            if(!file_exists($filePath)) {
                $logs[] = ['type' => 'danger', 'msg' => "Archivo no encontrado: $file"];
                continue;
            }

            try {
                $sql = file_get_contents($filePath);
                $query = str_replace('{TABLE_PREFIX}', $prefix, $sql);
                $conn->exec($query); 
                $logs[] = ['type' => 'success', 'msg' => "Tabla <b>$tableName</b> creada correctamente."];
            } catch(Exception $e) {
                if(strpos($e->getMessage(), 'There is already an object') !== false) {
                    $logs[] = ['type' => 'warning', 'msg' => "La tabla <b>$tableName</b> ya existe. Omitida."];
                } else {
                    $logs[] = ['type' => 'danger', 'msg' => "Error creando $tableName: " . $e->getMessage()];
                }
            }
        }
        
        // Instalar Procedimientos
        foreach($procedures as $file => $proc) {
             $filePath = __DIR__ . '/../sql/' . $file;
             if(file_exists($filePath)) {
                 try {
                     $sql = file_get_contents($filePath);
                     $query = str_replace('{TABLE_PREFIX}', $prefix, $sql);
                     $conn->exec("IF OBJECT_ID('$proc', 'P') IS NOT NULL DROP PROCEDURE $proc");
                     $conn->exec($query);
                     $logs[] = ['type' => 'success', 'msg' => "Procedimiento <b>$proc</b> instalado."];
                 } catch(Exception $e) {
                     $logs[] = ['type' => 'danger', 'msg' => "Error procedure $proc: " . $e->getMessage()];
                 }
             }
        }

        $completed = true;

    } catch(PDOException $e) {
        $logs[] = ['type' => 'danger', 'msg' => "Error de Conexión SQL: " . $e->getMessage()];
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h4 class="mb-0"><i class="fa fa-hdd me-2"></i> 3. Instalación de Tablas</h4>
    </div>
    <div class="card-body">
        
        <?php if(!$completed): ?>
            <div class="text-center py-4">
                <i class="fa fa-server fa-4x text-primary mb-3"></i>
                <h3 class="mb-3">Listo para instalar</h3>
                <p>Se conectará a la base de datos <strong><?php echo $_SESSION['db_name']; ?></strong>.</p>
                <p>Se crearán <strong><?php echo count($tables); ?></strong> tablas con el prefijo:</p>
                <div class="alert alert-info d-inline-block px-5 py-2 fw-bold" style="font-size:1.2rem;">
                    <?php echo $prefix; ?>
                </div>
                <br><br>
                <form method="post">
                    <button type="submit" name="run_install" class="btn btn-success btn-lg px-5">
                        <i class="fa fa-play me-2"></i> Ejecutar Instalación
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="installation-results mb-4" style="max-height:400px; overflow-y:auto; border:1px solid #dee2e6; border-radius:5px;">
                <ul class="list-group list-group-flush">
                    <?php foreach($logs as $log): ?>
                        <li class="list-group-item list-group-item-<?php echo $log['type']; ?>">
                            <?php if($log['type']=='success') echo '<i class="fa fa-check me-2"></i>'; ?>
                            <?php if($log['type']=='warning') echo '<i class="fa fa-exclamation-circle me-2"></i>'; ?>
                            <?php if($log['type']=='danger') echo '<i class="fa fa-times me-2"></i>'; ?>
                            <?php echo $log['msg']; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="d-flex justify-content-end">
                <a href="index.php?step=4" class="btn btn-primary btn-lg">
                    Siguiente: Configurar Admin <i class="fa fa-arrow-right ms-2"></i>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>