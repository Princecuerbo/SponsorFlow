<style>
    /*
     * SPONSORFLOW FASSG VERIFIED SLE-FHE STUDENTS — DESKTOP-ONLY UI REFINEMENT
     * Scope: /fassg/sle-fhe/verified
     * Strictly scoped under body.sf-fassg-portal and @media (min-width: 992px).
     * Tablet and mobile layouts (< 992px) are completely unaffected.
     * Design Tokens:
     *   Canvas background: #F0F4F8
     *   Surface / Cards: #FFFFFF
     *   Card border: #E1E8EC
     *   Border radius: 14px (cards), 8px (controls), 999px (badges/pills)
     *   Primary navy: #0F2942
     *   Secondary navy: #173B57
     *   Body slate: #23384D
     *   Muted gray: #64748B
     *   Verified green: #15803D (bg: #ECFDF3, border: #A6F4C5)
     *   Pending amber: #986515 (bg: #FFF8EA, border: #F6DDA1)
     */

    @media screen and (min-width: 992px) {
        /* -----------------------------------------------------------
         * 1. PAGE CANVAS & HEADER STAT PILLS
         * ----------------------------------------------------------- */
        body.sf-fassg-portal > .sf-content {
            background-color: #F0F4F8 !important;
        }

        body.sf-fassg-portal .sf-verified-page {
            color: #23384D;
            font-family: var(--font-body, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
        }

        /* Status Badges in Top Header Actions */
        body.sf-fassg-portal .sf-verified-stat-pill {
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

        body.sf-fassg-portal .sf-verified-stat-verified {
            background-color: #ECFDF3 !important;
            color: #15803D !important;
            border: 1px solid #A6F4C5 !important;
        }

        body.sf-fassg-portal .sf-verified-stat-verified:hover {
            background-color: #DCFCE7 !important;
            color: #166534 !important;
            border-color: #86EFAC !important;
            box-shadow: 0 2px 4px rgba(21, 128, 61, 0.12);
        }

        body.sf-fassg-portal .sf-verified-stat-pending {
            background-color: #FFF8EA !important;
            color: #986515 !important;
            border: 1px solid #F6DDA1 !important;
        }

        body.sf-fassg-portal .sf-verified-stat-pending:hover {
            background-color: #FEF3D6 !important;
            color: #784E0B !important;
            border-color: #EDC675 !important;
            box-shadow: 0 2px 4px rgba(184, 130, 25, 0.12);
        }

        /* -----------------------------------------------------------
         * 2. SEARCH AND CAMPUS FILTER TOOLBAR
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-verified-filter-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            padding: 16px 20px;
            margin-bottom: 22px;
        }

        body.sf-fassg-portal .sf-verified-filter-row {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            width: 100%;
        }

        body.sf-fassg-portal .sf-verified-filter-campus {
            width: 220px;
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-verified-filter-search {
            flex: 1;
            min-width: 0;
        }

        body.sf-fassg-portal .sf-verified-filter-reset {
            flex-shrink: 0;
        }

        body.sf-fassg-portal .sf-verified-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        body.sf-fassg-portal .sf-verified-filter-card .form-select,
        body.sf-fassg-portal .sf-verified-filter-card .form-control {
            height: 40px;
            font-size: 13.5px;
            border-radius: 8px;
            border: 1px solid #D1D9E0;
            color: #23384D;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        body.sf-fassg-portal .sf-verified-filter-card .form-select:focus,
        body.sf-fassg-portal .sf-verified-filter-card .form-control:focus {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.08);
            outline: none;
        }

        body.sf-fassg-portal .sf-verified-search-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        body.sf-fassg-portal .sf-verified-search-icon {
            position: absolute;
            left: 14px;
            color: #64748B;
            font-size: 14px;
            pointer-events: none;
            z-index: 2;
        }

        body.sf-fassg-portal .sf-verified-search-input {
            padding-left: 38px !important;
        }

        body.sf-fassg-portal .sf-verified-btn-reset {
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

        body.sf-fassg-portal .sf-verified-btn-reset i {
            font-size: 14px;
            line-height: 1;
            color: #173B57;
            transition: color 0.15s ease;
        }

        body.sf-fassg-portal .sf-verified-btn-reset:hover {
            background-color: #EEF3F7;
            border-color: #AFC3D3;
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-verified-btn-reset:hover i {
            color: #0F2942;
        }

        body.sf-fassg-portal .sf-verified-btn-reset:focus-visible {
            outline: none;
            border-color: #173B57;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2);
        }

        body.sf-fassg-portal .sf-verified-btn-reset:active {
            background-color: #E2EAF0;
            border-color: #9DB4C6;
            color: #0F2942;
        }

        /* -----------------------------------------------------------
         * 3. VERIFIED STUDENTS TABLE CARD & HEADING
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-verified-table-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            overflow: hidden;
            padding: 22px 24px 12px 24px;
        }

        body.sf-fassg-portal .sf-verified-table-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        body.sf-fassg-portal .sf-verified-table-title {
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

        body.sf-fassg-portal .sf-verified-table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
        }

        /* -----------------------------------------------------------
         * 4. TABLE STYLING & COLUMN SIZING
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-verified-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13.5px;
            color: #23384D;
        }

        body.sf-fassg-portal .sf-verified-table thead th {
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
            vertical-align: middle;
        }

        body.sf-fassg-portal .sf-verified-table thead th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            padding-left: 18px;
        }

        body.sf-fassg-portal .sf-verified-table thead th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            padding-right: 18px;
        }

        /* Balanced Column Distribution */
        body.sf-fassg-portal .sf-col-student-id {
            width: 130px;
            min-width: 120px;
        }

        body.sf-fassg-portal .sf-col-student-name {
            width: 200px;
            min-width: 180px;
        }

        body.sf-fassg-portal .sf-col-academic-program {
            width: 230px;
            min-width: 200px;
        }

        body.sf-fassg-portal .sf-col-year-level {
            width: 100px;
            min-width: 90px;
        }

        body.sf-fassg-portal .sf-col-campus {
            width: 160px;
            min-width: 140px;
        }

        body.sf-fassg-portal .sf-col-address {
            min-width: 220px;
            max-width: 320px;
        }

        body.sf-fassg-portal .sf-col-verified-at {
            width: 165px;
            min-width: 155px;
        }

        body.sf-fassg-portal .sf-col-status {
            width: 155px;
            min-width: 145px;
        }

        body.sf-fassg-portal .sf-verified-table tbody tr {
            transition: background-color 0.12s ease;
        }

        body.sf-fassg-portal .sf-verified-table tbody tr:hover {
            background-color: #F8FAFC;
        }

        body.sf-fassg-portal .sf-verified-table tbody td {
            padding: 14px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13.5px;
            line-height: 1.45;
        }

        body.sf-fassg-portal .sf-verified-table tbody td:first-child {
            padding-left: 18px;
        }

        body.sf-fassg-portal .sf-verified-table tbody td:last-child {
            padding-right: 18px;
        }

        body.sf-fassg-portal .sf-verified-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* -----------------------------------------------------------
         * 5. ROW CELLS TYPOGRAPHY & FORMATTING
         * ----------------------------------------------------------- */
        /* Student ID */
        body.sf-fassg-portal .sf-verified-student-id {
            font-family: var(--font-mono, 'JetBrains Mono', 'SFMono-Regular', Menlo, Monaco, Consolas, monospace);
            font-size: 12.5px;
            font-weight: 600;
            color: #0F2942;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* Student Name */
        body.sf-fassg-portal .sf-verified-student-name {
            font-weight: 600;
            color: #0F2942;
            font-size: 13.5px;
            line-height: 1.4;
            word-break: normal;
        }

        /* Academic Program */
        body.sf-fassg-portal .sf-verified-program-name {
            font-size: 13px;
            color: #23384D;
            line-height: 1.42;
            word-break: normal;
        }

        /* Year Level Badge */
        body.sf-fassg-portal .sf-verified-year-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 500;
            padding: 3px 9px;
            border-radius: 6px;
            background-color: #F1F5F9;
            color: #334155;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-verified-year-na {
            font-size: 12px;
            color: #94A3B8;
            font-style: italic;
        }

        /* Campus Badge */
        body.sf-fassg-portal .sf-verified-campus-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
            background-color: rgba(15, 41, 66, 0.07);
            color: #0F2942;
            line-height: 1.35;
        }

        /* Verified Residential Address */
        body.sf-fassg-portal .sf-verified-address-text {
            font-size: 13px;
            color: #334155;
            line-height: 1.45;
            word-break: break-word;
        }

        /* Verified At Date & Verifier Officer */
        body.sf-fassg-portal .sf-verified-date {
            font-size: 12.5px;
            font-weight: 600;
            color: #0F2942;
            line-height: 1.35;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-verified-by {
            font-size: 11.5px;
            color: #64748B;
            margin-top: 2px;
            line-height: 1.3;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sf-verified-by-label {
            color: #94A3B8;
        }

        /* Status Badge */
        body.sf-fassg-portal .sf-verified-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background-color: #ECFDF3;
            color: #15803D;
            border: 1px solid #A6F4C5;
            white-space: nowrap;
            line-height: 1.3;
        }

        body.sf-fassg-portal .sf-verified-status-badge i {
            font-size: 12px;
            color: #15803D;
        }

        /* -----------------------------------------------------------
         * 6. EMPTY STATE CARD
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sf-verified-empty-state {
            padding: 48px 24px;
            text-align: center;
        }

        body.sf-fassg-portal .sf-verified-empty-icon {
            font-size: 44px;
            color: #94A3B8;
            margin-bottom: 14px;
        }

        body.sf-fassg-portal .sf-verified-empty-title {
            font-size: 16px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 6px;
        }

        body.sf-fassg-portal .sf-verified-empty-desc {
            font-size: 13.5px;
            color: #64748B;
            max-width: 420px;
            margin: 0 auto;
        }
    }
</style>
