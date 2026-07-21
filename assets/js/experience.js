(function () {
    'use strict';

    var table = document.getElementById('experienceTable');
    if (!table) {
        return;
    }

    var form = document.getElementById('experienceForm');
    var modalEl = document.getElementById('experienceModal');
    var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    var alertBox = document.getElementById('experienceAlert');
    var formAlertBox = document.getElementById('experienceFormAlert');
    var tableBody = table.querySelector('tbody');
    var searchInput = document.getElementById('experienceSearch');
    var pageSizeSelect = document.getElementById('experiencePageSize');
    var pagination = document.getElementById('experiencePagination');
    var meta = document.getElementById('experienceMeta');
    var range = document.getElementById('experienceRange');
    var addBtn = document.getElementById('experienceAddBtn');
    var reloadBtn = document.getElementById('experienceReloadBtn');
    var saveBtn = document.getElementById('experienceSaveBtn');
    var saveText = saveBtn ? saveBtn.querySelector('.experience-save-text') : null;
    var saveSpinner = saveBtn ? saveBtn.querySelector('.experience-save-spinner') : null;
    var sortState = { key: 'start_date', direction: 'desc' };
    var paging = { page: 1, size: parseInt(pageSizeSelect ? pageSizeSelect.value : '10', 10) || 10 };
    var experiences = [];

    var loadUrl = form.dataset.showUrl;
    var saveUrl = form.dataset.saveUrl;
    var deleteUrl = form.dataset.deleteUrl;
    var csrfToken = form.querySelector('[name="csrf_token"]').value;
    var modalTitle = document.getElementById('experienceModalLabel');

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

    function showFormAlert(message, type) {
        if (!formAlertBox) {
            return;
        }

        formAlertBox.innerHTML = '<div class="alert alert-' + type + ' mb-0 py-2" role="alert">' + escapeHtml(message) + '</div>';
    }

    function clearFormAlert() {
        if (formAlertBox) {
            formAlertBox.innerHTML = '';
        }
    }

    function clearValidation() {
        form.querySelectorAll('.is-invalid').forEach(function (field) {
            field.classList.remove('is-invalid');
        });
        form.classList.remove('was-validated');
    }

    function setBusy(isBusy) {
        if (!saveBtn || !saveText || !saveSpinner) {
            return;
        }

        saveBtn.disabled = isBusy;
        saveText.textContent = isBusy ? 'Saving...' : 'Save Experience';
        saveSpinner.classList.toggle('d-none', !isBusy);
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }

        var parsed = new Date(value + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
    }

    function formatPeriod(item) {
        var start = formatDate(item.start_date);
        var end = item.is_current === '1' || item.is_current === 1 || item.is_current === true
            ? 'Present'
            : formatDate(item.end_date);

        return start ? start + ' - ' + (end || 'Present') : (end || 'Present');
    }

    function compareValues(a, b) {
        if (typeof a === 'number' && typeof b === 'number') {
            return a - b;
        }

        return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
    }

    function applyFilters() {
        var term = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var filtered = experiences.filter(function (experience) {
            if (!term) {
                return true;
            }

            return [
                experience.employer_name,
                experience.job_title,
                experience.employment_type,
                experience.location,
                experience.description,
                experience.employment_period,
                experience.display_order,
                experience.is_public ? 'public' : 'private'
            ].some(function (value) {
                return String(value || '').toLowerCase().indexOf(term) !== -1;
            });
        });

        filtered.sort(function (left, right) {
            var leftValue = left[sortState.key];
            var rightValue = right[sortState.key];
            var result;

            if (sortState.key === 'start_date' || sortState.key === 'display_order' || sortState.key === 'is_public') {
                result = compareValues(String(leftValue || ''), String(rightValue || ''));
            } else {
                result = compareValues(leftValue || '', rightValue || '');
            }

            return sortState.direction === 'asc' ? result : -result;
        });

        return filtered;
    }

    function renderPagination(total, filteredCount) {
        if (!pagination) {
            return;
        }

        var totalPages = Math.max(1, Math.ceil(filteredCount / paging.size));
        paging.page = Math.min(Math.max(paging.page, 1), totalPages);
        var start = ((paging.page - 1) * paging.size) + 1;
        var end = Math.min(paging.page * paging.size, filteredCount);

        if (filteredCount === 0) {
            start = 0;
            end = 0;
        }

        if (range) {
            range.textContent = 'Showing ' + start + ' to ' + end + ' of ' + filteredCount + ' experiences';
        }

        if (meta) {
            meta.textContent = total + ' total experience' + (total === 1 ? '' : 's');
        }

        if (filteredCount <= paging.size) {
            pagination.innerHTML = '';
            return;
        }

        var items = [];
        var disabledPrev = paging.page === 1 ? ' disabled' : '';
        var disabledNext = paging.page === totalPages ? ' disabled' : '';

        items.push('<li class="page-item' + disabledPrev + '"><button class="page-link" type="button" data-page="' + (paging.page - 1) + '">Previous</button></li>');

        var startPage = Math.max(1, paging.page - 2);
        var endPage = Math.min(totalPages, paging.page + 2);

        if (startPage > 1) {
            items.push('<li class="page-item"><button class="page-link" type="button" data-page="1">1</button></li>');
            if (startPage > 2) {
                items.push('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }

        for (var index = startPage; index <= endPage; index += 1) {
            items.push('<li class="page-item' + (index === paging.page ? ' active' : '') + '"><button class="page-link" type="button" data-page="' + index + '">' + index + '</button></li>');
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                items.push('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
            items.push('<li class="page-item"><button class="page-link" type="button" data-page="' + totalPages + '">' + totalPages + '</button></li>');
        }

        items.push('<li class="page-item' + disabledNext + '"><button class="page-link" type="button" data-page="' + (paging.page + 1) + '">Next</button></li>');
        pagination.innerHTML = items.join('');

        pagination.querySelectorAll('[data-page]').forEach(function (button) {
            button.addEventListener('click', function () {
                var nextPage = parseInt(button.getAttribute('data-page'), 10);
                if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage > totalPages) {
                    return;
                }

                paging.page = nextPage;
                renderTable();
            });
        });
    }

    function renderTable() {
        var filtered = applyFilters();
        var total = experiences.length;
        var totalPages = Math.max(1, Math.ceil(filtered.length / paging.size));
        paging.page = Math.min(Math.max(paging.page, 1), totalPages);
        var offset = (paging.page - 1) * paging.size;
        var pageItems = filtered.slice(offset, offset + paging.size);

        if (pageItems.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">No experiences found.</td></tr>';
            renderPagination(total, filtered.length);
            return;
        }

        tableBody.innerHTML = pageItems.map(function (experience) {
            return [
                '<tr data-experience-id="' + escapeHtml(experience.work_experience_id) + '">',
                '<td class="fw-semibold">' + escapeHtml(experience.employer_name || '') + '</td>',
                '<td>',
                '<div class="fw-semibold">' + escapeHtml(experience.job_title || '') + '</div>',
                experience.location ? '<div class="text-muted small">' + escapeHtml(experience.location) + '</div>' : '',
                '</td>',
                '<td>',
                '<div>' + escapeHtml(experience.employment_period || formatPeriod(experience)) + '</div>',
                experience.employment_type ? '<div class="text-muted small">' + escapeHtml(experience.employment_type) + '</div>' : '',
                '</td>',
                '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(experience.display_order) + '</span></td>',
                '<td>' + (Number(experience.is_public) === 1 ? '<span class="badge text-bg-success">Public</span>' : '<span class="badge text-bg-secondary">Private</span>') + '</td>',
                '<td class="text-end text-nowrap">',
                '<button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="edit" data-experience-id="' + escapeHtml(experience.work_experience_id) + '"><i class="fa fa-pencil"></i></button>',
                '<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-experience-id="' + escapeHtml(experience.work_experience_id) + '"><i class="fa fa-trash-o"></i></button>',
                '</td>',
                '</tr>'
            ].join('');
        }).join('');

        renderPagination(total, filtered.length);
    }

    function setSortIndicator(button, key) {
        var current = sortState.key === key ? sortState.direction : '';
        button.textContent = button.getAttribute('data-label') || button.textContent.replace(/\s\((?:asc|desc)\)$/, '');
        if (current === 'asc') {
            button.textContent += ' (asc)';
        } else if (current === 'desc') {
            button.textContent += ' (desc)';
        }
    }

    function updateSortIndicators() {
        table.querySelectorAll('[data-sort-key]').forEach(function (button) {
            setSortIndicator(button, button.getAttribute('data-sort-key'));
        });
    }

    function resetForm() {
        form.reset();
        clearValidation();
        clearFormAlert();
        form.experience_id.value = '';
        form.profile_id.value = experiences[0] && experiences[0].profile_id ? experiences[0].profile_id : (form.profile_id.value || '');
        form.is_public.checked = true;
        form.is_current.checked = false;
        form.display_order.value = '0';
        if (modalTitle) {
            modalTitle.textContent = 'Add Experience';
        }
    }

    function fillForm(experience) {
        resetForm();
        form.experience_id.value = experience.work_experience_id || '';
        form.profile_id.value = experience.profile_id || form.profile_id.value || '';
        form.employer_name.value = experience.employer_name || '';
        form.job_title.value = experience.job_title || '';
        form.employment_type.value = experience.employment_type || '';
        form.location.value = experience.location || '';
        form.start_date.value = experience.start_date || '';
        form.end_date.value = experience.end_date || '';
        form.is_current.checked = Number(experience.is_current) === 1;
        form.is_public.checked = Number(experience.is_public) === 1;
        form.display_order.value = experience.display_order !== null && experience.display_order !== undefined ? experience.display_order : 0;
        form.description.value = experience.description || '';
        if (modalTitle) {
            modalTitle.textContent = 'Edit Experience';
        }
    }

    function loadExperience(experienceId) {
        return fetch(loadUrl + '?action=show&id=' + encodeURIComponent(experienceId), {
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        });
    }

    function openCreateModal() {
        resetForm();
        if (modal) {
            modal.show();
        }
        setTimeout(function () {
            form.employer_name.focus();
        }, 150);
    }

    function openEditModal(experienceId) {
        showFormAlert('Loading experience...', 'info');
        if (modal) {
            modal.show();
        }

        loadExperience(experienceId).then(function (payload) {
            clearFormAlert();
            if (!payload.success || !payload.data || !payload.data.experience) {
                showFormAlert(payload.message || 'Unable to load experience.', 'danger');
                return;
            }

            fillForm(payload.data.experience);
            setTimeout(function () {
                form.employer_name.focus();
            }, 150);
        }).catch(function () {
            showFormAlert('Unable to load experience.', 'danger');
        });
    }

    function loadExperiences() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Loading experiences...</td></tr>';

        fetch(loadUrl + '?action=list', {
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                throw new Error(payload.message || 'Unable to load experiences.');
            }

            experiences = (payload.data && payload.data.experiences) ? payload.data.experiences : [];
            if (payload.data && payload.data.profile_id) {
                form.profile_id.value = payload.data.profile_id;
            }
            paging.page = 1;
            updateSortIndicators();
            renderTable();
        }).catch(function (error) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">' + escapeHtml(error.message || 'Unable to load experiences.') + '</td></tr>';
            if (meta) {
                meta.textContent = '';
            }
            if (range) {
                range.textContent = '';
            }
            if (pagination) {
                pagination.innerHTML = '';
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

    function serializeForm() {
        var data = new FormData(form);
        data.set('csrf_token', csrfToken);
        data.set('is_current', form.is_current.checked ? '1' : '0');
        data.set('is_public', form.is_public.checked ? '1' : '0');
        return data;
    }

    function saveExperience(event) {
        event.preventDefault();
        clearFormAlert();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setBusy(true);
        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: serializeForm()
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            setBusy(false);
            if (!payload.success) {
                applyErrors(payload.errors || {});
                showFormAlert(payload.message || 'Unable to save experience.', 'danger');
                return;
            }

            showAlert(payload.message || 'Experience saved successfully.', 'success');
            if (modal) {
                modal.hide();
            }
            loadExperiences();
        }).catch(function () {
            setBusy(false);
            showFormAlert('Unable to save experience.', 'danger');
        });
    }

    function deleteExperience(experienceId) {
        if (!window.confirm('Delete this experience?')) {
            return;
        }

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('experience_id', experienceId);

        fetch(deleteUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                showAlert(payload.message || 'Unable to delete experience.', 'danger');
                return;
            }

            showAlert(payload.message || 'Experience deleted successfully.', 'success');
            loadExperiences();
        }).catch(function () {
            showAlert('Unable to delete experience.', 'danger');
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', openCreateModal);
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', loadExperiences);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            paging.page = 1;
            renderTable();
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            paging.size = parseInt(pageSizeSelect.value, 10) || 10;
            paging.page = 1;
            renderTable();
        });
    }

    table.querySelectorAll('[data-sort-key]').forEach(function (button) {
        button.addEventListener('click', function () {
            var key = button.getAttribute('data-sort-key');
            if (sortState.key === key) {
                sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sortState.key = key;
                sortState.direction = key === 'start_date' ? 'desc' : 'asc';
            }

            updateSortIndicators();
            renderTable();
        });
    });

    tableBody.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }

        var action = button.getAttribute('data-action');
        var experienceId = button.getAttribute('data-experience-id');

        if (action === 'edit') {
            openEditModal(experienceId);
            return;
        }

        if (action === 'delete') {
            deleteExperience(experienceId);
        }
    });

    form.addEventListener('submit', saveExperience);

    modalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    loadExperiences();
}());
