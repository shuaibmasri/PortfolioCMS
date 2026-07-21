<?php
declare(strict_types=1);
?>
<div class="modal fade" id="certificateModal" tabindex="-1" aria-hidden="true" aria-labelledby="certificateModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="certificateForm" class="needs-validation" novalidate data-save-url="<?= escape(url('admin/certificates/save.php')) ?>" data-delete-url="<?= escape(url('admin/certificates/delete.php')) ?>" data-show-url="<?= escape(url('admin/certificates/ajax.php')) ?>">
                <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="certification_id" id="certification_id" value="">
                <input type="hidden" name="profile_id" id="certificate_profile_id" value="">
                <input type="hidden" name="current_image_path" id="current_image_path" value="">
                <input type="hidden" name="current_pdf_path" id="current_pdf_path" value="">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="certificateModalLabel">Add Certificate</h5>
                        <p class="mb-0 text-muted small">Create or update a professional certificate entry.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="certificateFormAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <label class="form-label" for="name">Certificate Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="name" name="name" type="text" maxlength="250" required>
                            <div class="invalid-feedback">Please enter a certificate name.</div>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label" for="issuing_organization">Issuing Organization <span class="text-danger">*</span></label>
                            <input class="form-control" id="issuing_organization" name="issuing_organization" type="text" maxlength="250" required>
                            <div class="invalid-feedback">Please enter an issuing organization.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="issued_date">Issue Date <span class="text-danger">*</span></label>
                            <input class="form-control" id="issued_date" name="issued_date" type="date" required>
                            <div class="invalid-feedback">Please choose an issue date.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="expiry_date">Expiration Date</label>
                            <input class="form-control" id="expiry_date" name="expiry_date" type="date">
                            <div class="form-text">Leave empty if the certificate does not expire.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="display_order">Display Order <span class="text-danger">*</span></label>
                            <input class="form-control" id="display_order" name="display_order" type="number" min="0" step="1" required>
                            <div class="invalid-feedback">Please enter a display order.</div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="credential_url">Credential URL</label>
                            <input class="form-control" id="credential_url" name="credential_url" type="url" maxlength="500" placeholder="https://...">
                            <div class="invalid-feedback">Please enter a valid credential URL.</div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="certificate_image">Certificate Image <span class="text-danger">*</span></label>
                            <input class="form-control" id="certificate_image" name="certificate_image" type="file" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text">Use JPG, PNG, or WebP. Upload a new file only when you want to replace the current image.</div>
                            <div class="invalid-feedback">Please upload a valid certificate image.</div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="certificate_pdf">Certificate PDF</label>
                            <input class="form-control" id="certificate_pdf" name="certificate_pdf" type="file" accept="application/pdf">
                            <div class="form-text">Optional PDF download that visitors can save from the details modal.</div>
                            <div class="invalid-feedback">Please upload a valid PDF certificate file.</div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" maxlength="4000" placeholder="Add certification notes, exam coverage, or achievement details."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="row g-3 align-items-stretch">
                                <div class="col-lg-7">
                                    <div class="media-preview media-preview--contain" id="certificateImagePreview">
                                        <span class="text-muted">Image preview will appear here.</span>
                                    </div>
                                    <div class="media-preview__meta mt-2" id="certificateImageMeta" aria-live="polite">No image selected.</div>
                                    <button class="btn btn-sm btn-outline-danger mt-2 d-none" type="button" id="certificateImageRemoveBtn"><i class="fa fa-times me-1"></i>Remove selected image</button>
                                </div>
                                <div class="col-lg-5">
                                    <div class="certificate-file-preview h-100" id="certificatePdfPreview">
                                        <div>
                                            <strong class="d-block mb-2">PDF preview</strong>
                                            <p class="mb-0 text-muted">No PDF uploaded yet.</p>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" id="is_public" name="is_public" type="checkbox" value="1" checked>
                                        <label class="form-check-label" for="is_public">Is Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="certificateSaveBtn">
                        <span class="certificate-save-text">Save Certificate</span>
                        <span class="certificate-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
