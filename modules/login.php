<?php
/**
 * Login Module
 * PREPARED FOR OBFUSCATION (Pure PHP)
 */

if(isLoggedIn()) redirect();

try {
    if(!mconfig('active')) throw new Exception(lang('error_47',true));
    
    // Proceso de Login
    if(isset($_POST['webengineLogin_submit'])) {
        try {
            $userLogin = new login();
            $userLogin->validateLogin($_POST['webengineLogin_user'],$_POST['webengineLogin_pwd']);
        } catch (Exception $ex) {
            message('error', $ex->getMessage());
        }
    }

    // Preparación de variables para el HTML
    $serverName = custom_config('server_name');
    $baseUrl = __BASE_URL__;

    // --- RENDERIZADO HTML (CONSTRUIDO CON PHP) ---
    echo '<div class="login-page-container">';
    
    // Header
    echo '<div class="login-header">';
    echo '<h1>INICIA SESIÓN</h1>';
    echo '<p>Accede a tu cuenta de ' . $serverName . '</p>';
    echo '</div>';

    // Card
    echo '<div class="login-card">';
    echo '<form action="" method="post">';
    
    // Input Usuario
    echo '<div class="form-group-custom">';
    echo '<label>Usuario</label>';
    echo '<div class="input-wrapper">';
    echo '<i class="fa fa-user"></i>';
    echo '<input type="text" name="webengineLogin_user" placeholder="Tu nombre de usuario" required>';
    echo '</div></div>';

    // Input Password
    echo '<div class="form-group-custom">';
    echo '<label>Contraseña</label>';
    echo '<div class="input-wrapper">';
    echo '<i class="fa fa-lock"></i>';
    echo '<input type="password" name="webengineLogin_pwd" id="login_password" placeholder="••••••••" required>';
    echo '<i class="fa fa-eye toggle-password" onclick="togglePwd()"></i>';
    echo '</div></div>';

    // Opciones (Remember / Forgot)
    echo '<div class="form-options">';
    echo '<label class="remember-me">';
    echo '<input type="checkbox"> <span>Recordarme</span>';
    echo '</label>';
    echo '<a href="' . $baseUrl . 'forgotpassword/" class="forgot-link">¿Olvidaste tu contraseña?</a>';
    echo '</div>';

    // Botón Submit
    echo '<button type="submit" name="webengineLogin_submit" value="submit" class="btn-login-submit">';
    echo 'Iniciar Sesión';
    echo '</button>';
    echo '</form>';
    
    // Footer Link Registro
    echo '<div class="login-footer">';
    echo '¿No tienes una cuenta? <a href="' . $baseUrl . 'register/">Regístrate</a>';
    echo '</div>';
    
    echo '</div>'; // Fin login-card
    echo '</div>'; // Fin container

    // Script JS
    echo '<script>';
    echo 'function togglePwd() {';
    echo 'var x = document.getElementById("login_password");';
    echo 'x.type = x.type === "password" ? "text" : "password";';
    echo '}';
    echo '</script>';

} catch(Exception $ex) {
    message('error', $ex->getMessage());
}
?>