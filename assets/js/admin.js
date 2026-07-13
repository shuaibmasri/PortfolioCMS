(function () {
    'use strict';

    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var closeButtons = document.querySelectorAll('[data-sidebar-close]');

    function closeSidebar() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    if (sidebar && overlay && toggle) {
        toggle.addEventListener('click', function () {
            var isOpen = sidebar.classList.toggle('is-open');
            overlay.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
        closeButtons.forEach(function (button) { button.addEventListener('click', closeSidebar); });
    }

    var chartCanvas = document.getElementById('visitorChart');
    if (chartCanvas && window.Chart) {
        new Chart(chartCanvas, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{ label: 'Visitors', data: [42, 58, 47, 74, 62, 89, 96], borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, .12)', fill: true, tension: .4, borderWidth: 3, pointRadius: 0, pointHoverRadius: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, .16)' }, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
        });
    }
}());
