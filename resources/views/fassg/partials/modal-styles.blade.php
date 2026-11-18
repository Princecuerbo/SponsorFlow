<style>
    /*
     * SPONSORFLOW FASSG CONFIRMATION MODALS — DESKTOP STYLING
     * Scoped strictly to body.sf-fassg-portal and @media (min-width: 992px).
     * Applied to:
     *   A. Archive Fixed List Modal
     *   B. Remove Candidate from Draft Modal
     *   C. Reject Application Modal
     *   D. Request Document Resubmission Modal
     * Below 992px the native browser confirm() fallback is kept by JavaScript.
     */
    @media screen and (min-width: 992px) {

        /* ── Modal Container ── */
        body.sf-fassg-portal .sf-confirm-modal .modal-dialog {
            max-width: 560px;
        }

        body.sf-fassg-portal .sf-confirm-modal.sf-confirm-modal-wide .modal-dialog {
            max-width: 620px;
        }

        body.sf-fassg-portal .sf-confirm-modal .modal-content {
            background-color: #FFFFFF !important;
            border: 1px solid #E1E8EC !important;
            border-radius: 16px !important;
            box-shadow: 0 20px 25px -5px rgba(15, 37, 55, 0.12),
                        0 10px 10px -5px rgba(15, 37, 55, 0.04) !important;
            overflow: hidden;
            padding: 0;
        }

        /* ── Header ── */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 24px 12px;
            border-bottom: none;
            background: #FFFFFF;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-header-left {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            flex: 1 1 auto;
            min-width: 0;
        }

        /* 48×48 Outlined Icon Container */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-icon-box {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-icon-box.amber {
            background-color: #FFF7E6;
            border: 1px solid #F6DDA1;
            color: #B88219;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-icon-box.red {
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            color: #DC2626;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-title-area {
            flex: 1 1 auto;
            padding-top: 4px;
            min-width: 0;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-title {
            font-size: 18px;
            font-weight: 700;
            color: #0F2942;
            margin: 0 0 2px;
            line-height: 1.35;
            font-family: var(--font-heading, 'Plus Jakarta Sans', sans-serif);
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-subtitle {
            font-size: 13px;
            color: #64748B;
            margin: 0;
            line-height: 1.4;
        }

        /* Close Button */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-close {
            background: transparent;
            border: none;
            color: #94A3B8;
            font-size: 15px;
            line-height: 1;
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-close:hover,
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-close:focus-visible {
            background-color: #F1F5F9;
            color: #0F2942;
        }

        /* ── Body ── */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-body {
            padding: 4px 24px 20px;
            font-size: 13.5px;
            color: #334155;
            line-height: 1.6;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-desc {
            font-size: 14px;
            font-weight: 500;
            color: #1E293B;
            margin-bottom: 14px;
        }

        /* Notice Boxes */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-notice {
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.55;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-notice .bi {
            font-size: 15px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-notice.amber {
            background-color: #FFF7E6;
            border: 1px solid #F6DDA1;
            color: #92400E;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-notice.red {
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-notice.neutral {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            color: #475569;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-notice:last-child {
            margin-bottom: 0;
        }

        /* Preview Box (rejection reason, documents, notes) */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-preview-box {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-preview-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            margin-bottom: 6px;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-preview-content {
            font-size: 13px;
            color: #1E293B;
            line-height: 1.55;
            word-break: break-word;
            white-space: pre-line;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-preview-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-preview-list li {
            font-size: 13px;
            color: #1E293B;
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-preview-list li .bi {
            color: #D97706;
            font-size: 12px;
        }

        /* ── Footer ── */
        body.sf-fassg-portal .sf-confirm-modal .sf-confirm-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 24px 20px;
            border-top: 1px solid #F1F5F9;
            background-color: #FAFCFE;
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        /* Button base */
        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn {
            height: 40px;
            padding: 0 18px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            text-decoration: none;
            border: 1px solid transparent;
            line-height: 1;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn:focus-visible {
            outline: 2px solid #0F2942;
            outline-offset: 2px;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Cancel */
        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-cancel {
            background-color: #FFFFFF;
            border-color: #CBD5E1;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-cancel:hover:not(:disabled) {
            background-color: #F8FAFC;
            border-color: #94A3B8;
        }

        /* Destructive Red */
        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-danger {
            background-color: #DC2626;
            border-color: #DC2626;
            color: #FFFFFF;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-danger:hover:not(:disabled) {
            background-color: #B91C1C;
            border-color: #B91C1C;
        }

        /* Amber */
        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-amber {
            background-color: #D97706;
            border-color: #D97706;
            color: #FFFFFF;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-amber:hover:not(:disabled) {
            background-color: #B45309;
            border-color: #B45309;
        }

        /* Navy */
        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-navy {
            background-color: #0F2942;
            border-color: #0F2942;
            color: #FFFFFF;
        }

        body.sf-fassg-portal .sf-confirm-modal .sf-modal-btn-navy:hover:not(:disabled) {
            background-color: #0A1E31;
            border-color: #0A1E31;
        }
    }
</style>
