<?php
/**
 * Paso 2: Conexión a Base de Datos (PRODUCCIÓN)
 */

$error = '';

if(isset($_POST['check_connection'])) {
    $host = trim($_POST['db_host']);
    $port = trim($_POST['db_port']);
    $user = trim($_POST['db_user']);
    $pass = trim($_POST['db_pass']);
    $name = trim($_POST['db_name']);

    if(empty($host) || empty($user) || empty($name)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            // Intentamos conectar
            $dsn = "sqlsrv:Server=$host,$port;Database=$name";
            if(!in_array('sqlsrv', PDO::getAvailableDrivers())) {
                $dsn = "dblib:host=$host:$port;dbname=$name";
            }

            $conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            // Si conecta, guardamos en sesión
            $_SESSION['db_host'] = $host;
            $_SESSION['db_port'] = $port;
            $_SESSION['db_user'] = $user;
            $_SESSION['db_pass'] = $pass;
            $_SESSION['db_name'] = $name;

            // REDIRECCIÓN SEGURA VÍA JS
            echo '<script>window.location.href = "index.php?step=3";</script>';
            exit;

        } catch(PDOException $e) {
            $error = "Error de conexión: " . $e->getMessage();
        }
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h4 class="mb-0"><i class="fa fa-database me-2"></i> 2. Conexión a SQL Server</h4>
    </div>
    <div class="card-body">
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Host / IP del VPS</label>
                    <input type="text" name="db_host" class="form-control" placeholder="127.0.0.1" value="<?php echo $_POST['db_host'] ?? '127.0.0.1'; ?>" required>
                    <div class="form-text">Si usas Docker, prueba <code>host.docker.internal</code></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Puerto</label>
                    <input type="number" name="db_port" class="form-control" value="<?php echo $_POST['db_port'] ?? '1433'; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Nombre de la Base de Datos</label>
                <input type="text" name="db_name" class="form-control" placeholder="MuOnline" value="<?php echo $_POST['db_name'] ?? 'MuOnline'; ?>" required>
                <div class="form-text">La DB del juego debe existir previamente.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Usuario SQL</label>
                    <input type="text" name="db_user" class="form-control" placeholder="sa" value="<?php echo $_POST['db_user'] ?? 'sa'; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Contraseña SQL</label>
                    <input type="password" name="db_pass" class="form-control" required>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="index.php?step=1" class="btn btn-outline-secondary">Atrás</a>
                <button type="submit" name="check_connection" class="btn btn-primary btn-lg px-5">
                    Probar Conexión <i class="fa fa-plug ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>