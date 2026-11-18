{{-- Sponsor Portal desktop presentation. Mobile/tablet retain their original styles. --}}
<style>
    @media screen and (min-width: 992px) {
        .sf-sponsor-portal .sponsor-original-copy { display: none !important; }
        .sf-sponsor-portal span.sponsor-desktop-copy:not([class*="badge"]):not([class*="count"]):not([class*="icon"]):not([class*="notice"]):not([class*="empty"]) { display: inline !important; }
        .sf-sponsor-portal div.sponsor-desktop-copy:not([class*="badge"]):not([class*="count"]):not([class*="icon"]):not([class*="notice"]):not([class*="empty"]) { display: block !important; }
        .sf-sponsor-portal i.sponsor-desktop-copy { display: inline-block !important; }
        .sf-sponsor-portal .sponsor-workflow-badge.sponsor-desktop-copy { display: inline-flex !important; }
        .sf-sponsor-portal .sponsor-step-icon.sponsor-desktop-copy { display: flex !important; }
        .sf-sponsor-portal .sponsor-pending-count.sponsor-desktop-copy { display: inline-flex !important; }
        .sf-sponsor-portal .sponsor-record-count.sponsor-desktop-copy { display: inline-flex !important; }
        .sf-sponsor-portal .sponsor-info-notice.sponsor-desktop-copy { display: flex !important; }
        .sf-sponsor-portal .sponsor-empty.sponsor-desktop-copy { display: flex !important; }

        /* ─── Page Header ─── */
        .sf-sponsor-portal .sf-content { padding: 28px 24px; min-width: 0; }
        .sf-sponsor-portal .sf-content > .d-flex:first-of-type {
            align-items: center !important;
            gap: 16px 24px !important;
        }
        .sf-sponsor-portal .sf-content > .d-flex:first-of-type > div:first-child {
            flex: 1 1 340px;
            min-width: 0;
        }
        .sf-sponsor-portal .sf-content > .d-flex:first-of-type > div:last-child {
            flex: 0 1 auto;
            gap: 8px !important;
        }
        .sf-sponsor-portal .sf-content > .d-flex:first-of-type .sf-eyebrow {
            font-size: 10.5px;
            letter-spacing: .08em;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
        }
        .sf-sponsor-portal .sf-content > .d-flex:first-of-type h2 {
            font-size: 26px;
            line-height: 1.25;
            color: #0F2942;
            font-weight: 700;
        }
        .sf-sponsor-portal .sf-content > .d-flex:first-of-type p {
            font-size: 13px;
            line-height: 1.55;
            color: #64748B !important;
            margin-top: 4px;
        }

        /* ─── Metric Cards ─── */
        .sf-sponsor-portal .sponsor-desktop-metrics {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .sf-sponsor-portal .sponsor-metric {
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
        .sf-sponsor-portal .sponsor-metric:hover {
            border-color: #D2E1ED;
            box-shadow: 0 2px 6px rgba(15, 41, 66, .06);
        }
        .sf-sponsor-portal .sponsor-metric-label {
            font-size: 10.5px;
            line-height: 1.5;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748B;
            font-weight: 600;
        }
        .sf-sponsor-portal .sponsor-metric-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            font-size: 20px;
        }
        .sf-sponsor-portal .sponsor-metric-icon.sponsor-icon-navy {
            background: #EEF3F7;
            color: #173B57;
        }
        .sf-sponsor-portal .sponsor-metric-icon.sponsor-icon-amber {
            background: #FFF7E6;
            color: #B88219;
            border: 1px solid #F6DDA1;
        }
        .sf-sponsor-portal .sponsor-metric-value {
            grid-column: 1 / -1;
            color: #0F2942;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
        }

        /* ─── Workflow Panel ─── */
        .sf-sponsor-portal .sponsor-workflow-card {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
            background: #FFF;
        }
        .sf-sponsor-portal .sponsor-workflow-card .card-body {
            padding: 24px !important;
        }
        .sf-sponsor-portal .sponsor-workflow-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .sf-sponsor-portal .sponsor-workflow-title {
            color: #0F2942;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px !important;
        }
        .sf-sponsor-portal .sponsor-workflow-subtitle {
            font-size: 12.5px;
            color: #64748B;
            margin: 0;
        }
        .sf-sponsor-portal .sponsor-workflow-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            background: #EEF3F7;
            color: #173B57;
            border: 1px solid #D6E1E9;
        }
        .sf-sponsor-portal .sponsor-workflow-steps {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 16px !important;
            margin: 0 !important;
        }
        @media screen and (min-width: 1200px) {
            .sf-sponsor-portal .sponsor-workflow-steps {
                gap: 20px !important;
            }
        }
        .sf-sponsor-portal .sponsor-workflow-steps > [class*="col-"],
        .sf-sponsor-portal .sponsor-workflow-col {
            width: 100% !important;
            max-width: 100% !important;
            flex: 1 1 0% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-top: 0 !important;
            min-width: 0 !important;
        }
        .sf-sponsor-portal .sponsor-workflow-step {
            display: flex !important;
            gap: 12px !important;
            align-items: flex-start !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        .sf-sponsor-portal .sponsor-step-icon {
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            border-radius: 10px;
            background: #EEF3F7;
            color: #173B57;
            font-size: 18px;
            border: 1px solid #D6E1E9;
            flex-shrink: 0 !important;
        }
        .sf-sponsor-portal .sponsor-step-content {
            flex: 1 1 0% !important;
            min-width: 0 !important;
        }
        .sf-sponsor-portal .sponsor-step-number {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 10px;
            background: #0F2942;
            color: #FFF;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
        }
        .sf-sponsor-portal .sponsor-step-title {
            font-size: 13.5px !important;
            font-weight: 600 !important;
            color: #0F2942 !important;
            line-height: 1.35 !important;
            margin-bottom: 4px !important;
        }
        .sf-sponsor-portal .sponsor-step-desc {
            font-size: 12.5px !important;
            line-height: 1.5 !important;
            color: #64748B !important;
            margin: 0 !important;
        }

        /* ─── Shared Card & Table ─── */
        .sf-sponsor-portal .sponsor-card {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
            overflow: hidden;
            min-width: 0;
        }
        .sf-sponsor-portal .sponsor-section-header {
            margin-bottom: 16px;
        }
        .sf-sponsor-portal .sponsor-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #0F2942;
            margin-bottom: 4px;
        }
        .sf-sponsor-portal .sponsor-section-desc {
            font-size: 12.5px;
            color: #64748B;
            line-height: 1.55;
            margin: 0;
        }
        .sf-sponsor-portal .sponsor-card table { width: 100%; }
        .sf-sponsor-portal .sponsor-card th {
            padding: 12px 16px !important;
            font-size: 10.5px;
            line-height: 1.5;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748B;
            background: #F5F7F9;
            white-space: nowrap;
        }
        .sf-sponsor-portal .sponsor-card td:not(.sponsor-empty-cell) {
            padding: 14px 16px !important;
            font-size: 12.5px;
            line-height: 1.55;
            color: #173B57;
            vertical-align: middle;
        }
        .sf-sponsor-portal .sponsor-card .small {
            font-size: 11px;
            margin-top: 3px;
        }
        .sf-sponsor-portal .sponsor-card .table-hover { --bs-table-hover-bg: #F3F7FA; }
        .sf-sponsor-portal .sponsor-card .card-footer {
            padding: 12px 16px !important;
            font-size: 11.5px;
            line-height: 1.55;
            color: #64748B;
            background: #FAFBFC !important;
            border-top: 1px solid #E8EDF2 !important;
        }
        .sf-sponsor-portal .sponsor-card .card-footer strong {
            font-variant-numeric: tabular-nums;
        }
        .sf-sponsor-portal .sponsor-card .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .sf-sponsor-portal .sponsor-card .btn-sf-navy {
            background: #0F2942;
            border-color: #0F2942;
            color: #FFF;
        }
        .sf-sponsor-portal .sponsor-card .btn-sf-navy:hover {
            background: #173B57;
            border-color: #173B57;
        }

        /* ─── Buttons ─── */
        .sf-sponsor-portal .sponsor-btn-primary {
            min-height: 38px;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 20px;
            white-space: nowrap;
            background: #0F2942;
            border-color: #0F2942;
            color: #FFF;
        }
        .sf-sponsor-portal .sponsor-btn-primary:hover {
            background: #173B57;
            border-color: #173B57;
        }
        .sf-sponsor-portal .sponsor-btn-outline {
            min-height: 38px;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 20px;
            white-space: nowrap;
            background: #FFF;
            border-color: #9AAEBE;
            color: #0F2942;
        }
        .sf-sponsor-portal .sponsor-btn-outline:hover {
            background: #EEF3F7;
        }

        /* ─── Record Count Pill ─── */
        .sf-sponsor-portal .sponsor-record-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            min-height: 38px;
            background: #EEF3F7;
            border: 1px solid #D6E1E9;
            border-radius: 8px;
            white-space: nowrap;
            color: #173B57;
            font-size: 12px;
            font-weight: 500;
        }
        .sf-sponsor-portal .sponsor-record-count strong {
            font-size: 13.5px;
            font-weight: 700;
            color: #0F2942;
            font-variant-numeric: tabular-nums;
        }

        /* ─── Amber / Pending Count Pill ─── */
        .sf-sponsor-portal .sponsor-pending-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            min-height: 38px;
            background: #FFF7E6;
            border: 1px solid #F6DDA1;
            border-radius: 8px;
            white-space: nowrap;
            color: #7B5B10;
            font-size: 12px;
            font-weight: 500;
        }
        .sf-sponsor-portal .sponsor-pending-count strong {
            font-size: 13.5px;
            font-weight: 700;
            color: #7B5B10;
            font-variant-numeric: tabular-nums;
        }

        /* ─── Filter Card ─── */
        .sf-sponsor-portal .sponsor-filters {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
        }
        .sf-sponsor-portal .sponsor-filters .card-body {
            padding: 16px !important;
        }
        .sf-sponsor-portal .sponsor-filters form {
            display: flex;
            align-items: end;
            gap: 12px;
            margin: 0;
        }
        .sf-sponsor-portal .sponsor-filters form > div {
            min-width: 0;
        }
        .sf-sponsor-portal .sponsor-filter-label {
            display: block !important;
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            margin-bottom: 6px;
        }
        .sf-sponsor-portal .sponsor-filters :is(.form-select, .form-control) {
            height: 38px;
            border-color: #D6E1E9;
            border-radius: 8px;
            font-size: 13px;
            color: #173B57;
        }
        .sf-sponsor-portal .sponsor-filters .btn,
        .sf-sponsor-portal .sponsor-btn-reset {
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
        .sf-sponsor-portal .sponsor-filters .btn i,
        .sf-sponsor-portal .sponsor-btn-reset i {
            font-size: 14px !important;
            line-height: 1 !important;
            color: #173B57 !important;
            transition: color 0.15s ease !important;
        }
        .sf-sponsor-portal .sponsor-filters .btn:hover,
        .sf-sponsor-portal .sponsor-btn-reset:hover {
            background-color: #EEF3F7 !important;
            color: #0F2942 !important;
            border-color: #AFC3D3 !important;
        }
        .sf-sponsor-portal .sponsor-filters .btn:hover i,
        .sf-sponsor-portal .sponsor-btn-reset:hover i {
            color: #0F2942 !important;
        }
        .sf-sponsor-portal .sponsor-filters .btn:focus-visible,
        .sf-sponsor-portal .sponsor-btn-reset:focus-visible {
            outline: none !important;
            border-color: #173B57 !important;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
        }
        .sf-sponsor-portal .sponsor-filters .btn:active,
        .sf-sponsor-portal .sponsor-btn-reset:active {
            background-color: #E2EAF0 !important;
            border-color: #9DB4C6 !important;
            color: #0F2942 !important;
        }

        /* ─── Info Notice ─── */
        .sf-sponsor-portal .sponsor-info-notice {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 10px;
            background: #EEF3F7;
            border: 1px solid #DFE7EE;
            font-size: 12px;
            line-height: 1.55;
            color: #173B57;
        }
        .sf-sponsor-portal .sponsor-info-notice > .bi {
            flex: 0 0 auto;
            font-size: 16px;
            margin-top: 1px;
            color: #4A6B82;
        }

        /* ─── Empty States ─── */
        .sf-sponsor-portal .sponsor-empty-cell { padding: 24px !important; }
        .sf-sponsor-portal .sponsor-empty {
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            text-align: center;
            gap: 8px;
            white-space: normal;
        }
        .sf-sponsor-portal .sponsor-empty > .bi {
            color: #7890A3;
            font-size: 38px;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .sf-sponsor-portal .sponsor-empty h3 {
            color: #173B57;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
            margin: 0;
        }
        .sf-sponsor-portal .sponsor-empty p {
            max-width: 440px;
            margin: 0;
            color: #64748B;
            font-size: 12px;
            line-height: 1.6;
        }

        /* ─── Legacy Section ─── */
        .sf-sponsor-portal .sponsor-legacy-section {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
        }
        .sf-sponsor-portal .sponsor-legacy-section .card-body {
            padding: 20px !important;
        }
        .sf-sponsor-portal .sponsor-legacy-section h3 {
            font-size: 14px;
            font-weight: 700;
            color: #0F2942;
        }
        .sf-sponsor-portal .sponsor-legacy-section .small {
            font-size: 11.5px;
            color: #64748B;
        }
        .sf-sponsor-portal .sponsor-legacy-section .border-top {
            border-color: #E8EDF2 !important;
        }
        .sf-sponsor-portal .sponsor-legacy-section a {
            font-size: 12px;
            color: #173B57;
            font-weight: 500;
        }
        .sf-sponsor-portal .sponsor-legacy-section a:hover {
            color: #0F2942;
        }

        /* ─── Focus Rings ─── */
        .sf-sponsor-portal .sf-content :is(.btn, .form-control, .form-select):focus-visible {
            outline: 2px solid #2874A6;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgba(40, 116, 166, .12);
        }
    }
</style>
