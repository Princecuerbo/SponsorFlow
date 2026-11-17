<style>
    /*
     * SPONSORFLOW STAFF PORTALS — UNIFIED DESKTOP SIDEBAR & LAYOUT
     * Roles: FASSG Personnel, Sponsor, Accounting, System Administrator
     * Palette: #F1F5F9 page bg, #FFFFFF panels, #E8F0F6 active item with #123B59 indicator.
     * Two-state desktop sidebar: Expanded (256px) and Collapsed icon-only (72px).
     * Mobile preservation: Below 992px, the desktop sidebar/header are completely hidden.
     */
    .sf-staff-sidebar,
    .sf-staff-header,
    .sf-staff-skip-link {
        display: none;
    }

    @media screen and (min-width: 992px) {
        body.sf-staff-portal {
            --sf-staff-sidebar-width: 256px;
            --sf-staff-sidebar-collapsed-width: 72px;
            --sf-bg: #F1F5F9;
            --sf-sidebar-bg: #FFFFFF;
            --sf-header-bg: #FFFFFF;
            --sf-card-bg: #FFFFFF;
            --sf-active-nav-bg: #E8F0F6;
            --sf-active-nav-text: #0F2942;
            --sf-active-nav-icon: #173B57;
            --sf-active-nav-indicator: #123B59;
            --sf-inactive-nav-text: #526579;
            --sf-inactive-nav-icon: #64788A;
            --sf-primary-text: #0F2942;
            --sf-secondary-text: #64748B;
            --sf-border-color: #E1E8EC;
            --sf-slate: #0F2942;
            --sf-border: #E1E8EC;
            --sf-muted: #64748B;
            --sf-sign-out: #DC3545;
            background-color: var(--sf-bg) !important;
            color: var(--sf-primary-text) !important;
            /* FIX: Prevent horizontal overflow from sidebar width calculations */
            overflow-x: hidden;
        }

        /* Hide mobile navigation navbar on desktop */
        .sf-staff-portal .sf-staff-mobile-navigation {
            display: none !important;
        }

        /* -------------------------------------------------------------
         * 1. DESKTOP SIDEBAR (EXPANDED & COLLAPSED)
         * ------------------------------------------------------------- */
        .sf-staff-portal .sf-staff-sidebar {
            display: flex;
            flex-direction: column;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1030;
            width: var(--sf-staff-sidebar-width);
            background-color: var(--sf-sidebar-bg);
            border-right: 1px solid var(--sf-border-color);
            transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Brand Container & Logo */
        .sf-staff-portal .sf-staff-brand-container {
            min-height: 72px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--sf-border-color);
            padding: 0 1.25rem;
            flex-shrink: 0;
        }

        .sf-staff-portal .sf-staff-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: var(--font-heading);
            font-size: 1.15rem;
            font-weight: 700;
            color: #0F2942;
            text-decoration: none;
            overflow: hidden;
            white-space: nowrap;
        }

        .sf-staff-portal .sf-staff-brand-icon {
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background-color: #0F2942;
            color: #FFFFFF;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .sf-staff-portal .sf-staff-brand-text {
            transition: opacity 0.15s ease;
        }

        /* Sidebar Body & Navigation */
        .sf-staff-portal .sf-staff-sidebar-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1.25rem 0.75rem;
        }

        .sf-staff-portal .sf-staff-nav-caption {
            margin: 0 0.75rem 0.75rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 700;
            color: var(--sf-secondary-text);
            transition: opacity 0.15s ease;
        }

        .sf-staff-portal .sf-staff-nav-list {
            display: grid;
            gap: 0.4rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .sf-staff-portal .sf-staff-nav-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            min-height: 44px;
            padding: 0.65rem 0.875rem;
            border: 0;
            border-radius: 10px;
            background-color: transparent;
            color: var(--sf-inactive-nav-text);
            text-decoration: none;
            text-align: left;
            font-size: 0.84rem;
            font-weight: 500;
            line-height: 1.4;
            transition: background-color 0.15s ease, color 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
        }

        .sf-staff-portal .sf-staff-nav-link > i {
            flex: 0 0 20px;
            font-size: 1.15rem;
            text-align: center;
            color: var(--sf-inactive-nav-icon);
            transition: color 0.15s ease;
        }

        /* Hover: subtle gray bg, NO left indicator */
        .sf-staff-portal .sf-staff-nav-link:hover {
            background-color: #F8FAFC;
            color: var(--sf-primary-text);
        }

        .sf-staff-portal .sf-staff-nav-link:hover > i {
            color: var(--sf-primary-text);
        }

        /* Active Navigation Item: Pale blue-gray + thin dark navy left indicator */
        .sf-staff-portal .sf-staff-nav-link.is-active {
            background-color: var(--sf-active-nav-bg) !important;
            color: var(--sf-active-nav-text) !important;
            font-weight: 600 !important;
        }

        .sf-staff-portal .sf-staff-nav-link.is-active > i {
            color: var(--sf-active-nav-icon) !important;
        }

        .sf-staff-portal .sf-staff-nav-link.is-active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 7px;
            bottom: 7px;
            width: 3.5px;
            border-radius: 0 3px 3px 0;
            background-color: var(--sf-active-nav-indicator);
        }

        /* Sidebar Footer & Sign Out */
        .sf-staff-portal .sf-staff-sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid var(--sf-border-color);
            padding: 0.875rem 0.75rem;
        }

        .sf-staff-portal .sf-staff-sign-out {
            color: var(--sf-sign-out) !important;
        }

        .sf-staff-portal .sf-staff-sign-out > i {
            color: var(--sf-sign-out) !important;
        }

        .sf-staff-portal .sf-staff-sign-out:hover {
            background-color: #FEF2F2 !important;
            color: #B91C1C !important;
        }

        .sf-staff-portal .sf-staff-sign-out:hover > i {
            color: #B91C1C !important;
        }

        /* -------------------------------------------------------------
         * 2. COLLAPSED SIDEBAR (72PX ICON-ONLY MODE)
         * ------------------------------------------------------------- */
        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-sidebar {
            width: var(--sf-staff-sidebar-collapsed-width) !important;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-brand-container {
            padding: 0;
            justify-content: center;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-brand-text,
        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-nav-caption,
        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-nav-label {
            display: none !important;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-sidebar-body {
            padding: 1rem 0.35rem;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-nav-link {
            justify-content: center;
            padding: 0;
            width: 44px;
            height: 44px;
            margin: 0 auto;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-nav-link > i {
            font-size: 1.25rem;
            flex: 0 0 auto;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-sidebar-footer {
            padding: 0.875rem 0.35rem;
        }

        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-sign-out {
            justify-content: center;
            width: 44px;
            height: 44px;
            padding: 0;
            margin: 0 auto;
        }

        /* Margin-left layout to prevent horizontal overflow */
        body.sf-staff-portal.sf-sidebar-collapsed .sf-staff-header {
            margin-left: var(--sf-staff-sidebar-collapsed-width) !important;
        }

        body.sf-staff-portal.sf-sidebar-collapsed > .sf-content {
            margin-left: var(--sf-staff-sidebar-collapsed-width) !important;
        }

        /* -------------------------------------------------------------
         * 3. TOP NAVIGATION HEADER (72PX HEIGHT)
         * ------------------------------------------------------------- */
        .sf-staff-portal .sf-staff-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            height: 72px;
            min-height: 72px;
            margin-left: var(--sf-staff-sidebar-width);
            width: auto !important;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            background-color: var(--sf-header-bg);
            border-bottom: 1px solid var(--sf-border-color);
            transition: margin-left 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hamburger Toggle Button */
        .sf-staff-portal .sf-staff-sidebar-toggle {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1px solid var(--sf-border-color);
            background-color: #FFFFFF;
            color: #1D3155;
            font-size: 1.15rem;
            cursor: pointer;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .sf-staff-portal .sf-staff-sidebar-toggle:hover {
            background-color: #E8F0F6;
            color: #0F2942;
            border-color: #CBD5E1;
        }

        .sf-staff-portal .sf-staff-sidebar-toggle .fa-bars {
            font-size: 1.1rem;
        }

        .sf-staff-portal .sf-staff-header-title {
            min-width: 0;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 1.05rem;
            color: #0F2942;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sf-staff-portal .sf-staff-header-actions {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .sf-staff-portal .sf-staff-account-button {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0;
            min-height: 44px;
            border: 0;
            background-color: transparent;
            color: #0F2942;
            text-align: left;
            cursor: pointer;
        }

        .sf-staff-portal .sf-staff-avatar {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #E8F0F6;
            color: #0F2942;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid #D2E1ED;
        }

        .sf-staff-portal .sf-staff-account-text {
            display: grid;
            gap: 0.15rem;
        }

        .sf-staff-portal .sf-staff-account-name {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.825rem;
            font-weight: 600;
            color: #0F2942;
        }

        .sf-staff-portal .sf-staff-account-role {
            font-size: 0.7rem;
            color: var(--sf-secondary-text);
        }

        .sf-staff-portal .sf-staff-account-button > .bi-chevron-down {
            font-size: 0.7rem;
            color: var(--sf-secondary-text);
        }

        .sf-staff-portal .sf-staff-header .dropdown-menu {
            border: 1px solid var(--sf-border-color);
            border-radius: 12px;
            padding: 0.375rem;
            box-shadow: 0 4px 20px rgba(15, 41, 66, 0.08);
        }

        .sf-staff-portal .sf-staff-header .dropdown-item {
            padding: 0.65rem 0.875rem;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #0F2942;
        }

        /* -------------------------------------------------------------
         * 4. MAIN CONTENT AREA & REFINED CARDS
         * ------------------------------------------------------------- */
        .sf-staff-portal > .sf-content {
            margin-left: var(--sf-staff-sidebar-width);
            width: auto !important;
            min-height: calc(100vh - 72px);
            background-color: var(--sf-bg);
            padding: 1.75rem 2rem;
            scroll-margin-top: 80px;
            transition: margin-left 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sf-staff-portal .sf-content .h2.sf-heading {
            font-size: 1.65rem;
            color: #0F2942;
            margin-bottom: 0.25rem !important;
        }

        .sf-staff-portal .sf-content .sf-eyebrow {
            margin-bottom: 0.25rem !important;
        }

        /* White rounded content cards with minimal borders & soft shadows */
        .sf-staff-portal .sf-content :is(.card, .sf-card, .sf-stat-card) {
            background-color: var(--sf-card-bg) !important;
            border: 1px solid var(--sf-border-color) !important;
            border-radius: 14px !important;
            box-shadow: 0 1px 3px 0 rgba(15, 41, 66, 0.04), 0 2px 6px 0 rgba(15, 41, 66, 0.02) !important;
            min-width: 0;
        }

        .sf-staff-portal .sf-content .card-header {
            background-color: var(--sf-card-bg) !important;
            border-bottom: 1px solid var(--sf-border-color) !important;
        }

        .sf-staff-portal .sf-content .card-footer {
            background-color: var(--sf-card-bg) !important;
            border-top: 1px solid var(--sf-border-color) !important;
        }

        /* Table header styling */
        .sf-staff-portal .sf-content table.sf-table thead th {
            background-color: #F8FAFC !important;
            color: var(--sf-secondary-text) !important;
            border-bottom: 1px solid var(--sf-border-color) !important;
        }

        /* Primary action button styling: #0F2942 */
        .sf-staff-portal .btn-primary,
        .sf-staff-portal .btn-sf-navy,
        .sf-staff-portal .btn-navy-submit {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #FFFFFF !important;
        }

        .sf-staff-portal .btn-primary:hover,
        .sf-staff-portal .btn-sf-navy:hover,
        .sf-staff-portal .btn-navy-submit:hover {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #FFFFFF !important;
        }

        /* Subtitle and secondary text */
        .sf-staff-portal .sf-content .text-secondary {
            color: var(--sf-secondary-text) !important;
        }

        /* Inner container padding normalization */
        .sf-staff-portal .sf-content > .container-fluid {
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-top: 0 !important;
            min-height: auto !important;
        }

        .sf-staff-portal .sf-content [style*="background-color: #f8fafc"] {
            background-color: transparent !important;
        }

        /* -------------------------------------------------------------
         * 5. ACCESSIBILITY & SKIP LINK
         * ------------------------------------------------------------- */
        .sf-staff-portal :is(.sf-staff-sidebar, .sf-staff-header) :is(a, button):focus-visible {
            outline: 2px solid var(--sf-active-nav-indicator);
            outline-offset: 3px;
        }

        .sf-staff-portal .sf-staff-skip-link:focus {
            display: block;
            position: fixed;
            left: 1rem;
            top: 1rem;
            z-index: 1040;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background-color: #0F2942;
            color: #FFFFFF;
        }

        .sf-staff-portal .sf-staff-skip-link:not(:focus) {
            display: block;
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip-path: inset(50%);
            white-space: nowrap;
        }
    }

    /* Tablet / Small Desktop adjustments (992px to 1199.98px) */
    @media screen and (min-width: 992px) and (max-width: 1199.98px) {
        .sf-staff-portal .sf-staff-header {
            padding: 0 1.25rem;
        }

        .sf-staff-portal > .sf-content {
            padding: 1.5rem 1.25rem;
        }

        .sf-staff-portal .sf-staff-account-name {
            max-width: 130px;
        }
    }

    /* Print styles */
    @media print {
        .sf-staff-sidebar,
        .sf-staff-header {
            display: none !important;
        }

        .sf-staff-portal > .sf-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 0 !important;
            background-color: #FFFFFF !important;
        }
    }

    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .sf-staff-sidebar,
        .sf-staff-header,
        .sf-staff-portal > .sf-content,
        .sf-staff-nav-link {
            transition: none !important;
        }
    }
</style>
