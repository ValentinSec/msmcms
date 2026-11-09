$(document).ready(function() {
    // Sidebar Toggle
    $('#sidebarToggle').on('click', function() {
        $('.navbar-default.sidebar').toggleClass('toggled');
        $('.top-header').toggleClass('toggled');
        $('#page-wrapper').toggleClass('toggled');
    });

    // Tooltip initialization (optional, for better UX)
    $('[data-toggle="tooltip"]').tooltip();
});