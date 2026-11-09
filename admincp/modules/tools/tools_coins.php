<?php
/**
 * Tools Coins - Dynamic Version (Reads from custom_settings.json)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */
if(!defined('access')) die();

// 1. CARGAR CONFIGURACI¨®N
$fileSettings = __DIR__ . '/../../../includes/config/custom_settings.json';
$config = [];
if(file_exists($fileSettings)) {
    $config = json_decode(file_get_contents($fileSettings), true);
}

// Funci¨®n helper para obtener valor o default
function getConf($key, $arr, $default) {
    return (isset($arr[$key]) && !empty($arr[$key])) ? $arr[$key] : $default;
}

// 2. OBTENER VARIABLES DE LA DB
$tableCoins = getConf('table_coins', $config, 'CashShopData');
$col1       = getConf('col_coin_1', $config, 'WCoinC');
$col2       = getConf('col_coin_2', $config, 'WCoinP');
$col3       = getConf('col_coin_3', $config, 'GoblinPoint');

// Auto-detectar columna de usuario (Compatibilidad MEMB_STAT vs CashShopData)
$colUser = 'AccountID';
if(in_array(strtolower($tableCoins), ['memb_info', 'memb_stat', 'character'])) {
    $colUser = 'memb___id';
}

// 3. L¨®GICA
$alertMessage = '';
try {
    $db = Connection::Database('Me_MuOnline');
    
    if(isset($_POST['add_coins'])) {
        $account = $_POST['account'];
        $amount  = (int)$_POST['amount'];
        $type    = $_POST['type']; // Esto traer¨¢ el nombre real de la columna (ej: WCoinC)

        // Verificar cuenta en MEMB_INFO (siempre fija para validar existencia)
        $checkAccount = $db->query_fetch_single("SELECT memb___id FROM MEMB_INFO WHERE memb___id = ?", [$account]);
        if(!$checkAccount) throw new Exception("La cuenta ingresada no existe.");

        // Verificar si existe en la tabla de Coins
        $checkData = $db->query_fetch_single("SELECT $colUser FROM $tableCoins WHERE $colUser = ?", [$account]);
        
        if($checkData) {
            // UPDATE
            $db->query("UPDATE $tableCoins SET $type = $type + ? WHERE $colUser = ?", [$amount, $account]);
        } else {
            // INSERT (Crea la fila si no existe)
            $db->query("INSERT INTO $tableCoins ($colUser, $type) VALUES (?, ?)", [$account, $amount]);
        }
        
        $alertMessage = '<div class="alert-success-custom">Se cargaron <b>'.$amount.'</b> <b>'.$type.'</b> a la cuenta <b>'.$account.'</b>.</div>';
    }

    // Listar (Ordenado por la primera moneda)
    $accounts = $db->query_fetch("SELECT TOP 100 $colUser, $col1, $col2, $col3 FROM $tableCoins ORDER BY $col1 DESC");

} catch (Exception $e) {
    $alertMessage = '<div class="alert alert-danger">Error SQL: ' . $e->getMessage() . '</div>';
    $accounts = []; 
}

// --- RENDERIZADO HTML (PHP PURO) ---

// Mostrar alertas si existen (procesadas arriba)
if(!empty($alertMessage)) {
    echo $alertMessage;
}

// Header Row
echo '<div class="row dt-controls-row">';
    echo '<div class="col-md-6">';
        echo '<h3 style="color: #fff; margin: 0;">Cargar Coins</h3>';
        echo '<small style="color: #888;">Tabla activa: <b>' . $tableCoins . '</b></small>';
    echo '</div>';
    echo '<div class="col-md-6 text-right">';
        echo '<button type="button" class="btn-add-blue" data-toggle="modal" data-target="#modalCargar">';
            echo '<i class="fa fa-plus"></i> Agregar Coins';
        echo '</button>';
    echo '</div>';
echo '</div>';

