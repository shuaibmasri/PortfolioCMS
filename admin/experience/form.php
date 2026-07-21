<?php
declare(strict_types=1);
?>
<div class="modal fade" id="experienceModal" tabindex="-1" aria-hidden="true" aria-labelledby="experienceModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="experienceForm" novalidate data-save-url="<?= escape(url('admin/experience/save.php')) ?>" data-delete-url="<?= escape(url('admin/experience/delete.php')) ?>" data-show-url="<?= escape(url('admin/experience/ajax.php')) ?>">
                <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="experience_id" id="experience_id" value="">
                <input type="hidden" name="profile_id" id="experience_profile_id" value="">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="experienceModalLabel">Add Experience</h5>
                        <p class="mb-0 text-muted small">Add or update an employment record for your public timeline.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="experienceFormAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="employer_name">Company <span class="text-danger">*</span></label>
                            <input class="form-control" id="employer_name" name="employer_name" type="text" maxlength="200" required>
                            <div class="invalid-feedback">Please enter a company name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="job_title">Position <span class="text-danger">*</span></label>
                            <input class="form-control" id="job_title" name="job_title" type="text" maxlength="200" required>
                            <div class="invalid-feedback">Please enter a position title.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="employment_type">Employment Type</label>
                            <input class="form-control" id="employment_type" name="employment_type" type="text" maxlength="50" placeholder="Full-time, Contract, Freelance">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" type="text" maxlength="200" placeholder="City, Country">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
                            <input class="form-control" id="start_date" name="start_date" type="date" required>
                            <div class="invalid-feedback">Please enter a start date.</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="end_date">End Date</label>
                            <input class="form-control" id="end_date" name="end_date" type="date">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" id="is_current" name="is_current" type="checkbox" value="1">
                                <label class="form-check-label" for="is_current">Current Role</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" id="is_public" name="is_public" type="checkbox" value="1" checked>
                                <label class="form-check-label" for="is_public">Is Public</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="display_order">Display Order <span class="text-danger">*</span></label>
                            <input class="form-control" id="display_order" name="display_order" type="number" min="0" step="1" required>
                            <div class="invalid-feedback">Please enter a display order.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" maxlength="4000" placeholder="Summarize responsibilities, outcomes, and achievements."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="experienceSaveBtn">
                        <span class="experience-save-text">Save Experience</span>
                        <span class="experience-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
