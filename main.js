// main.js - JavaScript for Skill Sphere website

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let isValid = true;
            const nameInput = document.getElementById('name');
            const surnameInput = document.getElementById('surname');
            const emailInput = document.getElementById('email');
            const messageInput = document.getElementById('message');
            
            // Reset any previous error styling
            const formInputs = contactForm.querySelectorAll('input, textarea');
            formInputs.forEach(input => {
                input.classList.remove('error');
            });
            
            // Name validation
            if (nameInput.value.trim() === '') {
                nameInput.classList.add('error');
                isValid = false;
            }
            
            // Surname validation
            if (surnameInput.value.trim() === '') {
                surnameInput.classList.add('error');
                isValid = false;
            }
            
            // Email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailInput.value)) {
                emailInput.classList.add('error');
                isValid = false;
            }
            
            // Message validation
            if (messageInput.value.trim() === '') {
                messageInput.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all fields correctly.');
            }
        });
    }
    
    // Add error styling for form inputs
    const formStyles = document.createElement('style');
    formStyles.textContent = `
        .error {
            border-color: #ff3860 !important;
            background-color: rgba(255, 56, 96, 0.05) !important;
        }
    `;
    document.head.appendChild(formStyles);
});