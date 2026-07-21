(function () {
    'use strict';

    var adminTable = document.getElementById('certificatesTable');
    var publicGrid = document.getElementById('certificatesGrid');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function compareValues(a, b) {
        if (typeof a === 'number' && typeof b === 'number') {
            return a - b;
        }

        return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
    }

    function compareDates(a, b) {
        var left = a ? new Date(a + 'T00:00:00').getTime() : 0;
        var right = b ? new Date(b + 'T00:00:00').getTime() : 0;
        return left - right;
    }

    function formatDate(value, options) {
        if (!value) {
            return '';
        }

        var parsed = new Date(value + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleDateString(undefined, options || { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function isExpired(certificate) {
        if (!certificate.expiry_date) {
            return false;
        }

        var expiry = new Date(certificate.expiry_date + 'T23:59:59');
        return !Number.isNaN(expiry.getTime()) && expiry.getTime() < Date.now();
    }

    if (adminTable) {
        initAdmin();
        return;
    }

    if (publicGrid) {
        initPublic();
    }

    function initAdmin() {
        var form = document.getElementById('certificateForm');
        var modalEl = document.getElementById('certificateModal');
        var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        var alertBox = document.getElementById('certificatesAlert');
        var formAlertBox = document.getElementById('certificateFormAlert');
        var tableBody = adminTable.querySelector('tbody');
        var searchInput = document.getElementById('certificatesSearch');
        var pageSizeSelect = document.getElementById('certificatesPageSize');
        var pagination = document.getElementById('certificatesPagination');
        var meta = document.getElementById('certificatesMeta');
        var range = document.getElementById('certificatesRange');
        var addBtn = document.getElementById('certificatesAddBtn');
        var reloadBtn = document.getElementById('certificatesReloadBtn');
        var saveBtn = document.getElementById('certificateSaveBtn');
        var saveText = saveBtn ? saveBtn.querySelector('.certificate-save-text') : null;
        var saveSpinner = saveBtn ? saveBtn.querySelector('.certificate-save-spinner') : null;
        var imageInput = document.getElementById('certificate_image');
        var pdfInput = document.getElementById('certificate_pdf');
        var imagePreview = document.getElementById('certificateImagePreview');
        var imageMeta = document.getElementById('certificateImageMeta');
        var imageRemoveBtn = document.getElementById('certificateImageRemoveBtn');
        var pdfPreview = document.getElementById('certificatePdfPreview');
        var modalTitle = document.getElementById('certificateModalLabel');
        var loadUrl = form.dataset.showUrl;
        var saveUrl = form.dataset.saveUrl;
        var deleteUrl = form.dataset.deleteUrl;
        var csrfToken = form.querySelector('[name="csrf_token"]').value;
        var sortState = { key: 'display_order', direction: 'asc' };
        var paging = { page: 1, size: parseInt(pageSizeSelect ? pageSizeSelect.value : '10', 10) || 10 };
        var certificates = [];

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
            saveText.textContent = isBusy ? 'Saving...' : 'Save Certificate';
            saveSpinner.classList.toggle('d-none', !isBusy);
        }

        function renderImagePreview(url, title) {
            if (!imagePreview) {
                return;
            }

            imagePreview.dataset.previewUrl = url || '';

            if (!url) {
                delete imagePreview.dataset.selectedName;
                delete imagePreview.dataset.selectedSize;
                imagePreview.innerHTML = '<span class="text-muted">Image preview will appear here.</span>';
                if (imageMeta) { imageMeta.textContent = 'No image selected.'; }
                if (imageRemoveBtn) { imageRemoveBtn.classList.add('d-none'); }
                return;
            }

            imagePreview.innerHTML = '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(title || 'Certificate image') + '" class="media-preview__image" decoding="async">';
            var image = imagePreview.querySelector('img');
            image.addEventListener('load', function () {
                if (imageMeta) {
                    var details = (image.naturalWidth || 0) + ' × ' + (image.naturalHeight || 0) + ' px';
                    if (imagePreview.dataset.selectedName) {
                        details = imagePreview.dataset.selectedName + ' · ' + imagePreview.dataset.selectedSize + ' · ' + details;
                    }
                    imageMeta.textContent = details;
                }
            });
            if (imageRemoveBtn) { imageRemoveBtn.classList.remove('d-none'); }
        }

        function describeSelectedImage(file) {
            if (!file || !imageMeta) { return; }
            imagePreview.dataset.selectedName = file.name;
            imagePreview.dataset.selectedSize = Math.ceil(file.size / 1024) + ' KB';
            imageMeta.textContent = imagePreview.dataset.selectedName + ' · ' + imagePreview.dataset.selectedSize + ' · Loading resolution…';
        }

        function renderPdfPreview(url, fileName) {
            if (!pdfPreview) {
                return;
            }

            pdfPreview.dataset.previewUrl = url || '';
            pdfPreview.dataset.fileName = fileName || '';

            if (!url) {
                pdfPreview.innerHTML = '<div><strong class="d-block mb-2">PDF preview</strong><p class="mb-0 text-muted">No PDF uploaded yet.</p></div>';
                return;
            }

            pdfPreview.innerHTML = [
                '<div>',
                '<strong class="d-block mb-2">PDF preview</strong>',
                '<p class="mb-2 text-muted small">' + escapeHtml(fileName || 'Certificate PDF') + '</p>',
                '<a class="btn btn-outline-light btn-sm" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">View current PDF</a>',
                '</div>'
            ].join('');
        }

        function applyErrors(errors) {
            clearValidation();
            Object.keys(errors || {}).forEach(function (name) {
                var input = form.elements[name];
                if (input && input.classList) {
                    input.classList.add('is-invalid');
                }
            });
        }

        function applyFilters() {
            var term = (searchInput ? searchInput.value : '').trim().toLowerCase();
            var filtered = certificates.filter(function (certificate) {
                if (!term) {
                    return true;
                }

                return [
                    certificate.name,
                    certificate.issuing_organization,
                    certificate.issue_date_label,
                    certificate.expiry_date_label,
                    certificate.description,
                    certificate.display_order,
                    certificate.is_public ? 'public' : 'private'
                ].some(function (value) {
                    return String(value || '').toLowerCase().indexOf(term) !== -1;
                });
            });

            filtered.sort(function (left, right) {
                var result;
                switch (sortState.key) {
                    case 'display_order':
                    case 'is_public':
                        result = compareValues(Number(left[sortState.key]), Number(right[sortState.key]));
                        break;
                    case 'issued_date':
                    case 'expiry_date':
                        result = compareDates(left[sortState.key], right[sortState.key]);
                        break;
                    default:
                        result = compareValues(left[sortState.key] || '', right[sortState.key] || '');
                        break;
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
                range.textContent = 'Showing ' + start + ' to ' + end + ' of ' + filteredCount + ' certificates';
            }

            if (meta) {
                meta.textContent = total + ' total certificate' + (total === 1 ? '' : 's');
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
            var total = certificates.length;
            var totalPages = Math.max(1, Math.ceil(filtered.length / paging.size));
            paging.page = Math.min(Math.max(paging.page, 1), totalPages);
            var offset = (paging.page - 1) * paging.size;
            var pageItems = filtered.slice(offset, offset + paging.size);

            if (pageItems.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">No certificates found.</td></tr>';
                renderPagination(total, filtered.length);
                return;
            }

            tableBody.innerHTML = pageItems.map(function (certificate) {
                var image = certificate.image_url
                    ? '<img src="' + escapeHtml(certificate.image_url) + '" alt="' + escapeHtml(certificate.name || '') + '" class="certificate-thumb">'
                    : '<div class="certificate-thumb certificate-thumb--placeholder"><i class="fa fa-image"></i></div>';

                return [
                    '<tr data-certification-id="' + escapeHtml(certificate.certification_id) + '">',
                    '<td>' + image + '</td>',
                    '<td class="fw-semibold">' + escapeHtml(certificate.name || '') + '</td>',
                    '<td>' + escapeHtml(certificate.issuing_organization || '') + '</td>',
                    '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(certificate.issue_date_label || '-') + '</span></td>',
                    '<td>' + (certificate.expiry_date_label && certificate.expiry_date !== null && certificate.expiry_date !== '' ? '<span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle">' + escapeHtml(certificate.expiry_date_label) + '</span>' : '<span class="badge text-bg-light border">No expiry</span>') + '</td>',
                    '<td><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(certificate.display_order) + '</span></td>',
                    '<td>' + (Number(certificate.is_public) === 1 ? '<span class="badge text-bg-success">Public</span>' : '<span class="badge text-bg-secondary">Private</span>') + '</td>',
                    '<td class="text-end text-nowrap">',
                    '<button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="edit" data-certification-id="' + escapeHtml(certificate.certification_id) + '"><i class="fa fa-pencil"></i></button>',
                    '<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-certification-id="' + escapeHtml(certificate.certification_id) + '"><i class="fa fa-trash-o"></i></button>',
                    '</td>',
                    '</tr>'
                ].join('');
            }).join('');

            renderPagination(total, filtered.length);
        }

        function resetForm() {
            form.reset();
            clearValidation();
            clearFormAlert();
            form.certification_id.value = '';
            form.profile_id.value = certificates[0] && certificates[0].profile_id ? certificates[0].profile_id : (form.profile_id.value || '');
            form.current_image_path.value = '';
            form.current_pdf_path.value = '';
            form.is_public.checked = true;
            form.display_order.value = '0';
            if (imageInput) {
                imageInput.required = true;
            }
            renderImagePreview('', '');
            renderPdfPreview('', '');
            if (modalTitle) {
                modalTitle.textContent = 'Add Certificate';
            }
        }

        function fillForm(certificate) {
            resetForm();
            form.certification_id.value = certificate.certification_id || '';
            form.profile_id.value = certificate.profile_id || form.profile_id.value || '';
            form.current_image_path.value = certificate.certificate_image_path || '';
            form.current_pdf_path.value = certificate.certificate_file_path || '';
            form.name.value = certificate.name || '';
            form.issuing_organization.value = certificate.issuing_organization || '';
            form.issued_date.value = certificate.issued_date || '';
            form.expiry_date.value = certificate.expiry_date || '';
            form.credential_url.value = certificate.credential_url || '';
            form.description.value = certificate.description || '';
            form.display_order.value = certificate.display_order !== null && certificate.display_order !== undefined ? certificate.display_order : 0;
            form.is_public.checked = Number(certificate.is_public) === 1;
            if (imageInput) {
                imageInput.required = !certificate.certificate_image_path;
            }
            renderImagePreview(certificate.image_url || '', certificate.name || '');
            renderPdfPreview(certificate.pdf_url || '', certificate.certificate_pdf_name || '');
            if (modalTitle) {
                modalTitle.textContent = 'Edit Certificate';
            }
        }

        function loadCertificate(certificationId) {
            return fetch(loadUrl + '?action=show&id=' + encodeURIComponent(certificationId), {
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
                form.name.focus();
            }, 150);
        }

        function openEditModal(certificationId) {
            showFormAlert('Loading certificate...', 'info');
            if (modal) {
                modal.show();
            }

            loadCertificate(certificationId).then(function (payload) {
                clearFormAlert();
                if (!payload.success || !payload.data || !payload.data.certificate) {
                    showFormAlert(payload.message || 'Unable to load certificate.', 'danger');
                    return;
                }

                fillForm(payload.data.certificate);
                setTimeout(function () {
                    form.name.focus();
                }, 150);
            }).catch(function () {
                showFormAlert('Unable to load certificate.', 'danger');
            });
        }

        function loadCertificates() {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">Loading certificates...</td></tr>';

            fetch(loadUrl + '?action=list', {
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    throw new Error(payload.message || 'Unable to load certificates.');
                }

                certificates = (payload.data && payload.data.certificates) ? payload.data.certificates : [];
                if (payload.data && payload.data.profile_id) {
                    form.profile_id.value = payload.data.profile_id;
                }
                paging.page = 1;
                updateSortIndicators();
                renderTable();
            }).catch(function (error) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-5">' + escapeHtml(error.message || 'Unable to load certificates.') + '</td></tr>';
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

        function serializeForm() {
            var data = new FormData(form);
            data.set('csrf_token', csrfToken);
            data.set('is_public', form.is_public.checked ? '1' : '0');
            return data;
        }

        function saveCertificate(event) {
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
                    showFormAlert(payload.message || 'Unable to save certificate.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Certificate saved successfully.', 'success');
                if (modal) {
                    modal.hide();
                }
                loadCertificates();
            }).catch(function () {
                setBusy(false);
                showFormAlert('Unable to save certificate.', 'danger');
            });
        }

        function deleteCertificate(certificationId) {
            if (!window.confirm('Delete this certificate?')) {
                return;
            }

            var data = new FormData();
            data.append('csrf_token', csrfToken);
            data.append('certification_id', certificationId);

            fetch(deleteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    showAlert(payload.message || 'Unable to delete certificate.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Certificate deleted successfully.', 'success');
                loadCertificates();
            }).catch(function () {
                showAlert('Unable to delete certificate.', 'danger');
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', openCreateModal);
        }

        if (reloadBtn) {
            reloadBtn.addEventListener('click', loadCertificates);
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
                var key = button.getAttribute('data-sort-key');
                if (sortState.key === key) {
                    sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState.key = key;
                    sortState.direction = key === 'issued_date' || key === 'expiry_date' ? 'desc' : 'asc';
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
            var certificationId = button.getAttribute('data-certification-id');

            if (action === 'edit') {
                openEditModal(certificationId);
                return;
            }

            if (action === 'delete') {
                deleteCertificate(certificationId);
            }
        });

        if (imageInput) {
            imageInput.addEventListener('change', function () {
                var file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
                if (!file) {
                    if (form.current_image_path.value && imagePreview && imagePreview.dataset.previewUrl) {
                        renderImagePreview(imagePreview.dataset.previewUrl, form.name.value || '');
                    } else {
                        renderImagePreview('', '');
                    }
                    return;
                }

                var reader = new FileReader();
                describeSelectedImage(file);
                reader.onload = function (event) {
                    renderImagePreview(String(event.target && event.target.result ? event.target.result : ''), form.name.value || '');
                };
                reader.readAsDataURL(file);
            });
        }

        if (imageRemoveBtn) {
            imageRemoveBtn.addEventListener('click', function () {
                imageInput.value = '';
                delete imagePreview.dataset.selectedName;
                delete imagePreview.dataset.selectedSize;
                if (form.current_image_path.value && imagePreview.dataset.previewUrl) {
                    renderImagePreview(imagePreview.dataset.previewUrl, form.name.value || '');
                } else {
                    renderImagePreview('', '');
                }
            });
        }

        if (pdfInput) {
            pdfInput.addEventListener('change', function () {
                var file = pdfInput.files && pdfInput.files[0] ? pdfInput.files[0] : null;
                if (!file) {
                    if (form.current_pdf_path.value && pdfPreview && pdfPreview.dataset.previewUrl) {
                        renderPdfPreview(pdfPreview.dataset.previewUrl, pdfPreview.dataset.fileName || 'Certificate PDF');
                    } else {
                        renderPdfPreview('', '');
                    }
                    return;
                }

                renderPdfPreview(window.URL.createObjectURL(file), file.name);
            });
        }

        form.addEventListener('submit', saveCertificate);

        modalEl.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });

        loadCertificates();
    }

    function initPublic() {
        var dataEl = document.getElementById('certificatesData');
        var detailsModalEl = document.getElementById('certificateDetailsModal');
        var detailsModal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(detailsModalEl) : null;
        var titleEl = document.getElementById('certificateDetailsTitle');
        var organizationEl = document.getElementById('certificateDetailsOrganization');
        var dateEl = document.getElementById('certificateDetailsDate');
        var issueEl = document.getElementById('certificateDetailsIssueDate');
        var expiryEl = document.getElementById('certificateDetailsExpiryDate');
        var descriptionEl = document.getElementById('certificateDetailsDescription');
        var imageEl = document.getElementById('certificateDetailsImage');
        var imageWrap = imageEl ? imageEl.parentElement : null;
        var pdfBtn = document.getElementById('certificateDetailsPdf');
        var verifyBtn = document.getElementById('certificateDetailsVerify');
        var expiryBadge = document.getElementById('certificateDetailsExpiryBadge');
        var viewerEl = document.getElementById('certificateImageViewer');
        var viewer = viewerEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(viewerEl) : null;
        var viewerImage = document.getElementById('certificateViewerImage');
        var viewerTitle = document.getElementById('certificateImageViewerTitle');
        var viewerIndex = 0;
        var certificates = [];
        var certificateMap = {};

        if (dataEl) {
            try {
                certificates = JSON.parse(dataEl.textContent || '[]');
            } catch (error) {
                certificates = [];
            }
        }

        certificates.forEach(function (certificate) {
            certificateMap[String(certificate.certification_id)] = certificate;
        });

        function viewerItems() { return certificates.filter(function (certificate) { return Boolean(certificate.image_url); }); }
        function renderViewer(index) {
            var items = viewerItems();
            if (!items.length || !viewerImage) { return; }
            viewerIndex = (index + items.length) % items.length;
            var certificate = items[viewerIndex];
            viewerImage.src = certificate.image_url;
            viewerImage.alt = certificate.name || 'Certificate image';
            viewerImage.style.setProperty('--image-zoom', '1');
            if (viewerTitle) { viewerTitle.textContent = certificate.name || 'Certificate image'; }
        }

        function fillDetails(certificate) {
            if (!certificate) {
                return;
            }

            if (titleEl) {
                titleEl.textContent = certificate.name || 'Certificate Details';
            }
            if (organizationEl) {
                organizationEl.textContent = certificate.issuing_organization || 'Issuing Organization';
            }
            if (dateEl) {
                dateEl.textContent = certificate.issue_date_label ? 'Issued ' + certificate.issue_date_label : 'Issue date unavailable';
            }
            if (issueEl) {
                issueEl.textContent = certificate.issue_date_label || '-';
            }
            if (expiryEl) {
                expiryEl.textContent = certificate.expiry_date_label || 'No expiration date';
            }
            if (descriptionEl) {
                descriptionEl.innerHTML = escapeHtml(certificate.description || 'No description provided.').replace(/\n/g, '<br>');
            }
            if (imageEl && imageWrap) {
                if (certificate.image_url) {
                    imageEl.src = certificate.image_url;
                    imageEl.alt = certificate.name || 'Certificate image';
                    imageWrap.style.display = '';
                } else {
                    imageWrap.style.display = 'none';
                }
            }
            if (pdfBtn) {
                if (certificate.pdf_url) {
                    pdfBtn.href = certificate.pdf_url;
                    pdfBtn.style.display = '';
                } else {
                    pdfBtn.style.display = 'none';
                }
            }
            if (verifyBtn) {
                if (certificate.credential_url) {
                    verifyBtn.href = certificate.credential_url;
                    verifyBtn.style.display = '';
                } else {
                    verifyBtn.style.display = 'none';
                }
            }
            if (expiryBadge) {
                if (certificate.expiry_date_label) {
                    expiryBadge.style.display = '';
                    expiryBadge.className = 'badge ' + (isExpired(certificate) ? 'text-bg-danger' : 'text-bg-success');
                    expiryBadge.textContent = isExpired(certificate) ? 'Expired' : 'Valid';
                } else {
                    expiryBadge.style.display = 'none';
                }
            }
        }

        publicGrid.addEventListener('click', function (event) {
            var imageButton = event.target.closest('[data-certificate-view]');
            if (imageButton) {
                var certificateIdForViewer = imageButton.getAttribute('data-certificate-view');
                var items = viewerItems();
                var index = items.findIndex(function (certificate) { return String(certificate.certification_id) === String(certificateIdForViewer); });
                renderViewer(index < 0 ? 0 : index);
                if (viewer) { viewer.show(); }
                return;
            }
            var button = event.target.closest('button[data-certification-id]');
            if (!button) {
                return;
            }

            var certificationId = button.getAttribute('data-certification-id');
            fillDetails(certificateMap[certificationId]);
            if (detailsModal) {
                detailsModal.show();
            }
        });

        if (viewerEl && viewerImage) {
            var previous = viewerEl.querySelector('.image-viewer__nav--previous');
            var next = viewerEl.querySelector('.image-viewer__nav--next');
            if (previous) { previous.addEventListener('click', function () { renderViewer(viewerIndex - 1); }); }
            if (next) { next.addEventListener('click', function () { renderViewer(viewerIndex + 1); }); }
            viewerImage.addEventListener('wheel', function (event) {
                event.preventDefault();
                var zoom = Number(viewerImage.style.getPropertyValue('--image-zoom') || 1);
                zoom = Math.max(1, Math.min(3, zoom + (event.deltaY < 0 ? .15 : -.15)));
                viewerImage.style.setProperty('--image-zoom', String(zoom));
            }, { passive: false });
            viewerEl.addEventListener('hidden.bs.modal', function () { viewerImage.removeAttribute('src'); });
        }
    }
}());
