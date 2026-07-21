<?php
declare(strict_types=1);
?>
<div class="modal fade" id="skillModal" tabindex="-1" aria-hidden="true" aria-labelledby="skillModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="skillForm" class="needs-validation" novalidate data-save-url="<?= escape(url('admin/skills/save.php')) ?>" data-delete-url="<?= escape(url('admin/skills/delete.php')) ?>" data-show-url="<?= escape(url('admin/skills/ajax.php')) ?>">
                <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="skill_id" id="skill_id" value="">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="skillModalLabel">Add Skill</h5>
                        <p class="mb-0 text-muted small">Create or update a portfolio skill.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="skillFormAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="skill_name">Skill Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="skill_name" name="skill_name" type="text" maxlength="150" required>
                            <div class="invalid-feedback">Please enter a skill name.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="skill_category">Category <span class="text-danger">*</span></label>
                            <input class="form-control" id="skill_category" name="skill_category" type="text" maxlength="100" list="skillCategorySuggestions" required>
                            <datalist id="skillCategorySuggestions"></datalist>
                            <div class="form-text">Type the category name used for this skill.</div>
                            <div class="invalid-feedback">Please enter a category name.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="proficiency">Proficiency <span class="text-danger">*</span></label>
                            <input class="form-control" id="proficiency" name="proficiency" type="number" min="0" max="100" step="1" required>
                            <div class="form-text">Use a value between 0 and 100.</div>
                            <div class="invalid-feedback">Enter a proficiency value between 0 and 100.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="display_order">Display Order <span class="text-danger">*</span></label>
                            <input class="form-control" id="display_order" name="display_order" type="number" min="0" step="1" required>
                            <div class="form-text">Lower numbers appear first.</div>
                            <div class="invalid-feedback">Please enter a display order.</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" id="is_public" name="is_public" type="checkbox" value="1" checked>
                                <label class="form-check-label" for="is_public">Is Public</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="skillSaveBtn">
                        <span class="skill-save-text">Save Skill</span>
                        <span class="skill-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
