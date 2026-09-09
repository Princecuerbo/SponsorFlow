import './bootstrap';

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    // Toggle active text/icon color for visual feedback
    if (isPassword) {
        btn.classList.add('text-indigo-600');
        btn.classList.remove('text-gray-400');
    } else {
        btn.classList.remove('text-indigo-600');
        btn.classList.add('text-gray-400');
    }
}
window.togglePasswordVisibility = togglePasswordVisibility;
