<?php
session_start();

// Check for messages from other scripts
$resetEmailSent = $_SESSION['reset_email_sent'] ?? false;
$resetSuccess = $_SESSION['reset_success'] ?? false;
$resetError = $_SESSION['reset_error'] ?? null;

// Clear messages after displaying
unset($_SESSION['reset_email_sent']);
unset($_SESSION['reset_success']);
unset($_SESSION['reset_error']);

// Get token from URL if present
$token = $_GET['token'] ?? '';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop Prediction System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <header>
        <div class="header-title">
            <i class="fas fa-leaf"></i> Crop Prediction System
        </div>
    </header>

    <!-- Navigation -->
    <nav>
        <ul class="nav-links">
            <li><a href="#" onclick="showSection('home')">Home</a></li>
            <li><a href="#" onclick="showSection('about')">About</a></li>
            <li><a href="#" onclick="showSection('contact')">Contact</a></li>
            <li><a href="#" onclick="showSection('login')">Login</a></li>
            <li><a href="#" onclick="showSection('signup')">Sign Up</a></li>
        </ul>
    </nav>

    <div class="container">

        <!-- Home Section -->
        <section id="home" class="hero-section">
            <h1>Welcome to Crop Prediction System</h1>
            <div class="search-container">
                <input type="text" placeholder="Search crops or regions..." class="search-input">
                <button class="search-btn"><i class="fas fa-search"></i></button>
            </div>
            <p>Your trusted partner for agricultural insights.</p>
            <div class="social-links">
                <i class="fab fa-facebook social-icon"></i>
                <i class="fab fa-twitter social-icon"></i>
                <i class="fab fa-instagram social-icon"></i>
                <i class="fab fa-youtube social-icon"></i>
            </div>
        </section>

        <!-- About Section (Hidden by Default) -->
        <section id="about" class="hero-section hidden">
            <h1>About Us</h1>
            <p>Crop Prediction System is dedicated to helping farmers make informed decisions.</p>
        </section>

        <!-- Contact Section (Hidden by Default) -->
        <section id="contact" class="hero-section hidden">
            <h1>Contact Us</h1>
            <p>Email: support@croppredictionsystem.com</p>
            <p>Phone: +977-123456789</p>
        </section>

        <!-- Login Section -->
        <section id="login" class="form-container hidden">
            <h1>Login</h1>
               <form id="loginForm" action="login.php" method="POST">
                <input type="text" id="loginUsername" name="username" placeholder="Username" required>
                <!-- Password Input with Toggle -->
        <div class="password-wrapper">
            <input type="password" id="loginPassword" name="password" placeholder="Password" required>
            <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('loginPassword')"></i>
        </div>
                <button type="submit">Login Now</button>
                <div class="forgot-password">
                    <a href="#" onclick="showSection('forgotPassword')">Forgot Password?</a>
                </div>
            </form>
        </section>

  <!-- Forgot Password Section - Initial Form -->
<section id="forgotPassword" class="form-container <?php echo ($resetEmailSent || $resetSuccess || isset($_SESSION['reset_token'])) ? 'hidden' : ''; ?>">
    <h1>Reset Password</h1>
    <?php if ($resetError): ?>
        <p class="error"><?php echo htmlspecialchars($resetError); ?></p>
    <?php endif; ?>
    <form id="forgotPasswordForm" action="send_reset_link.php" method="POST">
        <input type="email" id="resetEmail" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
        <div class="back-to-login">
            <a href="#" onclick="showSection('login')">Back to Login</a>
        </div>
    </form>
</section>

<!-- Email Sent Confirmation -->
<section id="emailSent" class="form-container <?php echo $resetEmailSent ? '' : 'hidden'; ?>">
    <h1>Check Your Email</h1>
    <p>If the email address exists in our system, we've sent a password reset link.</p>
    <?php if ($resetError): ?>
        <p class="error"><?php echo htmlspecialchars($resetError); ?></p>
    <?php endif; ?>
    <div class="back-to-login">
        <a href="#" onclick="showSection('login')">Back to Login</a>
    </div>
</section>

<!-- New Password Form -->
<section id="newPassword" class="form-container <?php echo isset($_SESSION['reset_token']) ? '' : 'hidden'; ?>">
    <h1>Create New Password</h1>
    <?php if ($resetError): ?>
        <p class="error"><?php echo htmlspecialchars($resetError); ?></p>
    <?php endif; ?>
    <form id="newPasswordForm" action="process_reset.php" method="POST">
        <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
</section>

<!-- Success Message -->
<section id="resetSuccess" class="form-container <?php echo $resetSuccess ? '' : 'hidden'; ?>">
    <h1>Success!</h1>
    <p>Your password has been reset successfully.</p>
    <div class="back-to-home">
        <a href="index.php">Return to Home Page</a>
    </div>
</section>

<script>
    // Simple function to show/hide sections (for the onclick handlers)
    function showSection(sectionId) {
        document.querySelectorAll('.form-container').forEach(el => {
            el.classList.add('hidden');
        });
        document.getElementById(sectionId).classList.remove('hidden');
    }
</script>

        <!-- Sign Up Section -->
        <section id="signup" class="form-container hidden">
            <h1>Sign Up</h1>
            <form id="signupForm" action="signup.php" method="POST">
                <input type="text" id="signupName" name="username" placeholder="Full Name" required>
                <input type="email" id="signupEmail" name="email" placeholder="Email" required>
                
                        <!-- Password Input with Toggle -->
                        <div class="password-wrapper">
    <input type="password" id="signupPassword" name="password" placeholder="Password" required>
    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('signupPassword')"></i>
</div>
                <button type="submit">Signup Now</button>
            </form>
        </section>
        </div>
    
<!-- Subscription Section -->
<section class="subscription-section">
    <div class="subscription-container">
        <h2>Subscribe for Updates</h2>
        <p>Get the latest agricultural insights delivered to your inbox</p>
        <!-- Form to submit email -->
        <form action="subscribe.php" method="POST" id="subscriptionForm">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Subscribe</button>
        </form>
        <div id="subscriptionMessage"></div> <!-- Message display area -->
    </div>
</section>

    <script src="script.js"></script>

</body>
</html>
