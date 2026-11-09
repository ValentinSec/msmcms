<footer class="footer-main">
    <div class="footer-texture"></div>
    
    <div class="container footer-content">
        <div class="row">
            
            <div class="col-md-3">
                <img src="<?php echo __PATH_TEMPLATE_IMG__; ?>logo.png" class="footer-logo mb-3" alt="Logo">
                <p class="footer-desc"><?php echo custom_config('server_desc'); ?></p>
                <div class="status-badge">
                    <span class="dot"></span> ONLINE 24/7
                </div>
            </div>

            <div class="col-md-3">
                <h5 class="footer-title">ENLACES RÁPIDOS</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo __BASE_URL__; ?>"><i class="fa fa-chevron-right"></i> Inicio</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>downloads"><i class="fa fa-chevron-right"></i> Descargas</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>rankings"><i class="fa fa-chevron-right"></i> Rankings</a></li>
                    <li><a href="<?php echo __BASE_URL__; ?>register"><i class="fa fa-chevron-right"></i> Registro</a></li>
                </ul>
            </div>

            <div class="col-md-3">
                <h5 class="footer-title">REDES SOCIALES</h5>
                <ul class="footer-social">
                    <?php if(custom_config('social_discord')): ?>
                        <li><a href="<?php echo custom_config('social_discord'); ?>" target="_blank"><i class="fab fa-discord"></i> Discord</a></li>
                    <?php endif; ?>
                    <?php if(custom_config('social_fb')): ?>
                        <li><a href="<?php echo custom_config('social_fb'); ?>" target="_blank"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <?php endif; ?>
                    <?php if(custom_config('social_wpp')): ?>
                        <li><a href="<?php echo custom_config('social_wpp'); ?>" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-md-3">
                <h5 class="footer-title">INFORMACIÓN</h5>
                <div class="info-box">
                    <p><i class="fa fa-info-circle"></i> Versión: <?php echo custom_config('server_version'); ?></p>
                    <p><i class="fa fa-envelope"></i> <?php echo custom_config('social_email'); ?></p>
                    <div class="server-tag">
                        <span>SERVIDOR</span>
                        <small>Estabilidad 99.9%</small>
                    </div>
                </div>
            </div>

        </div> <hr class="footer-hr">
        
        <div class="footer-bottom">
            <div class="row">
                <div class="col-md-6 text-start">
                    <p>© <?php echo date("Y"); ?> <strong><?php echo custom_config('server_name'); ?></strong>. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="<?php echo __BASE_URL__; ?>privacy">Privacidad</a> • <a href=<?php echo __BASE_URL__; ?>tos>Términos</a> • <a href="#"><?php echo custom_config('server_version'); ?></a>
                </div>
            </div>
        </div>
    </div>
</footer>