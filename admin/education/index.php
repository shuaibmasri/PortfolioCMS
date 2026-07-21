<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Education';
$activeMenu = 'education';
$breadcrumbs = [['label' => 'Dashboard'], ['label' => 'Education']];

ob_start();
?>
<section class="dashboard-panel">
    <div class="dashboard-panel__header flex-wrap gap-3">
        <div>
            <h2>Education</h2>
            <p>Manage your academic history and public education timeline.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary" type="button" id="educationReloadBtn">
                <i class="fa fa-refresh me-1"></i>Refresh
            </button>
            <button class="btn btn-primary" type="button" id="educationAddBtn">
                <i class="fa fa-plus me-1"></i>Add Education
            </button>
        </div>
    </div>

    <div class="p-4 pt-2">
        <div id="educationAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>

        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="educationSearch">Search</label>
                <input class="form-control" id="educationSearch" type="search" placeholder="Search by institution or degree">
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="educationPageSize">Rows per page</label>
                <select class="form-select" id="educationPageSize">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-5 text-xl-end">
                <div class="small text-muted" id="educationMeta">Loading education records...</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="educationTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="institution_name" data-label="Institution">Institution</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="degree" data-label="Degree">Degree</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="field_of_study" data-label="Field of Study">Field of Study</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="start_date" data-label="Graduation Year">Graduation Year</button>
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
                        <td colspan="7" class="text-center text-muted py-5">Loading education records...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
            <div class="small text-muted" id="educationRange"></div>
            <nav aria-label="Education pagination">
                <ul class="pagination mb-0" id="educationPagination"></ul>
            </nav>
        </div>
    </div>
</section>

<?php require __DIR__ . '/form.php'; ?>
<?php
$pageContent = (string) ob_get_clean();
$pageScripts = '<script src="' . escape(asset('js/education.js')) . '"></script>';

require dirname(__DIR__, 2) . '/includes/admin-layout.php';
