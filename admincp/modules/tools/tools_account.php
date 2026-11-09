<?php
/**
 * Tools Account - Sistema Unificado (Lista + Edición + Kick + PJs)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * Versión Final: Incluye Kick y Visor de Personajes
 */

if (!defined("access") or !access) die();

// --------------------------------------------------------------------
// 1. CONFIGURACIÓN & DB
// --------------------------------------------------------------------
$serverFile = __DIR__ . '/../../../includes/config/muservermaker.json';
$settingsFile = __DIR__ . '/../../../includes/config/custom_settings.json';

$serverData = file_exists($serverFile) ? json_decode(file_get_contents($serverFile), true) : [];
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$dbName = isset($serverData['SQL_DB_NAME']) ? $serverData['SQL_DB_NAME'] : 'MuOnline';
$db = Connection::Database('Me_MuOnline');

// Función helper para clases
function getClassName($code) {
    $classNames = [
        0=>'Dark Wizard', 1=>'Soul Master', 2=>'Grand Master', 3=>'Soul Wizard',
        16=>'Dark Knight', 17=>'Blade Knight', 18=>'Blade Master', 19=>'Dragon Knight',
        32=>'Fairy Elf', 33=>'Muse Elf', 34=>'High Elf', 35=>'Noble Elf',
        48=>'Magic Gladiator', 50=>'Duel Master', 49=>'Duel Master', 
        64=>'Dark Lord', 66=>'Lord Emperor', 65=>'Lord Emperor',
        80=>'Summoner', 81=>'Bloody Summoner', 82=>'Dimension Master', 83=>'Dimension Master',
        96=>'Rage Fighter', 98=>'Fist Master'
    ];
    return isset($classNames[$code]) ? $classNames[$code] : 'Unknown (' . $code . ')';
}

