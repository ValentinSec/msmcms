<footer class="morph-footer">
    <div class="footer-container">
        
        <div class="footer-logo">
            <?php echo custom_config('server_name'); ?>
        </div>
        
        <div class="footer-links">
            <a href="<?php echo __BASE_URL__; ?>">Inicio</a>
            <?php if(!isLoggedIn()): ?>
            <a href="<?php echo __BASE_URL__; ?>register">Registrarse</a>
            <?php endif; ?>
            <a href="<?php echo __BASE_URL__; ?>downloads">Descargas</a>
            <a href="<?php echo __BASE_URL__; ?>rankings">Rankings</a>
            <a href="<?php echo __BASE_URL__; ?>info">Información</a>
        </div>

        <div class="footer-social">
            <?php if(custom_config('social_fb')): ?>
                <a href="<?php echo custom_config('social_fb'); ?>" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>

            <?php if(custom_config('social_discord')): ?>
                <a href="<?php echo custom_config('social_discord'); ?>" target="_blank" title="Discord"><i class="fab fa-discord"></i></a>
            <?php endif; ?>

            <?php if(custom_config('social_wpp')): ?>
                <a href="<?php echo custom_config('social_wpp'); ?>" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <?php endif; ?>
            
            <?php 
            if(custom_config('social_yt')): ?>
                <a href="<?php echo custom_config('social_yt'); ?>" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
            <?php endif; ?>
        </div>
        
        <div class="footer-bottom">
            &copy; <?php echo date("Y"); ?> <span><?php echo custom_config('server_name'); ?></span>. Todos los derechos reservados.<br>
            Powered by MuServerMaker CMS
        </div>

    </div>
</footer>