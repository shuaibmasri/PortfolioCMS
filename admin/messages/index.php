<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Messages';
$activeMenu = 'messages';
$breadcrumbs = [['label' => 'Dashboard'], ['label' => 'Messages']];

ob_start();
?>
<section class="dashboard-panel">
    <div class="dashboard-panel__header flex-wrap gap-3">
        <div>
            <h2>Messages</h2>
            <p>Review inquiries submitted through the public contact form.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary" type="button" id="messagesReloadBtn">
                <i class="fa fa-refresh me-1"></i>Refresh
            </button>
        </div>
    </div>

    <div class="p-4 pt-2">
        <input type="hidden" id="messagesCsrfToken" value="<?= escape(csrfToken()) ?>">
        <div id="messagesAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>

        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="messagesSearch">Search</label>
                <input class="form-control" id="messagesSearch" type="search" placeholder="Search by sender, subject, or message">
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="messagesStatusFilter">Status</label>
                <select class="form-select" id="messagesStatusFilter">
                    <option value="all" selected>All</option>
                    <option value="new">New</option>
                    <option value="read">Read</option>
                    <option value="replied">Replied</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="messagesPageSize">Rows per page</label>
                <select class="form-select" id="messagesPageSize">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-sm-12 col-xl-4 text-xl-end">
                <div class="small text-muted" id="messagesMeta">Loading messages...</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="messagesTable" data-load-url="<?= escape(url('admin/messages/ajax.php')) ?>" data-delete-url="<?= escape(url('admin/messages/delete.php')) ?>">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="sender_name" data-label="Sender">Sender</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="subject" data-label="Subject">Subject</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="status" data-label="Status">Status</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="created_at" data-label="Received">Received</button>
                        </th>
                        <th scope="col" class="text-nowrap text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">Loading messages...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
            <div class="small text-muted" id="messagesRange"></div>
            <nav aria-label="Messages pagination">
                <ul class="pagination mb-0" id="messagesPagination"></ul>
            </nav>
        </div>
    </div>
</section>

<?php require __DIR__ . '/view.php'; ?>
<?php
$pageContent = (string) ob_get_clean();
$pageScripts = '<script src="' . escape(asset('js/messages.js')) . '"></script>';

require dirname(__DIR__, 2) . '/includes/admin-layout.php';
