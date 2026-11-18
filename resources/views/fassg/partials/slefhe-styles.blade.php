<style>
    /*
     * SPONSORFLOW FASSG SLE-FHE VERIFICATION — DESKTOP-ONLY UI REFINEMENT
     * Applies to: FASSG SLE-FHE Verification queue and its three interactive modals.
     * Scoped strictly under body.sf-fassg-portal and @media (min-width: 992px).
     * Tablet and mobile layouts (< 992px) are completely unaffected.
     * Design Tokens:
     *   Main background: #F0F4F8 / #F1F5F9
     *   Card & modal background: #FFFFFF
     *   Card border: #E1E8EC
     *   Border radius: 14px (cards), 16px (modals)
     *   Primary navy: #0F2942
     *   Secondary navy: #173B57
     *   Slate text: #23384D
     *   Muted text: #64748B
     *   Pending amber: #B88219 (text #986515, bg #FFF8EA, border #F6DDA1)
     *   Success green: #15803D (bg #ECFDF3, border #A6F4C5)
     *   Rejection red: #DC2626 (bg #FEF2F2, border #FECACA)
     */

    @media screen and (min-width: 992px) {
        /* -----------------------------------------------------------
         * 1. PAGE CANVAS & HEADER STAT PILLS
         * ----------------------------------------------------------- */
        body.sf-fassg-portal > .sf-content {
            background-color: #F0F4F8 !important;
        }

        body.sf-fassg-portal .sf-slefhe-page {
            color: #23384D;
            font-family: var(--font-body, 'Inter', sans-serif);
        }

        /* Status Badges in Top Header Actions */
        body.sf-fassg-portal .sf-slefhe-stat-pill {
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
        }

        body.sf-fassg-portal .sf-slefhe-stat-pending {
            background-color: #FFF8EA !important;
            color: #986515 !important;
            border: 1px solid #F6DDA1 !important;
        }

        body.sf-fassg-portal .sf-slefhe-stat-pending:hover {
            background-color: #FEF3D6 !important;
            color: #784E0B !important;
            border-color: #EDC675 !important;
        }

        body.sf-fassg-portal .sf-slefhe-stat-verified {
            background-color: #ECFDF3 !important;
            color: #15803D !important;
            border: 1px solid #A6F4C5 !important;
        }

        body.sf-fassg-portal .sf-slefhe-stat-verified:hover {
            background-color: #DCFCE7 !important;
            color: #166534 !important;
            border-color: #86EFAC !important;
        }

        /* -----------------------------------------------------------
         * 2. SEARCH AND CAMPUS FILTER TOOLBAR
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-slefhe-filter-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            padding: 16px 20px;
            margin-bottom: 22px;
        }

        body.sf-fassg-portal .sf-slefhe-filter-row {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            width: 100%;
        }

        body.sf-fassg-portal .sf-slefhe-filter-campus {
            width: 220px;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-slefhe-filter-search {
            flex: 1;
            min-width: 0;
        }

        body.sf-fassg-portal .sf-slefhe-filter-reset {
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-slefhe-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        body.sf-fassg-portal .sf-slefhe-filter-card .form-select,
        body.sf-fassg-portal .sf-slefhe-filter-card .form-control {
            height: 40px;
            font-size: 13.5px;
            border-radius: 8px;
            border: 1px solid #D1D9E0;
            color: #23384D;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        body.sf-fassg-portal .sf-slefhe-filter-card .form-select:focus,
        body.sf-fassg-portal .sf-slefhe-filter-card .form-control:focus {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.08);
            outline: none;
        }

        body.sf-fassg-portal .sf-slefhe-search-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        body.sf-fassg-portal .sf-slefhe-search-icon {
            position: absolute;
            left: 14px;
            color: #64748B;
            font-size: 14px;
            pointer-events: none;
            z-index: 2;
        }

        body.sf-fassg-portal .sf-slefhe-search-input {
            padding-left: 38px !important;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reset {
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
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reset i {
            font-size: 14px;
            line-height: 1;
            color: #173B57;
            transition: color 0.15s ease;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reset:hover {
            background-color: #EEF3F7;
            border-color: #AFC3D3;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reset:hover i {
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reset:focus-visible {
            outline: none;
            border-color: #173B57;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2);
        }

        body.sf-fassg-portal .sf-slefhe-btn-reset:active {
            background-color: #E2EAF0;
            border-color: #9DB4C6;
            color: #0F2942;
        }

        /* -----------------------------------------------------------
         * 3. PENDING REQUESTS TABLE & CARD
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-slefhe-table-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            overflow: hidden;
            padding: 20px 22px 6px 22px;
        }

        body.sf-fassg-portal .sf-slefhe-table-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        body.sf-fassg-portal .sf-slefhe-table-title {
            font-family: var(--font-heading, 'Plus Jakarta Sans', sans-serif);
            font-size: 16px;
            font-weight: 700;
            color: #0F2942;
            margin: 0;
            letter-spacing: -0.01em;
        }

        body.sf-fassg-portal .sf-slefhe-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13.5px;
            color: #23384D;
        }

        body.sf-fassg-portal .sf-slefhe-table thead th {
            background-color: #F5F7F9;
            color: #64748B;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px 14px;
            border-bottom: 1px solid #E1E8EC;
            border-top: none;
            white-space: nowrap;
            line-height: 1.4;
        }

        body.sf-fassg-portal .sf-slefhe-table thead th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            padding-left: 16px;
        }

        body.sf-fassg-portal .sf-slefhe-table thead th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            padding-right: 16px;
        }

        body.sf-fassg-portal .sf-slefhe-table tbody tr {
            transition: background-color 0.12s ease;
        }

        body.sf-fassg-portal .sf-slefhe-table tbody tr:hover {
            background-color: #F8FAFC;
        }

        body.sf-fassg-portal .sf-slefhe-table tbody td {
            padding: 14px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13.5px;
            line-height: 1.45;
        }

        body.sf-fassg-portal .sf-slefhe-table tbody td:first-child {
            padding-left: 16px;
        }

        body.sf-fassg-portal .sf-slefhe-table tbody td:last-child {
            padding-right: 16px;
        }

        body.sf-fassg-portal .sf-slefhe-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Specific Table Data Styling */
        body.sf-fassg-portal .sf-slefhe-student-id {
            font-family: var(--font-mono, monospace);
            font-size: 12.5px;
            font-weight: 600;
            color: #526579;
        }

        body.sf-fassg-portal .sf-slefhe-student-name {
            font-weight: 600;
            color: #0F2942;
            font-size: 13.5px;
            line-height: 1.35;
        }

        body.sf-fassg-portal .sf-slefhe-student-timestamp {
            font-size: 11.5px;
            color: #64748B;
            margin-top: 2px;
        }

        body.sf-fassg-portal .sf-slefhe-course-name {
            font-size: 13px;
            color: #23384D;
            line-height: 1.4;
            max-width: 240px;
        }

        body.sf-fassg-portal .sf-slefhe-campus-badge {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
            background-color: rgba(15, 41, 66, 0.07);
            color: #0F2942;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-slefhe-registry-match-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            background-color: rgba(13, 148, 136, 0.12);
            color: #0F766E;
            border: 1px solid rgba(13, 148, 136, 0.25);
            margin-bottom: 4px;
            white-space: nowrap;
        }

        /* Action Buttons Group */
        body.sf-fassg-portal .sf-slefhe-actions-group {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-slefhe-btn-action {
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
            padding: 0 12px;
            border-radius: 8px;
            transition: all 0.12s ease;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-slefhe-btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        body.sf-fassg-portal .sf-slefhe-btn-view {
            background-color: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-slefhe-btn-view:hover {
            background-color: #F1F5F9;
            border-color: #94A3B8;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-slefhe-btn-verify {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #FFFFFF !important;
        }

        body.sf-fassg-portal .sf-slefhe-btn-verify:hover {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #FFFFFF !important;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reject {
            background-color: #FFFFFF;
            border: 1px solid #FECACA;
            color: #DC2626;
        }

        body.sf-fassg-portal .sf-slefhe-btn-reject:hover {
            background-color: #FEF2F2;
            border-color: #F87171;
            color: #B91C1C;
        }

        /* -----------------------------------------------------------
         * 4. UNIFIED MODAL DIALOGS & COMPONENTS
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .modal-content {
            border: 1px solid #E1E8EC !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 25px -5px rgba(15, 41, 66, 0.12), 0 8px 10px -6px rgba(15, 41, 66, 0.08) !important;
            overflow: hidden;
        }

        body.sf-fassg-portal .sf-slefhe-modal-header {
            padding: 22px 26px 16px 26px;
            border-bottom: 1px solid #E6EBF0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            background-color: #FFFFFF;
        }

        body.sf-fassg-portal .sf-slefhe-modal-title {
            font-family: var(--font-heading, 'Plus Jakarta Sans', sans-serif);
            font-size: 17px;
            font-weight: 700;
            color: #0F2942;
            margin: 0 0 3px 0;
            letter-spacing: -0.01em;
        }

        body.sf-fassg-portal .sf-slefhe-modal-subtitle {
            font-size: 12.5px;
            color: #64748B;
            margin: 0;
        }

        body.sf-fassg-portal .sf-slefhe-modal-body {
            padding: 22px 26px;
            max-height: calc(85vh - 140px);
            overflow-y: auto;
            color: #23384D;
        }

        body.sf-fassg-portal .sf-slefhe-modal-footer {
            padding: 16px 26px;
            border-top: 1px solid #E6EBF0;
            background-color: #FAFBFC;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Custom Scrollbar in Modal Body */
        body.sf-fassg-portal .sf-slefhe-modal-body::-webkit-scrollbar {
            width: 6px;
        }

        body.sf-fassg-portal .sf-slefhe-modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        body.sf-fassg-portal .sf-slefhe-modal-body::-webkit-scrollbar-thumb {
            background-color: #CBD5E1;
            border-radius: 3px;
        }

        /* Modal Info Boxes & Section Dividers */
        body.sf-fassg-portal .sf-slefhe-info-panel {
            background-color: #F8FAFC;
            border: 1px solid #E1E8EC;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        body.sf-fassg-portal .sf-slefhe-info-label {
            font-size: 11.5px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }

        body.sf-fassg-portal .sf-slefhe-info-val {
            font-size: 13.5px;
            font-weight: 600;
            color: #0F2942;
            line-height: 1.4;
        }

        body.sf-fassg-portal .sf-slefhe-info-val.mono {
            font-family: var(--font-mono, monospace);
        }

        body.sf-fassg-portal .sf-slefhe-section-heading {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748B;
            margin-bottom: 10px;
        }

        /* Registry Match & Warning Boxes */
        body.sf-fassg-portal .sf-slefhe-registry-box-match {
            background-color: #ECFDF3;
            border: 1px solid #A6F4C5;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        body.sf-fassg-portal .sf-slefhe-registry-match-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 700;
            color: #15803D;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 8px;
        }

        body.sf-fassg-portal .sf-slefhe-registry-box-nomatch {
            background-color: #FFF8EA;
            border: 1px solid #F6DDA1;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        body.sf-fassg-portal .sf-slefhe-registry-nomatch-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #986515;
            letter-spacing: -0.01em;
            margin-bottom: 6px;
        }

        body.sf-fassg-portal .sf-slefhe-registry-nomatch-desc {
            font-size: 12.5px;
            color: #784E0B;
            line-height: 1.5;
            margin: 0;
        }

        /* Manual Override Section */
        body.sf-fassg-portal .sf-slefhe-override-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 12px;
            padding: 16px 18px;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        body.sf-fassg-portal .sf-slefhe-override-card .form-check-label {
            font-size: 13px;
            font-weight: 600;
            color: #0F2942;
            cursor: pointer;
        }

        body.sf-fassg-portal .sf-slefhe-override-card .form-label {
            font-size: 12.5px;
            font-weight: 600;
            color: #23384D;
            margin-top: 12px;
            margin-bottom: 4px;
        }

        body.sf-fassg-portal .sf-slefhe-override-card .form-control {
            font-size: 13px;
            border-radius: 8px;
            border: 1px solid #D1D9E0;
            color: #23384D;
        }

        body.sf-fassg-portal .sf-slefhe-override-card .form-control:focus {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.08);
        }

        body.sf-fassg-portal .sf-slefhe-override-helper {
            font-size: 11.5px;
            color: #64748B;
            margin-top: 6px;
            line-height: 1.45;
        }

        body.sf-fassg-portal .sf-slefhe-prefill-box {
            background-color: #F8FAFC;
            border: 1px solid #E1E8EC;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #526579;
            margin-top: 10px;
        }

        /* Checkbox Styling */
        body.sf-fassg-portal .modal-content .form-check-input {
            cursor: pointer;
            margin-top: 0.2em;
        }

        body.sf-fassg-portal .modal-content .form-check-input:checked {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }

        /* Buttons in Modal Footers */
        body.sf-fassg-portal .sf-slefhe-modal-footer .btn {
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        body.sf-fassg-portal .sf-slefhe-btn-modal-cancel {
            background-color: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #526579;
        }

        body.sf-fassg-portal .sf-slefhe-btn-modal-cancel:hover {
            background-color: #F1F5F9;
            border-color: #94A3B8;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-slefhe-btn-modal-reject {
            background-color: #FFFFFF;
            border: 1px solid #FECACA;
            color: #DC2626;
        }

        body.sf-fassg-portal .sf-slefhe-btn-modal-reject:hover {
            background-color: #FEF2F2;
            border-color: #F87171;
            color: #B91C1C;
        }
    }
</style>
