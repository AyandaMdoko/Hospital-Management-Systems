<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/authentication-styles.css">
    <title>Forgot Password | Hospital Management System</title>
</head>
<body class="forgot-password-page">
    <!-- Header with return-to-login navigation -->
    <header class="login-header">
        <div class="login-header-content">
            <a class="login-brand" href="index.html" aria-label="Hospital Management System home">
                <span class="login-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 48 48"><path d="M24 42S6 31.2 6 17.8C6 10.7 14.8 6.4 21 12l3 3 3-3c6.2-5.6 15-1.3 15 5.8C42 31.2 24 42 24 42Z"/><path class="pulse" d="M9 24h9l4-8 6.2 16 4.1-8H39"/></svg>
                </span>
                <span class="login-brand-name">Hospital Management<span>System</span></span>
            </a>
            <a class="back-home" href="user-login.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Login</a>
        </div>
    </header>

    <main class="forgot-password-main">
        <section class="forgot-password-card" aria-labelledby="forgot-password-title">
            <div class="forgot-password-icon" aria-hidden="true">
                <i class="bi bi-key"></i>
            </div>
            <h1 id="forgot-password-title">Forgot your password?</h1>
            <p class="forgot-password-description">Don't worry. Enter your registered username and we'll help you reset your password.</p>

            <form class="forgot-password-form" action="#" method="post">
                <label for="reset-email">Registered username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
                <button type="submit" class="reset-link-button">Send Reset Link <span aria-hidden="true">→</span></button>
            </form>

            <a class="return-to-login" href="user-login.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Return to Login</a>
        </section>
    </main>
</body>
</html>
