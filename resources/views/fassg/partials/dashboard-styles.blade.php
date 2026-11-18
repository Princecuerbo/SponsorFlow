<style>
    /*
     * SPONSORFLOW FASSG DASHBOARD — REFINED INSTITUTIONAL UI/UX
     * Strict visual polish for the FASSG Dashboard interface only.
     * Preserves all backend logic, metrics, routes, navigation, and permissions.
     */

    /* Page Canvas & Header Alignment */
    .sf-fassg-portal > .sf-content {
        background-color: #F1F5F9 !important;
    }

    .sf-fassg-portal .sf-content > .d-flex {
        margin-bottom: 22px !important;
    }

    .sf-fassg-portal .sf-content > .d-flex > div > .sf-eyebrow {
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: .08em !important;
        text-transform: uppercase !important;
        color: #64748B !important;
        margin-bottom: 4px !important;
    }

    .sf-fassg-portal .sf-content > .d-flex > div > .sf-heading {
        color: #0F2942 !important;
        font-size: 28px !important;
        line-height: 1.25 !important;
        font-weight: 700 !important;
        margin-bottom: 0 !important;
        letter-spacing: -0.01em !important;
    }

    .fassg-dashboard {
        color: #23384D;
        font-family: var(--font-body, 'Inter', sans-serif);
    }

    /* -------------------------------------------------------------
     * 1. FOUR SUMMARY METRIC CARDS
     * ------------------------------------------------------------- */
    .fassg-dashboard-metrics {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        margin-bottom: 22px;
    }

    .fassg-dashboard-card {
        min-width: 0;
        background-color: #FFFFFF;
        border: 1px solid #E1E8EC;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 41, 66, 0.04), 0 2px 6px rgba(15, 41, 66, 0.02);
    }

    .fassg-dashboard-metric {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20px 22px;
        min-height: 128px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .fassg-dashboard-metric:hover {
        border-color: #D2E1ED;
        box-shadow: 0 2px 6px rgba(15, 41, 66, 0.06);
    }

    .fassg-dashboard-metric-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 8px;
    }

    .fassg-dashboard-metric-label {
        margin: 0;
        color: #64748B;
        font-size: 11px;
        line-height: 1.4;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        flex: 1;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    /* Outlined Metric Icons */
    .fassg-dashboard-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background-color: #EEF3F7;
        color: #173B57;
        font-size: 21px;
        border: 1px solid transparent;
        transition: transform .15s ease;
    }

    .fassg-dashboard-icon-amber {
        background-color: #FFF7E6 !important;
        color: #B88219 !important;
        border: 1px solid #F6DDA1 !important;
    }

    .fassg-dashboard-metric-body {
        margin-top: auto;
    }

    .fassg-dashboard-metric-value {
        margin: 0 0 3px 0;
        color: #0F2942;
        font-size: 28px;
        line-height: 1.2;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }

    .fassg-dashboard-metric-description {
        margin: 0;
        color: #64748B;
        font-size: 11.5px;
        line-height: 1.4;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* -------------------------------------------------------------
     * 2. LOWER DASHBOARD PANELS (58% / 42% ON DESKTOP)
     * ------------------------------------------------------------- */
    .fassg-dashboard-panels {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        align-items: start;
    }

    .fassg-dashboard-panel {
        padding: 24px;
    }

    .fassg-dashboard-panel-heading {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 8px 16px;
        margin-bottom: 20px;
    }

    .fassg-dashboard-panel-title {
        margin: 0;
        color: #0F2942;
        font-size: 16px;
        line-height: 1.4;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .fassg-dashboard-panel-subtitle {
        color: #64748B;
        font-size: 12px;
        font-style: italic;
        white-space: nowrap;
    }

    /* -------------------------------------------------------------
     * 3. APPLICATION PROGRESS THIS TERM (LEFT PANEL)
     * ------------------------------------------------------------- */
    .fassg-dashboard-progress-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .fassg-dashboard-progress-item {
        display: flex;
        flex-direction: column;
    }

    .fassg-dashboard-progress-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 7px;
    }

    .fassg-dashboard-status-name {
        font-size: 13px;
        font-weight: 500;
        color: #23384D;
        line-height: 1.4;
    }

    .fassg-dashboard-status-count {
        font-size: 13px;
        font-weight: 600;
        color: #0F2942;
        font-variant-numeric: tabular-nums;
    }

    .fassg-dashboard-progress {
        height: 6px;
        border-radius: 999px;
        background-color: #E8EEF3;
        overflow: hidden;
        box-shadow: none;
    }

    .fassg-dashboard-progress > .progress-bar {
        height: 100%;
        border-radius: 999px;
        background-color: #173B57;
        transition: none;
    }

    /* -------------------------------------------------------------
     * 4. PENDING VERIFICATIONS & RECENT PROGRAMS (RIGHT PANEL)
     * ------------------------------------------------------------- */
    .fassg-dashboard-pending-section .fassg-dashboard-panel-title {
        margin-bottom: 14px;
    }

    .fassg-dashboard-pending-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 12px;
        border-radius: 10px;
        text-decoration: none;
        transition: background-color .15s ease;
        margin: 0 -12px;
    }

    .fassg-dashboard-pending-link:hover {
        background-color: #F8FAFC;
    }

    .fassg-dashboard-pending-link:focus-visible {
        outline: 2px solid #173B57;
        outline-offset: 2px;
    }

    .fassg-dashboard-pending-details {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .fassg-dashboard-pending-count {
        font-size: 26px;
        font-weight: 700;
        color: #0F2942;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        margin-bottom: 2px;
        letter-spacing: -0.01em;
    }

    .fassg-dashboard-pending-description {
        color: #64748B;
        font-size: 12px;
        line-height: 1.4;
    }

    .fassg-dashboard-divider {
        height: 1px;
        background-color: #E6EBF0;
        margin: 18px 0 20px 0;
        border: none;
    }

    .fassg-dashboard-programs-section .fassg-dashboard-panel-title {
        margin-bottom: 12px;
    }

    .fassg-dashboard-programs {
        display: flex;
        flex-direction: column;
    }

    .fassg-dashboard-program {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 11px 0;
    }

    .fassg-dashboard-program + .fassg-dashboard-program {
        border-top: 1px solid #E6EBF0;
    }

    .fassg-dashboard-program:last-child {
        padding-bottom: 0;
    }

    .fassg-dashboard-program-name {
        font-size: 13px;
        font-weight: 600;
        color: #0F2942;
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
        min-width: 0;
    }

    .fassg-dashboard-program-status {
        flex-shrink: 0;
        font-size: 11px;
    }

    /* -------------------------------------------------------------
     * 5. RESPONSIVE BREAKPOINTS
     * ------------------------------------------------------------- */
    @media (min-width: 576px) {
        .fassg-dashboard-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .fassg-dashboard-metrics {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .fassg-dashboard-panels {
            grid-template-columns: 58fr 42fr;
        }
    }

    @media (max-width: 1399.98px) {
        .fassg-dashboard-metric-description {
            white-space: normal;
        }
    }

    @media (max-width: 575.98px) {
        .fassg-dashboard-panel {
            padding: 18px;
        }

        .fassg-dashboard-metric {
            padding: 16px 18px;
        }

        .fassg-dashboard-program-name {
            white-space: normal;
        }
    }

    /* Accessibility / Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .fassg-dashboard-metric,
        .fassg-dashboard-icon,
        .fassg-dashboard-pending-link {
            transition: none !important;
        }
    }
</style>
