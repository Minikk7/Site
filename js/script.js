// Vahvista salasanan vahvuus rekisteröitymislomakkeessa
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const passwordFeedback = document.getElementById('password-feedback');
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = passwordField.value;
            
            // Tarkista salasanan vahvuus
            if (password.length < 8) {
                passwordFeedback.textContent = 'Salasanan tulee olla vähintään 8 merkkiä pitkä';
                passwordFeedback.className = 'text-danger';
            } else if (!/[A-Z]/.test(password)) {
                passwordFeedback.textContent = 'Salasanassa tulee olla vähintään yksi iso kirjain';
                passwordFeedback.className = 'text-danger';
            } else if (!/[0-9]/.test(password)) {
                passwordFeedback.textContent = 'Salasanassa tulee olla vähintään yksi numero';
                passwordFeedback.className = 'text-danger';
            } else {
                passwordFeedback.textContent = 'Salasana on riittävän vahva';
                passwordFeedback.className = 'text-success';
            }
        });
    }
    
    // Tarkista että salasanat täsmäävät
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Salasanat eivät täsmää');
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        });
    }
});