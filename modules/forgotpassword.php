<?php
/**
 * Forgot Password Module
 * PREPARED FOR OBFUSCATION (Pure PHP)
 */

if(isLoggedIn()) redirect();

// Título de la página
echo '<div class="page-title"><span>' . lang('module_titles_txt_15',true) . '</span></div>';

try {
    
    if(!mconfig('active')) throw new Exception(lang('error_47',true));
    
    // CASO 1: VERIFICACIÓN DE RECUPERACIÓN (Link del correo)
    if(isset($_GET['ui']) && isset($_GET['ue']) && isset($_GET['key'])) {
        
        try {
            $Account = new Account();
            $Account->passwordRecoveryVerificationProcess($_GET['ui'], $_GET['ue'], $_GET['key']);
        } catch (Exception $ex) {
            message('error', $ex->getMessage());
        }
        
    } else {
        
        // CASO 2: SOLICITUD DE RECUPERACIÓN (Formulario)
        if(isset($_POST['msmEmail_submit'])) {
            try {
                $Account = new Account();
                $Account->passwordRecoveryProcess($_POST['msmEmail_current'], $_SERVER['REMOTE_ADDR']);
            } catch (Exception $ex) {
                message('error', $ex->getMessage());
            }
        }
        
        // Renderizado del Formulario
        echo '<div class="col-xs-8 col-xs-offset-2" style="margin-top:30px;">';
            echo '<form class="form-horizontal" action="" method="post">';
                
                // Input Email
                echo '<div class="form-group">';
                    echo '<label for="msmEmail" class="col-sm-4 control-label">' . lang('forgotpass_txt_1',true) . '</label>';
                    echo '<div class="col-sm-8">';
                        echo '<input type="text" class="form-control" id="msmEmail" name="msmEmail_current" required>';
                    echo '</div>';
                echo '</div>';
                
                // Botón Submit
                echo '<div class="form-group">';
                    echo '<div class="col-sm-offset-4 col-sm-8">';
                        echo '<button type="submit" name="msmEmail_submit" value="submit" class="btn btn-primary">' . lang('forgotpass_txt_2',true) . '</button>';
                    echo '</div>';
                echo '</div>';
                
            echo '</form>';
        echo '</div>';
    }
    
} catch(Exception $ex) {
    message('error', $ex->getMessage());
}
?>