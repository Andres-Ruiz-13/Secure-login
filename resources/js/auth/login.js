const passwordInput = document.querySelector('#password');
const toggleButton = document.querySelector('#togglePassword');

if (passwordInput && toggleButton) {
    toggleButton.addEventListener('click', () => {
        const hidden = passwordInput.type === 'password';
        passwordInput.type = hidden ? 'text' : 'password';
        toggleButton.textContent = hidden ? 'Ocultar' : 'Mostrar';
        toggleButton.setAttribute('aria-label', hidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
}