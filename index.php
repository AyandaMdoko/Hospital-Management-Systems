<?php require_once "config/dbconnect.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Page metadata and global setup -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Hospital Management System - a centralized platform for patient registration, medical records and healthcare staff management.">
  <title>Hospital Management System</title>

  <!-- Font loading -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Main stylesheet -->
  <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
  <!-- Main site header with navigation -->
  <header class="site-header">
    <div class="container nav-container">
      <a class="brand" href="#home" aria-label="Hospital Management System home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 48 48" role="img"><path d="M24 42S6 31.2 6 17.8C6 10.7 14.8 6.4 21 12l3 3 3-3c6.2-5.6 15-1.3 15 5.8C42 31.2 24 42 24 42Z"/><path class="pulse" d="M9 24h9l4-8 6.2 16 4.1-8H39"/></svg>
        </span>
        <span class="brand-name">Hospital Management<span>System</span></span>
      </a>

      <button class="menu-toggle" id="menuToggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="primaryNav">
        <span></span><span></span><span></span>
      </button>

      <nav class="primary-nav" id="primaryNav" aria-label="Main navigation">
        <a class="nav-link active" href="#home">Home</a>
        <a class="nav-link" href="#about">About</a>
        <a class="nav-link" href="#features">Features</a>
        <a class="nav-link" href="#contact">Contact</a>
        <a class="button button-small" href="user-login.php">Login <span aria-hidden="true">→</span></a>
      </nav>
    </div>
  </header>

  <main>
    <!-- Hero section -->
    <section class="hero section" id="home">
      <div class="container hero-grid">
        <div class="hero-copy">
          <span class="eyebrow"><span class="eyebrow-dot"></span> Healthcare management solution</span>
          <h1>Simplifying healthcare through <span>better information management.</span></h1>
          <p class="hero-description">A centralized platform designed to improve patient registration, organize medical records and support healthcare professionals.</p>
          <div class="hero-actions">
            <a class="button" href="user-login.php        ">Access System <span aria-hidden="true">→</span></a>
            <a class="button button-outline" href="#features">Explore Features</a>
          </div>
          <div class="hero-note">
            <span class="note-icon" aria-hidden="true">✓</span>
            Secure access for authorized healthcare personnel
          </div>
        </div>

        <div class="hero-visual" aria-label="Modern hospital reception area">
          <div class="visual-image"></div>
          <div class="visual-shade"></div>
          <div class="visual-caption">
            <span class="caption-icon" aria-hidden="true">✚</span>
            <span><strong>Connected care starts here</strong><small>Organized information. Supported teams.</small></span>
          </div>
          <div class="floating-card">
            <span class="floating-icon" aria-hidden="true">▤</span>
            <span><strong>Patient information</strong><small>Organized in one place</small></span>
            <span class="status-dot" aria-label="Available"></span>
          </div>
          <div class="decor decor-one"></div>
          <div class="decor decor-two"></div>
        </div>
      </div>
    </section>

    <!-- About / system overview -->
    <section class="intro section" id="about">
      <div class="container intro-content">
        <span class="eyebrow eyebrow-centered">About the system</span>
        <h2>More organized information.<br><span>More time for patient care.</span></h2>
        <p>The Hospital Management System is designed to help healthcare facilities manage essential patient and staff information through one centralized digital platform.</p>
      </div>
    </section>

    <!-- Core system features -->
    <section class="features section" id="features">
      <div class="container">
        <div class="section-heading">
          <div>
            <span class="eyebrow">Core capabilities</span>
            <h2>What the system offers</h2>
          </div>
          <p>Practical tools designed to support everyday hospital information management.</p>
        </div>

        <div class="feature-grid">
          <article class="feature-card">
            <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM19 8v6m3-3h-6"/></svg></div>
            <h3>Patient Registration</h3>
            <p>Register patients and maintain their essential personal information for easier retrieval.</p>
          </article>
          <article class="feature-card">
            <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h8"/></svg></div>
            <h3>Medical Records</h3>
            <p>Access patient histories, consultation notes, diagnoses and prescribed treatment.</p>
          </article>
          <article class="feature-card">
            <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1ZM12 12v5m-2.5-2.5h5"/></svg></div>
            <h3>Clinical Management</h3>
            <p>Support doctors and nurses with consultation documentation and nursing observations.</p>
          </article>
          <article class="feature-card">
            <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6m-3-3h6"/></svg></div>
            <h3>Staff Management</h3>
            <p>Maintain basic staff information for doctors and nurses within the facility.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- Call-to-action section -->
    <section class="access-section section" id="contact">
      <div class="container">
        <div class="access-panel">
          <div class="access-symbol" aria-hidden="true">✚</div>
          <div class="access-copy">
            <span class="eyebrow eyebrow-light">Authorized personnel only</span>
            <h2>Better information.<br>Better healthcare.</h2>
            <p>Access the system to manage patient information and support your healthcare team's daily work.</p>
          </div>
          <a class="button button-white" href="user-login.php">Access the System <span aria-hidden="true">→</span></a>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="container footer-content">
      <a class="brand brand-footer" href="#home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 48 48"><path d="M24 42S6 31.2 6 17.8C6 10.7 14.8 6.4 21 12l3 3 3-3c6.2-5.6 15-1.3 15 5.8C42 31.2 24 42 24 42Z"/><path class="pulse" d="M9 24h9l4-8 6.2 16 4.1-8H39"/></svg>
        </span>
        <span class="brand-name">Hospital Management<span>System</span></span>
      </a>
      <p>A digital solution for organized healthcare information management.</p>
      <span class="copyright">© <span id="year">2026</span> Hospital Management System. All rights reserved.</span>
    </div>
  </footer>

  <script src="assets/js/script.js"></script>
</body>
</html>
