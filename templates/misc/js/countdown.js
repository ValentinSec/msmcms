(function() {
    // 1. Obtener Configuración
    if(typeof window.countdownConfig === 'undefined') return;
    var config = window.countdownConfig;
    
    // 2. Variables de Tiempo
    var targetTime = config.target;
    var serverTimeNow = config.now;
    var clientTimeNow = new Date().getTime();
    var timeOffset = serverTimeNow - clientTimeNow;

    // 3. Referencias DOM
    var timerDisplay = document.getElementById("timer_display");
    var finishMsg = document.getElementById("finish_message");
    var container = document.getElementById("global_countdown_container");
    var mainWrapper = document.getElementById("global_countdown_wrapper");
    var toggleBtn = document.getElementById("cd_toggle_btn");

    // 4. Lógica de Colapsar (Toggle)
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            container.classList.toggle('collapsed');
            // Opcional: Cambiar ícono
            var icon = this.querySelector('i');
            if(container.classList.contains('collapsed')) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            } else {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            }
        });
    }

    // 5. Función de Actualización
    function updateTimer() {
        var now = new Date().getTime() + timeOffset; 
        var distance = targetTime - now;

        if (distance < 0) {
            // Tiempo Terminado
            if(timerDisplay) timerDisplay.style.display = "none";
            
            if (config.action === 'message') {
                if(finishMsg) finishMsg.style.display = "block";
            } else if (config.action === 'redirect' && config.url) {
                window.location.href = config.url;
            } else {
                // Ocultar todo
                if(mainWrapper) mainWrapper.style.display = "none";
            }
            
            clearInterval(timerInterval);
            return;
        }

        // Cálculos
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Renderizado
        var elD = document.getElementById("cd_days");
        var elH = document.getElementById("cd_hours");
        var elM = document.getElementById("cd_min");
        var elS = document.getElementById("cd_sec");

        if(elD) elD.innerText = (days < 10 ? "0" : "") + days;
        if(elH) elH.innerText = (hours < 10 ? "0" : "") + hours;
        if(elM) elM.innerText = (minutes < 10 ? "0" : "") + minutes;
        if(elS) elS.innerText = (seconds < 10 ? "0" : "") + seconds;
    }

    // 6. Iniciar Intervalo
    var timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); 
})();