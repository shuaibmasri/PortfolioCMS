<?php
declare(strict_types=1);
?>
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true" aria-labelledby="messageModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content message-details-modal">
            <div class="modal-header">
                <div>
                    <span class="message-details-modal__eyebrow">Incoming Message</span>
                    <h5 class="modal-title" id="messageModalLabel">Message Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="messageDetailsAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                    <span class="badge" id="messageStatusBadge">New</span>
                    <span class="small text-muted" id="messageCreatedAt"></span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="message-details__meta p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-5">
                            <small class="d-block text-uppercase fw-semibold mb-1 text-white-50">Sender</small>
                            <strong id="messageSenderName">-</strong>
                            <div class="small text-muted" id="messageSenderEmail"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="message-details__meta p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-5">
                            <small class="d-block text-uppercase fw-semibold mb-1 text-white-50">Subject</small>
                            <strong id="messageSubject">-</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="message-details__meta p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-5">
                            <small class="d-block text-uppercase fw-semibold mb-1 text-white-50">Read At</small>
                            <strong id="messageReadAt">-</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="message-details__meta p-3 rounded-4 border border-white border-opacity-10 bg-white bg-opacity-5">
                            <small class="d-block text-uppercase fw-semibold mb-1 text-white-50">Replied At</small>
                            <strong id="messageRepliedAt">-</strong>
                        </div>
                    </div>
                </div>

                <div class="message-details__body p-4 rounded-4 border border-white border-opacity-10 bg-black bg-opacity-25" id="messageBody"></div>
                <div class="mt-3 small text-muted" id="messageMeta"></div>
            </div>
            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <div class="small text-muted">Use the actions below to manage this message.</div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-light" type="button" id="messageToggleBtn">
                        <span class="message-toggle-text">Mark as Read</span>
                        <span class="message-toggle-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                    <button class="btn btn-outline-danger" type="button" id="messageDeleteBtn">
                        <span class="message-delete-text">Delete Message</span>
                        <span class="message-delete-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                    <button class="btn btn-primary" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
