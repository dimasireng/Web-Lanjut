// theme.js - small helpers
document.addEventListener('DOMContentLoaded', function () {
    // simple hover lift for cards
    document.querySelectorAll('.card').forEach(function (c) {
        c.addEventListener('mouseenter', () => c.style.transform = 'translateY(-6px) scale(1.004)');
        c.addEventListener('mouseleave', () => c.style.transform = 'translateY(0) scale(1)');
        c.style.transition = 'transform .25s cubic-bezier(.2,.8,.2,1)';
    });
});
