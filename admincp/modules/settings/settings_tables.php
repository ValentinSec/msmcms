<?php
/**
 * Tables Configuration Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Ruta al archivo de configuración
$configPath = __DIR__ . '/../../../includes/config/custom_settings.json';

// 2. Cargar configuraciones actuales
$currentConfig = [];
if (file_exists($configPath)) {
    $currentConfig = json_decode(file_get_contents($configPath), true);
}

// 3. Si se envían los datos por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newSettings = array_merge($currentConfig, [
        'table_coins'       => $_POST['table_coins'],
        'col_coin_1'        => $_POST['col_coin_1'],
        'col_coin_2'        => $_POST['col_coin_2'],
        'col_coin_3'        => $_POST['col_coin_3'],
        
        'table_duels'       => $_POST['table_duels'],
        'col_duel_win'      => $_POST['col_duel_win'],
        'col_duel_lose'     => $_POST['col_duel_lose'],
        'col_duel_name'     => $_POST['col_duel_name'],
        
        'table_kills'       => $_POST['table_kills'],
        'col_kills'         => $_POST['col_kills'],
        'col_deads'         => $_POST['col_deads'],
        
        'table_vip'         => $_POST['table_vip'],
        'col_vip_level'     => $_POST['col_vip_level'],
        'col_vip_expire'    => $_POST['col_vip_expire'],
        
        'table_master'      => $_POST['table_master'],
        'col_master_level'  => $_POST['col_master_level'],
        'col_master_points' => $_POST['col_master_points'],
        
        'table_event_cc'    => $_POST['table_event_cc'],
        'table_event_ds'    => $_POST['table_event_ds'],
        'table_event_bc'    => $_POST['table_event_bc'],
        'table_event_it'    => $_POST['table_event_it'],
        'col_event_name'    => $_POST['col_event_name'],
        'col_event_score'   => $_POST['col_event_score']
    ]);

    // 3.1 Guardar el archivo JSON actualizado
    file_put_contents($configPath, json_encode($newSettings, JSON_PRETTY_PRINT));
    
    // 3.2 Recargar variable
    $currentConfig = $newSettings;
    
    // MENSAJE DE ÉXITO
    echo '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
}

// 4. Función para obtener valores
function getVal($key, $default, $config) {
    return isset($config[$key]) ? $config[$key] : $default;
}

// --- RENDERIZADO HTML (PHP PURO) ---

// Estilos CSS
echo '<style>
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';

echo '<div class="container-fluid">';
    
    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="row" style="margin-bottom: 20px; align-items: center; display: flex;">';
                    echo '<div class="col-xs-6">';
                        echo '<h3 style="margin:0; color:#fff; font-weight:600;">Configuración de Tablas & Columnas</h3>';
                    echo '</div>';
                    echo '<div class="col-xs-6 text-right">';
                        echo '<button type="submit" class="btn-save-green">Guardar Cambios</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // Main Grid
        echo '<div class="row row-tables-flex">';
            
            // COLUMNA 1: COINS
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Tabla de Coins</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tabla de Coins</label>';
                        echo '<input type="text" name="table_coins" class="form-control-dark" value="' . getVal('table_coins', 'CashShopData', $currentConfig) . '" placeholder="CashShopData">';
                    echo '</div>';
                
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna WCoinC</label>';
                        echo '<input type="text" name="col_coin_1" class="form-control-dark" value="' . getVal('col_coin_1', 'WCoinC', $currentConfig) . '" placeholder="WCoinC">';
                    echo '</div>';
                
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna WCoinP</label>';
                        echo '<input type="text" name="col_coin_2" class="form-control-dark" value="' . getVal('col_coin_2', 'WCoinP', $currentConfig) . '" placeholder="WCoinP">';
                    echo '</div>';
                
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna GoblinPoint</label>';
                        echo '<input type="text" name="col_coin_3" class="form-control-dark" value="' . getVal('col_coin_3', 'GoblinPoint', $currentConfig) . '" placeholder="GoblinPoint">';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 2: DUELOS
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Tabla Duelos</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tabla Duelos</label>';
                        echo '<input type="text" name="table_duels" class="form-control-dark" value="' . getVal('table_duels', 'RankingDuel', $currentConfig) . '" placeholder="RankingDuel">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Win</label>';
                        echo '<input type="text" name="col_duel_win" class="form-control-dark" value="' . getVal('col_duel_win', 'WinScore', $currentConfig) . '" placeholder="WinScore">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Lose</label>';
                        echo '<input type="text" name="col_duel_lose" class="form-control-dark" value="' . getVal('col_duel_lose', 'LoseScore', $currentConfig) . '" placeholder="LoseScore">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Nombre</label>';
                        echo '<input type="text" name="col_duel_name" class="form-control-dark" value="' . getVal('col_duel_name', 'Name', $currentConfig) . '" placeholder="Name">';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 3: KILLS & VIP
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Tabla de Kills & Deads</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tabla de Kills & Deads</label>';
                        echo '<input type="text" name="table_kills" class="form-control-dark" value="' . getVal('table_kills', 'Character', $currentConfig) . '" placeholder="Character">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Kills</label>';
                        echo '<input type="text" name="col_kills" class="form-control-dark" value="' . getVal('col_kills', 'PkCount', $currentConfig) . '" placeholder="PkCount">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Deads</label>';
                        echo '<input type="text" name="col_deads" class="form-control-dark" value="' . getVal('col_deads', 'PkLevel', $currentConfig) . '" placeholder="PkLevel">';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 4: VIP
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Tabla Sistema VIP</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tabla Sistema VIP</label>';
                        echo '<input type="text" name="table_vip" class="form-control-dark" value="MEMB_INFO" placeholder="MEMB_INFO">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Nivel VIP</label>';
                        echo '<input type="text" name="col_vip_level" class="form-control-dark" value="AccountLevel" placeholder="AccountLevel">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna VIP Expire</label>';
                        echo '<input type="text" name="col_vip_expire" class="form-control-dark" value="AccountExpireDate" placeholder="AccountExpireDate">';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 5: MASTER SKILL
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Tabla MasterSkillTree</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tabla MasterSkillTree</label>';
                        echo '<input type="text" name="table_master" class="form-control-dark" value="' . getVal('table_master', 'MasterSkillTree', $currentConfig) . '" placeholder="MasterSkillTree">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Master Level</label>';
                        echo '<input type="text" name="col_master_level" class="form-control-dark" value="' . getVal('col_master_level', 'MasterLevel', $currentConfig) . '" placeholder="MasterLevel">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Master Points</label>';
                        echo '<input type="text" name="col_master_points" class="form-control-dark" value="' . getVal('col_master_points', 'mlPoints', $currentConfig) . '" placeholder="mlPoints">';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 6: EVENTOS
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Tablas de Eventos</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Chaos Castle</label>';
                        echo '<input type="text" name="table_event_cc" class="form-control-dark" value="' . getVal('table_event_cc', 'RankingChaosCastle', $currentConfig) . '" placeholder="RankingChaosCastle">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Devil Square</label>';
                        echo '<input type="text" name="table_event_ds" class="form-control-dark" value="' . getVal('table_event_ds', 'RankingDevilSquare', $currentConfig) . '" placeholder="RankingDevilSquare">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Blood Castle</label>';
                        echo '<input type="text" name="table_event_bc" class="form-control-dark" value="' . getVal('table_event_bc', 'RankingBloodCastle', $currentConfig) . '" placeholder="RankingBloodCastle">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Illusion Temple</label>';
                        echo '<input type="text" name="table_event_it" class="form-control-dark" value="' . getVal('table_event_it', 'RankingIllusionTemple', $currentConfig) . '" placeholder="RankingIllusionTemple">';
                    echo '</div>';
            
                    echo '<hr style="border-color: #1e293b; margin: 15px 0;">';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Nombre</label>';
                        echo '<input type="text" name="col_event_name" class="form-control-dark" value="' . getVal('col_event_name', 'Name', $currentConfig) . '" placeholder="Name">';
                    echo '</div>';
            
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Columna Score</label>';
                        echo '<input type="text" name="col_event_score" class="form-control-dark" value="' . getVal('col_event_score', 'Score', $currentConfig) . '" placeholder="Score">';
                    echo '</div>';
                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row Flex
    echo '</form>';
echo '</div>';
?>