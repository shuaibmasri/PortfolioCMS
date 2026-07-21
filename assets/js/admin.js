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
        var labels = [];
        var values = [];
        try {
            labels = JSON.parse(chartCanvas.getAttribute('data-labels') || '[]');
            values = JSON.parse(chartCanvas.getAttribute('data-values') || '[]');
        } catch (error) {
            labels = [];
            values = [];
        }
        new Chart(chartCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{ label: 'Visitors', data: values, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, .12)', fill: true, lineTension: .4, borderWidth: 3, pointRadius: 0, pointHoverRadius: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: 'rgba(148, 163, 184, .16)' } }], xAxes: [{ gridLines: { display: false } }] } }
        });
    }
}());
