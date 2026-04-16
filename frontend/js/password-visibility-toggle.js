/**
 * Password Visibility Toggle
 * Provides functionality to show/hide passwords in input fields
 * Automatically initializes toggles with the following ID pattern:
 * - toggle{FieldName}Password (button ID)
 * - {fieldName}Password (input ID)
 * 
 * For custom mappings, pass an object to initPasswordToggle()
 */

function initPasswordToggle(customMappings = null) {
    const defaultMappings = {
        // Change password page
        'toggleCurrentPassword': 'currentPassword',
        'toggleNewPassword': 'newPassword',
        'toggleConfirmNewPassword': 'confirmNewPassword',
        // Reset password page
        'toggleResetPassword': 'password',
        'toggleResetConfirmPassword': 'confirm_password'
    };

    const mappings = customMappings || defaultMappings;

    Object.entries(mappings).forEach(([btnId, inputId]) => {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        
        if (!btn || !input) return;
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }
        });
    });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggle);
} else {
    initPasswordToggle();
}
