<?php
session_start();
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

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
    
    <title>User Login | Hospital Management System</title>
</head>
<body>
    <!-- Header and return-home navigation -->
    <header class="login-header">
        <div class="login-header-content">
            <a class="login-brand" href="index.html" aria-label="Hospital Management System home">
                <span class="login-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 48 48"><path d="M24 42S6 31.2 6 17.8C6 10.7 14.8 6.4 21 12l3 3 3-3c6.2-5.6 15-1.3 15 5.8C42 31.2 24 42 24 42Z"/><path class="pulse" d="M9 24h9l4-8 6.2 16 4.1-8H39"/></svg>
                </span>
                <span class="login-brand-name">Hospital Management<span>System</span></span>
            </a>
            <a class="back-home" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home</a>
        </div>
    </header>

    <!-- Welcome message shown above the sign-in form -->
    <section class="login-welcome" aria-labelledby="welcome-title">
        <span class="welcome-badge">Authorized personnel only</span>
        <h1 id="welcome-title">Welcome back.</h1>
        <p>Sign in with your assigned account to access the Hospital Management System.</p>
    </section>

    <!-- Login section -->
    <section class="login-form">
        <div class="login-container">
            <div class="logo">
                <h2><i class="bi bi-shield-lock"></i>HMS | User Login</h2>
            </div>

            <div class="login-box">

            <?php if (!empty($error)): ?>

                <div class="login-error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

               <form action="/config/login.php" id="login" method="POST" name="login">
                    <fieldset>
                        <legend><i class="bi bi-box-arrow-in-right"></i> Sign-In</legend>

                        <p>Enter your username and password to continue.</p>

                        <div class="form-group">
                            <input type="text" name="username" id="username" class="username" placeholder="Username" required>
                            <div class="password-field">
                                <input type="password" name="password" id="password" class="password" placeholder="Password" required>
                               
                                    <button type="button" class="eye-btn" onclick="togglePass('password', this)" aria-label="Show password">
                                        <img src="assets/icons/eye close.png" alt="Show password">
                                    </button>                            
                                </div>

                                <a href="forgot-password.php" class="forgot-pass"><i class="bi bi-key"></i> Forgot password?</a>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="login-button" name="submit"><i class="bi bi-unlock"></i> Login</button>
                        </div>

                    </fieldset>
                </form>

                <div class="copyright">
                    <span class="text-bold text-uppercase">Hospital Management System</span>
                </div>
            </div>
        </div>
    </section>
    <script src="assets/js/script.js"></script>
</body>
</html>