// Table Container
echo '<div class="bottom-card" style="padding: 0;">';
    echo '<table class="table-accounts">';
        echo '<thead>';
            echo '<tr>';
                echo '<th>#</th>';
                echo '<th>Cuenta</th>';
                echo '<th>' . $col1 . '</th>';
                echo '<th>' . $col2 . '</th>';
                echo '<th>' . $col3 . '</th>';
            echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
            
            if(is_array($accounts) && count($accounts) > 0) {
                $i = 1;
                foreach($accounts as $acc) {
                    echo '<tr>';
                        echo '<td>' . $i++ . '</td>';
                        echo '<td><span class="donator-user">' . $acc[$colUser] . '</span></td>';
                        echo '<td><span class="coin-pill-white">' . number_format((int)$acc[$col1]) . '</span></td>';
                        echo '<td><span class="coin-val-white"><i class="fa fa-diamond coin-icon-sm"></i> ' . number_format((int)$acc[$col2]) . '</span></td>';
                        echo '<td><span class="coin-val-green"><i class="fa fa-bolt coin-icon-sm"></i> ' . number_format((int)$acc[$col3]) . '</span></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center" style="padding:20px;">No se encontraron datos en la tabla ' . $tableCoins . '</td></tr>';
            }
            
        echo '</tbody>';
    echo '</table>';
echo '</div>';

// Modal
echo '<div class="modal fade" id="modalCargar" tabindex="-1" role="dialog" aria-hidden="true">';
    echo '<div class="modal-dialog modal-dialog-centered" role="document">';
        echo '<div class="modal-content" style="background: #1a1d21; border: 1px solid #334155; border-radius: 8px;">';
            
            // Modal Header
            echo '<div class="modal-header" style="border-bottom: 1px solid #334155;">';
                echo '<h5 class="modal-title" style="color: #fff; font-weight: 600;">Cargar Coins</h5>';
                echo '<button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: 0.5;">&times;</button>';
            echo '</div>';

            // Modal Body
            echo '<form method="post" accept-charset="UTF-8">';
                echo '<div class="modal-body" style="padding: 20px;">';
                    
                    echo '<div class="form-group">';
                        echo '<label style="color: #94a3b8; font-size: 12px; text-transform: uppercase;">Cuenta</label>';
                        echo '<input type="text" name="account" class="form-control-dark" placeholder="Ingrese ID de Usuario" required>';
                    echo '</div>';

                    echo '<div class="row">';
                        echo '<div class="col-md-6">';
                            echo '<div class="form-group">';
                                echo '<label style="color: #94a3b8; font-size: 12px; text-transform: uppercase;">Cantidad</label>';
                                echo '<input type="number" name="amount" class="form-control-dark" value="0" required>';
                            echo '</div>';
                        echo '</div>';
                        echo '<div class="col-md-6">';
                            echo '<div class="form-group">';
                                echo '<label style="color: #94a3b8; font-size: 12px; text-transform: uppercase;">Tipo de Moneda</label>';
                                echo '<select name="type" class="form-control-dark">';
                                    echo '<option value="' . $col1 . '">' . $col1 . '</option>';
                                    echo '<option value="' . $col2 . '">' . $col2 . '</option>';
                                    echo '<option value="' . $col3 . '">' . $col3 . '</option>';
                                echo '</select>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                echo '</div>';

                // Modal Footer
                echo '<div class="modal-footer" style="border-top: 1px solid #334155; display: flex; justify-content: flex-end; gap: 10px;">';
                    echo '<button type="button" class="btn" style="background: transparent; color: #94a3b8; font-size: 13px;" data-dismiss="modal">Cancelar</button>';
                    echo '<button type="submit" name="add_coins" class="btn-add-blue" style="border-radius: 6px; padding: 8px 25px;">Agregar</button>';
                echo '</div>';
            echo '</form>';

        echo '</div>';
    echo '</div>';
echo '</div>';

// CSS
echo '<style>
    /* ESTILO MENSAJE ¨¦XITO CUSTOM */
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';
?>