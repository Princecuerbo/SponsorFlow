{{-- Generated Batches Desktop Scoped Stylesheet --}}
<style>
@media screen and (min-width: 992px) {
    body.sf-fassg-portal .sf-batches-page,
    body.sf-fassg-portal .sf-batch-detail-page {
        font-family: var(--sf-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
        color: #1E293B;
    }

    /* ==========================================================================
       1. Common Card & Container Styles
       ========================================================================== */
    body.sf-fassg-portal .sf-batches-page .card,
    body.sf-fassg-portal .sf-batch-detail-page .card {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
        box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04) !important;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    /* ==========================================================================
       2. Filter Toolbar (Listing Page)
       ========================================================================== */
    body.sf-fassg-portal .sf-batches-filter-card {
        padding: 1.125rem 1.25rem !important;
        margin-bottom: 1.5rem !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .form-label {
        color: #475569 !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        margin-bottom: 0.375rem !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .form-select {
        border: 1px solid #D0D9E0 !important;
        border-radius: 8px !important;
        font-size: 0.875rem !important;
        color: #1E293B !important;
        padding: 0.45rem 2rem 0.45rem 0.75rem !important;
        background-color: #FFFFFF !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    body.sf-fassg-portal .sf-batches-filter-card .form-select:focus {
        border-color: #0F2942 !important;
        box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
        outline: none !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .btn-reset {
        height: 38px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        border: 1px solid #D5E0E8 !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #173B57 !important;
        background-color: #FFFFFF !important;
        padding: 0 16px !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: all 0.15s ease !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .btn-reset i {
        font-size: 14px !important;
        line-height: 1 !important;
        color: #173B57 !important;
        transition: color 0.15s ease !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .btn-reset:hover {
        background-color: #EEF3F7 !important;
        color: #0F2942 !important;
        border-color: #AFC3D3 !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .btn-reset:hover i {
        color: #0F2942 !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .btn-reset:focus-visible {
        outline: none !important;
        border-color: #173B57 !important;
        box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
    }

    body.sf-fassg-portal .sf-batches-filter-card .btn-reset:active {
        background-color: #E2EAF0 !important;
        border-color: #9DB4C6 !important;
        color: #0F2942 !important;
    }

    /* ==========================================================================
       3. Batch Cards (Listing Page)
       ========================================================================== */
    body.sf-fassg-portal .sf-batch-card {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
        margin-bottom: 1rem !important;
        box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    }

    body.sf-fassg-portal .sf-batch-card:hover {
        border-color: #CBD5E1 !important;
        box-shadow: 0 4px 12px rgba(15, 41, 66, 0.07) !important;
    }

    body.sf-fassg-portal .sf-batch-card-body {
        padding: 1.25rem 1.5rem !important;
    }

    body.sf-fassg-portal .sf-batch-title {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
        margin-bottom: 0.25rem !important;
        text-decoration: none !important;
        display: inline-block;
    }

    body.sf-fassg-portal .sf-batch-title:hover {
        color: #0284C7 !important;
        text-decoration: underline !important;
    }

    body.sf-fassg-portal .sf-batch-meta {
        font-size: 0.8125rem !important;
        color: #64748B !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    body.sf-fassg-portal .sf-batch-meta-dot {
        color: #CBD5E1;
        font-size: 0.75rem;
    }

    body.sf-fassg-portal .sf-batch-btn-view {
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

    body.sf-fassg-portal .sf-batch-btn-view:hover {
        background-color: #1A3D5E !important;
        border-color: #1A3D5E !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 6px rgba(15, 41, 66, 0.2) !important;
    }

    /* ==========================================================================
       4. Notice Banner (Detail Page)
       ========================================================================== */
    body.sf-fassg-portal .sf-batch-notice-card {
        background-color: #F0F7FF !important;
        border: 1px solid #BAE6FD !important;
        border-radius: 12px !important;
        padding: 0.875rem 1.25rem !important;
        margin-bottom: 1.25rem !important;
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
    }

    body.sf-fassg-portal .sf-batch-notice-card i {
        color: #0284C7 !important;
        font-size: 1.125rem !important;
        margin-top: 0.125rem;
        flex-shrink: 0;
    }

    body.sf-fassg-portal .sf-batch-notice-card .sf-notice-title {
        color: #0F2942 !important;
        font-size: 0.84375rem !important;
        font-weight: 700 !important;
    }

    body.sf-fassg-portal .sf-batch-notice-card .sf-notice-text {
        color: #0369A1 !important;
        font-size: 0.8125rem !important;
        line-height: 1.45;
    }

    /* ==========================================================================
       5. Candidates Table (Detail Page)
       ========================================================================== */
    body.sf-fassg-portal .sf-table-card {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
        overflow: hidden !important;
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
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    body.sf-fassg-portal .sf-table-card-header .sf-table-subtitle {
        font-size: 0.78125rem !important;
        color: #64748B !important;
        background-color: #F1F5F9 !important;
        border: 1px solid #E2E8F0 !important;
        padding: 0.2rem 0.625rem !important;
        border-radius: 9999px !important;
        font-weight: 600 !important;
    }

    body.sf-fassg-portal .sf-batch-table {
        margin-bottom: 0 !important;
        width: 100% !important;
        border-collapse: separate;
        border-spacing: 0;
    }

    body.sf-fassg-portal .sf-batch-table thead th {
        background-color: #F8FAFC !important;
        color: #475569 !important;
        font-size: 0.6875rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.04em !important;
        border-bottom: 1px solid #E1E8EC !important;
        border-top: none !important;
        padding: 0.625rem 0.35rem !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }

    body.sf-fassg-portal .sf-batch-table tbody td {
        padding: 0.625rem 0.35rem !important;
        border-bottom: 1px solid #F1F5F9 !important;
        font-size: 0.78125rem !important;
        color: #334155 !important;
        vertical-align: middle !important;
    }

    body.sf-fassg-portal .sf-batch-table tbody tr:last-child td {
        border-bottom: none !important;
    }

    body.sf-fassg-portal .sf-batch-table tbody tr:hover {
        background-color: #F8FAFC !important;
    }

    /* Badges inside table */
    body.sf-fassg-portal .sf-batch-table .badge {
        padding: 0.2rem 0.45rem !important;
        font-size: 0.6875rem !important;
        font-weight: 600 !important;
    }

    /* Number badge */
    body.sf-fassg-portal .sf-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background-color: #F1F5F9;
        color: #475569;
        border: 1px solid #E2E8F0;
        font-size: 0.6875rem;
        font-weight: 700;
    }

    /* Candidate Source Badges */
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
        padding: 0.15rem 0.35rem !important;
        border-radius: 5px !important;
        white-space: nowrap !important;
    }

    body.sf-fassg-portal .sf-source-badge-ordinary {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        background-color: #F0F7FF !important;
        color: #0369A1 !important;
        border: 1px solid #BAE6FD !important;
        font-size: 0.65625rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.02em !important;
        padding: 0.15rem 0.35rem !important;
        border-radius: 5px !important;
        white-space: nowrap !important;
    }

    /* Student ID */
    body.sf-fassg-portal .sf-student-id {
        font-family: var(--bs-font-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace) !important;
        color: #475569 !important;
        font-weight: 600 !important;
        font-size: 0.78125rem !important;
        white-space: nowrap !important;
    }

    /* Student Name */
    body.sf-fassg-portal .sf-student-name {
        font-weight: 700 !important;
        color: #0F2942 !important;
        font-size: 0.8125rem !important;
        line-height: 1.3;
        word-break: break-word;
    }

    /* Course wrapping */
    body.sf-fassg-portal .sf-course-cell {
        font-size: 0.78125rem;
        line-height: 1.3;
        color: #334155;
        word-break: break-word;
    }

    /* Combined Year & Campus */
    body.sf-fassg-portal .sf-year-campus-cell {
        line-height: 1.25;
    }

    body.sf-fassg-portal .sf-year-text {
        font-weight: 600;
        color: #1E293B;
        font-size: 0.78125rem;
        display: block;
    }

    body.sf-fassg-portal .sf-campus-text {
        font-size: 0.71875rem;
        color: #64748B;
        display: block;
        margin-top: 1px;
        word-break: break-word;
    }

    body.sf-fassg-portal .sf-gwa-text {
        font-size: 0.8125rem;
        color: #0F2942;
    }

    /* Row actions */
    body.sf-fassg-portal .sf-action-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.25rem;
        white-space: nowrap;
    }

    body.sf-fassg-portal .sf-btn-review-member {
        background-color: #0F2942 !important;
        color: #FFFFFF !important;
        border: 1px solid #0F2942 !important;
        font-size: 0.71875rem !important;
        font-weight: 600 !important;
        padding: 0.25rem 0.45rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.2rem !important;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-review-member:hover {
        background-color: #1A3D5E !important;
        border-color: #1A3D5E !important;
        color: #FFFFFF !important;
    }

    body.sf-fassg-portal .sf-btn-remove-draft {
        background-color: transparent !important;
        color: #DC2626 !important;
        border: 1px solid #FECACA !important;
        font-size: 0.71875rem !important;
        font-weight: 600 !important;
        padding: 0.25rem 0.45rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.2rem !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-remove-draft:hover {
        background-color: #FEF2F2 !important;
        border-color: #DC2626 !important;
        color: #991B1B !important;
    }

    body.sf-fassg-portal .sf-awaiting-badge {
        font-size: 0.65625rem !important;
        padding: 0.12rem 0.35rem !important;
    }

    /* ==========================================================================
       6. Empty State Card (Completely eliminates table scrollbar!)
       ========================================================================== */
    body.sf-fassg-portal .sf-batch-empty-card {
        padding: 3.5rem 1.5rem !important;
        text-align: center !important;
        background-color: #FFFFFF !important;
        border-radius: 14px !important;
    }

    body.sf-fassg-portal .sf-batch-empty-icon {
        width: 60px;
        height: 60px;
        line-height: 60px;
        border-radius: 50%;
        background-color: #F1F5F9;
        color: #94A3B8;
        font-size: 1.75rem;
        margin: 0 auto 1rem auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    body.sf-fassg-portal .sf-batch-empty-title {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: #1E293B !important;
        margin-bottom: 0.375rem !important;
    }

    body.sf-fassg-portal .sf-batch-empty-text {
        font-size: 0.84375rem !important;
        color: #64748B !important;
        max-width: 480px;
        margin: 0 auto !important;
        line-height: 1.45;
    }

    /* ==========================================================================
       7. Workflow & Submission Section (Full-Width Card Below Table)
       ========================================================================== */
    body.sf-fassg-portal .sf-workflow-card {
        border-radius: 14px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #FFFFFF !important;
    }

    body.sf-fassg-portal .sf-workflow-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background-color: #EEF3F7 !important;
        color: #173B57 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    body.sf-fassg-portal .sf-workflow-title {
        font-size: 1.0625rem !important;
        font-weight: 700 !important;
        color: #0F2942 !important;
    }

    body.sf-fassg-portal .sf-workflow-subtitle {
        font-size: 0.84375rem !important;
        color: #64748B !important;
        line-height: 1.4;
    }

    /* Action Required Warning Alert */
    body.sf-fassg-portal .sf-action-required-alert {
        background-color: #FFF7E6 !important;
        color: #986515 !important;
        border: 1px solid #F6DDA1 !important;
        border-radius: 10px !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.8125rem !important;
        line-height: 1.45 !important;
        text-align: left;
    }

    body.sf-fassg-portal .sf-action-required-alert i {
        color: #B88219 !important;
    }

    body.sf-fassg-portal .sf-action-required-alert strong {
        color: #78350F !important;
    }

    /* Submit Batch Button */
    body.sf-fassg-portal .sf-btn-submit-batch {
        background-color: #0F2942 !important;
        color: #FFFFFF !important;
        border: 1px solid #0F2942 !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        padding: 0.6rem 1.25rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        transition: all 0.15s ease-in-out !important;
    }

    body.sf-fassg-portal .sf-btn-submit-batch:hover:not(:disabled) {
        background-color: #1A3D5E !important;
        border-color: #1A3D5E !important;
        box-shadow: 0 2px 6px rgba(15, 41, 66, 0.2) !important;
    }

    body.sf-fassg-portal .sf-btn-submit-batch:disabled {
        background-color: #E2E8F0 !important;
        border-color: #CBD5E1 !important;
        color: #94A3B8 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
        opacity: 0.7 !important;
    }

    /* Delete Batch Section - Clearly Separated from Submit */
    body.sf-fassg-portal .sf-delete-section {
        border-top: 1px solid #E1E8EC !important;
        margin-top: 0.75rem !important;
        padding-top: 0.75rem !important;
    }

    body.sf-fassg-portal .sf-delete-note {
        font-size: 0.75rem !important;
        color: #64748B !important;
        line-height: 1.4;
    }

    body.sf-fassg-portal .sf-btn-delete-batch-outlined {
        background-color: #FFFFFF !important;
        color: #DC2626 !important;
        border: 1px solid #FECACA !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        padding: 0.35rem 0.65rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        cursor: pointer !important;
        transition: all 0.15s ease-in-out !important;
        white-space: nowrap !important;
    }

    body.sf-fassg-portal .sf-btn-delete-batch-outlined:hover {
        background-color: #FEF2F2 !important;
        border-color: #DC2626 !important;
        color: #991B1B !important;
    }

    /* Evidence card */
    body.sf-fassg-portal .sf-evidence-card {
        border-radius: 10px !important;
        border: 1px solid #E1E8EC !important;
        background-color: #F8FAFC !important;
    }

    /* Submitted Batch Status (Amber tone) */
    body.sf-fassg-portal .sf-submitted-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background-color: #FFF8EA !important;
        color: #986515 !important;
        border: 1px solid #F6DDA1 !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        padding: 0.45rem 1rem !important;
        border-radius: 8px !important;
    }

    body.sf-fassg-portal .sf-submitted-status-badge i {
        color: #B88219 !important;
    }

    /* Legacy Alert */
    body.sf-fassg-portal .sf-legacy-alert {
        background-color: #FFFBEB !important;
        color: #92400E !important;
        border: 1px solid #FDE68A !important;
        border-radius: 10px !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.8125rem !important;
        line-height: 1.4;
    }

    /* ==========================================================================
       8. Standardized Status Badges (Saved, Submitted, Approved, Rejected)
       ========================================================================== */
    body.sf-fassg-portal .sf-batch-status-badge {
        font-size: 0.75rem !important; /* 12px for high legibility */
        font-weight: 600 !important;
        padding: 0.35rem 0.65rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        line-height: 1.2 !important;
    }

    body.sf-fassg-portal .sf-batch-status-badge i.bi {
        font-size: 0.8125rem !important;
        line-height: 1 !important;
        margin-right: 0 !important;
    }

    /* Saved (Neutral slate/navy) - bi-bookmark-check */
    body.sf-fassg-portal .sf-batch-status-saved {
        background-color: #F1F5F9 !important;
        color: #475569 !important;
        border-color: #CBD5E1 !important;
    }
    body.sf-fassg-portal .sf-batch-status-saved i.bi {
        color: #64748B !important;
    }
    body.sf-fassg-portal .sf-batch-status-saved i.bi::before {
        content: "\f196" !important; /* bi-bookmark-check */
    }

    /* Submitted (Amber styling) - bi-send */
    body.sf-fassg-portal .sf-batch-status-submitted {
        background-color: #FFF8EA !important;
        color: #986515 !important;
        border-color: #F6DDA1 !important;
    }
    body.sf-fassg-portal .sf-batch-status-submitted i.bi {
        color: #B88219 !important;
    }
    body.sf-fassg-portal .sf-batch-status-submitted i.bi::before {
        content: "\f6c0" !important; /* bi-send */
    }

    /* Approved (Emerald styling) - bi-check-circle */
    body.sf-fassg-portal .sf-batch-status-approved {
        background-color: #ECFDF5 !important;
        color: #065F46 !important;
        border-color: #A7F3D0 !important;
    }
    body.sf-fassg-portal .sf-batch-status-approved i.bi {
        color: #059669 !important;
    }
    body.sf-fassg-portal .sf-batch-status-approved i.bi::before {
        content: "\f26b" !important; /* bi-check-circle */
    }

    /* Rejected (Red styling) - bi-x-circle */
    body.sf-fassg-portal .sf-batch-status-rejected {
        background-color: #FEF2F2 !important;
        color: #991B1B !important;
        border-color: #FECACA !important;
    }
    body.sf-fassg-portal .sf-batch-status-rejected i.bi {
        color: #DC2626 !important;
    }
    body.sf-fassg-portal .sf-batch-status-rejected i.bi::before {
        content: "\f623" !important; /* bi-x-circle */
    }
}
</style>
