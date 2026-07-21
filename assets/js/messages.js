(function () {
    'use strict';

    var adminTable = document.getElementById('messagesTable');
    var contactForm = document.getElementById('contactForm');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function nl2br(value) {
        return escapeHtml(value).replace(/\n/g, '<br>');
    }

    function compareValues(a, b) {
        if (typeof a === 'number' && typeof b === 'number') {
            return a - b;
        }

        return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }

        var parsed = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function statusLabel(status) {
        switch (status) {
            case 'read':
                return 'Read';
            case 'replied':
                return 'Replied';
            default:
                return 'New';
        }
    }

    function statusClass(status) {
        switch (status) {
            case 'read':
                return 'text-bg-success';
            case 'replied':
                return 'text-bg-info';
            default:
                return 'text-bg-warning';
        }
    }

    function toggleStatusTarget(status) {
        return status === 'new' ? 'read' : 'new';
    }

    if (adminTable) {
        initAdmin();
        return;
    }

    if (contactForm) {
        initPublic();
    }

    function initAdmin() {
        var formAlertBox = document.getElementById('messageDetailsAlert');
        var alertBox = document.getElementById('messagesAlert');
        var tableBody = adminTable.querySelector('tbody');
        var searchInput = document.getElementById('messagesSearch');
        var statusFilter = document.getElementById('messagesStatusFilter');
        var pageSizeSelect = document.getElementById('messagesPageSize');
        var pagination = document.getElementById('messagesPagination');
        var meta = document.getElementById('messagesMeta');
        var range = document.getElementById('messagesRange');
        var reloadBtn = document.getElementById('messagesReloadBtn');
        var modalEl = document.getElementById('messageModal');
        var modal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        var modalTitle = document.getElementById('messageModalLabel');
        var statusBadge = document.getElementById('messageStatusBadge');
        var createdAtEl = document.getElementById('messageCreatedAt');
        var senderNameEl = document.getElementById('messageSenderName');
        var senderEmailEl = document.getElementById('messageSenderEmail');
        var subjectEl = document.getElementById('messageSubject');
        var readAtEl = document.getElementById('messageReadAt');
        var repliedAtEl = document.getElementById('messageRepliedAt');
        var bodyEl = document.getElementById('messageBody');
        var metaEl = document.getElementById('messageMeta');
        var toggleBtn = document.getElementById('messageToggleBtn');
        var deleteBtn = document.getElementById('messageDeleteBtn');
        var toggleText = toggleBtn ? toggleBtn.querySelector('.message-toggle-text') : null;
        var toggleSpinner = toggleBtn ? toggleBtn.querySelector('.message-toggle-spinner') : null;
        var deleteText = deleteBtn ? deleteBtn.querySelector('.message-delete-text') : null;
        var deleteSpinner = deleteBtn ? deleteBtn.querySelector('.message-delete-spinner') : null;
        var loadUrl = adminTable.dataset.loadUrl || '';
        var deleteUrl = adminTable.dataset.deleteUrl || '';
        var csrfToken = '';
        var sortState = { key: 'created_at', direction: 'desc' };
        var paging = { page: 1, size: parseInt(pageSizeSelect ? pageSizeSelect.value : '10', 10) || 10 };
        var messages = [];
        var stats = { total: 0, unread: 0, read: 0, replied: 0 };
        var currentMessage = null;

        var tokenField = document.getElementById('messagesCsrfToken');
        if (tokenField) {
            csrfToken = tokenField.value || '';
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
            if (!contactForm) {
                return;
            }

            contactForm.querySelectorAll('.is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });
            contactForm.classList.remove('was-validated');
        }

        function setBusy(button, textNode, spinnerNode, busy, busyLabel, idleLabel) {
            if (!button || !textNode || !spinnerNode) {
                return;
            }

            button.disabled = busy;
            textNode.textContent = busy ? busyLabel : idleLabel;
            spinnerNode.classList.toggle('d-none', !busy);
        }

        function applyErrors(errors) {
            if (!contactForm) {
                return;
            }

            clearValidation();
            Object.keys(errors || {}).forEach(function (field) {
                var input = contactForm.elements[field];
                if (input && input.classList) {
                    input.classList.add('is-invalid');
                }
            });
        }

        function compareCreated(left, right) {
            return compareValues(left.created_at || '', right.created_at || '');
        }

        function applyFilters() {
            var term = (searchInput ? searchInput.value : '').trim().toLowerCase();
            var selectedStatus = statusFilter ? statusFilter.value : 'all';
            var filtered = messages.filter(function (message) {
                if (selectedStatus !== 'all' && message.status !== selectedStatus) {
                    return false;
                }

                if (!term) {
                    return true;
                }

                return [
                    message.sender_name,
                    message.sender_email,
                    message.subject,
                    message.message_body,
                    message.status_label,
                    message.created_label
                ].some(function (value) {
                    return String(value || '').toLowerCase().indexOf(term) !== -1;
                });
            });

            filtered.sort(function (left, right) {
                var result;
                switch (sortState.key) {
                    case 'created_at':
                        result = compareCreated(left, right);
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
                range.textContent = 'Showing ' + start + ' to ' + end + ' of ' + filteredCount + ' messages';
            }

            if (meta) {
                meta.textContent = total + ' total message' + (total === 1 ? '' : 's') + ' | ' + stats.unread + ' unread';
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
            var total = messages.length;
            var totalPages = Math.max(1, Math.ceil(filtered.length / paging.size));
            paging.page = Math.min(Math.max(paging.page, 1), totalPages);
            var offset = (paging.page - 1) * paging.size;
            var pageItems = filtered.slice(offset, offset + paging.size);

            if (pageItems.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5">No messages found.</td></tr>';
                renderPagination(total, filtered.length);
                return;
            }

            tableBody.innerHTML = pageItems.map(function (message) {
                var rowClass = message.status === 'new' ? 'table-warning-subtle' : '';
                var toggleLabel = toggleStatusTarget(message.status) === 'read' ? 'Mark Read' : 'Mark Unread';
                var toggleIcon = toggleStatusTarget(message.status) === 'read' ? 'fa-envelope-open' : 'fa-envelope';

                return [
                    '<tr class="' + rowClass + '" data-message-id="' + escapeHtml(message.contact_message_id) + '">',
                    '<td class="' + (message.status === 'new' ? 'fw-semibold' : '') + '">',
                    '<div>' + escapeHtml(message.sender_name || '') + '</div>',
                    '<div class="small text-muted">' + escapeHtml(message.sender_email || '') + '</div>',
                    '</td>',
                    '<td>',
                    '<div class="fw-semibold">' + escapeHtml(message.subject || '(No subject)') + '</div>',
                    '<div class="small text-muted text-truncate" style="max-width: 28rem;">' + escapeHtml(message.body_snippet || '') + '</div>',
                    '</td>',
                    '<td><span class="badge ' + escapeHtml(message.status_class) + '">' + escapeHtml(message.status_label) + '</span></td>',
                    '<td><span class="small text-muted">' + escapeHtml(message.created_label || '') + '</span></td>',
                    '<td class="text-end text-nowrap">',
                    '<button class="btn btn-sm btn-outline-primary me-1" type="button" data-action="view" data-message-id="' + escapeHtml(message.contact_message_id) + '"><i class="fa fa-eye"></i></button>',
                    '<button class="btn btn-sm btn-outline-secondary me-1" type="button" data-action="toggle" data-message-id="' + escapeHtml(message.contact_message_id) + '" data-status-target="' + escapeHtml(toggleStatusTarget(message.status)) + '"><i class="fa ' + toggleIcon + '"></i> ' + toggleLabel + '</button>',
                    '<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-message-id="' + escapeHtml(message.contact_message_id) + '"><i class="fa fa-trash-o"></i></button>',
                    '</td>',
                    '</tr>'
                ].join('');
            }).join('');

            renderPagination(total, filtered.length);
        }

        function fillModal(message) {
            currentMessage = message || null;

            if (!currentMessage) {
                return;
            }

            if (modalTitle) {
                modalTitle.textContent = currentMessage.subject || 'Message Details';
            }
            if (statusBadge) {
                statusBadge.className = 'badge ' + statusClass(currentMessage.status || 'new');
                statusBadge.textContent = statusLabel(currentMessage.status || 'new');
            }
            if (createdAtEl) {
                createdAtEl.textContent = currentMessage.created_label || '';
            }
            if (senderNameEl) {
                senderNameEl.textContent = currentMessage.sender_name || '-';
            }
            if (senderEmailEl) {
                senderEmailEl.innerHTML = currentMessage.sender_email ? '<a href="mailto:' + escapeHtml(currentMessage.sender_email) + '">' + escapeHtml(currentMessage.sender_email) + '</a>' : '';
            }
            if (subjectEl) {
                subjectEl.textContent = currentMessage.subject || '(No subject)';
            }
            if (readAtEl) {
                readAtEl.textContent = currentMessage.read_label || 'Not read yet';
            }
            if (repliedAtEl) {
                repliedAtEl.textContent = currentMessage.replied_label || 'Not replied yet';
            }
            if (bodyEl) {
                bodyEl.innerHTML = nl2br(currentMessage.message_body || '');
            }
            if (metaEl) {
                metaEl.textContent = 'Message ID #' + currentMessage.contact_message_id + (currentMessage.ip_address_text ? ' | IP ' + currentMessage.ip_address_text : '');
            }
            if (toggleBtn && toggleText) {
                var nextStatus = toggleStatusTarget(currentMessage.status || 'new');
                toggleText.textContent = nextStatus === 'read' ? 'Mark as Read' : 'Mark as Unread';
                toggleBtn.dataset.statusTarget = nextStatus;
            }
        }

        function openMessage(messageId) {
            showFormAlert('Loading message...', 'info');
            if (modal) {
                modal.show();
            }

            fetch(loadUrl + '?action=show&id=' + encodeURIComponent(messageId), {
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                clearFormAlert();
                if (!payload.success || !payload.data || !payload.data.message) {
                    showFormAlert(payload.message || 'Unable to load message.', 'danger');
                    return;
                }

                fillModal(payload.data.message);
            }).catch(function () {
                showFormAlert('Unable to load message.', 'danger');
            });
        }

        function loadMessages() {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5">Loading messages...</td></tr>';

            fetch(loadUrl + '?action=list', {
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    throw new Error(payload.message || 'Unable to load messages.');
                }

                messages = (payload.data && payload.data.messages) ? payload.data.messages : [];
                stats = (payload.data && payload.data.stats) ? payload.data.stats : { total: messages.length, unread: 0, read: 0, replied: 0 };
                paging.page = 1;
                updateSortIndicators();
                renderTable();
            }).catch(function (error) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-5">' + escapeHtml(error.message || 'Unable to load messages.') + '</td></tr>';
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

        function toggleMessageStatus(messageId, targetStatus) {
            var data = new FormData();
            data.append('csrf_token', csrfToken);
            data.append('contact_message_id', messageId);
            data.append('status', targetStatus);

            return fetch(loadUrl + '?action=toggle-status', {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    showAlert(payload.message || 'Unable to update message status.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Message status updated.', 'success');
                if (currentMessage && String(currentMessage.contact_message_id) === String(messageId)) {
                    currentMessage.status = targetStatus;
                    currentMessage.status_label = statusLabel(targetStatus);
                    currentMessage.status_class = statusClass(targetStatus);
                    currentMessage.read_label = targetStatus === 'read' ? formatDate(new Date().toISOString().slice(0, 19).replace('T', ' ')) : 'Not read yet';
                    fillModal(currentMessage);
                }
                loadMessages();
            }).catch(function () {
                showAlert('Unable to update message status.', 'danger');
            });
        }

        function deleteMessage(messageId) {
            if (!window.confirm('Delete this message?')) {
                return Promise.resolve();
            }

            var data = new FormData();
            data.append('csrf_token', csrfToken);
            data.append('contact_message_id', messageId);

            return fetch(deleteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload.success) {
                    showAlert(payload.message || 'Unable to delete message.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Message deleted successfully.', 'success');
                if (modal) {
                    modal.hide();
                }
                loadMessages();
            }).catch(function () {
                showAlert('Unable to delete message.', 'danger');
            });
        }

        if (reloadBtn) {
            reloadBtn.addEventListener('click', loadMessages);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                paging.page = 1;
                renderTable();
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', function () {
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
                    sortState.direction = key === 'created_at' ? 'desc' : 'asc';
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
            var messageId = button.getAttribute('data-message-id');
            var targetStatus = button.getAttribute('data-status-target');

            if (action === 'view') {
                openMessage(messageId);
                return;
            }

            if (action === 'toggle') {
                toggleMessageStatus(messageId, targetStatus || 'read');
                return;
            }

            if (action === 'delete') {
                deleteMessage(messageId);
            }
        });

        if (toggleBtn && toggleText && toggleSpinner) {
            toggleBtn.addEventListener('click', function () {
                if (!currentMessage) {
                    return;
                }

                var targetStatus = toggleBtn.dataset.statusTarget || toggleStatusTarget(currentMessage.status || 'new');
                setBusy(toggleBtn, toggleText, toggleSpinner, true, 'Updating...', toggleText.textContent);
                toggleMessageStatus(currentMessage.contact_message_id, targetStatus).finally(function () {
                    setBusy(toggleBtn, toggleText, toggleSpinner, false, toggleText.textContent, toggleText.textContent);
                });
            });
        }

        if (deleteBtn && deleteText && deleteSpinner) {
            deleteBtn.addEventListener('click', function () {
                if (!currentMessage) {
                    return;
                }

                setBusy(deleteBtn, deleteText, deleteSpinner, true, 'Deleting...', deleteText.textContent);
                deleteMessage(currentMessage.contact_message_id).finally(function () {
                    setBusy(deleteBtn, deleteText, deleteSpinner, false, deleteText.textContent, deleteText.textContent);
                });
            });
        }

        modalEl.addEventListener('hidden.bs.modal', function () {
            currentMessage = null;
            if (formAlertBox) {
                formAlertBox.innerHTML = '';
            }
        });

        loadMessages();
    }

    function initPublic() {
        var alertBox = document.getElementById('contactFormAlert');
        var saveBtn = document.getElementById('contactSubmitBtn');
        var saveText = saveBtn ? saveBtn.querySelector('.contact-save-text') : null;
        var saveSpinner = saveBtn ? saveBtn.querySelector('.contact-save-spinner') : null;
        var submitUrl = contactForm.dataset.submitUrl;
        var csrfTokenField = contactForm.querySelector('[name="csrf_token"]');
        var csrfToken = csrfTokenField ? csrfTokenField.value : '';

        function showAlert(message, type) {
            if (!alertBox) {
                return;
            }

            alertBox.innerHTML = '<div class="alert alert-' + type + ' mb-0" role="alert">' + escapeHtml(message) + '</div>';
        }

        function clearValidation() {
            contactForm.querySelectorAll('.is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });
            contactForm.classList.remove('was-validated');
        }

        function applyErrors(errors) {
            clearValidation();
            Object.keys(errors || {}).forEach(function (field) {
                var input = contactForm.elements[field];
                if (input && input.classList) {
                    input.classList.add('is-invalid');
                }
            });
        }

        function setBusy(isBusy) {
            if (!saveBtn || !saveText || !saveSpinner) {
                return;
            }

            saveBtn.disabled = isBusy;
            saveText.textContent = isBusy ? 'Sending...' : 'Send Message';
            saveSpinner.classList.toggle('d-none', !isBusy);
        }

        function serializeForm() {
            var data = new FormData(contactForm);
            data.set('csrf_token', csrfToken);
            return data;
        }

        function submitMessage(event) {
            event.preventDefault();
            clearValidation();

            if (!contactForm.checkValidity()) {
                contactForm.classList.add('was-validated');
                return;
            }

            setBusy(true);
            fetch(submitUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: serializeForm()
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                setBusy(false);
                if (!payload.success) {
                    applyErrors(payload.errors || {});
                    showAlert(payload.message || 'Unable to send your message.', 'danger');
                    return;
                }

                showAlert(payload.message || 'Message sent successfully.', 'success');
                contactForm.reset();
                contactForm.classList.remove('was-validated');
            }).catch(function () {
                setBusy(false);
                showAlert('Unable to send your message right now.', 'danger');
            });
        }

        contactForm.addEventListener('submit', submitMessage);
    }
}());

