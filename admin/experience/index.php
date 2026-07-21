<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Experience';
$activeMenu = 'experience';
$breadcrumbs = [['label' => 'Dashboard'], ['label' => 'Experience']];

ob_start();
?>
<section class="dashboard-panel">
    <div class="dashboard-panel__header flex-wrap gap-3">
        <div>
            <h2>Experience</h2>
            <p>Manage your employment history and timeline entries.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary" type="button" id="experienceReloadBtn">
                <i class="fa fa-refresh me-1"></i>Refresh
            </button>
            <button class="btn btn-primary" type="button" id="experienceAddBtn">
                <i class="fa fa-plus me-1"></i>Add Experience
            </button>
        </div>
    </div>

    <div class="p-4 pt-2">
        <div id="experienceAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>

        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="experienceSearch">Search</label>
                <input class="form-control" id="experienceSearch" type="search" placeholder="Search by company or position">
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="experiencePageSize">Rows per page</label>
                <select class="form-select" id="experiencePageSize">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="col-sm-6 col-xl-5 text-xl-end">
                <div class="small text-muted" id="experienceMeta">Loading experiences...</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="experienceTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="employer_name" data-label="Company">Company</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="job_title" data-label="Position">Position</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="start_date" data-label="Employment Period">Employment Period</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="display_order" data-label="Order">Display Order</button>
                        </th>
                        <th scope="col" class="text-nowrap">
                            <button class="btn btn-link p-0 text-decoration-none text-dark fw-semibold" type="button" data-sort-key="is_public" data-label="Public">Is Public</button>
                        </th>
                        <th scope="col" class="text-nowrap text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">Loading experiences...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
            <div class="small text-muted" id="experienceRange"></div>
            <nav aria-label="Experience pagination">
                <ul class="pagination mb-0" id="experiencePagination"></ul>
            </nav>
        </div>
    </div>
</section>

<?php require __DIR__ . '/form.php'; ?>
<?php
$pageContent = (string) ob_get_clean();
$pageScripts = '<script src="' . escape(asset('js/experience.js')) . '"></script>';

require dirname(__DIR__, 2) . '/includes/admin-layout.php';
