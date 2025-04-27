// Section Switching with Animation
function showSection(sectionId) {
    document.querySelectorAll('.container section').forEach(section => {
        section.classList.add('hidden');
    });

    const activeSection = document.getElementById(sectionId);
    if (activeSection) {
        activeSection.classList.remove('hidden');
    }
}

// Initialize with home section
document.addEventListener('DOMContentLoaded', () => {
    showSection('home');
});

// Password Toggle Functionality
function togglePassword(inputId) {
    console.log("Toggle function called for:", inputId);
    const passwordInput = document.getElementById(inputId);
    const icon = passwordInput.nextElementSibling;

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
}




// Subscription Form Handler
document.getElementById('subscriptionForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target.querySelector('input').value;
    const messageDiv = document.getElementById('subscriptionMessage');
    
    if (validateEmail(email)) {
        try {
            const response = await fetch('subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}`,
            });

            const result = await response.text();
            
            if (response.ok) {
                messageDiv.textContent = result || 'Subscription successful!';
                messageDiv.style.color = 'green';
                e.target.reset();
            } else {
                messageDiv.textContent = result || 'Failed to subscribe. Please try again.';
                messageDiv.style.color = 'red';
            }
        } catch (error) {
            console.error('Error:', error);
            messageDiv.textContent = 'An error occurred. Please try again.';
            messageDiv.style.color = 'red';
        }
    } else {
        messageDiv.textContent = 'Please enter a valid email address';
        messageDiv.style.color = 'red';
    }
});

// Email Validation Function
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}