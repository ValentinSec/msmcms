$(document).ready(function() {
    // Inicializar MetisMenu con par¨¢metros de toggle
    if (typeof $.fn.metisMenu === 'function') {
        $('#side-menu').metisMenu({
            toggle: true
        });
    }

    // Prevenir comportamientos por defecto y mejorar clicks
    $('#side-menu a').on('click', function(e) {
        if ($(this).attr('href') === '#') {
            e.preventDefault();
        }
    });

    // Limpiar clases de enfoque de Bootstrap que dejan el bot¨®n "iluminado"
    $('#side-menu a').on('focus', function() {
        $(this).blur();
    });
});