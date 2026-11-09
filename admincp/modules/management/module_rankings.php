<?php
/**
 * Rankings Configuration Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */

$xmlPath = __PATH_CONFIGS__ . 'modules/rankings.xml';

// Lógica de Guardado
if(isset($_POST['save_rankings'])) {
    $xml = new SimpleXMLElement('<rankings></rankings>');
    
    // Lista de todos los switches
    $switches = [
        'active_rank_general', 'active_rank_guilds', 'active_rank_killers', 
        'active_rank_events', 'active_rank_gens', 'active_rank_duels', 'active_rank_bosses',
        'active_rank_elo',
        'show_resets', 'show_master_resets', 'show_master_level', 
        'show_country', 'show_status', 'show_profile', 'show_guild'
    ];

    foreach($switches as $switch) {
        $xml->addChild($switch, (isset($_POST[$switch]) ? 1 : 0));
    }

    $xml->addChild('qty_chars', $_POST['qty_chars']);
    $xml->addChild('qty_guilds', $_POST['qty_guilds']);
    $xml->addChild('qty_killers', $_POST['qty_killers']);
    $xml->addChild('exclude_list', $_POST['exclude_list']);

    $xml->asXML($xmlPath);
    
    // MENSAJE DE ÉXITO
    echo '<div class="alert-success-custom">Configuración de rankings guardada.</div>';
}

// Carga de datos
$view = simplexml_load_file($xmlPath);
function isChecked($field, $view) { return ((string)$view->$field == '1') ? 'checked' : ''; }

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<style>
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
</style>';

echo '<div class="container-fluid">';
    echo '<form action="" method="post">';
        
        // Header
        echo '<div class="row" style="margin-bottom: 20px;">';
            echo '<div class="col-xs-6">';
                echo '<h3 style="color:#fff; font-weight:600;">Configuración de Rankings</h3>';
            echo '</div>';
            echo '<div class="col-xs-6 text-right">';
                echo '<button type="submit" name="save_rankings" class="btn-save-green">Guardar Cambios</button>';
            echo '</div>';
        echo '</div>';

        // Main Content
        echo '<div class="row row-flex">';
            
            // COLUMNA 1: SWITCHES PRINCIPALES
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Personalizar Rankings</h3>';
                    echo '<div class="switch-list">';
                        
                        // General
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_general" ' . isChecked('active_rank_general', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activar Ranking General</span>';
                        echo '</div>';

                        // Guilds
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_guilds" ' . isChecked('active_rank_guilds', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activar Ranking Guilds</span>';
                        echo '</div>';

                        // Killers
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_killers" ' . isChecked('active_rank_killers', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activar Ranking Killers</span>';
                        echo '</div>';

                        // Events
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_events" ' . isChecked('active_rank_events', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activate Ranking Events</span>';
                        echo '</div>';

                        // Gens
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_gens" ' . isChecked('active_rank_gens', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activar Ranking Gens</span>';
                        echo '</div>';

                        // Duels
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_duels" ' . isChecked('active_rank_duels', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activate Ranking Duel\'s</span>';
                        echo '</div>';

                        // Bosses
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_bosses" ' . isChecked('active_rank_bosses', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activar Ranking Bosses</span>';
                        echo '</div>';
                        
                        // ELO
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="active_rank_elo" ' . isChecked('active_rank_elo', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Activar Ranking ELO (PVP)</span>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 2: OPCIONES VISUALES
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Opciones de Customización</h3>';
                    echo '<div class="switch-list">';
                        
                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="show_resets" ' . isChecked('show_resets', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Mostrar Resets</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="show_master_resets" ' . isChecked('show_master_resets', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Mostrar Master Resets</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="show_status" ' . isChecked('show_status', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Mostrar Status</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="show_profile" ' . isChecked('show_profile', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Mostrar Perfil</span>';
                        echo '</div>';

                        echo '<div class="form-group switch-container">';
                            echo '<label class="switch"><input type="checkbox" name="show_guild" ' . isChecked('show_guild', $view) . '><span class="slider"></span></label>';
                            echo '<span class="switch-label">Mostrar Guild</span>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // COLUMNA 3: CANTIDADES
            echo '<div class="col-md-4">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Quantity options</h3>';
                    
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Cantidad de Personajes a Mostrar</label>';
                        echo '<input type="number" name="qty_chars" class="form-control-dark" placeholder="Limite 50" value="' . $view->qty_chars . '">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Cantidad de Guilds a Mostrar</label>';
                        echo '<input type="number" name="qty_guilds" class="form-control-dark" placeholder="Limite 50" value="' . $view->qty_guilds . '">';
                    echo '</div>';

                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Cantidad de Killers a Mostrar</label>';
                        echo '<input type="number" name="qty_killers" class="form-control-dark" placeholder="Limite 50" value="' . $view->qty_killers . '">';
                    echo '</div>';

                echo '</div>';
            echo '</div>';

        echo '</div>'; // End Row Flex

        // TEXTAREA EXCLUSIONES
        echo '<div class="row">';
            echo '<div class="col-lg-12">';
                echo '<div class="settings-section">';
                    echo '<h3 class="settings-title">Exclude Character\'s from Ranking:</h3>';
                    echo '<textarea name="exclude_list" class="form-control-dark" rows="3" placeholder="Agrega en este campo a todos aquellos jugadores que no quieres que aparezcan en el ranking, separados por una coma. EJ: KOSMOS, Cruel, MuServerMaker, etc">' . $view->exclude_list . '</textarea>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

    echo '</form>';
echo '</div>';
?>