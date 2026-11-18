<style>
    /*
     * SPONSORFLOW FASSG PROGRAMS — DESKTOP-ONLY UI REFINEMENT
     * Applies to: Programs Listing, Create Program, and Update Program.
     * Scoped strictly under body.sf-fassg-portal and @media (min-width: 992px).
     * Mobile/tablet layouts (< 992px) are completely unaffected.
     * Design Tokens:
     *   Page background: #F1F5F9
     *   Card background: #FFFFFF
     *   Card border: #E1E8EC
     *   Border radius: 14px
     *   Primary navy: #0F2942
     *   Accent navy: #173B57
     *   Text slate: #23384D
     *   Muted text: #64748B
     */

    /* Active Filter Pill Accent (Fallback for global) */
    .sf-filter-pill-active {
        background-color: #0F2942 !important;
        border-color: #0F2942 !important;
        color: #FFFFFF !important;
    }

    /* Checkbox Active Accent */
    .form-check-input:checked {
        background-color: #0F2942 !important;
        border-color: #0F2942 !important;
    }

    @media screen and (min-width: 992px) {
        /* -----------------------------------------------------------
         * 1. PAGE CONTAINER & SPACING
         * ----------------------------------------------------------- */
        body.sf-fassg-portal > .sf-content {
            background-color: #F1F5F9 !important;
        }

        body.sf-fassg-portal .sf-programs-page {
            color: #23384D;
            font-family: var(--font-body, 'Inter', sans-serif);
        }

        /* -----------------------------------------------------------
         * 2. TOOLBAR & FILTER PILLS
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sfp-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        body.sf-fassg-portal .sfp-filter-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 41, 66, 0.03);
        }

        body.sf-fassg-portal .sfp-filter-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 7px;
            border: none;
            background: transparent;
            color: #64748B;
            text-decoration: none;
            line-height: 1.4;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sfp-filter-pill:hover {
            color: #0F2942;
            background-color: #F1F5F9;
        }

        body.sf-fassg-portal .sfp-filter-pill.active,
        body.sf-fassg-portal .sfp-filter-pill-active {
            background-color: #0F2942 !important;
            color: #FFFFFF !important;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.15);
        }

        body.sf-fassg-portal .sfp-btn-create {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13.5px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 9px;
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #FFFFFF !important;
            box-shadow: 0 1px 2px rgba(15, 41, 66, 0.08);
            transition: all 0.15s ease;
        }

        body.sf-fassg-portal .sfp-btn-create:hover {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #FFFFFF !important;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(15, 41, 66, 0.12);
        }

        /* -----------------------------------------------------------
         * 3. PROGRAMS TABLE & CARD
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sfp-table-card {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            overflow: hidden;
        }

        body.sf-fassg-portal .sfp-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13.5px;
            color: #23384D;
        }

        body.sf-fassg-portal .sfp-table thead th {
            background-color: #F8FAFC;
            color: #64748B;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 13px 14px;
            border-bottom: 1px solid #E1E8EC;
            border-top: none;
            white-space: nowrap;
            line-height: 1.4;
        }

        body.sf-fassg-portal .sfp-table thead th:first-child {
            padding-left: 22px;
        }

        body.sf-fassg-portal .sfp-table thead th:last-child {
            padding-right: 22px;
        }

        body.sf-fassg-portal .sfp-table tbody tr {
            transition: background-color 0.12s ease;
        }

        body.sf-fassg-portal .sfp-table tbody tr:hover {
            background-color: #F8FAFC;
        }

        body.sf-fassg-portal .sfp-table tbody td {
            padding: 14px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13.5px;
            line-height: 1.45;
        }

        body.sf-fassg-portal .sfp-table tbody td:first-child {
            padding-left: 22px;
        }

        body.sf-fassg-portal .sfp-table tbody td:last-child {
            padding-right: 22px;
        }

        body.sf-fassg-portal .sfp-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Specific Table Columns */
        body.sf-fassg-portal .sfp-col-program {
            min-width: 200px;
        }

        body.sf-fassg-portal .sfp-program-name {
            font-weight: 600;
            color: #0F2942;
            font-size: 13.5px;
            line-height: 1.35;
        }

        body.sf-fassg-portal .sfp-relative-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 7px;
            border-radius: 5px;
            background-color: rgba(15, 41, 66, 0.06);
            color: #0F2942;
            border: 1px solid rgba(15, 41, 66, 0.1);
            line-height: 1.4;
        }

        body.sf-fassg-portal .sfp-sponsor-name {
            color: #526579;
            font-size: 13px;
        }

        body.sf-fassg-portal .sfp-category-badge {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
            background-color: rgba(15, 41, 66, 0.07);
            color: #0F2942;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sfp-slots-avail {
            font-weight: 700;
            color: #0F2942;
            font-variant-numeric: tabular-nums;
        }

        body.sf-fassg-portal .sfp-slots-slash {
            color: #CBD5E1;
            margin: 0 2px;
        }

        body.sf-fassg-portal .sfp-slots-total {
            color: #94A3B8;
            font-variant-numeric: tabular-nums;
        }

        body.sf-fassg-portal .sfp-gpa-val {
            font-weight: 500;
            color: #23384D;
            font-variant-numeric: tabular-nums;
        }

        body.sf-fassg-portal .sfp-gpa-empty {
            color: #94A3B8;
        }

        body.sf-fassg-portal .sfp-location-val {
            color: #23384D;
            font-weight: 500;
        }

        body.sf-fassg-portal .sfp-location-empty {
            color: #94A3B8;
            font-weight: 400;
        }

        /* Action Buttons */
        body.sf-fassg-portal .sfp-actions-group {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            white-space: nowrap;
        }

        body.sf-fassg-portal .sfp-action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 13.5px;
            transition: all 0.12s ease;
        }

        body.sf-fassg-portal .sfp-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        body.sf-fassg-portal .sfp-action-btn-edit {
            border-color: #CBD5E1;
            color: #173B57;
            background-color: #FFFFFF;
        }

        body.sf-fassg-portal .sfp-action-btn-edit:hover {
            background-color: #173B57;
            border-color: #173B57;
            color: #FFFFFF;
        }

        /* Empty State */
        body.sf-fassg-portal .sfp-empty-box {
            text-align: center;
            padding: 56px 24px;
            color: #64748B;
        }

        body.sf-fassg-portal .sfp-empty-box i {
            font-size: 42px;
            color: #CBD5E1;
            margin-bottom: 12px;
            display: block;
        }

        body.sf-fassg-portal .sfp-empty-title {
            font-size: 15px;
            font-weight: 600;
            color: #0F2942;
            margin-bottom: 4px;
        }

        body.sf-fassg-portal .sfp-empty-text {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 18px;
        }

        /* -----------------------------------------------------------
         * 4. CREATE / UPDATE PROGRAM FORM
         * ----------------------------------------------------------- */
        body.sf-fassg-portal .sfp-form-container {
            max-width: 960px;
            margin-left: auto;
            margin-right: auto;
        }

        body.sf-fassg-portal .sfp-card-form {
            background-color: #FFFFFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
            overflow: hidden;
        }

        body.sf-fassg-portal .sfp-form-body {
            padding: 32px 36px;
        }

        body.sf-fassg-portal .sfp-section-title {
            font-family: var(--font-heading, 'Plus Jakarta Sans', sans-serif);
            font-size: 15px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }

        body.sf-fassg-portal .sfp-section-desc {
            font-size: 12.5px;
            color: #64748B;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        body.sf-fassg-portal .sfp-divider {
            height: 1px;
            background-color: #E6EBF0;
            border: none;
            margin: 28px 0;
        }

        /* Form Controls & Labels */
        body.sf-fassg-portal .sfp-card-form .form-label {
            font-size: 12.5px;
            font-weight: 600;
            color: #23384D;
            margin-bottom: 6px;
        }

        body.sf-fassg-portal .sfp-card-form .form-label .text-danger {
            font-weight: 400;
        }

        body.sf-fassg-portal .sfp-card-form .form-control,
        body.sf-fassg-portal .sfp-card-form .form-select {
            font-size: 13.5px;
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #D1D9E0;
            color: #23384D;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        body.sf-fassg-portal .sfp-card-form .form-control:focus,
        body.sf-fassg-portal .sfp-card-form .form-select:focus {
            border-color: #0F2942;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.08);
            outline: none;
        }

        body.sf-fassg-portal .sfp-card-form .form-control[readonly] {
            background-color: #F8FAFC;
            color: #64748B;
            border-color: #E1E8EC;
        }

        body.sf-fassg-portal .sfp-card-form small.text-muted,
        body.sf-fassg-portal .sfp-card-form .form-text {
            font-size: 11.5px;
            color: #64748B !important;
            line-height: 1.45;
            margin-top: 5px;
            display: block;
        }

        /* Checkbox Groups */
        body.sf-fassg-portal .sfp-checkbox-group {
            margin-top: 6px;
        }

        body.sf-fassg-portal .sfp-checkbox-group-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 3px;
        }

        body.sf-fassg-portal .sfp-checkbox-group-desc {
            font-size: 12px;
            color: #64748B;
            margin-bottom: 12px;
        }

        body.sf-fassg-portal .sfp-checkbox-group .form-check-label {
            font-size: 13px;
            color: #23384D;
            cursor: pointer;
            line-height: 1.4;
        }

        body.sf-fassg-portal .sfp-checkbox-group .form-check-input {
            margin-top: 0.18em;
            cursor: pointer;
        }

        /* Academic Programs Box */
        body.sf-fassg-portal .sfp-academic-box {
            max-height: 240px;
            overflow-y: auto;
            border: 1px solid #E1E8EC;
            border-radius: 10px;
            padding: 14px 18px;
            background-color: #FAFBFC;
        }

        body.sf-fassg-portal .sfp-academic-box::-webkit-scrollbar {
            width: 6px;
        }

        body.sf-fassg-portal .sfp-academic-box::-webkit-scrollbar-track {
            background: transparent;
        }

        body.sf-fassg-portal .sfp-academic-box::-webkit-scrollbar-thumb {
            background-color: #CBD5E1;
            border-radius: 3px;
        }

        body.sf-fassg-portal .sfp-btn-bulk {
            font-size: 12px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 6px;
            border: 1px solid #CBD5E1;
            color: #526579;
            background: #FFFFFF;
            transition: all 0.12s ease;
        }

        body.sf-fassg-portal .sfp-btn-bulk:hover {
            background-color: #F1F5F9;
            color: #0F2942;
            border-color: #94A3B8;
        }

        /* Form Footer */
        body.sf-fassg-portal .sfp-form-footer {
            background-color: #FAFBFC;
            border-top: 1px solid #E6EBF0;
            padding: 18px 36px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        body.sf-fassg-portal .sfp-form-footer .btn {
            font-size: 13.5px;
            padding: 9px 22px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Advisory & Alert Styling */
        body.sf-fassg-portal .sfp-alert-advisory {
            background-color: #FFF7E6;
            border: 1px solid #F6DDA1;
            color: #92610E;
            border-radius: 8px;
            font-size: 12.5px;
            padding: 10px 14px;
        }

        /* Back Button on Edit Page */
        body.sf-fassg-portal .sfp-back-btn {
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 500;
            border-color: #CBD5E1;
            color: #526579;
            background: #FFFFFF;
            transition: all 0.15s ease;
        }

        body.sf-fassg-portal .sfp-back-btn:hover {
            color: #0F2942;
            border-color: #0F2942;
            background-color: #F1F5F9;
        }
    }

    /* Extra Wide Desktop (1400px+) Enhancements */
    @media screen and (min-width: 1400px) {
        body.sf-fassg-portal .sfp-form-body {
            padding: 36px 42px;
        }

        body.sf-fassg-portal .sfp-form-footer {
            padding: 20px 42px;
        }

        body.sf-fassg-portal .sfp-table thead th:first-child,
        body.sf-fassg-portal .sfp-table tbody td:first-child {
            padding-left: 26px;
        }

        body.sf-fassg-portal .sfp-table thead th:last-child,
        body.sf-fassg-portal .sfp-table tbody td:last-child {
            padding-right: 26px;
        }
    }
</style>
