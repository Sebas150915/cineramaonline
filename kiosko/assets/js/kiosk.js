document.addEventListener('DOMContentLoaded', () => {
    console.log('Cinerama Kiosk Loaded');

    // Prevent double-tap zoom
    document.addEventListener('dblclick', function (event) {
        event.preventDefault();
    }, { passive: false });

    // Touch feedback for buttons
    const buttons = document.querySelectorAll('button, .movie-card, .nav-item');
    buttons.forEach(btn => {
        btn.addEventListener('touchstart', function () {
            this.style.opacity = '0.7';
            this.style.transform = 'scale(0.95)';
        }, { passive: true });

        btn.addEventListener('touchend', function () {
            this.style.opacity = '1';
            this.style.transform = 'scale(1)';
        }, { passive: true });
    });
});
