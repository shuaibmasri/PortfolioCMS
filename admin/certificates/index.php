<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Certificates';
$activeMenu = 'certificates';
$breadcrumbs = [['label' => 'Dashboard'], ['label' => 'Certificates']];

ob_start();
?>
<section class="dashboard-panel">
    <div class="dashboard-panel__header flex-wrap gap-3">
        <div>
            <h2>Certificates</h2>
            <p>Manage professional certificates, images, downloads, and public visibility.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary" type="button" id="certificatesReloadBtn">
                <i class="fa fa-refresh me-1"></i>Refresh
            </button>
            <button class="btn btn-primary" type="button" id="certificatesAddBtn">
                <i class="fa fa-plus me-1"></i>Add Certificate
            </button>
        </div>
    </div>

    <div class="p-4 pt-2">
        <div id="certificatesAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>

        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="certificatesSearch">Search</label>
                <input class="form-control" id="certificatesSearch" type="search" placeholder="Search by certificate, organization, or dates">
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="certificatesPageSize">Rows per page</label>
                <select class="form-select" id="certificatesPageSize">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-5 text-xl-end">
                <div class="small text-muted" id="certificatesMeta">Loading certificates...</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="certificatesTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">Image</th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="name" data-label="Certificate Name">Certificate Name</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="issuing_organization" data-label="Organization">Organization</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="issued_date" data-label="Issue Date">Issue Date</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="expiry_date" data-label="Expiration Date">Expiration Date</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="display_order" data-label="Display Order">Display Order</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="is_public" data-label="Is Public">Is Public</button>
                        </th>
                        <th scope="col" class="text-nowrap text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">Loading certificates...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
            <div class="small text-muted" id="certificatesRange"></div>
            <nav aria-label="Certificates pagination">
                <ul class="pagination mb-0" id="certificatesPagination"></ul>
            </nav>
        </div>
    </div>
</section>

<?php require __DIR__ . '/form.php'; ?>
<?php
$pageContent = (string) ob_get_clean();
$pageScripts = '<script src="' . escape(asset('js/certificates.js')) . '"></script>';

require dirname(__DIR__, 2) . '/includes/admin-layout.php';
