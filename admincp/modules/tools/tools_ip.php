<?php
/**
 * Tools: IP Search Manager Corregido - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */
if(!defined('access')) die();

header('Content-Type: text/html; charset=utf-8');

$results = [];
$searched = false;

try {
    $db = Connection::Database('Me_MuOnline');

    // 1. BUSCAR IP POR USUARIO (ID Exacto)
    if(isset($_POST['do_search_ip'])) {
        $searched = true;
        $user = trim($_POST['search_user']);
        if(!empty($user)) {
            $sql = "SELECT i.memb___id, s.IP, s.ConnectTM, s.ConnectStat 
                    FROM MEMB_INFO i
                    LEFT JOIN MEMB_STAT s ON i.memb___id = s.memb___id
                    WHERE i.memb___id = ?";
            $results = $db->query_fetch($sql, [$user]);
        }
    }

    // 2. BUSCAR USUARIOS POR IP (Búsqueda por coincidencia)
    if(isset($_POST['do_search_users'])) {
        $searched = true;
        $ip = trim($_POST['search_ip']);
        if(!empty($ip)) {
            $sql = "SELECT i.memb___id, s.IP, s.ConnectTM, s.ConnectStat 
                    FROM MEMB_INFO i
                    JOIN MEMB_STAT s ON i.memb___id = s.memb___id
                    WHERE s.IP LIKE ?
                    ORDER BY s.ConnectTM DESC";
            $results = $db->query_fetch($sql, ['%'.$ip.'%']);
        }
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger">'.$e->getMessage().'</div>';
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<div class="container-fluid">';
    echo '<div class="row">';
        echo '<div class="col-lg-12">';
            echo '<div class="settings-section">';
                echo '<h3 class="settings-title" style="border:none; margin-bottom: 20px;">Buscar IP</h3>';

                // Formulario 1: Buscar por Usuario
                echo '<form action="" method="post" accept-charset="UTF-8">';
                    echo '<div class="row" style="align-items: center; margin-bottom: 20px;">';
                        echo '<div class="col-md-3">';
                            echo '<label class="settings-label" style="margin:0;">Buscar IP por Usuario:</label>';
                        echo '</div>';
                        echo '<div class="col-md-7">';
                            $valUser = isset($_POST['search_user']) ? htmlspecialchars($_POST['search_user']) : '';
                            echo '<input type="text" name="search_user" class="form-control-dark" placeholder="Nombre de cuenta" value="' . $valUser . '">';
                        echo '</div>';
                        echo '<div class="col-md-2">';
                            echo '<button type="submit" name="do_search_ip" class="btn-search-darkblue">Buscar IP</button>';
                        echo '</div>';
                    echo '</div>';
                echo '</form>';

                echo '<div style="border-bottom: 1px dashed #334155; margin: 20px 0;"></div>';

                // Formulario 2: Buscar por IP
                echo '<form action="" method="post" accept-charset="UTF-8">';
                    echo '<div class="row" style="align-items: center; margin-bottom: 10px;">';
                        echo '<div class="col-md-3">';
                            echo '<label class="settings-label" style="margin:0;">Buscar Usuarios por IP:</label>';
                        echo '</div>';
                        echo '<div class="col-md-7">';
                            $valIP = isset($_POST['search_ip']) ? htmlspecialchars($_POST['search_ip']) : '';
                            echo '<input type="text" name="search_ip" class="form-control-dark" placeholder="IP Aqui" value="' . $valIP . '">';
                        echo '</div>';
                        echo '<div class="col-md-2">';
                            echo '<button type="submit" name="do_search_users" class="btn-search-cyan-filled">Buscar Usuarios</button>';
                        echo '</div>';
                    echo '</div>';
                echo '</form>';

                echo '<h3 class="section-subtitle">Cuentas Encontradas</h3>';

                // Tabla de Resultados
                echo '<div class="table-responsive">';
                    echo '<table class="table-accounts">';
                        echo '<thead>';
                            echo '<tr>';
                                echo '<th style="width: 25%;">Cuenta</th>';
                                echo '<th style="width: 25%;">Status</th>';
                                echo '<th style="width: 25%;">IP</th>';
                                echo '<th style="width: 25%;">Ultima Conexion</th>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                            
                            if($searched && is_array($results) && count($results) > 0) {
                                foreach($results as $row) {
                                    $isOnline = (isset($row['ConnectStat']) && $row['ConnectStat'] == 1);
                                    $status = $isOnline ? '<span class="status-badge status-online">Online</span>' : '<span class="status-badge status-offline">Offline</span>';
                                    $lastConn = !empty($row['ConnectTM']) ? $row['ConnectTM'] : 'N/A';
                                    $ipAddr = !empty($row['IP']) ? $row['IP'] : 'Sin registro';

                                    echo '<tr>';
                                        echo '<td><a href="index.php?module=tools_account&id=' . $row['memb___id'] . '" style="color: #10b981; font-weight: 600;">' . $row['memb___id'] . '</a></td>';
                                        echo '<td>' . $status . '</td>';
                                        echo '<td>' . $ipAddr . '</td>';
                                        echo '<td>' . $lastConn . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr>';
                                    echo '<td colspan="4" style="text-align: center; color: #64748b; padding: 30px;">';
                                        echo ($searched) ? 'No se encontraron resultados para la búsqueda actual.' : 'Utilice los formularios de arriba para buscar resultados.';
                                    echo '</td>';
                                echo '</tr>';
                            }
                            
                        echo '</tbody>';
                    echo '</table>';
                echo '</div>'; // End table-responsive
            echo '</div>'; // End settings-section
        echo '</div>'; // End col-12
    echo '</div>'; // End row
echo '</div>'; // End container-fluid
?>