<?php
declare(strict_types=1);
?>
<div class="modal fade" id="educationModal" tabindex="-1" aria-hidden="true" aria-labelledby="educationModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="educationForm" novalidate data-save-url="<?= escape(url('admin/education/save.php')) ?>" data-delete-url="<?= escape(url('admin/education/delete.php')) ?>" data-show-url="<?= escape(url('admin/education/ajax.php')) ?>">
                <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="education_id" id="education_id" value="">
                <input type="hidden" name="profile_id" id="education_profile_id" value="">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="educationModalLabel">Add Education</h5>
                        <p class="mb-0 text-muted small">Add or update an academic record for your public timeline.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="educationFormAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="institution_name">Institution <span class="text-danger">*</span></label>
                            <input class="form-control" id="institution_name" name="institution_name" type="text" maxlength="250" required>
                            <div class="invalid-feedback">Please enter an institution name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="degree">Degree</label>
                            <input class="form-control" id="degree" name="degree" type="text" maxlength="200" placeholder="Bachelor of Science">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="field_of_study">Field of Study</label>
                            <input class="form-control" id="field_of_study" name="field_of_study" type="text" maxlength="200" placeholder="Computer Science">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" type="text" maxlength="200" placeholder="City, Country">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="grade">Grade</label>
                            <input class="form-control" id="grade" name="grade" type="text" maxlength="100" placeholder="GPA 3.8/4.0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="start_date">Start Date</label>
                            <input class="form-control" id="start_date" name="start_date" type="date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="end_date">Graduation Date</label>
                            <input class="form-control" id="end_date" name="end_date" type="date">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" id="is_public" name="is_public" type="checkbox" value="1" checked>
                                <label class="form-check-label" for="is_public">Is Public</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="display_order">Display Order <span class="text-danger">*</span></label>
                            <input class="form-control" id="display_order" name="display_order" type="number" min="0" step="1" required>
                            <div class="invalid-feedback">Please enter a display order.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" maxlength="4000" placeholder="Add details about coursework, achievements, honors, or focus areas."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="educationSaveBtn">
                        <span class="education-save-text">Save Education</span>
                        <span class="education-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
