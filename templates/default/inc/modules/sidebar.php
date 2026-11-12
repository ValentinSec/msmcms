<?php
/**
 * Sidebar Dinámico - Custom CMS
 */

// Bloque de Login
if(!isLoggedIn()) {
    echo '<div class="panel panel-sidebar">';
        echo '<div class="panel-heading"><h3 class="panel-title">'.lang('module_titles_txt_2').'</h3></div>';
        echo '<div class="panel-body">';
            echo '<form action="'.__BASE_URL__.'login" method="post">';
                echo '<div class="form-group"><input type="text" class="form-control" name="msmLogin_user" placeholder="Usuario" required></div>';
                echo '<div class="form-group"><input type="password" class="form-control" name="msmLogin_pwd" placeholder="Contraseña" required></div>';
                echo '<button type="submit" name="msmLogin_submit" value="submit" class="btn btn-primary btn-block">'.lang('login_txt_3').'</button>';
            echo '</form>';
        echo '</div>';
    echo '</div>';
}

// Bloque de Usuario Logueado
if(isLoggedIn()) {
    echo '<div class="panel panel-sidebar">';
        echo '<div class="panel-heading"><h3 class="panel-title">'.lang('usercp_menu_title').'</h3></div>';
        echo '<div class="panel-body">';
            templateBuildUsercp();
            echo '<hr><a href="'.__BASE_URL__.'logout" class="btn btn-danger btn-xs btn-block">Cerrar Sesión</a>';
        echo '</div>';
    echo '</div>';
}

// Bloque de Información del Servidor (Usando tu custom_config)
echo '<div class="panel panel-sidebar">';
    echo '<div class="panel-heading"><h3 class="panel-title">Información</h3></div>';
    echo '<div class="panel-body">';
        echo '<table class="table table-condensed" style="margin-bottom:0px; color:#fff;">';
            echo '<tr><td>Versión:</td><td>'.custom_config('server_version').'</td></tr>';
            echo '<tr><td>Experiencia:</td><td>'.custom_config('server_exp').'</td></tr>';
            echo '<tr><td>Drop:</td><td>'.custom_config('server_drop').'</td></tr>';
            echo '<tr><td>Online:</td><td style="color:#00ff88; font-weight:bold;">'.$displayOnline.'</td></tr>';
            echo '<tr><td>Cuentas:</td><td>'.$displayAccounts.'</td></tr>';
        echo '</table>';
    echo '</div>';
echo '</div>';

// Castle Siege Widget original
templateCastleSiegeWidget();
?>