(function () {
    'use strict';

    var form = document.getElementById('settingsForm');
    if (!form) {
        return;
    }

    var alertBox = document.getElementById('settingsAlert');
    var loadUrl = form.dataset.loadUrl;
    var saveUrl = form.dataset.saveUrl;
    var reloadBtn = document.getElementById('settingsReloadBtn');
    var saveBtn = document.getElementById('settingsSaveBtn');
    var saveText = saveBtn ? saveBtn.querySelector('.settings-save-text') : null;
    var saveSpinner = saveBtn ? saveBtn.querySelector('.settings-save-spinner') : null;
    var fieldNames = [
        'website_name', 'website_tagline', 'website_description', 'footer_text', 'copyright_text',
        'contact_email', 'contact_phone', 'address', 'google_maps_url',
        'default_language', 'time_zone', 'date_format', 'time_format',
        'meta_title', 'meta_description', 'meta_keywords', 'robots', 'canonical_url',
        'maintenance_mode', 'maintenance_message',
        'facebook_url', 'linkedin_url', 'github_url', 'x_url', 'youtube_url'
    ];

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showAlert(message, type) {
        if (!alertBox) {
            return;
        }

        alertBox.innerHTML = '<div class="alert alert-' + type + ' mb-0" role="alert">' + escapeHtml(message) + '</div>';
    }

    function clearAlert() {
        if (alertBox) {
            alertBox.innerHTML = '';
        }
    }

    function setBusy(isBusy) {
        if (!saveBtn || !saveText || !saveSpinner) {
            return;
        }

        saveBtn.disabled = isBusy;
        saveText.innerHTML = isBusy ? 'Saving...' : '<i class="fa fa-save me-1"></i>Save Changes';
        saveSpinner.classList.toggle('d-none', !isBusy);
    }

    function clearValidation() {
        fieldNames.forEach(function (name) {
            var field = form.elements[name];
            if (field && field.classList) {
                field.classList.remove('is-invalid');
            }
        });
        form.classList.remove('was-validated');
    }

    function setFieldValue(name, value) {
        var field = form.elements[name];
        if (!field) {
            return;
        }

        if (field.type === 'checkbox') {
            field.checked = value === true || value === '1' || value === 1 || value === 'true';
            return;
        }

        field.value = value === null || value === undefined ? '' : String(value);
    }

    function fillForm(settings) {
        fieldNames.forEach(function (name) {
            if (Object.prototype.hasOwnProperty.call(settings, name)) {
                setFieldValue(name, settings[name]);
            }
        });
    }

    function applyErrors(errors) {
        clearValidation();

        Object.keys(errors || {}).forEach(function (name) {
            var field = form.elements[name];
            if (field && field.classList) {
                field.classList.add('is-invalid');
            }
        });
    }

    function fetchSettings() {
        clearAlert();
        if (reloadBtn) {
            reloadBtn.disabled = true;
        }

        return fetch(loadUrl, { credentials: 'same-origin' })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload.success) {
                    throw new Error(payload.message || 'Unable to load website settings.');
                }

                fillForm((payload.data && payload.data.settings) || {});
                clearValidation();
                return payload;
            })
            .catch(function (error) {
                showAlert(error.message || 'Unable to load website settings.', 'danger');
            })
            .finally(function () {
                if (reloadBtn) {
                    reloadBtn.disabled = false;
                }
            });
    }

    function saveSettings(event) {
        event.preventDefault();
        clearAlert();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setBusy(true);

        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: new FormData(form)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                setBusy(false);

                if (!payload.success) {
                    applyErrors(payload.errors || {});
                    showAlert(payload.message || 'Unable to save website settings.', 'danger');
                    return;
                }

                fillForm((payload.data && payload.data.settings) || {});
                clearValidation();
                showAlert(payload.message || 'Website settings saved successfully.', 'success');
            })
            .catch(function () {
                setBusy(false);
                showAlert('Unable to save website settings.', 'danger');
            });
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', function () {
            fetchSettings();
        });
    }

    form.addEventListener('submit', saveSettings);
    fetchSettings();
}());
