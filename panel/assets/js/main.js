$(document).ready(function () {
    // Sidebar Toggle
    $('#sidebarToggle').on('click', function (e) {
        e.stopPropagation();
        $('.admin-sidebar').toggleClass('active');
        $('.admin-sidebar-overlay').toggleClass('active');
    });

    // Close sidebar when clicking overlay
    $('body').on('click', '.admin-sidebar-overlay', function () {
        $('.admin-sidebar').removeClass('active');
        $(this).removeClass('active');
    });

    // Initialize DataTable responsiveness if not already set
    if ($.fn.DataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            responsive: true,
            scrollX: true,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
            }
        });
    }
});
