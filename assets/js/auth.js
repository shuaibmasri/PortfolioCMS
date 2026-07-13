/* global bootstrap */
(function () {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!input) return;

            var reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
            button.querySelector('i').className = reveal ? 'fa fa-eye-slash' : 'fa fa-eye';
        });
    });

    document.querySelectorAll('form[data-submit-lock]').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) return;
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<i class="fa fa-spinner fa-spin me-2" aria-hidden="true"></i>Please wait…';
            }
        });
    });
}());
