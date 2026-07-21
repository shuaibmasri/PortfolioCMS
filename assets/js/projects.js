(function () {
    'use strict';

    var adminTable = document.getElementById('projectsTable');
    var publicGrid = document.getElementById('projectsGrid');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toArray(value) {
        if (Array.isArray(value)) {
            return value;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            return value.split('||').map(function (item) {
                return item.trim();
            }).filter(Boolean);
        }

        return [];
    }

    function statusLabel(status) {
        switch (status) {
            case 'in_progress':
                return 'In Progress';
            case 'completed':
                return 'Completed';
            case 'archived':
                return 'Archived';
            default:
                return 'Planned';
        }
    }

    function statusBadgeClass(status) {
        switch (status) {
            case 'in_progress':
                return 'text-bg-info';
            case 'completed':
                return 'text-bg-success';
            case 'archived':
                return 'text-bg-secondary';
            default:
                return 'text-bg-warning';
        }
    }

    if (adminTable) {
        initAdmin();
        return;
    }

    if (publicGrid) {
        initPublic();
    }

    function initAdmin() {
        var form = document.getElementById('projectForm');
        var modalEl = document.getElementById('projectModal');
        var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        var alertBox = document.getElementById('projectsAlert');
        var formAlertBox = document.getElementById('projectFormAlert');
        var tableBody = adminTable.querySelector('tbody');
        var searchInput = document.getElementById('projectsSearch');
        var pageSizeSelect = document.getElementById('projectsPageSize');
        var pagination = document.getElementById('projectsPagination');
        var meta = document.getElementById('projectsMeta');
        var range = document.getElementById('projectsRange');
        var addBtn = document.getElementById('projectsAddBtn');
        var reloadBtn = document.getElementById('projectsReloadBtn');
        var saveBtn = document.getElementById('projectSaveBtn');
        var saveText = saveBtn ? saveBtn.querySelector('.project-save-text') : null;
        var saveSpinner = saveBtn ? saveBtn.querySelector('.project-save-spinner') : null;
        var imageInput = document.getElementById('project_image');
        var imagePreview = document.getElementById('projectImagePreview');
        var categorySuggestions = document.getElementById('projectCategorySuggestions');
        var technologySuggestions = document.getElementById('projectTechnologySuggestions');
        var modalTitle = document.getElementById('projectModalLabel');
        var csrfToken = form.querySelector('[name="csrf_token"]').value;
        var loadUrl = form.dataset.showUrl;
        var saveUrl = form.dataset.saveUrl;
        var deleteUrl = form.dataset.deleteUrl;
        var sortState = { key: 'display_order', direction: 'asc' };
        var paging = { page: 1, size: parseInt(pageSizeSelect ? pageSizeSelect.value : '10', 10) || 10 };
        var projects = [];

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
            saveText.textContent = isBusy ? 'Saving...' : 'Save Project';
            saveSpinner.classList.toggle('d-none', !isBusy);
        }

        function statusBadge(status) {
            return '<span class="badge ' + statusBadgeClass(status) + '">' + escapeHtml(statusLabel(status)) + '</span>';
        }

        function buildTechnologyChips(items) {
            if (!items.length) {
                return '<span class="text-muted small">No technologies listed</span>';
            }

            return items.map(function (item) {
                return '<span class="badge rounded-pill text-bg-light border project-tech-chip">' + escapeHtml(item) + '</span>';
            }).join(' ');
        }

        function compareValues(a, b) {
            if (typeof a === 'number' && typeof b === 'number') {
                return a - b;
            }

            return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
        }

        function applyFilters() {
            var term = (searchInput ? searchInput.value : '').trim().toLowerCase();
            var filtered = projects.filter(function (project) {
                if (!term) {
                    return true;
                }

                return [
                    project.title,
                    project.category_name,
                    project.status_label,
                    project.short_description,
                    project.technology_names,
                    project.display_order,
                    project.is_public ? 'public' : 'private'
                ].some(function (value) {
                    return String(value || '').toLowerCase().indexOf(term) !== -1;
                });
            });

            filtered.sort(function (left, right) {
                var leftValue = left[sortState.key];
                var rightValue = right[sortState.key];
                var result;

                if (sortState.key === 'display_order' || sortState.key === 'is_public') {
                    result = compareValues(Number(leftValue), Number(rightValue));
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
                range.textContent = 'Showing ' + start + ' to ' + end + ' of ' + filteredCount + ' projects';
            }

            if (meta) {
                meta.textContent = total + ' total project' + (total === 1 ? '' : 's');
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

        function updateSortIndicators() {
            adminTable.querySelectorAll('[data-sort-key]').forEach(function (button) {
                var key = button.getAttribute('data-sort-key');
                var label = button.getAttribute('data-label') || button.textContent;
                var active = sortState.key === key ? sortState.direction : '';
                button.textContent = label + (active === 'asc' ? ' (asc)' : active === 'desc' ? ' (desc)' : '');
            });
        }

        function renderTable() {
            var filtered = applyFilters();
            var total = projects.length;
            var totalPages = Math.max(1, Math.ceil(filtered.length / paging.size));
            paging.page = Math.min(Math.max(paging.page, 1), totalPages);
            var offset = (paging.page - 1) * paging.size;
            var pageItems = filtered.slice(offset, offset + paging.size);

            if (pageItems.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">No projects found.</td></tr>';
                renderPagination(total, filtered.length);
                return;
            }

            tableBody.innerHTML = pageItems.map(function (project) {
                var image = project.image_url
                    ? '<img src="' + escapeHtml(project.image_url) + '" alt="' + escapeHtml(project.title || '') + '" class="project-thumb">'
                    : '<div class="project-thumb project-thumb--generated"><i class="fa fa-code" aria-hidden="true"></i><span>' + escapeHtml(project.title || 'Project') + '</span></div>';

                return [
                    '<tr data-project-id="' + escapeHtml(project.project_id) + '">',
                    '<td>' + image + '</td>',
                    '<td class="fw-semibold">' + escapeHtml(project.title || '') + '</td>',
                    '<td><span class="badge rounded-pill text-bg-light border">' + escapeHtml(project.category_name || 'Uncategorized') + '</span></td>',
                    '<td><div class="d-flex flex-wrap gap-1">' + buildTechnologyChips(toArray(project.technology_names)) + '</div></td>',
                    '<td>' + statusBadge(project.status || 'planned') + '</td>',
                    '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(project.display_order) + '</span></td>',
                    '<td>' + (Number(project.is_public) === 1 ? '<span class="badge text-bg-success">Public</span>' : '<span class="badge text-bg-secondary">Private</span>') + '</td>',
                    '<td class="text-end text-nowrap">',
                    '<button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="edit" data-project-id="' + escapeHtml(project.project_id) + '"><i class="fa fa-pencil"></i></button>',
                    '<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-project-id="' + escapeHtml(project.project_id) + '"><i class="fa fa-trash-o"></i></button>',
                    '</td>',
                    '</tr>'
                ].join('');
            }).join('');

            renderPagination(total, filtered.length);
        }

        function setSortFromButton(button) {
            var key = button.getAttribute('data-sort-key');
            if (sortState.key === key) {
                sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sortState.key = key;
                sortState.direction = key === 'display_order' || key === 'is_public' ? 'asc' : 'asc';
            }

            updateSortIndicators();
            renderTable();
        }

        function updateSuggestions(list, target) {
            if (!target) {
                return;
            }

            var seen = {};
            var items = [];
            list.forEach(function (value) {
                var name = String(value || '').trim();
                if (!name) {
                    return;
                }

                var key = name.toLowerCase();
                if (seen[key]) {
                    return;
                }

                seen[key] = true;
                items.push('<option value="' + escapeHtml(name) + '"></option>');
            });

            target.innerHTML = items.join('');
        }

        function setImagePreview(url, title) {
            if (!imagePreview) {
                return;
            }

            if (!url) {
                imagePreview.innerHTML = '<span class="text-muted">Image preview will appear here.</span>';
                return;
            }

            imagePreview.innerHTML = '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(title || 'Project image') + '" class="media-preview__image" decoding="async">';
        }

        function resetForm() {
            form.reset();
            clearValidation();
            clearFormAlert();
            form.project_id.value = '';
            form.current_image_path.value = '';
            form.is_public.checked = true;
            form.display_order.value = '0';
            form.status.value = 'planned';
            imageInput.required = true;
            if (imagePreview) {
                imagePreview.dataset.previewUrl = '';
            }
            setImagePreview('', '');
            if (modalTitle) {
                modalTitle.textContent = 'Add Project';
            }
        }

        function fillForm(project) {
            resetForm();
            form.project_id.value = project.project_id || '';
            form.current_image_path.value = project.image_path || '';
            form.title.value = project.title || '';
            form.short_description.value = project.short_description || '';
            form.description.value = project.description || '';
            form.category_name.value = project.category_name || '';
            form.technologies.value = toArray(project.technology_names).join(', ');
            form.repository_url.value = project.repository_url || '';
            form.project_url.value = project.project_url || '';
            form.display_order.value = project.display_order !== null && project.display_order !== undefined ? project.display_order : 0;
            form.status.value = project.status || 'planned';
            form.is_public.checked = Number(project.is_public) === 1;
            imageInput.required = false;
            if (imagePreview) {
                imagePreview.dataset.previewUrl = project.image_url || '';
            }
            setImagePreview(project.image_url || '', project.title || '');
            if (modalTitle) {
                modalTitle.textContent = 'Edit Project';
            }
        }

        function loadProject(projectId) {
            return fetch(loadUrl + '?action=show&id=' + encodeURIComponent(projectId), {
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
                form.title.focus();
            }, 150);
        }

        function openEditModal(projectId) {
            showFormAlert('Loading project...', 'info');
            if (modal) {
                modal.show();
            }

            loadProject(projectId).then(function (payload) {
                clearFormAlert();
                if (!payload.success || !payload.data || !payload.data.project) {
                    showFormAlert(payload.message || 'Unable to load project.', 'danger');
                    return;
                }

                fillForm(payload.data.project);
                setTimeout(function () {
                    form.title.focus();
                }, 150);
            }).catch(function () {
                showFormAlert('Unable to load project.', 'danger');
            });
        }

        function loadProjects() {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">Loading projects...</td></tr>';

            fetch(loadUrl + '?action=list', {
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    throw new Error(payload.message || 'Unable to load projects.');
                }

                projects = (payload.data && payload.data.projects) ? payload.data.projects : [];
                updateSuggestions((payload.data && payload.data.categories) ? payload.data.categories : [], categorySuggestions);
                updateSuggestions((payload.data && payload.data.technologies) ? payload.data.technologies : [], technologySuggestions);
                paging.page = 1;
                updateSortIndicators();
                renderTable();
            }).catch(function (error) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-5">' + escapeHtml(error.message || 'Unable to load projects.') + '</td></tr>';
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
            Object.keys(errors || {}).forEach(function (field) {
                var input = form.elements[field];
                if (input && input.classList) {
                    input.classList.add('is-invalid');
                }
            });
        }

        function serializeForm() {
            var data = new FormData(form);
            data.set('csrf_token', csrfToken);
            data.set('is_public', form.is_public.checked ? '1' : '0');
            return data;
        }

        function saveProject(event) {
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
                    showFormAlert(payload.message || 'Unable to save project.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Project saved successfully.', 'success');
                if (modal) {
                    modal.hide();
                }
                loadProjects();
            }).catch(function () {
                setBusy(false);
                showFormAlert('Unable to save project.', 'danger');
            });
        }

        function deleteProject(projectId) {
            if (!window.confirm('Delete this project?')) {
                return;
            }

            var data = new FormData();
            data.append('csrf_token', csrfToken);
            data.append('project_id', projectId);

            fetch(deleteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    showAlert(payload.message || 'Unable to delete project.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Project deleted successfully.', 'success');
                loadProjects();
            }).catch(function () {
                showAlert('Unable to delete project.', 'danger');
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', openCreateModal);
        }

        if (reloadBtn) {
            reloadBtn.addEventListener('click', loadProjects);
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

        adminTable.querySelectorAll('[data-sort-key]').forEach(function (button) {
            button.addEventListener('click', function () {
                setSortFromButton(button);
            });
        });

        tableBody.addEventListener('click', function (event) {
            var button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }

            var action = button.getAttribute('data-action');
            var projectId = button.getAttribute('data-project-id');

            if (action === 'edit') {
                openEditModal(projectId);
                return;
            }

            if (action === 'delete') {
                deleteProject(projectId);
            }
        });

        if (imageInput) {
            imageInput.addEventListener('change', function () {
                var file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
                if (!file) {
                    if (form.project_id.value && imagePreview && imagePreview.dataset.previewUrl) {
                        setImagePreview(imagePreview.dataset.previewUrl, form.title.value || '');
                    } else {
                        setImagePreview('', '');
                    }
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (event) {
                    setImagePreview(String(event.target && event.target.result ? event.target.result : ''), form.title.value || '');
                };
                reader.readAsDataURL(file);
            });
        }

        form.addEventListener('submit', saveProject);

        modalEl.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });

        loadProjects();
    }

    function initPublic() {
        var dataEl = document.getElementById('projectsData');
        var detailsModalEl = document.getElementById('projectDetailsModal');
        var detailsModal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(detailsModalEl) : null;
        var titleEl = document.getElementById('projectDetailsTitle');
        var categoryEl = document.getElementById('projectDetailsCategory');
        var statusEl = document.getElementById('projectDetailsStatus');
        var shortDescEl = document.getElementById('projectDetailsShortDescription');
        var fullDescEl = document.getElementById('projectDetailsDescription');
        var techEl = document.getElementById('projectDetailsTechnologies');
        var githubBtn = document.getElementById('projectDetailsGithub');
        var liveBtn = document.getElementById('projectDetailsDemo');
        var imageEl = document.getElementById('projectDetailsImage');
        var coverEl = document.getElementById('projectDetailsCover');
        var projects = [];
        var projectMap = {};

        if (dataEl) {
            try {
                projects = JSON.parse(dataEl.textContent || '[]');
            } catch (error) {
                projects = [];
            }
        }

        projects.forEach(function (project) {
            projectMap[String(project.project_id)] = project;
        });

        function renderTechnologyTags(items) {
            if (!items.length) {
                return '<span class="text-muted">No technologies listed.</span>';
            }

            return items.map(function (item) {
                return '<span class="badge rounded-pill text-bg-light border project-tech-chip">' + escapeHtml(item) + '</span>';
            }).join(' ');
        }

        function projectCoverIcon(project) {
            var context = [project.title, project.category_name].concat(toArray(project.technologies)).join(' ').toLowerCase();
            if (/hospital|health|clinic|medical/.test(context)) return 'fa-heartbeat';
            if (/erp|inventory|accounting|finance/.test(context)) return 'fa-line-chart';
            if (/human resources|\bhr\b|recruit/.test(context)) return 'fa-users';
            if (/api|integration|backend/.test(context)) return 'fa-exchange';
            if (/flutter|mobile|android|ios/.test(context)) return 'fa-mobile';
            if (/shop|ecommerce|store|market/.test(context)) return 'fa-shopping-cart';
            if (/education|school|course|learning/.test(context)) return 'fa-graduation-cap';
            if (/security|auth|cyber/.test(context)) return 'fa-shield';
            if (/dashboard|analytics|report/.test(context)) return 'fa-bar-chart';
            return 'fa-code';
        }

        function renderProjectCover(project) {
            var taglines = {
                'fa-heartbeat': 'Care, connected', 'fa-line-chart': 'Clarity at scale', 'fa-users': 'People, in sync',
                'fa-exchange': 'Systems in motion', 'fa-mobile': 'Built for every screen', 'fa-shopping-cart': 'Commerce, refined',
                'fa-graduation-cap': 'Learning, elevated', 'fa-shield': 'Trust by design', 'fa-bar-chart': 'Insights in focus', 'fa-code': 'Crafted with purpose'
            };
            var icon = projectCoverIcon(project);
            return '<div class="project-cover" aria-label="' + escapeHtml(project.title || 'Project') + ' project cover">' +
                '<div class="project-cover__glow project-cover__glow--one"></div><div class="project-cover__glow project-cover__glow--two"></div>' +
                '<i class="fa ' + icon + ' project-cover__icon" aria-hidden="true"></i>' +
                '<div class="project-cover__content"><span class="project-cover__tagline">' + taglines[icon] + '</span></div></div>';
        }

        function fillDetails(project) {
            if (!project) {
                return;
            }

            if (titleEl) {
                titleEl.textContent = project.title || 'Project Details';
            }
            if (categoryEl) {
                categoryEl.textContent = project.category_name || 'Uncategorized';
            }
            if (statusEl) {
                statusEl.className = 'badge ' + statusBadgeClass(project.status || 'planned');
                statusEl.textContent = statusLabel(project.status || 'planned');
            }
            if (shortDescEl) {
                shortDescEl.textContent = project.short_description || '';
            }
            if (fullDescEl) {
                fullDescEl.innerHTML = escapeHtml(project.description || '').replace(/\n/g, '<br>');
            }
            if (techEl) {
                techEl.innerHTML = renderTechnologyTags(toArray(project.technologies));
            }
            if (githubBtn) {
                if (project.repository_url) {
                    githubBtn.href = project.repository_url;
                    githubBtn.classList.remove('disabled');
                    githubBtn.style.display = '';
                } else {
                    githubBtn.style.display = 'none';
                }
            }
            if (liveBtn) {
                if (project.project_url) {
                    liveBtn.href = project.project_url;
                    liveBtn.classList.remove('disabled');
                    liveBtn.style.display = '';
                } else {
                    liveBtn.style.display = 'none';
                }
            }
            if (coverEl && imageEl) {
                if (project.image_url) {
                    coverEl.innerHTML = '';
                    coverEl.appendChild(imageEl);
                    imageEl.src = project.image_url;
                    imageEl.alt = project.title || 'Project image';
                } else {
                    coverEl.innerHTML = renderProjectCover(project);
                }
            }
        }

        publicGrid.addEventListener('click', function (event) {
            var button = event.target.closest('button[data-project-id]');
            if (!button) {
                return;
            }

            var projectId = button.getAttribute('data-project-id');
            fillDetails(projectMap[projectId]);
            if (detailsModal) {
                detailsModal.show();
            }
        });
    }
}());
