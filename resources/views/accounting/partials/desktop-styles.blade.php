{{-- Accounting presentation only. Mobile/tablet and print retain their original styles. --}}
<style>
    @media screen and (min-width: 992px) {
        .sf-accounting-portal .accounting-original-copy { display: none !important; }
        .sf-accounting-portal .accounting-desktop-copy { display: inline !important; }

        .sf-accounting-portal .sf-content { padding: 28px 24px; min-width: 0; }
        .sf-accounting-portal .sf-content > .d-flex:first-of-type {
            align-items: center !important;
            gap: 16px 24px !important;
        }
        .sf-accounting-portal .sf-content > .d-flex:first-of-type > div:first-child {
            flex: 1 1 340px;
            min-width: 0;
        }
        .sf-accounting-portal .sf-content > .d-flex:first-of-type > div:last-child {
            flex: 0 1 auto;
            gap: 8px !important;
        }
        .sf-accounting-portal .sf-content > .d-flex:first-of-type .sf-eyebrow {
            font-size: 10.5px;
            letter-spacing: .08em;
            color: #64748B;
            font-weight: 600;
        }
        .sf-accounting-portal .sf-content > .d-flex:first-of-type h2 {
            font-size: 26px;
            line-height: 1.25;
            color: #0F2942;
            font-weight: 700;
        }
        .sf-accounting-portal .sf-content > .d-flex:first-of-type p {
            font-size: 13px;
            line-height: 1.55;
            color: #64748B !important;
            margin-top: 4px;
        }
        .sf-accounting-portal .accounting-primary-link,
        .sf-accounting-portal .accounting-print,
        .sf-accounting-portal .accounting-export {
            min-height: 38px;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 20px;
            white-space: nowrap;
        }
        .sf-accounting-portal .accounting-primary-link,
        .sf-accounting-portal .accounting-export {
            background: #0F2942;
            border-color: #0F2942;
            color: #FFF;
        }
        .sf-accounting-portal .accounting-primary-link:hover,
        .sf-accounting-portal .accounting-export:hover { background: #173B57; border-color: #173B57; }
        .sf-accounting-portal .accounting-print { color: #0F2942; border-color: #9AAEBE; background: #FFF; }
        .sf-accounting-portal .accounting-print:hover { background: #EEF3F7; }

        .sf-accounting-portal .accounting-notice {
            display: flex;
            align-items: center;
            gap: 12px !important;
            padding: 13px 16px !important;
            border: 1px solid #DFE7EE !important;
            border-left: 3px solid #173B57 !important;
            border-radius: 12px !important;
            background: #EEF3F7 !important;
            color: #173B57 !important;
            font-size: 12.5px;
            line-height: 1.5;
        }
        .sf-accounting-portal .accounting-notice > .bi { flex: 0 0 auto; font-size: 18px; color: #173B57; }

        .sf-accounting-portal .accounting-desktop-metrics {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 12px;
        }
        .sf-accounting-portal .accounting-metric {
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
        }
        .sf-accounting-portal .accounting-metric-label {
            font-size: 10.5px;
            line-height: 1.5;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748B;
            font-weight: 600;
        }
        .sf-accounting-portal .accounting-metric-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #EEF3F7;
            color: #173B57;
            font-size: 20px;
        }
        .sf-accounting-portal .accounting-metric-value {
            grid-column: 1 / -1;
            color: #0F2942;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
            overflow-wrap: anywhere;
        }
        .sf-accounting-portal .accounting-metric-note { font-size: 11px; line-height: 1.55; color: #64748B !important; }
        .sf-accounting-portal .accounting-summary-panels {
            display: grid;
            grid-template-columns: minmax(0, 58fr) minmax(0, 42fr);
            gap: 20px;
            margin: 0;
        }
        .sf-accounting-portal .accounting-summary-panels > div { width: auto; min-width: 0; padding: 0; margin: 0; }
        .sf-accounting-portal .accounting-summary-panels .sf-card,
        .sf-accounting-portal .accounting-filters,
        .sf-accounting-portal .accounting-reference {
            border: 1px solid #E1E8EC;
            border-radius: 14px;
            box-shadow: 0 2px 4px rgba(15, 41, 66, .035);
            min-width: 0;
        }
        .sf-accounting-portal .accounting-summary-panels .card-body {
            display: flex;
            flex-direction: column;
            min-height: 300px;
            padding: 20px !important;
            min-width: 0;
        }
        .sf-accounting-portal .accounting-summary-panels h3 { color: #0F2942; font-size: 15px; font-weight: 600; }
        .sf-accounting-portal .accounting-sponsor-table { table-layout: fixed; }
        .sf-accounting-portal .accounting-sponsor-table th:first-child { width: 54%; }
        .sf-accounting-portal .accounting-sponsor-table th,
        .sf-accounting-portal .accounting-sponsor-table td { padding: 12px 14px !important; overflow-wrap: anywhere; }
        .sf-accounting-portal .accounting-sponsor-table th { font-size: 10.5px; letter-spacing: .04em; text-transform: uppercase; font-weight: 600; color: #64748B; background: #F8FAFC; }
        .sf-accounting-portal .accounting-sponsor-table td { font-size: 12.5px; color: #173B57; }
        .sf-accounting-portal .accounting-category-row { gap: 16px; font-size: 13px; color: #173B57; }
        .sf-accounting-portal .accounting-category-row span { min-width: 0; overflow-wrap: anywhere; }
        .sf-accounting-portal .accounting-category-row strong { font-variant-numeric: tabular-nums; }

        .sf-accounting-portal .accounting-empty-cell { padding: 24px !important; }
        .sf-accounting-portal .accounting-empty {
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            text-align: center;
            gap: 8px;
            white-space: normal;
        }
        .sf-accounting-portal .accounting-empty > .bi { color: #7890A3; font-size: 38px; line-height: 1.2; margin-bottom: 4px; }
        .sf-accounting-portal .accounting-empty h3,
        .sf-accounting-portal .accounting-empty h4 { color: #173B57; font-size: 14px; font-weight: 600; line-height: 1.5; margin: 0; }
        .sf-accounting-portal .accounting-empty p { max-width: 440px; margin: 0; color: #64748B; font-size: 12px; line-height: 1.6; }
        .sf-accounting-portal .accounting-category-empty { flex: 1; }

        .sf-accounting-portal .accounting-record-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            order: -1;
            margin: 0 !important;
            padding: 8px 12px !important;
            min-height: 38px;
            background: #EEF3F7;
            border: 1px solid #D6E1E9;
            border-radius: 8px;
            box-shadow: none;
            white-space: nowrap;
            color: #173B57;
            font-size: 12px;
            font-weight: 500;
        }
        .sf-accounting-portal .accounting-record-count > .h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: #0F2942;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .sf-accounting-portal .accounting-filters .card-body { padding: 16px !important; }
        .sf-accounting-portal .accounting-filters form { display: grid; grid-template-columns: 25fr 25fr 35fr 15fr; gap: 12px; align-items: end; margin: 0; }
        .sf-accounting-portal .accounting-filters form > div { width: auto; min-width: 0; padding: 0; margin: 0; }
        .sf-accounting-portal .accounting-filter-label { display: block !important; font-size: 11px; font-weight: 600; color: #64748B; margin-bottom: 6px; }
        .sf-accounting-portal .accounting-filters :is(.form-select, .form-control) {
            height: 38px;
            border-color: #D6E1E9;
            border-radius: 8px;
            font-size: 13px;
            color: #173B57;
        }
        .sf-accounting-portal .accounting-filters .btn,
        .sf-accounting-portal .accounting-btn-reset {
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
        .sf-accounting-portal .accounting-filters .btn i,
        .sf-accounting-portal .accounting-btn-reset i {
            font-size: 14px !important;
            line-height: 1 !important;
            color: #173B57 !important;
            transition: color 0.15s ease !important;
        }
        .sf-accounting-portal .accounting-filters .btn:hover,
        .sf-accounting-portal .accounting-btn-reset:hover {
            background-color: #EEF3F7 !important;
            color: #0F2942 !important;
            border-color: #AFC3D3 !important;
        }
        .sf-accounting-portal .accounting-filters .btn:hover i,
        .sf-accounting-portal .accounting-btn-reset:hover i {
            color: #0F2942 !important;
        }
        .sf-accounting-portal .accounting-filters .btn:focus-visible,
        .sf-accounting-portal .accounting-btn-reset:focus-visible {
            outline: none !important;
            border-color: #173B57 !important;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
        }
        .sf-accounting-portal .accounting-filters .btn:active,
        .sf-accounting-portal .accounting-btn-reset:active {
            background-color: #E2EAF0 !important;
            border-color: #9DB4C6 !important;
            color: #0F2942 !important;
        }
        .sf-accounting-portal .accounting-filters .input-group { flex-wrap: nowrap; }
        .sf-accounting-portal .accounting-filters .input-group-text { border-radius: 8px 0 0 8px; padding-inline: 10px; }
        .sf-accounting-portal .accounting-filters .input-group .form-control { border-radius: 0 8px 8px 0; min-width: 0; }
        .sf-accounting-portal .accounting-reference { overflow: hidden; }
        .sf-accounting-portal .accounting-table-scroll {
            max-width: 100%;
            scrollbar-color: #A8BAC9 #F1F5F9;
        }
        .sf-accounting-portal .accounting-table-scroll::-webkit-scrollbar {
            height: 7px;
        }
        .sf-accounting-portal .accounting-table-scroll::-webkit-scrollbar-track {
            background: #F1F5F9;
            border-radius: 4px;
        }
        .sf-accounting-portal .accounting-table-scroll::-webkit-scrollbar-thumb {
            background: #A8BAC9;
            border-radius: 4px;
        }
        .sf-accounting-portal .accounting-table-scroll::-webkit-scrollbar-thumb:hover {
            background: #7E96AA;
        }
        .sf-accounting-portal .accounting-reference table { width: 100%; table-layout: fixed; }
        .sf-accounting-portal .accounting-reference .accounting-populated { min-width: 1220px; }
        .sf-accounting-portal .accounting-reference th {
            padding: 12px 10px !important;
            font-size: 10.5px;
            line-height: 1.5;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748B;
            background: #F8FAFC;
            white-space: normal !important;
        }
        .sf-accounting-portal .accounting-reference td:not(.accounting-empty-cell) {
            padding: 14px 10px !important;
            font-size: 12px;
            line-height: 1.55;
            color: #173B57;
            vertical-align: top;
            overflow-wrap: anywhere;
        }
        .sf-accounting-portal .accounting-reference td:nth-child(10) { white-space: normal !important; }
        .sf-accounting-portal .accounting-reference th:nth-child(1) { width: 8.5%; }
        .sf-accounting-portal .accounting-reference th:nth-child(2) { width: 10%; }
        .sf-accounting-portal .accounting-reference th:nth-child(3) { width: 12%; }
        .sf-accounting-portal .accounting-reference th:nth-child(4) { width: 5.5%; }
        .sf-accounting-portal .accounting-reference th:nth-child(5) { width: 6%; }
        .sf-accounting-portal .accounting-reference th:nth-child(6) { width: 11%; }
        .sf-accounting-portal .accounting-reference th:nth-child(7) { width: 12%; }
        .sf-accounting-portal .accounting-reference th:nth-child(8) { width: 5%; }
        .sf-accounting-portal .accounting-reference th:nth-child(9) { width: 11.5%; }
        .sf-accounting-portal .accounting-reference th:nth-child(10) { width: 8.5%; }
        .sf-accounting-portal .accounting-reference th:nth-child(11) { width: 10%; }
        .sf-accounting-portal .accounting-reference .small { font-size: 11px; margin-top: 3px; overflow-wrap: anywhere; }
        .sf-accounting-portal .accounting-reference .badge { max-width: 100%; white-space: normal; line-height: 1.5; }
        .sf-accounting-portal .accounting-reference .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            white-space: normal;
            color: #173B57;
            border-color: #B8C7D3;
        }
        .sf-accounting-portal .accounting-reference .btn .bi { flex-shrink: 0; margin: 0 !important; }
        .sf-accounting-portal .accounting-reference .btn:hover { background: #EEF3F7; }
        .sf-accounting-portal .accounting-reference .table-hover { --bs-table-hover-bg: #F3F7FA; }
        .sf-accounting-portal .accounting-reference .accounting-empty { min-height: 220px; }
        .sf-accounting-portal .accounting-reference .accounting-empty p { max-width: 500px; }
        .sf-accounting-portal .sf-content :is(.btn, .form-control, .form-select, .accounting-table-scroll):focus-visible {
            outline: 2px solid #2874A6;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgba(40, 116, 166, .12);
        }
        .sf-accounting-portal .accounting-table-scroll:focus-visible { outline-offset: -2px; }
    }
</style>