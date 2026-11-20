<style>
    .sf-content {
        padding: 0 !important;
    }

    .auth-login-wrap {
        min-height: 100vh;
    }

    /* ===== Unified Input Groups & Buttons (Mobile & Desktop) ===== */
    .sl-auth-input-group {
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        overflow: hidden;
        background-color: #F8FAFC;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        display: flex;
    }

    .sl-auth-input-group:focus-within {
        border-color: #0F2942 !important;
        background-color: #FFFFFF !important;
        box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.12) !important;
    }

    .sl-auth-input-group.is-invalid {
        border-color: #DC3545 !important;
        background-color: #FFFFFF !important;
    }

    .sl-auth-input-group.is-invalid:focus-within {
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12) !important;
    }

    .sl-auth-input-group .input-group-text {
        background-color: transparent !important;
        border: none !important;
        color: #64748B !important;
        width: 42px !important;
        min-width: 42px !important;
        height: 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 0.95rem;
        padding: 0 !important;
    }

    .sl-auth-input-group .form-control {
        background-color: transparent !important;
        border: none !important;
        font-size: 0.875rem !important;
        height: 42px !important;
        color: #0F2942 !important;
        box-shadow: none !important;
        padding-left: 0 !important;
        padding-right: 0.5rem !important;
        min-width: 0;
    }

    .sl-auth-input-group .form-control:focus {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .sl-auth-input-group .form-control::placeholder {
        color: #94A3B8 !important;
        font-size: 0.875rem !important;
    }

    /* Native date picker stays light and visually separate from the leading icon. */
    .sl-auth-input-group .form-control[type="date"] {
        color-scheme: light;
    }

    .sl-auth-input-group input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.55;
        width: 16px;
        height: 16px;
        margin: 0 0 0 0.25rem;
    }

    /* Password visibility toggle - mobile & desktop */
    .sl-auth-toggle-btn {
        appearance: none;
        -webkit-appearance: none;
        background-color: transparent !important;
        border: none !important;
        color: #64748B !important;
        width: 42px !important;
        min-width: 42px !important;
        height: 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        cursor: pointer;
        border-radius: 6px !important;
        transition: color 180ms ease, background-color 180ms ease;
    }

    .sl-auth-toggle-btn:hover,
    .sl-auth-toggle-btn[aria-pressed="true"] {
        color: #0F2942 !important;
        background-color: #EEF3F7 !important;
    }

    .sl-auth-toggle-btn:focus-visible {
        outline: none !important;
        box-shadow: inset 0 0 0 2px #0F2942 !important;
    }

    /* Compact recovery-level alert inside the form column. */
    .sl-auth-alert-danger {
        background-color: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 8px;
        padding: 0.75rem 0.875rem;
        color: #991B1B;
        font-size: 0.8125rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .sl-auth-alert-danger i {
        color: #B91C1C;
        font-size: 0.95rem;
    }

    /* Validation helper text: wraps cleanly without widening the form. */
    .sl-auth-field-error {
        margin-top: 0.375rem !important;
        font-size: 0.75rem;
        line-height: 1.4;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .sl-auth-policy {
        color: #64748B !important;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    /* Neutral institutional information panel for the disabled-recovery state. */
    .sl-auth-info-panel {
        background-color: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        padding: 0.875rem 1rem;
        color: #475569;
        font-size: 0.8125rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .sl-auth-info-panel i {
        color: #0F2942;
        font-size: 0.95rem;
    }

    /* Primary Action Button */
    .sl-auth-submit-btn {
        background-color: #0F2942 !important;
        border: 1px solid #0F2942 !important;
        border-radius: 8px !important;
        height: 44px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 4px rgba(15, 41, 66, 0.15);
        transition: background-color 180ms ease, border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease !important;
    }

    .sl-auth-submit-btn:hover:not(:disabled) {
        background-color: #173B57 !important;
        border-color: #173B57 !important;
        color: #FFFFFF !important;
        transform: translateY(-1px);
    }

    .sl-auth-submit-btn:active:not(:disabled) {
        transform: translateY(0);
    }

    .sl-auth-submit-btn:focus-visible {
        outline: none !important;
        border-color: #0F2942 !important;
        box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25) !important;
    }

    .sl-auth-submit-btn:disabled,
    .sl-auth-submit-btn[aria-busy="true"] {
        background-color: #94A3B8 !important;
        border-color: #94A3B8 !important;
        color: #FFFFFF !important;
        box-shadow: none !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
        transform: none !important;
    }

    /* Secondary navigation button - matches the established SponsorFlow convention. */
    .sl-create-account-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        height: 42px !important;
        padding: 0 1rem !important;
        background-color: #FFFFFF !important;
        color: #0F2942 !important;
        border: 1px solid #CBD5E1 !important;
        border-radius: 8px !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        text-decoration: none !important;
        transition: background-color 180ms ease, color 180ms ease, border-color 180ms ease, box-shadow 180ms ease !important;
    }

    .sl-create-account-btn:hover {
        background-color: #EEF3F7 !important;
        color: #0F2942 !important;
        border-color: #AFC3D3 !important;
    }

    .sl-create-account-btn:focus-visible {
        outline: none !important;
        border-color: #0F2942 !important;
        box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.2) !important;
    }

    .sl-create-account-btn:active {
        background-color: #DCE7EF !important;
        border-color: #AFC3D3 !important;
        color: #0F2942 !important;
    }

    /* Desktop Scoped Constraints (992px+) */
    @media screen and (min-width: 992px) {
        .sl-form-container {
            max-width: 410px !important;
        }
    }

    /* Back to Home Button */
    .btn-back-home {
        color: #64748b;
        transition: color 180ms ease, transform 180ms ease;
    }

    .btn-back-home:hover {
        color: #0F2537 !important;
        transform: translateX(-3px);
    }

    @media (max-width: 575.98px) {
        .sl-auth-input-group .form-control,
        .sl-auth-input-group .input-group-text,
        .sl-auth-toggle-btn {
            min-width: 0;
        }
    }
</style>
