document.addEventListener('DOMContentLoaded', function () {
    const mappings = {
        toggleCurrentPassword: 'currentPassword',
        toggleNewPassword: 'newPassword',
        toggleConfirmNewPassword: 'confirmNewPassword'
    };

    Object.entries(mappings).forEach(function ([buttonId, inputId]) {
        const button = document.getElementById(buttonId);
        const input = document.getElementById(inputId);

        if (!button || !input) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', showing);
                icon.classList.toggle('bi-eye-slash', !showing);
            }
        });
    });
});
