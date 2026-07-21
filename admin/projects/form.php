<?php
declare(strict_types=1);
?>
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true" aria-labelledby="projectModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form
                id="projectForm"
                class="needs-validation"
                novalidate
                data-save-url="<?= escape(url('admin/projects/save.php')) ?>"
                data-delete-url="<?= escape(url('admin/projects/delete.php')) ?>"
                data-show-url="<?= escape(url('admin/projects/ajax.php')) ?>"
            >
                <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="project_id" id="project_id" value="">
                <input type="hidden" name="current_image_path" id="current_image_path" value="">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="projectModalLabel">Add Project</h5>
                        <p class="mb-0 text-muted small">Create or update a portfolio project.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="projectFormAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <label class="form-label" for="title">Project Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="title" name="title" type="text" maxlength="250" required>
                            <div class="invalid-feedback">Please enter a project name.</div>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label" for="category_name">Category <span class="text-danger">*</span></label>
                            <input class="form-control" id="category_name" name="category_name" type="text" maxlength="100" list="projectCategorySuggestions" required>
                            <datalist id="projectCategorySuggestions"></datalist>
                            <div class="form-text">Choose or type the category that best fits this project.</div>
                            <div class="invalid-feedback">Please enter a category.</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="short_description">Short Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="2" maxlength="500" required></textarea>
                            <div class="invalid-feedback">Please provide a short description.</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="description">Full Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            <div class="invalid-feedback">Please provide the full description.</div>
                        </div>
                        <div class="col-lg-7">
                            <label class="form-label" for="technologies">Technologies <span class="text-danger">*</span></label>
                            <input class="form-control" id="technologies" name="technologies" type="text" maxlength="1000" list="projectTechnologySuggestions" required>
                            <datalist id="projectTechnologySuggestions"></datalist>
                            <div class="form-text">Enter comma-separated technologies such as PHP, Bootstrap, and MySQL.</div>
                            <div class="invalid-feedback">Please enter at least one technology.</div>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label" for="project_image">Main Image <span class="text-muted fw-normal">(optional)</span></label>
                            <input class="form-control" id="project_image" name="project_image" type="file" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text">Use JPG, PNG, or WebP. A designed project cover is used automatically when no image is uploaded.</div>
                            <div class="invalid-feedback">Please upload a valid project image.</div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label" for="repository_url">GitHub URL</label>
                            <input class="form-control" id="repository_url" name="repository_url" type="url" maxlength="500" placeholder="https://github.com/...">
                            <div class="invalid-feedback">Enter a valid GitHub URL.</div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label" for="project_url">Live Demo URL</label>
                            <input class="form-control" id="project_url" name="project_url" type="url" maxlength="500" placeholder="https://example.com/...">
                            <div class="invalid-feedback">Enter a valid live demo URL.</div>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label" for="display_order">Display Order <span class="text-danger">*</span></label>
                            <input class="form-control" id="display_order" name="display_order" type="number" min="0" step="1" required>
                            <div class="invalid-feedback">Please enter a display order.</div>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="planned">Planned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed" selected>Completed</option>
                                <option value="archived">Archived</option>
                            </select>
                            <div class="invalid-feedback">Please choose a status.</div>
                        </div>
                        <div class="col-12">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <div class="media-preview media-preview--cover" id="projectImagePreview">
                                        <span class="text-muted">Image preview will appear here.</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" id="is_public" name="is_public" type="checkbox" value="1" checked>
                                        <label class="form-check-label" for="is_public">Is Public</label>
                                    </div>
                                    <div class="small text-muted">Public projects are displayed on the public website.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="projectSaveBtn">
                        <span class="project-save-text">Save Project</span>
                        <span class="project-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
