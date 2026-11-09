(function() {
    if(typeof window.countdownConfig === 'undefined') return;
    var config = window.countdownConfig;
    
    var targetTime = config.target;
    var serverTimeNow = config.now;
    var clientTimeNow = new Date().getTime();
    var timeOffset = serverTimeNow - clientTimeNow;

    var timerDisplay = document.getElementById("timer_display");
    var finishMsg = document.getElementById("finish_message");
    var container = document.getElementById("global_countdown_container");
    var mainWrapper = document.getElementById("global_countdown_wrapper");
    var toggleBtn = document.getElementById("cd_toggle_btn");

    // TOGGLE LOGIC
    if(toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            container.classList.toggle('collapsed');
            // Rotamos el icono via JS también por seguridad visual
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

    function updateTimer() {
        var now = new Date().getTime() + timeOffset; 
        var distance = targetTime - now;

        if (distance < 0) {
            if(timerDisplay) timerDisplay.style.display = "none";
            if (config.action === 'message') {
                if(finishMsg) finishMsg.style.display = "block";
            } else if (config.action === 'redirect' && config.url) {
                window.location.href = config.url;
            } else {
                if(mainWrapper) mainWrapper.style.display = "none";
                if(toggleBtn) toggleBtn.style.display = "none";
            }
            clearInterval(timerInterval);
            return;
        }

        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        var elD = document.getElementById("cd_days");
        var elH = document.getElementById("cd_hours");
        var elM = document.getElementById("cd_min");
        var elS = document.getElementById("cd_sec");

        if(elD) elD.innerText = (days < 10 ? "0" : "") + days;
        if(elH) elH.innerText = (hours < 10 ? "0" : "") + hours;
        if(elM) elM.innerText = (minutes < 10 ? "0" : "") + minutes;
        if(elS) elS.innerText = (seconds < 10 ? "0" : "") + seconds;
    }

    var timerInterval = setInterval(updateTimer, 1000);
    updateTimer(); 
})();