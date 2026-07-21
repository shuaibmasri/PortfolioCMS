(function () {
    'use strict';

    var page = document.getElementById('skillsTable');
    if (!page) {
        return;
    }

    var form = document.getElementById('skillForm');
    var modalEl = document.getElementById('skillModal');
    var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    var alertBox = document.getElementById('skillsAlert');
    var formAlertBox = document.getElementById('skillFormAlert');
    var tableBody = page.querySelector('tbody');
    var searchInput = document.getElementById('skillsSearch');
    var pageSizeSelect = document.getElementById('skillsPageSize');
    var pagination = document.getElementById('skillsPagination');
    var meta = document.getElementById('skillsMeta');
    var range = document.getElementById('skillsRange');
    var addBtn = document.getElementById('skillsAddBtn');
    var reloadBtn = document.getElementById('skillsReloadBtn');
    var saveBtn = document.getElementById('skillSaveBtn');
    var saveText = saveBtn ? saveBtn.querySelector('.skill-save-text') : null;
    var saveSpinner = saveBtn ? saveBtn.querySelector('.skill-save-spinner') : null;
    var sortState = { key: 'display_order', direction: 'asc' };
    var paging = { page: 1, size: parseInt(pageSizeSelect ? pageSizeSelect.value : '10', 10) || 10 };
    var skills = [];

    var listUrl = form.dataset.showUrl;
    var saveUrl = form.dataset.saveUrl;
    var deleteUrl = form.dataset.deleteUrl;
    var csrfToken = form.querySelector('[name="csrf_token"]').value;
    var modalTitle = document.getElementById('skillModalLabel');
    var categorySuggestions = document.getElementById('skillCategorySuggestions');

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
        form.querySelectorAll('.is-invalid').forEach(function (element) {
            element.classList.remove('is-invalid');
        });
    }

    function setBusy(isBusy) {
        if (!saveBtn || !saveText || !saveSpinner) {
            return;
        }

        saveBtn.disabled = isBusy;
        saveText.textContent = isBusy ? 'Saving...' : 'Save Skill';
        saveSpinner.classList.toggle('d-none', !isBusy);
    }

    function buildBadge(isPublic) {
        return isPublic ? '<span class="badge text-bg-success">Public</span>' : '<span class="badge text-bg-secondary">Private</span>';
    }

    function buildProgress(value) {
        var percentage = Number.isFinite(value) ? Math.max(0, Math.min(100, value)) : 0;
        return (
            '<div class="d-flex align-items-center gap-2">' +
            '<div class="progress flex-grow-1" style="height:.6rem">' +
            '<div class="progress-bar" style="width:' + percentage + '%"></div>' +
            '</div>' +
            '<span class="small text-muted" style="min-width:3rem">' + percentage + '%</span>' +
            '</div>'
        );
    }

    function compareValues(a, b) {
        if (typeof a === 'number' && typeof b === 'number') {
            return a - b;
        }

        return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
    }

    function applyFilters() {
        var term = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var filtered = skills.filter(function (skill) {
            if (!term) {
                return true;
            }

            return [
                skill.skill_name,
                skill.category_name,
                skill.proficiency_level,
                skill.display_order,
                skill.is_public ? 'public' : 'private'
            ].some(function (value) {
                return String(value).toLowerCase().indexOf(term) !== -1;
            });
        });

        filtered.sort(function (left, right) {
            var leftValue = left[sortState.key];
            var rightValue = right[sortState.key];
            var result;

            if (sortState.key === 'proficiency_level' || sortState.key === 'display_order' || sortState.key === 'is_public') {
                result = compareValues(Number(leftValue), Number(rightValue));
            } else {
                result = compareValues(leftValue, rightValue);
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
            range.textContent = 'Showing ' + start + ' to ' + end + ' of ' + filteredCount + ' skills';
        }

        if (meta) {
            meta.textContent = total + ' total skill' + (total === 1 ? '' : 's');
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
            items.push(
                '<li class="page-item' + (index === paging.page ? ' active' : '') + '">' +
                '<button class="page-link" type="button" data-page="' + index + '">' + index + '</button>' +
                '</li>'
            );
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
        var total = skills.length;
        var totalPages = Math.max(1, Math.ceil(filtered.length / paging.size));
        paging.page = Math.min(Math.max(paging.page, 1), totalPages);
        var offset = (paging.page - 1) * paging.size;
        var pageItems = filtered.slice(offset, offset + paging.size);

        if (pageItems.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">No skills found.</td></tr>';
            renderPagination(total, filtered.length);
            return;
        }

        tableBody.innerHTML = pageItems.map(function (skill) {
            var proficiency = parseInt(skill.proficiency_level, 10);
            if (!Number.isFinite(proficiency)) {
                proficiency = 0;
            }

            return [
                '<tr data-skill-id="' + escapeHtml(skill.skill_id) + '">',
                '<td class="fw-semibold">' + escapeHtml(skill.skill_name) + '</td>',
                '<td><span class="badge rounded-pill text-bg-light border">' + escapeHtml(skill.category_name) + '</span></td>',
                '<td>' + buildProgress(proficiency) + '</td>',
                '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(skill.display_order) + '</span></td>',
                '<td>' + buildBadge(Number(skill.is_public) === 1) + '</td>',
                '<td class="text-end text-nowrap">',
                '<button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="edit" data-skill-id="' + escapeHtml(skill.skill_id) + '"><i class="fa fa-pencil"></i></button>',
                '<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-skill-id="' + escapeHtml(skill.skill_id) + '"><i class="fa fa-trash-o"></i></button>',
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
        page.querySelectorAll('[data-sort-key]').forEach(function (button) {
            setSortIndicator(button, button.getAttribute('data-sort-key'));
        });
    }

    function updateCategorySuggestions(list) {
        if (!categorySuggestions) {
            return;
        }

        var seen = {};
        var items = [];
        list.forEach(function (skill) {
            var category = String(skill.category_name || '').trim();
            if (!category || seen[category.toLowerCase()]) {
                return;
            }

            seen[category.toLowerCase()] = true;
            items.push('<option value="' + escapeHtml(category) + '"></option>');
        });

        categorySuggestions.innerHTML = items.join('');
    }

    function resetForm() {
        form.reset();
        clearValidation();
        clearFormAlert();
        form.skill_id.value = '';
        form.is_public.checked = true;
        form.display_order.value = '0';
        form.proficiency.value = '0';
        if (modalTitle) {
            modalTitle.textContent = 'Add Skill';
        }
    }

    function openCreateModal() {
        resetForm();
        if (modal) {
            modal.show();
        }
        setTimeout(function () {
            form.skill_name.focus();
        }, 150);
    }

    function fillForm(skill) {
        resetForm();
        form.skill_id.value = skill.skill_id || '';
        form.skill_name.value = skill.skill_name || '';
        form.skill_category.value = skill.category_name || '';
        form.proficiency.value = skill.proficiency_level !== null && skill.proficiency_level !== undefined ? skill.proficiency_level : 0;
        form.display_order.value = skill.display_order !== null && skill.display_order !== undefined ? skill.display_order : 0;
        form.is_public.checked = Number(skill.is_public) === 1;
        if (modalTitle) {
            modalTitle.textContent = 'Edit Skill';
        }
    }

    function loadSkill(skillId) {
        return fetch(listUrl + '?action=show&id=' + encodeURIComponent(skillId), {
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        });
    }

    function openEditModal(skillId) {
        showFormAlert('Loading skill...', 'info');
        if (modal) {
            modal.show();
        }

        loadSkill(skillId).then(function (data) {
            clearFormAlert();
            if (!data.success || !data.skill) {
                showFormAlert(data.message || 'Unable to load skill.', 'danger');
                return;
            }

            fillForm(data.skill);
            setTimeout(function () {
                form.skill_name.focus();
            }, 150);
        }).catch(function () {
            showFormAlert('Unable to load skill.', 'danger');
        });
    }

    function loadSkills() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Loading skills...</td></tr>';

        fetch(listUrl + '?action=list', {
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (!data.success) {
                throw new Error(data.message || 'Unable to load skills.');
            }

            skills = Array.isArray(data.skills) ? data.skills : [];
            updateCategorySuggestions(skills);
            paging.page = 1;
            updateSortIndicators();
            renderTable();
        }).catch(function (error) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">' + escapeHtml(error.message || 'Unable to load skills.') + '</td></tr>';
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

    function serializeFormData() {
        var data = new FormData(form);
        data.set('csrf_token', csrfToken);
        data.set('is_public', form.is_public.checked ? '1' : '0');
        return data;
    }

    function markValidationErrors(errors) {
        clearValidation();
        Object.keys(errors || {}).forEach(function (field) {
            var input = form.elements[field];
            if (input) {
                input.classList.add('is-invalid');
            }
        });
    }

    function saveSkill(event) {
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
            body: serializeFormData()
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            setBusy(false);

            if (!data.success) {
                markValidationErrors(data.errors || {});
                showFormAlert(data.message || 'Unable to save skill.', 'danger');
                return;
            }

            showAlert(data.message || 'Skill saved successfully.', 'success');
            if (modal) {
                modal.hide();
            }
            loadSkills();
        }).catch(function () {
            setBusy(false);
            showFormAlert('Unable to save skill.', 'danger');
        });
    }

    function deleteSkill(skillId) {
        if (!window.confirm('Delete this skill?')) {
            return;
        }

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('skill_id', skillId);

        fetch(deleteUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                showAlert(payload.message || 'Unable to delete skill.', 'danger');
                return;
            }

            showAlert(payload.message || 'Skill deleted successfully.', 'success');
            loadSkills();
        }).catch(function () {
            showAlert('Unable to delete skill.', 'danger');
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', openCreateModal);
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', loadSkills);
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

    page.querySelectorAll('[data-sort-key]').forEach(function (button) {
        button.addEventListener('click', function () {
            var key = button.getAttribute('data-sort-key');
            if (sortState.key === key) {
                sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sortState.key = key;
                sortState.direction = 'asc';
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
        var skillId = button.getAttribute('data-skill-id');

        if (action === 'edit') {
            openEditModal(skillId);
            return;
        }

        if (action === 'delete') {
            deleteSkill(skillId);
        }
    });

    form.addEventListener('submit', saveSkill);

    modalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    loadSkills();
}());
