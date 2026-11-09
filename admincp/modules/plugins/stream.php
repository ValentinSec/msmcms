<?php
/**
 * Stream Manager Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */
if(!defined('access') or !access) die();

// Ruta ajustada
$streamFile = __DIR__ . '/../../../includes/config/stream_config.json';

// CREAR ARCHIVO SI NO EXISTE
if (!file_exists($streamFile)) {
    $dir = dirname($streamFile);
    if(!is_dir($dir)) @mkdir($dir, 0777, true);
    file_put_contents($streamFile, json_encode(['module_active' => 'off', 'streamers' => []], JSON_PRETTY_PRINT));
}

// LEER CONFIGURACIÓN
$streamConfig = json_decode(file_get_contents($streamFile), true);
if (!isset($streamConfig['streamers'])) $streamConfig['streamers'] = [];

// PROCESAR ADD
if (isset($_POST['add_streamer'])) {
    $platform = $_POST['platform'];
    $user = trim($_POST['username']);
    
    if (!empty($user)) {
        $id = uniqid();
        $streamConfig['streamers'][] = [
            'id' => $id,
            'platform' => $platform,
            'user' => $user
        ];
        file_put_contents($streamFile, json_encode($streamConfig, JSON_PRETTY_PRINT));
        echo '<script>window.location.href="index.php?module=my_plugins&load_plugin=stream";</script>';
    }
}

// PROCESAR DELETE
if (isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    foreach ($streamConfig['streamers'] as $key => $st) {
        if ($st['id'] == $deleteId) {
            unset($streamConfig['streamers'][$key]);
        }
    }
    $streamConfig['streamers'] = array_values($streamConfig['streamers']);
    file_put_contents($streamFile, json_encode($streamConfig, JSON_PRETTY_PRINT));
    echo '<script>window.location.href="index.php?module=my_plugins&load_plugin=stream";</script>';
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

echo '<style>
    .settings-section { background-color: #1b1b28; border-radius: 8px; padding: 25px; border: 1px solid #2b2b40; margin-bottom: 20px; }
    .settings-title { font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 20px; border-bottom: 1px solid #2b2b40; padding-bottom: 10px; }
    .form-control-dark { background-color: #151521; border: 1px solid #2b2b40; color: #fff; width: 100%; padding: 10px; border-radius: 6px; outline: none; }
    .form-control-dark:focus { border-color: #3699ff; }
    .btn-save-green { background-color: #00d084; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; }
    .btn-save-green:hover { background-color: #00b372; }
    .btn-delete { background-color: #f1416c; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
    
    .stream-table { width: 100%; color: #fff; }
    .stream-table th { text-align: left; padding: 10px; color: #7e8299; border-bottom: 1px solid #2b2b40; }
    .stream-table td { padding: 10px; border-bottom: 1px solid #2b2b40; }
</style>';

echo '<div class="container-fluid">';
    
    // Header
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            echo '<h3 style="color:#fff; font-weight:700; margin-bottom: 20px;">Administrar Streams</h3>';
            
            echo '<a href="index.php?module=my_plugins" style="color: #7e8299; text-decoration: none; margin-bottom: 20px; display: inline-block;">';
                echo '<i class="fa fa-arrow-left"></i> Volver a Mis Plugins';
            echo '</a>';
        echo '</div>';
    echo '</div>';

    // Formulario Agregar
    echo '<div class="row">';
        echo '<div class="col-md-12">';
            echo '<div class="settings-section">';
                echo '<h3 class="settings-title">Agregar Nuevo Streamer</h3>';
                echo '<form action="" method="POST">';
                    echo '<div class="row">';
                        
                        echo '<div class="col-md-4">';
                            echo '<label style="color:#7e8299; display:block; margin-bottom:5px;">Plataforma</label>';
                            echo '<select name="platform" class="form-control-dark">';
                                echo '<option value="twitch">Twitch</option>';
                                echo '<option value="kick">Kick</option>';
                            echo '</select>';
                        echo '</div>';

                        echo '<div class="col-md-5">';
                            echo '<label style="color:#7e8299; display:block; margin-bottom:5px;">Usuario / Canal</label>';
                            echo '<input type="text" name="username" class="form-control-dark" placeholder="Ej: coscu (Twitch/Kick)">';
                        echo '</div>';

                        echo '<div class="col-md-3">';
                            echo '<label style="visibility:hidden; display:block; margin-bottom:5px;">Accion</label>';
                            echo '<button type="submit" name="add_streamer" class="btn-save-green">Agregar Streamer</button>';
                        echo '</div>';

                    echo '</div>';
                echo '</form>';
            echo '</div>';
        echo '</div>';
    echo '</div>';

    // Tabla Lista
    echo '<div class="row">';
        echo '<div class="col-md-12">';
            echo '<div class="settings-section">';
                echo '<h3 class="settings-title">Lista de Streamers Activos</h3>';
                echo '<table class="stream-table">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th>Plataforma</th>';
                            echo '<th>Usuario</th>';
                            echo '<th>Acciones</th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        
                        if(!empty($streamConfig['streamers'])) {
                            foreach($streamConfig['streamers'] as $st) {
                                echo '<tr>';
                                    
                                    // Plataforma Icono
                                    echo '<td>';
                                    if($st['platform'] == 'twitch') {
                                        echo '<i class="fab fa-twitch" style="color:#9146ff; font-size:16px;"></i> Twitch';
                                    } elseif($st['platform'] == 'kick') {
                                        echo '<i class="fas fa-k" style="color:#53fc18; font-weight:900; font-size:16px;"></i> Kick';
                                    }
                                    echo '</td>';

                                    // Usuario
                                    echo '<td>' . htmlspecialchars($st['user']) . '</td>';

                                    // Botón Borrar
                                    echo '<td>';
                                        echo '<form action="" method="POST" style="display:inline;" onsubmit="return confirm(\'¿Eliminar?\');">';
                                            echo '<input type="hidden" name="delete_id" value="' . $st['id'] . '">';
                                            echo '<button type="submit" class="btn-delete"><i class="fa fa-trash"></i> Eliminar</button>';
                                        echo '</form>';
                                    echo '</td>';

                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" style="text-align:center; color:#7e8299;">No hay streamers configurados.</td></tr>';
                        }

                    echo '</tbody>';
                echo '</table>';
            echo '</div>';
        echo '</div>';
    echo '</div>';

echo '</div>';
?>