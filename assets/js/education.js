(function () {
    'use strict';

    var table = document.getElementById('educationTable');
    if (!table) {
        return;
    }

    var form = document.getElementById('educationForm');
    var modalEl = document.getElementById('educationModal');
    var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    var alertBox = document.getElementById('educationAlert');
    var formAlertBox = document.getElementById('educationFormAlert');
    var tableBody = table.querySelector('tbody');
    var searchInput = document.getElementById('educationSearch');
    var pageSizeSelect = document.getElementById('educationPageSize');
    var pagination = document.getElementById('educationPagination');
    var meta = document.getElementById('educationMeta');
    var range = document.getElementById('educationRange');
    var addBtn = document.getElementById('educationAddBtn');
    var reloadBtn = document.getElementById('educationReloadBtn');
    var saveBtn = document.getElementById('educationSaveBtn');
    var saveText = saveBtn ? saveBtn.querySelector('.education-save-text') : null;
    var saveSpinner = saveBtn ? saveBtn.querySelector('.education-save-spinner') : null;
    var sortState = { key: 'display_order', direction: 'asc' };
    var paging = { page: 1, size: parseInt(pageSizeSelect ? pageSizeSelect.value : '10', 10) || 10 };
    var educations = [];

    var loadUrl = form.dataset.showUrl;
    var saveUrl = form.dataset.saveUrl;
    var deleteUrl = form.dataset.deleteUrl;
    var csrfToken = form.querySelector('[name="csrf_token"]').value;
    var modalTitle = document.getElementById('educationModalLabel');

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
        saveText.textContent = isBusy ? 'Saving...' : 'Save Education';
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

    function graduationYear(item) {
        if (item.end_date) {
            return formatDate(item.end_date).replace(/^[A-Za-z]{3}\s/, '');
        }

        if (item.start_date) {
            return formatDate(item.start_date).replace(/^[A-Za-z]{3}\s/, '');
        }

        return '';
    }

    function compareValues(a, b) {
        if (typeof a === 'number' && typeof b === 'number') {
            return a - b;
        }

        return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
    }

    function applyFilters() {
        var term = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var filtered = educations.filter(function (education) {
            if (!term) {
                return true;
            }

            return [
                education.institution_name,
                education.degree,
                education.field_of_study,
                education.location,
                education.grade,
                education.description,
                education.graduation_year,
                education.display_order,
                education.is_public ? 'public' : 'private'
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
            range.textContent = 'Showing ' + start + ' to ' + end + ' of ' + filteredCount + ' education records';
        }

        if (meta) {
            meta.textContent = total + ' total education record' + (total === 1 ? '' : 's');
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
        var total = educations.length;
        var totalPages = Math.max(1, Math.ceil(filtered.length / paging.size));
        paging.page = Math.min(Math.max(paging.page, 1), totalPages);
        var offset = (paging.page - 1) * paging.size;
        var pageItems = filtered.slice(offset, offset + paging.size);

        if (pageItems.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5">No education records found.</td></tr>';
            renderPagination(total, filtered.length);
            return;
        }

        tableBody.innerHTML = pageItems.map(function (education) {
            var year = education.graduation_year || graduationYear(education);
            return [
                '<tr data-education-id="' + escapeHtml(education.education_id) + '">',
                '<td class="fw-semibold">' + escapeHtml(education.institution_name || '') + '</td>',
                '<td>',
                '<div class="fw-semibold">' + escapeHtml(education.degree || '') + '</div>',
                education.location ? '<div class="text-muted small">' + escapeHtml(education.location) + '</div>' : '',
                '</td>',
                '<td>' + escapeHtml(education.field_of_study || '') + '</td>',
                '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(year || '-') + '</span></td>',
                '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(education.display_order) + '</span></td>',
                '<td>' + (Number(education.is_public) === 1 ? '<span class="badge text-bg-success">Public</span>' : '<span class="badge text-bg-secondary">Private</span>') + '</td>',
                '<td class="text-end text-nowrap">',
                '<button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="edit" data-education-id="' + escapeHtml(education.education_id) + '"><i class="fa fa-pencil"></i></button>',
                '<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-education-id="' + escapeHtml(education.education_id) + '"><i class="fa fa-trash-o"></i></button>',
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
        form.education_id.value = '';
        form.profile_id.value = educations[0] && educations[0].profile_id ? educations[0].profile_id : (form.profile_id.value || '');
        form.is_public.checked = true;
        form.display_order.value = '0';
        if (modalTitle) {
            modalTitle.textContent = 'Add Education';
        }
    }

    function fillForm(education) {
        resetForm();
        form.education_id.value = education.education_id || '';
        form.profile_id.value = education.profile_id || form.profile_id.value || '';
        form.institution_name.value = education.institution_name || '';
        form.degree.value = education.degree || '';
        form.field_of_study.value = education.field_of_study || '';
        form.location.value = education.location || '';
        form.start_date.value = education.start_date || '';
        form.end_date.value = education.end_date || '';
        form.grade.value = education.grade || '';
        form.description.value = education.description || '';
        form.is_public.checked = Number(education.is_public) === 1;
        form.display_order.value = education.display_order !== null && education.display_order !== undefined ? education.display_order : 0;
        if (modalTitle) {
            modalTitle.textContent = 'Edit Education';
        }
    }

    function loadEducation(educationId) {
        return fetch(loadUrl + '?action=show&id=' + encodeURIComponent(educationId), {
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
            form.institution_name.focus();
        }, 150);
    }

    function openEditModal(educationId) {
        showFormAlert('Loading education record...', 'info');
        if (modal) {
            modal.show();
        }

        loadEducation(educationId).then(function (payload) {
            clearFormAlert();
            if (!payload.success || !payload.data || !payload.data.education) {
                showFormAlert(payload.message || 'Unable to load education record.', 'danger');
                return;
            }

            fillForm(payload.data.education);
            setTimeout(function () {
                form.institution_name.focus();
            }, 150);
        }).catch(function () {
            showFormAlert('Unable to load education record.', 'danger');
        });
    }

    function loadEducations() {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5">Loading education records...</td></tr>';

        fetch(loadUrl + '?action=list', {
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                throw new Error(payload.message || 'Unable to load education records.');
            }

            educations = (payload.data && payload.data.educations) ? payload.data.educations : [];
            if (payload.data && payload.data.profile_id) {
                form.profile_id.value = payload.data.profile_id;
            }
            paging.page = 1;
            updateSortIndicators();
            renderTable();
        }).catch(function (error) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-5">' + escapeHtml(error.message || 'Unable to load education records.') + '</td></tr>';
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
        data.set('is_public', form.is_public.checked ? '1' : '0');
        return data;
    }

    function saveEducation(event) {
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
                showFormAlert(payload.message || 'Unable to save education record.', 'danger');
                return;
            }

            showAlert(payload.message || 'Education record saved successfully.', 'success');
            if (modal) {
                modal.hide();
            }
            loadEducations();
        }).catch(function () {
            setBusy(false);
            showFormAlert('Unable to save education record.', 'danger');
        });
    }

    function deleteEducation(educationId) {
        if (!window.confirm('Delete this education record?')) {
            return;
        }

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('education_id', educationId);

        fetch(deleteUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                showAlert(payload.message || 'Unable to delete education record.', 'danger');
                return;
            }

            showAlert(payload.message || 'Education record deleted successfully.', 'success');
            loadEducations();
        }).catch(function () {
            showAlert('Unable to delete education record.', 'danger');
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', openCreateModal);
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', loadEducations);
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
        var educationId = button.getAttribute('data-education-id');

        if (action === 'edit') {
            openEditModal(educationId);
            return;
        }

        if (action === 'delete') {
            deleteEducation(educationId);
        }
    });

    form.addEventListener('submit', saveEducation);

    modalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    loadEducations();
}());
