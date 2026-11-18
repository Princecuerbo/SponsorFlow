<style>
    /*
     * SPONSORFLOW FASSG APPLICATION QUEUE — DESKTOP-ONLY UI REFINEMENT
     * Applies to:
     *   1. Application Queue listing page (/fassg/applications)
     *   2. Review Application detail page (/fassg/applications/{application})
     *   3. Create Batch List from Selected modal
     * Scoped strictly under body.sf-fassg-portal and @media (min-width: 992px).
     * Tablet and mobile layouts (< 992px) are completely unaffected.
     * Design Tokens:
     *   Canvas background: #F0F4F8 / #F1F5F9
     *   Card surface: #FFFFFF
     *   Card border: #E1E8EC
     *   Border radius: 14px (cards/modals), 8px (controls), 999px (pills)
     *   Primary navy: #0F2942
     *   Secondary navy: #173B57
     *   Body slate: #23384D
     *   Muted gray: #64748B
     *   Warning amber: #986515 (bg: #FFF8EA, border: #F6DDA1)
     *   Success green: #15803D (bg: #ECFDF3, border: #A6F4C5)
     *   Info blue: #1D4ED8 (bg: #EFF6FF, border: #BFDBFE)
     *   Danger red: #DC2626 (bg: #FEF2F2, border: #FECACA)
     */

    @media screen and (min-width: 992px) {
        /* -----------------------------------------------------------
         * 1. PAGE CANVAS & HEADER STAT PILLS
         * ----------------------------------------------------------- */
        body.sf-fassg-portal > .sf-content {
            background-color: #F0F4F8 !important;
        }

        body.sf-fassg-portal .sf-applications-page,
        body.sf-fassg-portal .sf-review-page {
            color: #23384D;
            font-family: var(--font-body, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
        }

        /* Status Summary Badges in Top Header Actions */
        body.sf-fassg-portal .sf-app-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.4;
            transition: all 0.15s ease;
            white-space: nowrap;
            text-decoration: none !important;
        }

        body.sf-fassg-portal .sf-app-stat-pending {
            background-color: #FFF8EA !important;
            color: #986515 !important;
            border: 1px solid #F6DDA1 !important;
        }
        body.sf-fassg-portal .sf-app-stat-pending:hover,
        body.sf-fassg-portal .sf-app-stat-pending.active {
            background-color: #FEF3D6 !important;
            color: #784E0B !important;
            border-color: #EDC675 !important;
            box-shadow: 0 2px 4px rgba(184, 130, 25, 0.12);
        }

        body.sf-fassg-portal .sf-app-stat-verified {
            background-color: #EFF6FF !important;
            color: #1D4ED8 !important;
            border: 1px solid #BFDBFE !important;
        }
        body.sf-fassg-portal .sf-app-stat-verified:hover,
        body.sf-fassg-portal .sf-app-stat-verified.active {
            background-color: #DBEAFE !important;
            color: #1E40AF !important;
            border-color: #93C5FD !important;
            box-shadow: 0 2px 4px rgba(29, 78, 216, 0.12);
        }

        body.sf-fassg-portal .sf-app-stat-approved {
            background-color: #ECFDF3 !important;
            color: #15803D !important;
            border: 1px solid #A6F4C5 !important;
        }
        body.sf-fassg-portal .sf-app-stat-approved:hover,
        body.sf-fassg-portal .sf-app-stat-approved.active {
            background-color: #DCFCE7 !important;
            color: #166534 !important;
            border-color: #86EFAC !important;
            box-shadow: 0 2px 4px rgba(21, 128, 61, 0.12);
        }

        body.sf-fassg-portal .sf-app-stat-resubmission {
            background-color: #F0F9FF !important;
            color: #0284C7 !important;
            border: 1px solid #BAE6FD !important;
        }
        body.sf-fassg-portal .sf-app-stat-resubmission:hover,
        body.sf-fassg-portal .sf-app-stat-resubmission.active {
            background-color: #E0F2FE !important;
            color: #0369A1 !important;
            border-color: #7DD3FC !important;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.12);
        }

        body.sf-fassg-portal .sf-app-stat-rejected {
            background-color: #FEF2F2 !important;
            color: #DC2626 !important;
            border: 1px solid #FECACA !important;
        }
        body.sf-fassg-portal .sf-app-stat-rejected:hover,
        body.sf-fassg-portal .sf-app-stat-rejected.active {
            background-color: #FEE2E2 !important;
            color: #B91C1C !important;
            border-color: #FCA5A5 !important;
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.12);
        }

        body.sf-fassg-portal .sf-app-stat-slots {
            background-color: #F8FAFC !important;
            color: #0F2942 !important;
            border: 1px solid #CBD5E1 !important;
        }

        /* -----------------------------------------------------------
         * 2. FILTER TOOLBAR CARD (Single Horizontal Desktop Row)
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-app-filter-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        body.sf-fassg-portal .sf-app-filter-row {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            width: 100%;
        }

        body.sf-fassg-portal .sf-app-filter-col-program {
            width: 25%;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-app-filter-col-campus {
            width: 17%;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-app-filter-col-status {
            width: 17%;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-app-filter-col-search {
            flex: 1;
            min-width: 0;
        }

        body.sf-fassg-portal .sf-app-filter-col-reset {
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-app-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        body.sf-fassg-portal .sf-app-filter-card .form-select,
        body.sf-fassg-portal .sf-app-filter-card .form-control {
            height: 40px;
            font-size: 13px;
            border-radius: 8px;
            border: 1px solid #D1D9E0;
            color: #23384D;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        body.sf-fassg-portal .sf-app-filter-card .form-select:focus,
        body.sf-fassg-portal .sf-app-filter-card .form-control:focus {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.08);
            outline: none;
        }

        body.sf-fassg-portal .sf-app-search-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        body.sf-fassg-portal .sf-app-search-icon {
            position: absolute;
            left: 14px;
            color: #64748B;
            font-size: 14px;
            pointer-events: none;
            z-index: 2;
        }

        body.sf-fassg-portal .sf-app-search-input {
            padding-left: 38px !important;
        }

        body.sf-fassg-portal .sf-app-btn-reset {
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            padding: 0 16px;
            border-radius: 8px;
            background-color: #FFFFFF;
            border: 1px solid #D5E0E8;
            color: #173B57;
            text-decoration: none !important;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-btn-reset i {
            font-size: 14px;
            line-height: 1;
            color: #173B57;
            transition: color 0.15s ease;
        }

        body.sf-fassg-portal .sf-app-btn-reset:hover {
            background-color: #EEF3F7;
            border-color: #AFC3D3;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-app-btn-reset:hover i {
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-app-btn-reset:focus-visible {
            outline: none;
            border-color: #173B57;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2);
        }

        body.sf-fassg-portal .sf-app-btn-reset:active {
            background-color: #E2EAF0;
            border-color: #9DB4C6;
            color: #0F2942;
        }

        /* -----------------------------------------------------------
         * 3. PROGRAM CRITERIA BANNER
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-app-criteria-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-left: 4px solid #0F2942 !important;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04);
            padding: 18px 22px;
            margin-bottom: 20px;
        }

        body.sf-fassg-portal .sf-app-criteria-tile {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 10px 14px;
            height: 100%;
        }

        /* -----------------------------------------------------------
         * 4. SELECTION TOOLBAR & BATCH ACTION AREA
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-app-selection-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 16px;
        }

        body.sf-fassg-portal .sf-app-note-text {
            font-size: 13px;
            color: #64748B;
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        body.sf-fassg-portal .sf-app-btn-batch {
            height: 40px;
            background-color: #0F2942 !important;
            border: 1px solid #0F2942 !important;
            color: #FFFFFF !important;
            font-weight: 600;
            font-size: 13.5px;
            padding: 0 18px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.12);
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-btn-batch:hover,
        body.sf-fassg-portal .sf-app-btn-batch:focus {
            background-color: #173B57 !important;
            border-color: #173B57 !important;
            color: #FFFFFF !important;
            box-shadow: 0 3px 8px rgba(15, 41, 66, 0.2);
        }

        body.sf-fassg-portal .sf-app-btn-batch:disabled {
            background-color: #F1F5F9 !important;
            border-color: #E2E8F0 !important;
            color: #94A3B8 !important;
            box-shadow: none !important;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* -----------------------------------------------------------
         * 5. APPLICANTS TABLE & CARD
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-app-table-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            overflow: hidden;
            padding: 22px 24px 12px 24px;
        }

        body.sf-fassg-portal .sf-app-table-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        body.sf-fassg-portal .sf-app-table-title {
            font-family: var(--font-heading, 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            font-size: 16px;
            font-weight: 700;
            color: #0F2942;
            margin: 0;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.sf-fassg-portal .sf-app-table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
        }

        body.sf-fassg-portal .sf-app-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13.5px;
            color: #23384D;
        }

        body.sf-fassg-portal .sf-app-table thead th {
            background-color: #F5F7F9;
            color: #64748B;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px 12px;
            border-bottom: 1px solid #E1E8EC;
            border-top: none;
            white-space: nowrap;
            line-height: 1.4;
            vertical-align: middle;
        }

        body.sf-fassg-portal .sf-app-table thead th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            padding-left: 16px;
        }

        body.sf-fassg-portal .sf-app-table thead th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            padding-right: 16px;
        }

        body.sf-fassg-portal .sf-app-table tbody tr {
            transition: background-color 0.12s ease;
        }

        body.sf-fassg-portal .sf-app-table tbody tr:hover {
            background-color: #F8FAFC;
        }

        body.sf-fassg-portal .sf-app-table tbody td {
            padding: 13px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13px;
            line-height: 1.45;
        }

        body.sf-fassg-portal .sf-app-table tbody td:first-child {
            padding-left: 16px;
        }

        body.sf-fassg-portal .sf-app-table tbody td:last-child {
            padding-right: 16px;
        }

        body.sf-fassg-portal .sf-app-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Specific Table Data Styling */
        body.sf-fassg-portal .sf-app-student-id {
            font-family: var(--font-mono, 'JetBrains Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, monospace);
            font-size: 12.5px;
            font-weight: 600;
            color: #0F2942;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-student-name {
            font-weight: 600;
            color: #0F2942;
            font-size: 13.5px;
            line-height: 1.35;
        }

        body.sf-fassg-portal .sf-app-program-name {
            font-size: 13px;
            color: #23384D;
            line-height: 1.4;
            max-width: 220px;
        }

        body.sf-fassg-portal .sf-app-year-badge {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 6px;
            background-color: #F1F5F9;
            color: #334155;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-gwa-text {
            font-family: var(--font-mono, monospace);
            font-weight: 600;
            font-size: 12.5px;
            color: #0F2942;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-target-program {
            font-size: 13px;
            font-weight: 600;
            color: #0F2942;
            line-height: 1.3;
            max-width: 200px;
        }

        body.sf-fassg-portal .sf-app-sponsor-name {
            font-size: 11.5px;
            color: #64748B;
            margin-top: 2px;
        }

        body.sf-fassg-portal .sf-app-fixed-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
            background-color: #FFF8EA;
            color: #986515;
            border: 1px solid #F6DDA1;
            margin-top: 4px;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-docs-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-btn-view {
            height: 32px;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 7px;
            background-color: #0F2942;
            color: #FFFFFF;
            border: 1px solid #0F2942;
            text-decoration: none !important;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-app-btn-view:hover {
            background-color: #173B57;
            border-color: #173B57;
            color: #FFFFFF;
            box-shadow: 0 2px 5px rgba(15, 41, 66, 0.15);
        }

        /* -----------------------------------------------------------
         * 6. CREATE BATCH MODAL STYLING
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-app-modal-dialog {
            max-width: 740px;
        }

        body.sf-fassg-portal .sf-app-modal-content {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(15, 41, 66, 0.12);
            overflow: hidden;
        }

        body.sf-fassg-portal .sf-app-modal-header {
            background-color: #FFFFFF;
            border-bottom: 1px solid #E1E8EC;
            padding: 18px 24px;
        }

        body.sf-fassg-portal .sf-app-modal-title {
            font-family: var(--font-heading, 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            font-size: 16px;
            font-weight: 700;
            color: #0F2942;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.sf-fassg-portal .sf-app-modal-body {
            padding: 22px 24px;
            max-height: 72vh;
            overflow-y: auto;
        }

        body.sf-fassg-portal .sf-app-modal-footer {
            background-color: #FAFCFE;
            border-top: 1px solid #E1E8EC;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        body.sf-fassg-portal .sf-app-summary-box {
            background-color: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 10px;
            padding: 14px 18px;
            color: #1E40AF;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 18px;
        }

        body.sf-fassg-portal .sf-app-summary-box.alert-danger {
            background-color: #FEF2F2 !important;
            border-color: #FECACA !important;
            color: #DC2626 !important;
        }

        body.sf-fassg-portal .sf-app-preview-card {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }

        body.sf-fassg-portal .sf-app-preview-list {
            max-height: 150px;
            overflow-y: auto;
            margin: 0;
            padding-left: 20px;
            font-size: 12.5px;
            color: #334155;
        }

        body.sf-fassg-portal .sf-app-preview-list li {
            margin-bottom: 4px;
        }

        /* -----------------------------------------------------------
         * 7. REVIEW APPLICATION PAGE SPECIFIC STYLES
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-review-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04);
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        body.sf-fassg-portal .sf-review-card-title {
            font-family: var(--font-heading, 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            font-size: 15px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.sf-fassg-portal .sf-review-stat-tile {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 10px 14px;
            height: 100%;
        }

        body.sf-fassg-portal .sf-review-tile-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748B;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        body.sf-fassg-portal .sf-review-tile-value {
            font-size: 13.5px;
            font-weight: 600;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-review-doc-row {
            padding: 12px 0;
            border-bottom: 1px solid #F1F5F9;
        }

        body.sf-fassg-portal .sf-review-doc-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        body.sf-fassg-portal .sf-review-doc-btn {
            height: 32px;
            padding: 0 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        body.sf-fassg-portal .sf-review-doc-btn-view {
            background-color: #FFFFFF;
            border: 1px solid #D1D9E0;
            color: #0F2942;
        }
        body.sf-fassg-portal .sf-review-doc-btn-view:hover {
            background-color: #F1F5F9;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-review-doc-btn-download {
            background-color: #FFFFFF;
            border: 1px solid #D1D9E0;
            color: #475569;
        }
        body.sf-fassg-portal .sf-review-doc-btn-download:hover {
            background-color: #F1F5F9;
            color: #1E293B;
        }

        body.sf-fassg-portal .sf-review-doc-badge-uploaded {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: #ECFDF3;
            color: #15803D;
            border: 1px solid #A6F4C5;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-review-doc-badge-missing {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-review-decision-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04);
            padding: 22px 24px;
            position: sticky;
            top: 5.5rem;
        }
    }
</style>