$currentModule = isset($_GET['module']) ? $_GET['module'] : 'tools/tools_account';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// =================================================================================
// VISTA: PANEL DE EDICIÓN (Cuando action == 'edit_info')
// =================================================================================
if ($action == 'edit_info') {
    
    $targetUser = isset($_GET['user']) ? strip_tags($_GET['user']) : '';
    
    if (empty($targetUser)) {
        echo '<div class="alert alert-danger">Usuario no especificado. <a href="index.php?module='.$currentModule.'">Volver</a></div>';
        return;
    }

    // Helpers configuración
    function getConf($key, $default, $arr) { return (isset($arr[$key]) && !empty($arr[$key])) ? $arr[$key] : $default; }
    
    $tbl_coins = getConf('table_coins', 'CashShopData', $settings);
    $col_wcoinc = getConf('col_coin_1', 'WCoinC', $settings);
    $col_wcoinp = getConf('col_coin_2', 'WCoinP', $settings);
    $col_goblin = getConf('col_coin_3', 'GoblinPoint', $settings);
    $col_user_coins = ($tbl_coins == 'MEMB_INFO') ? 'memb___id' : 'AccountID';

    $message = '';

    // --- PROCESAR POST (Guardar y Kick) ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            // ACCIÓN 1: KICK (Desconectar)
            if (isset($_POST['action_kick'])) {
                $db->query("UPDATE [$dbName].[dbo].[MEMB_STAT] SET ConnectStat = 0 WHERE memb___id = ?", [$targetUser]);
                $message = '<div class="alert-success-custom"><i class="fa fa-plug"></i> Cuenta desconectada exitosamente.</div>';
            } 
            // ACCIÓN 2: GUARDAR DATOS
            else {
                if (!empty($_POST['edit_pass'])) {
                    $db->query("UPDATE [$dbName].[dbo].[MEMB_INFO] SET memb__pwd = ? WHERE memb___id = ?", [$_POST['edit_pass'], $targetUser]);
                }
                if (isset($_POST['edit_mail'])) {
                    $db->query("UPDATE [$dbName].[dbo].[MEMB_INFO] SET mail_addr = ? WHERE memb___id = ?", [$_POST['edit_mail'], $targetUser]);
                }
                if (isset($_POST['edit_code'])) {
                    $newCode = $_POST['edit_code'];
                    if (strlen($newCode) > 1 && strlen($newCode) <= 18) { 
                         $db->query("UPDATE [$dbName].[dbo].[MEMB_INFO] SET sno__numb = ? WHERE memb___id = ?", [$newCode, $targetUser]);
                    }
                }
                $message = '<div class="alert-success-custom">Datos actualizados correctamente.</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }

    // --- LEER DATOS ---
    $accData = $db->query_fetch_single("SELECT * FROM [$dbName].[dbo].[MEMB_INFO] WHERE memb___id = ?", [$targetUser]);
    if (!$accData) {
        echo '<div class="alert alert-danger">Cuenta no encontrada. <a href="index.php?module='.$currentModule.'">Volver</a></div>';
        return;
    }

    // Leer Coins
    $coinData = $db->query_fetch_single("SELECT $col_wcoinc, $col_wcoinp, $col_goblin FROM [$dbName].[dbo].[$tbl_coins] WHERE $col_user_coins = ?", [$targetUser]);

    // Leer Estado Online
    $statData = $db->query_fetch_single("SELECT ConnectStat, IP FROM [$dbName].[dbo].[MEMB_STAT] WHERE memb___id = ?", [$targetUser]);
    $isOnline = ($statData && $statData['ConnectStat'] == 1);
    $lastIP = ($statData) ? $statData['IP'] : 'N/A';

    // Leer Personajes
    $charList = $db->query_fetch("SELECT Name, Class, cLevel, ResetCount FROM [$dbName].[dbo].[Character] WHERE AccountId = ?", [$targetUser]);

    // Valores para inputs
    $val_pass = isset($accData['memb__pwd']) ? $accData['memb__pwd'] : (isset($accData['MEMB__PWD']) ? $accData['MEMB__PWD'] : '');
    $val_mail = isset($accData['mail_addr']) ? $accData['mail_addr'] : '';
    $val_code = isset($accData['sno__numb']) ? $accData['sno__numb'] : '';

    $val_c1 = isset($coinData[$col_wcoinc]) ? number_format($coinData[$col_wcoinc]) : '0';
    $val_c2 = isset($coinData[$col_wcoinp]) ? number_format($coinData[$col_wcoinp]) : '0';
    $val_c3 = isset($coinData[$col_goblin]) ? number_format($coinData[$col_goblin]) : '0';

    // --- RENDERIZADO HTML (Editor) ---
    echo '<style>
        .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .form-control-dark { background-color: #1b1b28; border: 1px solid #2b2b40; color: #92929f; padding: 10px 15px; width: 100%; border-radius: 4px; font-size: 13px; transition: 0.3s; }
        .form-control-dark:focus { border-color: #3699ff; color: #fff; outline: none; }
        .form-control-dark:disabled, .form-control-dark[readonly] { background-color: #151521; border-color: #2b2b40; color: #5e6278; cursor: not-allowed; }
        .settings-section { background: #1e1e2d; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 20px 0 rgba(76, 87, 125, 0.02); border: 1px solid #2b2b40; height: 100%; }
        .settings-title { font-size: 16px; font-weight: 600; color: #fff; margin-top: 0; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #2b2b40; }
        .settings-label { font-size: 12px; color: #6c7293; font-weight: 600; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group { margin-bottom: 20px; }
        
        .btn-save-green { background: #1bc5bd; color: #fff; border: none; padding: 10px 25px; font-weight: 600; border-radius: 4px; cursor: pointer; transition: 0.3s; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        .btn-save-green:hover { background: #0bb7af; box-shadow: 0 0 15px rgba(27,197,189,0.3); }

        .btn-kick-red { background: #f64e60; color: #fff; border: none; padding: 10px 25px; font-weight: 600; border-radius: 4px; cursor: pointer; transition: 0.3s; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; margin-left: 10px; }
        .btn-kick-red:hover { background: #d63d4d; box-shadow: 0 0 15px rgba(246,78,96,0.3); }

        .btn-back-outline { background: transparent; color: #6c7293; border: 1px solid #2b2b40; padding: 10px 20px; font-weight: 600; border-radius: 4px; text-decoration: none; font-size: 12px; margin-right: 10px; transition: 0.2s; }
        .btn-back-outline:hover { border-color: #3699ff; color: #3699ff; }
        
        /* Tabla de Personajes */
        .char-table { width: 100%; border-collapse: collapse; }
        .char-table th { text-align: left; padding: 10px; color: #6c7293; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #2b2b40; }
        .char-table td { padding: 10px; border-bottom: 1px dashed #2b2b40; color: #b5b5c3; font-size: 13px; }
        .char-table tr:last-child td { border-bottom: none; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>';

    echo '<div class="container-fluid">';
        echo $message;
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">';
                            echo 'Editar: <span style="color:#f39c12">' . $targetUser . '</span>';
                            if($isOnline) {
                                echo '<span class="badge" style="background:#238636; font-size:10px; margin-left:10px;">ONLINE</span>';
                            } else {
                                echo '<span class="badge" style="background:#444; font-size:10px; margin-left:10px;">OFFLINE</span>';
                            }
                        echo '</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right" style="display: flex; justify-content: flex-end;">';
                        echo '<a href="index.php?module=' . $currentModule . '" class="btn-back-outline">Volver</a>';
                        
                        echo '<form action="" method="post" style="margin:0;">';
                            echo '<input type="hidden" name="action_kick" value="1">';
                            echo '<button type="submit" class="btn-kick-red" onclick="return confirm(\'¿Seguro deseas desconectar esta cuenta?\');">';
                                echo '<i class="fa fa-power-off"></i> Desconectar';
                            echo '</button>';
                        echo '</form>';
                        
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Form
        echo '<form action="" method="post">';
            echo '<div class="row" style="display:flex; flex-wrap:wrap;">';
                
                // Columna 1: Credenciales
                echo '<div class="col-md-6">';
                    echo '<div class="settings-section">';
                        echo '<h3 class="settings-title"><i class="fa fa-user-shield" style="color: #3699ff; margin-right: 8px;"></i> Credenciales</h3>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Usuario</label>';
                            echo '<input type="text" class="form-control-dark" value="' . $targetUser . '" disabled style="color: #fff; font-weight: bold;">';
                        echo '</div>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Contraseña</label>';
                            echo '<input type="text" name="edit_pass" class="form-control-dark" value="' . $val_pass . '" placeholder="Nueva contraseña">';
                        echo '</div>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Email</label>';
                            echo '<input type="email" name="edit_mail" class="form-control-dark" value="' . $val_mail . '" placeholder="email@ejemplo.com">';
                        echo '</div>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">Personal ID (IP: ' . $lastIP . ')</label>';
                            echo '<input type="text" name="edit_code" class="form-control-dark" value="' . $val_code . '">';
                        echo '</div>';
                        echo '<div class="text-right">';
                            echo '<button type="submit" class="btn-save-green" style="width:100%">Guardar Cambios</button>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';

                // Columna 2: Monedas
                echo '<div class="col-md-6">';
                    echo '<div class="settings-section">';
                        echo '<h3 class="settings-title"><i class="fa fa-coins" style="color: #f39c12; margin-right: 8px;"></i> Monedas (Lectura)</h3>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">' . $col_wcoinc . '</label>';
                            echo '<input type="text" class="form-control-dark" value="' . $val_c1 . '" readonly style="color: #f39c12; font-weight: bold;">';
                        echo '</div>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">' . $col_wcoinp . '</label>';
                            echo '<input type="text" class="form-control-dark" value="' . $val_c2 . '" readonly style="color: #3699ff; font-weight: bold;">';
                        echo '</div>';
                        echo '<div class="form-group">';
                            echo '<label class="settings-label">' . $col_goblin . '</label>';
                            echo '<input type="text" class="form-control-dark" value="' . $val_c3 . '" readonly style="color: #e74c3c; font-weight: bold;">';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';

                // Columna 3: Personajes
                echo '<div class="col-md-12" style="margin-top: 20px;">';
                    echo '<div class="settings-section">';
                        echo '<h3 class="settings-title"><i class="fa fa-users" style="color: #8950fc; margin-right: 8px;"></i> Personajes en la Cuenta</h3>';
                        
                        if(!empty($charList)) {
                            echo '<table class="char-table">';
                                echo '<thead>';
                                    echo '<tr>';
                                        echo '<th>Nombre</th>';
                                        echo '<th>Clase</th>';
                                        echo '<th>Nivel</th>';
                                        echo '<th>Resets</th>';
                                    echo '</tr>';
                                echo '</thead>';
                                echo '<tbody>';
                                    foreach($charList as $pj) {
                                        echo '<tr>';
                                            echo '<td style="font-weight:bold; color:#fff;">' . $pj['Name'] . '</td>';
                                            echo '<td>' . getClassName($pj['Class']) . '</td>';
                                            echo '<td>' . $pj['cLevel'] . '</td>';
                                            echo '<td>' . number_format($pj['ResetCount']) . '</td>';
                                        echo '</tr>';
                                    }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<div style="text-align:center; color:#5e6278; padding:20px;">';
                                echo 'Esta cuenta no tiene personajes creados.';
                            echo '</div>';
                        }
                    echo '</div>';
                echo '</div>';

            echo '</div>';
        echo '</form>';
    echo '</div>';

} else {
    // =================================================================================
    // VISTA: LISTADO DE CUENTAS (Default)
    // =================================================================================
    
    // Parámetros
    $search = isset($_REQUEST['search']) ? trim(strip_tags($_REQUEST['search'])) : '';
    $page = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
    if ($page < 1) $page = 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Filtros
    $whereSQL = "";
    $params = [];
    if (!empty($search)) {
        $whereSQL = "WHERE memb___id LIKE ?";
        $params[] = "%$search%";
    }

    // Paginación
    $countSql = "SELECT COUNT(*) as total FROM [$dbName].[dbo].[MEMB_INFO] $whereSQL";
    $countRow = $db->query_fetch_single($countSql, $params);
    $totalRecords = $countRow['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Consulta
    $sql = "SELECT memb___id, appl_days FROM [$dbName].[dbo].[MEMB_INFO] 
            $whereSQL 
            ORDER BY memb___id ASC 
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    $accounts = $db->query_fetch($sql, $params);
    
    // --- RENDERIZADO HTML (Lista) ---
    echo '<style>
        .account-list-container { background-color: #1e1e2d; border-radius: 8px; box-shadow: 0 0 20px 0 rgba(76, 87, 125, 0.02); border: 1px solid #2b2b40; padding: 20px; color: #fff; font-family: "Poppins", sans-serif; }
        .list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .list-title { font-size: 18px; font-weight: 600; color: #fff; margin: 0; }
        .search-box { display: flex; align-items: center; background: #151521; border: 1px solid #2b2b40; border-radius: 4px; padding: 5px 10px; }
        .search-input { background: transparent; border: none; color: #fff; outline: none; font-size: 13px; width: 200px; }
        .custom-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .custom-table th { text-align: left; padding: 12px 15px; color: #fff; font-size: 12px; font-weight: 700; border-bottom: 1px solid #2b2b40; text-transform: uppercase; }
        .custom-table td { padding: 12px 15px; border-bottom: 1px dashed #2b2b40; color: #b5b5c3; font-size: 13px; vertical-align: middle; }
        .custom-table tr:hover td { background-color: rgba(255,255,255,0.02); color: #fff; }
        .btn-edit { background: transparent; border: 1px solid #00d2ff; color: #00d2ff; padding: 5px 15px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; text-decoration: none; transition: 0.2s; display: inline-block; }
        .btn-edit:hover { background: #00d2ff; color: #000; box-shadow: 0 0 10px rgba(0, 210, 255, 0.4); }
        .list-footer { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #5e6278; border-top: 1px solid #2b2b40; padding-top: 20px; }
        .pagination { display: flex; list-style: none; margin: 0; padding: 0; gap: 5px; }
        .page-link { display: block; padding: 6px 12px; background: #151521; border: 1px solid #2b2b40; color: #92929f; text-decoration: none; border-radius: 4px; transition: 0.2s; }
        .page-link:hover, .page-link.active { background: #fff; color: #1e1e2d; border-color: #fff; font-weight: bold; }
    </style>';

    echo '<div class="container-fluid">';
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="account-list-container">';
                    
                    // Search Bar
                    echo '<div class="list-header">';
                        echo '<h3 class="list-title">Buscar Cuenta</h3>';
                        echo '<form action="" method="get" class="search-form" style="margin:0;">';
                            echo '<input type="hidden" name="module" value="' . htmlspecialchars($currentModule) . '">';
                            echo '<div class="search-box">';
                                echo '<span style="font-size: 12px; color: #92929f; margin-right: 10px; font-weight: 600;">Search:</span>';
                                echo '<input type="text" name="search" class="search-input" value="' . htmlspecialchars($search) . '" placeholder="Escribe una cuenta...">';
                            echo '</div>';
                        echo '</form>';
                    echo '</div>';

                    // Table
                    echo '<div class="table-responsive">';
                        echo '<table class="custom-table">';
                            echo '<thead>';
                                echo '<tr>';
                                    echo '<th width="40%">Cuenta</th>';
                                    echo '<th width="40%">Creada</th>';
                                    echo '<th width="20%" class="text-right">Editar</th>';
                                echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                                if (!empty($accounts)) {
                                    foreach ($accounts as $row) {
                                        echo '<tr>';
                                            echo '<td><span style="font-weight: 500; color:#fff;">' . htmlspecialchars($row['memb___id']) . '</span></td>';
                                            echo '<td>';
                                                $d = !empty($row['appl_days']) ? $row['appl_days'] : 'N/A';
                                                echo (strtotime($d) ? date("Y-m-d H:i:s", strtotime($d)) : $d);
                                            echo '</td>';
                                            echo '<td class="text-right">';
                                                echo '<a href="index.php?module=' . $currentModule . '&action=edit_info&user=' . urlencode($row['memb___id']) . '" class="btn-edit">Editar</a>';
                                            echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center" style="padding: 40px; color: #5e6278;">Sin resultados.</td></tr>';
                                }
                            echo '</tbody>';
                        echo '</table>';
                    echo '</div>';

                    // Pagination
                    echo '<div class="list-footer">';
                        echo '<div>Showing ' . ($offset + 1) . ' to ' . min($offset + $limit, $totalRecords) . ' of ' . $totalRecords . ' entries</div>';
                        
                        if ($totalPages > 1) {
                            echo '<ul class="pagination">';
                                if ($page > 1) {
                                    echo '<li><a href="index.php?module=' . $currentModule . '&search=' . urlencode($search) . '&p=' . ($page - 1) . '" class="page-link">Previous</a></li>';
                                }
                                
                                $range = 2;
                                for ($i = 1; $i <= $totalPages; $i++) {
                                    if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                                        $active = ($i == $page) ? 'active' : '';
                                        echo '<li><a href="index.php?module=' . $currentModule . '&search=' . urlencode($search) . '&p=' . $i . '" class="page-link ' . $active . '">' . $i . '</a></li>';
                                    } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                                        echo '<li><span class="page-link" style="border:none; background:transparent;">...</span></li>';
                                    }
                                }
                                
                                if ($page < $totalPages) {
                                    echo '<li><a href="index.php?module=' . $currentModule . '&search=' . urlencode($search) . '&p=' . ($page + 1) . '" class="page-link">Next</a></li>';
                                }
                            echo '</ul>';
                        }
                    echo '</div>';

                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}
?>