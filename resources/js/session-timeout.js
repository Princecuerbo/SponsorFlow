/**
 * Session Timeout Management
 *
 * Tracks user activity and displays a countdown warning before session expiration.
 * Redirects the user cleanly to their respective portal login gate upon timeout or when
 * "Logout" is clicked from the expired session modal, preventing 419 Page Expired errors on POST /logout.
 */
export function initSessionTimeout(options = {}) {
    const idleModal = document.getElementById(options.modalId || 'idle-modal');
    if (!idleModal) {
        return null;
    }

    const countdown = document.getElementById(options.countdownId || 'idle-countdown');
    const stayButton = document.getElementById(options.stayButtonId || 'stay-logged-in-btn');
    const logoutButton = document.getElementById(options.logoutButtonId || 'idle-logout-btn');

    // Default portal routes
    const studentLoginUrl = options.studentLoginUrl || '/login';
    const staffLoginUrl = options.staffLoginUrl || '/dorsu-staff-gate';
    const adminLoginUrl = options.adminLoginUrl || '/dorsu-sysadmin-gate';

    // Determine target login gate URL
    let loginGateUrl = options.loginGateUrl || idleModal.dataset.loginGate;

    if (!loginGateUrl) {
        const userRole = (options.userRole || idleModal.dataset.userRole || '').toLowerCase();
        if (userRole === 'admin') {
            loginGateUrl = adminLoginUrl;
        } else if (['fassg', 'sponsor', 'accounting', 'staff'].includes(userRole)) {
            loginGateUrl = staffLoginUrl;
        } else {
            loginGateUrl = studentLoginUrl;
        }
    }

    const warningDelay = parseInt(options.warningDelay || idleModal.dataset.warningDelay, 10) || 885000; // default: 14m 45s
    const countdownSeconds = parseInt(options.countdownSeconds || idleModal.dataset.countdownSeconds, 10) || 15;

    let warningTimer = null;
    let countdownTimer = null;
    let warningVisible = false;

    function clearTimers() {
        if (warningTimer) {
            clearTimeout(warningTimer);
            warningTimer = null;
        }
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function redirectToLoginGate() {
        clearTimers();
        const separator = loginGateUrl.indexOf('?') !== -1 ? '&' : '?';
        window.location.href = loginGateUrl + separator + 'session_expired=1';
    }

    function hideWarning() {
        warningVisible = false;
        idleModal.classList.add('d-none');
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function showWarning() {
        warningVisible = true;
        idleModal.classList.remove('d-none');
        let secondsLeft = countdownSeconds;

        if (countdown) {
            countdown.textContent = secondsLeft;
        }

        if (countdownTimer) {
            clearInterval(countdownTimer);
        }

        countdownTimer = setInterval(function () {
            secondsLeft -= 1;

            if (countdown) {
                countdown.textContent = Math.max(secondsLeft, 0);
            }

            if (secondsLeft <= 0) {
                clearInterval(countdownTimer);
                countdownTimer = null;
                redirectToLoginGate();
            }
        }, 1000);
    }

    function resetIdleTimer() {
        clearTimers();
        hideWarning();
        warningTimer = setTimeout(showWarning, warningDelay);
    }

    // User activity listeners to keep session alive while active
    const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
    activityEvents.forEach(function (eventName) {
        document.addEventListener(
            eventName,
            function () {
                if (!warningVisible) {
                    resetIdleTimer();
                }
            },
            { passive: true }
        );
    });

    if (stayButton) {
        stayButton.addEventListener('click', function (e) {
            e.preventDefault();
            resetIdleTimer();
        });
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', function (e) {
            e.preventDefault();
            redirectToLoginGate();
        });
    }

    // Initialize timer
    resetIdleTimer();

    return {
        reset: resetIdleTimer,
        redirect: redirectToLoginGate,
        showWarning: showWarning,
        clear: clearTimers,
    };
}

// Auto-initialize if DOM is ready and modal exists
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initSessionTimeout());
    } else {
        initSessionTimeout();
    }
}
