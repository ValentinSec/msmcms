<?php if(!defined('access')) die(); ?>
<script>
/* Disparo de tareas programadas: como mucho una vez por minuto por navegador */
(function() {
    try {
        var k = "msm_cron_tick", now = Date.now(), last = parseInt(localStorage.getItem(k) || "0", 10);
        if (now - last < 60000) return;
        localStorage.setItem(k, String(now));
    } catch (e) {}
    var run = function() {
        try { fetch("<?php echo __BASE_URL__; ?>api/cron.php", { credentials: "omit", cache: "no-store" }).catch(function() {}); } catch (e) {}
    };
    if (window.requestIdleCallback) { requestIdleCallback(run, { timeout: 5000 }); } else { setTimeout(run, 3000); }
})();
</script>