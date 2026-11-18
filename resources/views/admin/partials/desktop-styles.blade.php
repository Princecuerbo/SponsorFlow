{{-- Admin Portal desktop presentation. Mobile/tablet retain their original styles. --}}
<style>
    @media screen and (min-width: 992px) {
        .sf-admin-portal .admin-original-copy { display: none !important; }
        .sf-admin-portal span.admin-desktop-copy:not([class*="badge"]):not([class*="btn"]):not([class*="icon"]):not([class*="notice"]):not([class*="empty"]) { display: inline !important; }
        .sf-admin-portal div.admin-desktop-copy:not([class*="badge"]):not([class*="btn"]):not([class*="icon"]):not([class*="notice"]):not([class*="empty"]) { display: block !important; }
        .sf-admin-portal i.admin-desktop-copy { display: inline-block !important; }
        .sf-admin-portal .admin-badge.admin-desktop-copy { display: inline-flex !important; }
        .sf-admin-portal .admin-notice.admin-desktop-copy { display: flex !important; }
        .sf-admin-portal .admin-empty.admin-desktop-copy { display: flex !important; }

        /* ─── Page Header ─── */
        .sf-admin-portal .sf-content { padding: 28px 24px; min-width: 0; }
        .sf-admin-portal .sf-content > .d-flex:first-of-type {
            align-items: center !important;
            gap: 16px 24px !important;
        }
        .sf-admin-portal .sf-content > .d-flex:first-of-type > div:first-child {
            flex: 1 1 340px;
            min-width: 0;
        }
        .sf-admin-portal .sf-content > .d-flex:first-of-type > div:last-child {
            flex: 0 1 auto;
            gap: 8px !important;
        }
        .sf-admin-portal .sf-content > .d-flex:first-of-type .sf-eyebrow {
            font-size: 10.5px;
            letter-spacing: .08em;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
        }
        .sf-admin-portal .sf-content > .d-flex:first-of-type h2 {
            font-size: 26px;
            line-height: 1.25;
            color: #0F2942;
            font-weight: 700;
        }
        .sf-admin-portal .sf-content > .d-flex:first-of-type p {
            font-size: 13px;
            line-height: 1.55;
            color: #64748B !important;
            margin-top: 4px;
        }

        /* ─── Metric Cards ─── */
        .sf-admin-portal .admin-desktop-metrics {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .sf-admin-portal .admin-metric {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 44px;
            grid-template-rows: 1fr auto;
            gap: 8px 10px;
            min-height: 128px;
            padding: 18px 20px;
            background: #FFF;
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .sf-admin-portal .admin-metric:hover {
            border-color: #D2E1ED;
            box-shadow: 0 2px 6px rgba(15, 41, 66, .06);
        }
        .sf-admin-portal .admin-metric-label {
            font-size: 10.5px;
            line-height: 1.5;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748B;
            font-weight: 600;
        }
        .sf-admin-portal .admin-metric-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            font-size: 20px;
        }
        .sf-admin-portal .admin-metric-icon.admin-icon-navy {
            background: #EEF3F7;
            color: #173B57;
            border: 1px solid #D6E1E9;
        }
        .sf-admin-portal .admin-metric-icon.admin-icon-emerald {
            background: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }
        .sf-admin-portal .admin-metric-icon.admin-icon-amber {
            background: #FFF7E6;
            color: #B88219;
            border: 1px solid #F6DDA1;
        }
        .sf-admin-portal .admin-metric-icon.admin-icon-sky {
            background: #F0F9FF;
            color: #0284C7;
            border: 1px solid #BAE6FD;
        }
        .sf-admin-portal .admin-metric-value {
            grid-column: 1 / -1;
            color: #0F2942;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
        }
        .sf-admin-portal .admin-metric-empty {
            grid-column: 1 / -1;
            color: #64748B;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.35;
        }

        /* ─── Shared Cards & Panels ─── */
        .sf-admin-portal .admin-card {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
            background: #FFF;
            overflow: hidden;
            min-width: 0;
        }
        .sf-admin-portal .admin-section-header {
            margin-bottom: 16px;
        }
        .sf-admin-portal .admin-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 4px;
        }
        .sf-admin-portal .admin-section-desc {
            font-size: 12.5px;
            color: #64748B;
            line-height: 1.55;
            margin: 0;
        }

        /* ─── Filter Bars & Controls ─── */
        .sf-admin-portal .admin-filter-bar {
            background: #FFF;
            border: 1px solid #E1E8EC;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(15, 41, 66, .025);
        }
        .sf-admin-portal .admin-filter-bar .form-control,
        .sf-admin-portal .admin-filter-bar .form-select,
        .sf-admin-portal .admin-filter-bar .btn:not(.btn-sm) {
            height: 38px;
            font-size: 13px;
            border-radius: 8px;
        }
        .sf-admin-portal .admin-filter-bar .form-control:focus,
        .sf-admin-portal .admin-filter-bar .form-select:focus {
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 0.2rem rgba(15, 41, 66, 0.12) !important;
        }
        .sf-admin-portal .admin-btn-primary {
            background: #0F2942;
            border-color: #0F2942;
            color: #FFF;
            font-size: 13px;
            font-weight: 600;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 8px;
            padding: 8px 16px;
            transition: background-color .15s ease, border-color .15s ease;
        }
        .sf-admin-portal .admin-btn-primary:hover,
        .sf-admin-portal .admin-btn-primary:focus {
            background: #173B57;
            border-color: #173B57;
            color: #FFF;
        }

        /* ─── Standardized Reset Filters Button ─── */
        .sf-admin-portal .admin-btn-reset,
        .sf-admin-portal .sf-btn-reset-filters {
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            padding: 0 16px !important;
            border-radius: 8px !important;
            background-color: #FFFFFF !important;
            border: 1px solid #D5E0E8 !important;
            color: #173B57 !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .sf-admin-portal .admin-btn-reset i,
        .sf-admin-portal .sf-btn-reset-filters i {
            font-size: 14px !important;
            line-height: 1 !important;
            color: #173B57 !important;
            transition: color 0.15s ease !important;
        }
        .sf-admin-portal .admin-btn-reset:hover,
        .sf-admin-portal .sf-btn-reset-filters:hover {
            background-color: #EEF3F7 !important;
            color: #0F2942 !important;
            border-color: #AFC3D3 !important;
        }
        .sf-admin-portal .admin-btn-reset:hover i,
        .sf-admin-portal .sf-btn-reset-filters:hover i {
            color: #0F2942 !important;
        }
        .sf-admin-portal .admin-btn-reset:focus-visible,
        .sf-admin-portal .sf-btn-reset-filters:focus-visible {
            outline: none !important;
            border-color: #173B57 !important;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
        }
        .sf-admin-portal .admin-btn-reset:active,
        .sf-admin-portal .sf-btn-reset-filters:active {
            background-color: #E2EAF0 !important;
            border-color: #9DB4C6 !important;
            color: #0F2942 !important;
        }

        /* ─── Table Styling ─── */
        .sf-admin-portal .admin-card table { width: 100%; margin-bottom: 0; }
        .sf-admin-portal .admin-card th {
            padding: 12px 16px !important;
            font-size: 10.5px;
            line-height: 1.5;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748B;
            background: #F5F7F9;
            border-bottom: 1px solid #E1E8EC;
            white-space: nowrap;
        }
        .sf-admin-portal .admin-card td {
            padding: 14px 16px !important;
            font-size: 13px;
            line-height: 1.5;
            color: #173B57;
            vertical-align: middle;
            border-bottom: 1px solid #EDF2F7;
        }
        .sf-admin-portal .admin-card .table-hover tbody tr:hover {
            background-color: #F8FAFC !important;
        }
        .sf-admin-portal .admin-card .card-footer {
            padding: 12px 16px !important;
            font-size: 12px;
            line-height: 1.5;
            color: #64748B;
            background: #FAFBFC !important;
            border-top: 1px solid #E1E8EC;
        }

        /* ─── Action Buttons in Tables ─── */
        .sf-admin-portal .admin-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            font-size: 14px;
            transition: all .15s ease;
        }
        .sf-admin-portal .admin-action-edit {
            color: #173B57;
            border: 1px solid #CBD5E1;
            background: #FFF;
        }
        .sf-admin-portal .admin-action-edit:hover {
            background: #EEF3F7;
            border-color: #94A3B8;
            color: #0F2942;
        }
        .sf-admin-portal .admin-action-deactivate {
            color: #DC2626;
            border: 1px solid #FECACA;
            background: #FFF;
        }
        .sf-admin-portal .admin-action-deactivate:hover {
            background: #FEF2F2;
            border-color: #F87171;
            color: #B91C1C;
        }
        .sf-admin-portal .admin-action-activate {
            color: #059669;
            border: 1px solid #A7F3D0;
            background: #FFF;
        }
        .sf-admin-portal .admin-action-activate:hover {
            background: #ECFDF5;
            border-color: #34D399;
            color: #047857;
        }
        .sf-admin-portal .admin-action-view {
            color: #173B57;
            border: 1px solid #CBD5E1;
            background: #FFF;
            gap: 4px;
            padding: 4px 10px;
            width: auto;
            height: 28px;
            font-size: 12px;
            font-weight: 500;
        }
        .sf-admin-portal .admin-action-view:hover {
            background: #EEF3F7;
            border-color: #94A3B8;
            color: #0F2942;
        }

        /* ─── Recent System Activity on Dashboard ─── */
        .sf-admin-portal .admin-activity-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 16px;
            border-bottom: 1px solid #EDF2F7;
            transition: background-color .15s ease;
        }
        .sf-admin-portal .admin-activity-item:last-child {
            border-bottom: none;
        }
        .sf-admin-portal .admin-activity-item:hover {
            background-color: #F8FAFC;
        }
        .sf-admin-portal .admin-activity-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            font-size: 16px;
            background: #EEF3F7;
            color: #173B57;
            border: 1px solid #D6E1E9;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .sf-admin-portal .admin-activity-content {
            flex: 1 1 0%;
            min-width: 0;
        }
        .sf-admin-portal .admin-activity-title {
            font-size: 13.5px;
            font-weight: 600;
            color: #0F2942;
            line-height: 1.35;
            margin-bottom: 2px;
        }
        .sf-admin-portal .admin-activity-meta {
            font-size: 12px;
            color: #64748B;
            line-height: 1.45;
        }
        .sf-admin-portal .admin-activity-time {
            font-size: 11.5px;
            color: #64748B;
            white-space: nowrap;
            text-align: right;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* ─── Active Accounts by Role ─── */
        .sf-admin-portal .admin-role-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-bottom: 1px solid #EDF2F7;
            transition: background-color .15s ease;
        }
        .sf-admin-portal .admin-role-row:last-child {
            border-bottom: none;
        }
        .sf-admin-portal .admin-role-row:hover {
            background-color: #F8FAFC;
        }
        .sf-admin-portal .admin-role-label {
            font-size: 13px;
            color: #173B57;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sf-admin-portal .admin-role-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 700;
            color: #173B57;
            background: #EEF3F7;
            border-radius: 6px;
            border: 1px solid #D6E1E9;
            font-variant-numeric: tabular-nums;
        }

        /* ─── Info Alert Notice ─── */
        .sf-admin-portal .admin-info-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px !important;
            padding: 14px 18px !important;
            border: 1px solid #DFE7EE !important;
            border-left: 4px solid #173B57 !important;
            border-radius: 12px !important;
            background: #EEF3F7 !important;
            color: #173B57 !important;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .sf-admin-portal .admin-info-notice > i {
            font-size: 18px;
            color: #173B57;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ─── Modals ─── */
        .sf-admin-portal .modal-dialog {
            max-width: 600px;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .sf-admin-portal .admin-modal-audit-details {
            max-width: 720px !important;
        }
        .sf-admin-portal .modal-content {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(15, 41, 66, 0.15), 0 8px 10px -6px rgba(15, 41, 66, 0.1);
            overflow: hidden;
        }
        .sf-admin-portal .modal-header {
            padding: 18px 24px;
            background: #FFF;
            border-bottom: 1px solid #E1E8EC;
        }
        .sf-admin-portal .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #0F2942;
        }
        .sf-admin-portal .modal-subtitle {
            font-size: 12.5px;
            color: #64748B;
            margin-top: 2px;
            margin-bottom: 0;
        }
        .sf-admin-portal .modal-body {
            padding: 22px 24px;
        }
        .sf-admin-portal .modal-footer {
            padding: 14px 24px;
            background: #F8FAFC;
            border-top: 1px solid #E1E8EC;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        /* ─── Cancel Buttons (RED on Hover) ─── */
        .sf-admin-portal .admin-btn-cancel,
        .sf-admin-portal .modal-footer .admin-btn-cancel {
            height: 38px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            border-radius: 8px !important;
            padding: 0 16px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            background-color: #FFFFFF !important;
            color: #475569 !important;
            border: 1px solid #D5E0E8 !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease !important;
        }
        .sf-admin-portal .admin-btn-cancel i,
        .sf-admin-portal .modal-footer .admin-btn-cancel i {
            color: #64748B !important;
            font-size: 12px !important;
            transition: color 0.18s ease !important;
        }
        .sf-admin-portal .admin-btn-cancel:hover:not(:disabled),
        .sf-admin-portal .modal-footer .admin-btn-cancel:hover:not(:disabled) {
            background-color: #DC3545 !important;
            color: #FFFFFF !important;
            border-color: #DC3545 !important;
        }
        .sf-admin-portal .admin-btn-cancel:hover:not(:disabled) i,
        .sf-admin-portal .modal-footer .admin-btn-cancel:hover:not(:disabled) i {
            color: #FFFFFF !important;
        }
        .sf-admin-portal .admin-btn-cancel:focus-visible,
        .sf-admin-portal .modal-footer .admin-btn-cancel:focus-visible {
            outline: none !important;
            border-color: #DC3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25) !important;
        }
        .sf-admin-portal .admin-btn-cancel:active:not(:disabled),
        .sf-admin-portal .modal-footer .admin-btn-cancel:active:not(:disabled) {
            background-color: #BD2130 !important;
            border-color: #B21F2D !important;
            color: #FFFFFF !important;
        }
        .sf-admin-portal .admin-btn-cancel:disabled,
        .sf-admin-portal .admin-btn-cancel[disabled],
        .sf-admin-portal .modal-footer .admin-btn-cancel:disabled,
        .sf-admin-portal .modal-footer .admin-btn-cancel[disabled] {
            opacity: 0.65 !important;
            cursor: not-allowed !important;
            background-color: #FFFFFF !important;
            color: #94A3B8 !important;
            border-color: #E2E8F0 !important;
            box-shadow: none !important;
        }

        /* ─── Modal Close Button (Neutral Hover - NOT Red) ─── */
        .sf-admin-portal .admin-btn-close-modal,
        .sf-admin-portal .modal-footer .admin-btn-close-modal {
            height: 38px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
            padding: 0 20px !important;
            background-color: #FFFFFF !important;
            color: #173B57 !important;
            border: 1px solid #D5E0E8 !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .sf-admin-portal .admin-btn-close-modal:hover,
        .sf-admin-portal .modal-footer .admin-btn-close-modal:hover {
            background-color: #EEF3F7 !important;
            color: #0F2942 !important;
            border-color: #AFC3D3 !important;
        }
        .sf-admin-portal .admin-btn-close-modal:focus-visible,
        .sf-admin-portal .modal-footer .admin-btn-close-modal:focus-visible {
            outline: none !important;
            border-color: #173B57 !important;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
        }

        /* ─── Audit Log Details Modal ─── */
        .sf-admin-portal .admin-audit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }
        .sf-admin-portal .admin-audit-section {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .sf-admin-portal .admin-audit-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #64748B;
            margin-bottom: 4px;
        }
        .sf-admin-portal .admin-audit-value {
            font-size: 13px;
            color: #0F2942;
            font-weight: 500;
            overflow-wrap: anywhere;
        }
        .sf-admin-portal .admin-audit-codebox {
            background: #0F2537;
            color: #E2E8F0;
            padding: 14px 16px;
            border-radius: 8px;
            font-family: var(--font-mono, 'JetBrains Mono', monospace);
            font-size: 12px;
            line-height: 1.55;
            max-height: 280px;
            overflow-y: auto;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
            scrollbar-width: thin;
            scrollbar-color: #334E68 #0F2537;
        }
        .sf-admin-portal .admin-audit-codebox::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .sf-admin-portal .admin-audit-codebox::-webkit-scrollbar-track {
            background: #0F2537;
            border-radius: 3px;
        }
        .sf-admin-portal .admin-audit-codebox::-webkit-scrollbar-thumb {
            background: #334E68;
            border-radius: 3px;
        }
        .sf-admin-portal .admin-audit-diff-col {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12.5px;
        }
        .sf-admin-portal .admin-audit-diff-before {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }
        .sf-admin-portal .admin-audit-diff-after {
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #047857;
        }

        /* ─── User Accounts: Toolbar Layout ─── */
        .sf-admin-portal .admin-filter-bar:has(.admin-toolbar-add) form.row,
        .sf-admin-portal .admin-users-filter-bar form.row {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 12px !important;
        }
        .sf-admin-portal .admin-filter-bar .admin-toolbar-search {
            flex: 1 1 auto !important;
            min-width: 200px !important;
            width: auto !important;
            max-width: none !important;
            padding: 0 !important;
        }
        .sf-admin-portal .admin-filter-bar .admin-toolbar-role {
            flex: 0 0 170px !important;
            width: 170px !important;
            padding: 0 !important;
        }
        .sf-admin-portal .admin-filter-bar .admin-toolbar-status {
            flex: 0 0 160px !important;
            width: 160px !important;
            padding: 0 !important;
        }
        .sf-admin-portal .admin-filter-bar .admin-toolbar-add {
            flex: 0 0 auto !important;
            width: auto !important;
            padding: 0 !important;
        }
        .sf-admin-portal .admin-filter-bar .admin-toolbar-add .admin-btn-primary {
            min-width: 175px !important;
            max-width: 190px !important;
            width: auto !important;
            white-space: nowrap !important;
        }

        /* ─── Security Audit Logs: Toolbar Layout ─── */
        .sf-admin-portal .admin-audit-filter-bar {
            padding: 16px 20px !important;
            margin-bottom: 20px !important;
            background: #FFFFFF !important;
            border: 1px solid #E1E8EC !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px rgba(15, 41, 66, .025) !important;
        }
        .sf-admin-portal #auditLogFilterForm.admin-audit-toolbar,
        .sf-admin-portal #auditLogFilterForm {
            display: grid !important;
            grid-template-columns: minmax(180px, 2fr) minmax(160px, 1.2fr) minmax(140px, 1fr) minmax(140px, 1fr) 150px !important;
            gap: 12px !important;
            align-items: end !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .sf-admin-portal #auditLogFilterForm > div,
        .sf-admin-portal .admin-audit-toolbar > div {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            flex: none !important;
        }
        .sf-admin-portal #auditLogFilterForm .form-label,
        .sf-admin-portal .admin-audit-toolbar .form-label {
            font-size: 11.5px !important;
            font-weight: 600 !important;
            color: #64748B !important;
            margin-bottom: 6px !important;
            white-space: nowrap !important;
            display: block !important;
            line-height: 1.35 !important;
        }
        .sf-admin-portal #auditLogFilterForm .form-control,
        .sf-admin-portal #auditLogFilterForm .form-select,
        .sf-admin-portal .admin-audit-toolbar .form-control,
        .sf-admin-portal .admin-audit-toolbar .form-select {
            height: 38px !important;
            font-size: 13px !important;
            border-radius: 8px !important;
            border: 1px solid #CBD5E1 !important;
            color: #173B57 !important;
        }
        .sf-admin-portal #auditLogFilterForm input[type="date"].form-control,
        .sf-admin-portal .admin-audit-toolbar input[type="date"].form-control {
            height: 38px !important;
            padding: 6px 10px !important;
            font-size: 13px !important;
            color: #173B57 !important;
        }
        .sf-admin-portal #auditLogFilterForm .admin-audit-col-reset,
        .sf-admin-portal .admin-audit-toolbar .admin-audit-col-reset {
            display: flex !important;
            align-items: flex-end !important;
            justify-content: flex-end !important;
            height: 100% !important;
            width: 150px !important;
        }
        .sf-admin-portal #auditLogFilterForm .admin-btn-reset,
        .sf-admin-portal .admin-audit-toolbar .admin-btn-reset {
            height: 38px !important;
            width: 150px !important;
            max-width: 150px !important;
            min-width: 140px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            padding: 0 16px !important;
            border-radius: 8px !important;
            background-color: #FFFFFF !important;
            border: 1px solid #D5E0E8 !important;
            color: #173B57 !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .sf-admin-portal #auditLogFilterForm .admin-btn-reset:hover,
        .sf-admin-portal .admin-audit-toolbar .admin-btn-reset:hover {
            background-color: #EEF3F7 !important;
            color: #0F2942 !important;
            border-color: #AFC3D3 !important;
        }
        .sf-admin-portal #auditLogFilterForm .admin-btn-reset:focus-visible,
        .sf-admin-portal .admin-audit-toolbar .admin-btn-reset:focus-visible {
            outline: none !important;
            border-color: #173B57 !important;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
        }

        /* ─── User Accounts: Table Column Widths ─── */
        .sf-admin-portal .admin-col-name   { width: 20% !important; }
        .sf-admin-portal .admin-col-email  { width: 26% !important; }
        .sf-admin-portal .admin-col-role   { width: 13% !important; }
        .sf-admin-portal .admin-col-status { width: 12% !important; }
        .sf-admin-portal .admin-col-login  { width: 17% !important; }
        .sf-admin-portal .admin-card th.admin-col-actions,
        .sf-admin-portal .admin-card td.admin-col-actions {
            width: 116px !important;
            min-width: 110px !important;
            max-width: 124px !important;
            padding-right: 20px !important;
            padding-left: 12px !important;
            text-align: right !important;
        }

        /* ─── User Accounts: Row Height ─── */
        .sf-admin-portal .admin-card tbody tr {
            height: 56px;
            min-height: 56px;
        }
        .sf-admin-portal .admin-card tbody td {
            height: 56px;
            vertical-align: middle !important;
        }

        /* ─── User Accounts: Actions Button Group ─── */
        .sf-admin-portal .admin-actions-group {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 8px !important;
            flex-wrap: nowrap !important;
        }
        .sf-admin-portal .admin-actions-group form {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .sf-admin-portal .admin-actions-group .admin-action-btn {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            min-height: 32px !important;
            max-width: 32px !important;
            max-height: 32px !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 6px !important;
        }

        /* ─── Empty State ─── */
        .sf-admin-portal .admin-empty-state {
            padding: 48px 24px;
            text-align: center;
        }
        .sf-admin-portal .admin-empty-icon {
            display: inline-grid;
            place-items: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #EEF3F7;
            color: #173B57;
            font-size: 26px;
            margin-bottom: 14px;
            border: 1px solid #D6E1E9;
        }
        .sf-admin-portal .admin-empty-title {
            font-size: 15px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 4px;
        }
        .sf-admin-portal .admin-empty-desc {
            font-size: 13px;
            color: #64748B;
            max-width: 420px;
            margin: 0 auto;
            line-height: 1.5;
        }
    }
</style>
