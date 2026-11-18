{{-- FASSG Fixed Lists Desktop Scoped Stylesheet --}}
<style>
@media screen and (min-width: 992px) {
    body.sf-fassg-portal .sf-fixed-lists-page,
    body.sf-fassg-portal .sf-fixed-list-detail-page,
    body.sf-fassg-portal .sf-fixed-list-edit-page {
        font-family: var(--sf-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
        color: #1E293B;
    }

    /* ==========================================================================
       1. Common Card & Container Styles
       ========================================================================== */
    body.sf-fassg-portal .sf-card,
    body.sf-fassg-portal .sf-fixed-list-card,
    body.sf-fassg-portal .fixed-list-panel {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
        box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04) !important;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    body.sf-fassg-portal .sf-fixed-list-card {
        margin-bottom: 1.25rem !important;
    }

    body.sf-fassg-portal .sf-fixed-list-card:hover {
        border-color: #CBD5E1 !important;
        box-shadow: 0 4px 12px rgba(15, 41, 66, 0.07) !important;
    }

    body.sf-fassg-portal .sf-fixed-list-card-body {
        padding: 1.25rem 1.5rem !important;
    }

    body.sf-fassg-portal .sf-fixed-list-title {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
        margin-bottom: 0.25rem !important;
        text-decoration: none !important;
        display: inline-block;
    }

    body.sf-fassg-portal .sf-fixed-list-title:hover {
        color: #0284C7 !important;
        text-decoration: underline !important;
    }

    body.sf-fassg-portal .sf-fixed-list-meta {
        font-size: 0.8125rem !important;
        color: #64748B !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    body.sf-fassg-portal .sf-meta-dot {
        color: #CBD5E1;
        font-size: 0.75rem;
    }

    /* Buttons */
    body.sf-fassg-portal .btn-sf-navy {
        background-color: #0F2942 !important;
        color: #FFFFFF !important;
        border: 1px solid #0F2942 !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .btn-sf-navy:hover:not(:disabled) {
        background-color: #1A3D5E !important;
        border-color: #1A3D5E !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 6px rgba(15, 41, 66, 0.2) !important;
    }

    body.sf-fassg-portal .btn-sf-navy:disabled {
        background-color: #E2E8F0 !important;
        border-color: #CBD5E1 !important;
        color: #94A3B8 !important;
        cursor: not-allowed !important;
        opacity: 0.65 !important;
    }

    body.sf-fassg-portal .sf-btn-view-list {
        background-color: #0F2942 !important;
        color: #FFFFFF !important;
        border: 1px solid #0F2942 !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        padding: 0.45rem 1rem !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-view-list:hover {
        background-color: #1A3D5E !important;
        border-color: #1A3D5E !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 6px rgba(15, 41, 66, 0.2) !important;
    }

    /* ==========================================================================
       2. Information Banner
       ========================================================================== */
    body.sf-fassg-portal .sf-notice-card {
        background-color: #F0F7FF !important;
        border: 1px solid #BAE6FD !important;
        border-radius: 12px !important;
        padding: 0.875rem 1.25rem !important;
        margin-bottom: 1.25rem !important;
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
    }

    body.sf-fassg-portal .sf-notice-card i {
        color: #0284C7 !important;
        font-size: 1.125rem !important;
        margin-top: 0.125rem;
        flex-shrink: 0;
    }

    body.sf-fassg-portal .sf-notice-card .sf-notice-title {
        color: #0F2942 !important;
        font-size: 0.84375rem !important;
        font-weight: 700 !important;
    }

    body.sf-fassg-portal .sf-notice-card .sf-notice-text {
        color: #0369A1 !important;
        font-size: 0.8125rem !important;
        line-height: 1.45;
    }

    /* Finalized Notification Card (Compact Teal / Soft Blue) */
    body.sf-fassg-portal .sf-finalized-notice-card {
        background-color: #F0F9FF !important;
        border: 1px solid #BAE6FD !important;
        border-radius: 12px !important;
        padding: 1rem 1.25rem !important;
        margin-bottom: 1.5rem !important;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    body.sf-fassg-portal .sf-finalized-notice-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background-color: #E0F2FE;
        color: #0284C7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    body.sf-fassg-portal .sf-finalized-notice-title {
        color: #0F2942 !important;
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
    }

    body.sf-fassg-portal .sf-finalized-notice-desc {
        color: #0369A1 !important;
        font-size: 0.8125rem !important;
        line-height: 1.4;
        margin-bottom: 0 !important;
    }

    /* ==========================================================================
       3. Standardized Status Badges
       ========================================================================== */
    body.sf-fassg-portal .sf-fixed-list-status-badge {
        font-size: 0.75rem !important; /* 12px */
        font-weight: 600 !important;
        padding: 0.35rem 0.65rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        line-height: 1.2 !important;
    }

    body.sf-fassg-portal .sf-fixed-list-status-badge i.bi {
        font-size: 0.8125rem !important;
        line-height: 1 !important;
        margin-right: 0 !important;
    }

    /* Draft - bi-file-earmark-text (Neutral slate) */
    body.sf-fassg-portal .sf-fixed-list-status-draft {
        background-color: #F1F5F9 !important;
        color: #475569 !important;
        border: 1px solid #CBD5E1 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-draft i.bi {
        color: #64748B !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-draft i.bi::before {
        content: "\f38b" !important; /* bi-file-earmark-text */
    }

    /* Finalized - bi-lock (Soft Blue / Teal) - NOT green approval! */
    body.sf-fassg-portal .sf-fixed-list-status-finalized {
        background-color: #E0F2FE !important;
        color: #0369A1 !important;
        border: 1px solid #BAE6FD !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-finalized i.bi {
        color: #0284C7 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-finalized i.bi::before {
        content: "\f47b" !important; /* bi-lock */
    }

    /* Submitted - bi-send (Amber) */
    body.sf-fassg-portal .sf-fixed-list-status-submitted {
        background-color: #FFF8EA !important;
        color: #986515 !important;
        border: 1px solid #F6DDA1 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-submitted i.bi {
        color: #B88219 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-submitted i.bi::before {
        content: "\f6c0" !important; /* bi-send */
    }

    /* Approved - bi-check-circle (Emerald) */
    body.sf-fassg-portal .sf-fixed-list-status-approved {
        background-color: #ECFDF5 !important;
        color: #065F46 !important;
        border: 1px solid #A7F3D0 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-approved i.bi {
        color: #059669 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-approved i.bi::before {
        content: "\f26b" !important; /* bi-check-circle */
    }

    /* Rejected - bi-x-circle (Red) */
    body.sf-fassg-portal .sf-fixed-list-status-rejected {
        background-color: #FEF2F2 !important;
        color: #991B1B !important;
        border: 1px solid #FECACA !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-rejected i.bi {
        color: #DC2626 !important;
    }
    body.sf-fassg-portal .sf-fixed-list-status-rejected i.bi::before {
        content: "\f623" !important; /* bi-x-circle */
    }

    /* In-table Status Badges */
    body.sf-fassg-portal .sf-status-badge-inline {
        font-size: 0.6875rem !important;
        font-weight: 600 !important;
        padding: 0.2rem 0.5rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    /* SLE-FHE Verified - bi-shield-check (Soft green) */
    body.sf-fassg-portal .sf-status-verified {
        background-color: #ECFDF5 !important;
        color: #065F46 !important;
        border: 1px solid #A7F3D0 !important;
    }
    body.sf-fassg-portal .sf-status-verified i {
        color: #059669 !important;
    }

    /* FASSG Endorsed - bi-check2-circle (Soft blue) */
    body.sf-fassg-portal .sf-status-endorsed {
        background-color: #EFF6FF !important;
        color: #1D4ED8 !important;
        border: 1px solid #BFDBFE !important;
    }
    body.sf-fassg-portal .sf-status-endorsed i {
        color: #2563EB !important;
    }

    /* Pending - bi-clock (Warm amber) */
    body.sf-fassg-portal .sf-status-pending {
        background-color: #FFF8EA !important;
        color: #986515 !important;
        border: 1px solid #F6DDA1 !important;
    }
    body.sf-fassg-portal .sf-status-pending i {
        color: #B88219 !important;
    }

    /* Ineligible - bi-x-circle (Soft red) */
    body.sf-fassg-portal .sf-status-ineligible {
        background-color: #FEF2F2 !important;
        color: #991B1B !important;
        border: 1px solid #FECACA !important;
    }
    body.sf-fassg-portal .sf-status-ineligible i {
        color: #DC2626 !important;
    }

    /* Source Badge */
    body.sf-fassg-portal .sf-source-badge-fixed {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        background-color: #FFF8EA !important;
        color: #986515 !important;
        border: 1px solid #F6DDA1 !important;
        font-size: 0.65625rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.02em !important;
        padding: 0.15rem 0.4rem !important;
        border-radius: 5px !important;
        white-space: nowrap !important;
    }

    /* ==========================================================================
       4. Table Styles & Clean Layout
       ========================================================================== */
    body.sf-fassg-portal .sf-table-card {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
        overflow: hidden !important;
        margin-bottom: 1.5rem !important;
    }

    body.sf-fassg-portal .sf-table-card-header {
        background-color: #FFFFFF !important;
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid #E1E8EC !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    body.sf-fassg-portal .sf-table-card-header .sf-table-title {
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
        margin: 0 !important;
    }

    body.sf-fassg-portal .sf-table-card-header .sf-table-subtitle {
        font-size: 0.78125rem !important;
        color: #64748B !important;
        margin: 0.15rem 0 0 0 !important;
    }

    body.sf-fassg-portal .sf-fixed-list-table {
        margin-bottom: 0 !important;
        width: 100% !important;
        border-collapse: separate;
        border-spacing: 0;
    }

    body.sf-fassg-portal .sf-fixed-list-table thead th {
        background-color: #F8FAFC !important;
        color: #475569 !important;
        font-size: 0.6875rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.04em !important;
        border-bottom: 1px solid #E1E8EC !important;
        border-top: none !important;
        padding: 0.625rem 0.5rem !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }

    body.sf-fassg-portal .sf-fixed-list-table tbody td {
        padding: 0.625rem 0.5rem !important;
        border-bottom: 1px solid #F1F5F9 !important;
        font-size: 0.78125rem !important;
        color: #334155 !important;
        vertical-align: middle !important;
    }

    body.sf-fassg-portal .sf-fixed-list-table tbody tr:last-child td {
        border-bottom: none !important;
    }

    body.sf-fassg-portal .sf-fixed-list-table tbody tr:hover {
        background-color: #F8FAFC !important;
    }

    body.sf-fassg-portal .sf-student-name {
        font-weight: 700 !important;
        color: #0F2942 !important;
        font-size: 0.8125rem !important;
        line-height: 1.3;
        word-break: break-word;
    }

    body.sf-fassg-portal .sf-student-id {
        font-family: var(--bs-font-monospace, monospace) !important;
        color: #64748B !important;
        font-size: 0.75rem !important;
        white-space: nowrap;
        display: block;
        margin-top: 2px;
    }

    body.sf-fassg-portal .sf-course-name {
        font-size: 0.78125rem !important;
        color: #1E293B !important;
        font-weight: 600;
        line-height: 1.3;
        word-break: break-word;
    }

    body.sf-fassg-portal .sf-academic-meta {
        font-size: 0.71875rem !important;
        color: #64748B !important;
        margin-top: 2px;
        line-height: 1.3;
        word-break: break-word;
    }

    body.sf-fassg-portal .sf-row-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
        white-space: nowrap;
    }

    body.sf-fassg-portal .sf-btn-action-endorse {
        background-color: #EFF6FF !important;
        color: #1D4ED8 !important;
        border: 1px solid #BFDBFE !important;
        font-size: 0.71875rem !important;
        font-weight: 600 !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-action-endorse:hover {
        background-color: #DBEAFE !important;
        color: #1E40AF !important;
        border-color: #93C5FD !important;
    }

    body.sf-fassg-portal .sf-btn-action-verify {
        background-color: #ECFDF5 !important;
        color: #065F46 !important;
        border: 1px solid #A7F3D0 !important;
        font-size: 0.71875rem !important;
        font-weight: 600 !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-action-verify:hover {
        background-color: #D1FAE5 !important;
        color: #047857 !important;
    }

    body.sf-fassg-portal .sf-btn-action-remove {
        background-color: transparent !important;
        color: #DC2626 !important;
        border: 1px solid #FECACA !important;
        font-size: 0.71875rem !important;
        font-weight: 600 !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-action-remove:hover {
        background-color: #FEF2F2 !important;
        border-color: #DC2626 !important;
        color: #991B1B !important;
    }

    /* ==========================================================================
       5. Workflow & Finalization Panels
       ========================================================================== */
    body.sf-fassg-portal .sf-panel-card {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
        margin-bottom: 1.5rem !important;
    }

    body.sf-fassg-portal .sf-panel-card-body {
        padding: 1.5rem !important;
    }

    body.sf-fassg-portal .sf-panel-title {
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem !important;
    }

    body.sf-fassg-portal .sf-panel-desc {
        font-size: 0.8125rem !important;
        color: #64748B !important;
        line-height: 1.45;
        margin-bottom: 1rem !important;
    }

    /* Action Required Warning */
    body.sf-fassg-portal .sf-action-required-card {
        background-color: #FFF7E6 !important;
        color: #986515 !important;
        border: 1px solid #F6DDA1 !important;
        border-radius: 10px !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.8125rem !important;
        line-height: 1.45 !important;
        text-align: left;
        margin-bottom: 1rem !important;
    }

    body.sf-fassg-portal .sf-action-required-card i {
        color: #B88219 !important;
    }

    body.sf-fassg-portal .sf-action-required-card strong {
        color: #78350F !important;
    }

    /* Helper Code Pill */
    body.sf-fassg-portal .sf-code-pill {
        font-family: var(--bs-font-monospace, monospace);
        font-size: 0.71875rem;
        background-color: #F1F5F9;
        color: #334155;
        padding: 0.15rem 0.35rem;
        border-radius: 4px;
        border: 1px solid #E2E8F0;
    }

    /* ==========================================================================
       6. Modals (New List Modal & Remove Confirmation Modal)
       ========================================================================== */
    body.sf-fassg-portal .modal-content {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        box-shadow: 0 10px 25px rgba(15, 41, 66, 0.1) !important;
        overflow: hidden !important;
    }

    body.sf-fassg-portal .modal-header {
        padding: 1.25rem 1.5rem !important;
        border-bottom: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
    }

    body.sf-fassg-portal .modal-header .modal-title {
        font-size: 1.0625rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
    }

    body.sf-fassg-portal .modal-body {
        padding: 1.5rem !important;
    }

    body.sf-fassg-portal .modal-footer {
        padding: 1rem 1.5rem !important;
        border-top: 1px solid #E1E8EC !important;
        background-color: #F8FAFC !important;
    }

    body.sf-fassg-portal .sf-form-section-title {
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em !important;
        margin-bottom: 0.75rem !important;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    body.sf-fassg-portal .sf-form-section-desc {
        font-size: 0.75rem !important;
        color: #64748B !important;
        font-weight: 400 !important;
        text-transform: none !important;
        letter-spacing: normal !important;
    }

    body.sf-fassg-portal .form-label {
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        color: #334155 !important;
        margin-bottom: 0.375rem !important;
    }

    body.sf-fassg-portal .form-control,
    body.sf-fassg-portal .form-select {
        border-radius: 8px !important;
        border: 1px solid #D0D9E0 !important;
        font-size: 0.875rem !important;
        color: #1E293B !important;
    }

    body.sf-fassg-portal .form-control:focus,
    body.sf-fassg-portal .form-select:focus {
        border-color: #0F2942 !important;
        box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
        outline: none !important;
    }

    /* Destructive Modal Header */
    body.sf-fassg-portal .sf-modal-header-danger {
        background-color: #FEF2F2 !important;
        border-bottom: 1px solid #FEE2E2 !important;
    }

    body.sf-fassg-portal .sf-modal-header-danger .modal-title {
        color: #DC2626 !important;
    }
}
</style>
