<?php
require_once 'core/session.php'; 

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials': $error = "Invalid credentials. Please check your email and password."; break;
        case 'access_denied':       $error = "Access Denied: Insufficient privileges."; break;
        case 'unauthorized':        $error = "Please login to access this area."; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Library Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        /* ══════════════════════════════════════
           FULL-SCREEN BACKGROUND
        ══════════════════════════════════════ */
        .bg-scene {
            position: fixed;
            inset: 0;
            background-image: url('assets/img/library_bg.jpg');
            background-size: cover;
            background-position: center;
            animation: slowZoom 20s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes slowZoom {
            from { transform: scale(1.0); }
            to   { transform: scale(1.08); }
        }

        /* Gradient overlay — darker on the right to help card readability */
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(
                105deg,
                rgba(5, 15, 45, 0.30) 0%,
                rgba(5, 15, 45, 0.45) 50%,
                rgba(5, 15, 45, 0.72) 100%
            );
            z-index: 1;
        }

        /* Floating particles */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            overflow: hidden;
        }
        .particle {
            position: absolute;
            width: 3px; height: 3px;
            background: rgba(255,255,255,0.45);
            border-radius: 50%;
            animation: float linear infinite;
        }

        @keyframes float {
            0%   { transform: translateY(110vh) translateX(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 0.6; }
            100% { transform: translateY(-10vh) translateX(40px); opacity: 0; }
        }

        /* ══════════════════════════════════════
           BOTTOM LEFT QUOTE
        ══════════════════════════════════════ */
        .page-quote {
            position: fixed;
            bottom: 2.5rem;
            left: 3rem;
            z-index: 10;
            max-width: 380px;
            animation: fadeUp 1.2s ease both;
        }
        .page-quote blockquote {
            font-size: 1.15rem;
            font-weight: 600;
            color: rgba(255,255,255,0.92);
            line-height: 1.55;
            text-shadow: 0 2px 16px rgba(0,0,0,0.5);
            margin-bottom: 0.45rem;
            border: none;
            padding: 0;
        }
        .page-quote cite {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.55);
            font-style: normal;
            font-weight: 500;
        }

        /* Top-left system name */
        .page-brand {
            position: fixed;
            top: 2rem;
            left: 2.5rem;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            animation: fadeDown 1s ease both;
        }
        .page-brand .brand-icon {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #fff;
        }
        .page-brand .brand-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .page-brand .brand-sub {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.6);
            display: block;
            font-weight: 400;
        }

        /* ══════════════════════════════════════
           FLOATING LOGIN CARD — RIGHT SIDE
        ══════════════════════════════════════ */
        .login-panel {
            position: fixed;
            top: 50%;
            right: 4rem;
            transform: translateY(-50%);
            width: 360px;
            z-index: 20;
            animation: slideInRight 0.75s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateY(-50%) translateX(60px); }
            to   { opacity: 1; transform: translateY(-50%) translateX(0); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 20px;
            box-shadow:
                0 32px 64px rgba(0,0,0,0.28),
                0 8px 24px rgba(0,0,0,0.16),
                inset 0 1px 0 rgba(255,255,255,0.8);
            overflow: hidden;
        }

        /* Blue accent bar top of card */
        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, #1246b5, #3b82f6, #60a5fa);
            border-radius: 20px 20px 0 0;
        }

        .card-body {
            padding: 1.75rem 2rem 2rem;
        }

        /* Card heading */
        .card-heading { margin-bottom: 1.5rem; }
        .card-heading h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }
        .card-heading p {
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 400;
        }

        /* Error box */
        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-left: 3px solid #ef4444;
            border-radius: 8px;
            color: #be123c;
            font-size: 0.81rem;
            padding: 0.6rem 0.9rem;
            margin-bottom: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        /* Form Fields */
        .field { margin-bottom: 1rem; }
        .field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.35rem;
        }
        .input-box {
            position: relative;
        }
        .input-box .ico {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.2s;
        }
        .input-box input {
            width: 100%;
            padding: 0.65rem 2.5rem 0.65rem 2.35rem;
            background: #f8faff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }
        .input-box input:focus {
            border-color: #1a56db;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.1);
        }
        .input-box input:focus ~ .ico { color: #1a56db; }
        .input-box input::placeholder { color: #b0bec5; }

        /* Eye toggle */
        .btn-eye {
            position: absolute;
            right: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 0.95rem;
            padding: 0;
            transition: color 0.2s;
        }
        .btn-eye:hover { color: #1a56db; }

        /* Remember row */
        .check-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            margin-top: 0.25rem;
        }
        .check-row input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: #1a56db;
            cursor: pointer;
        }
        .check-row label {
            font-size: 0.8rem;
            color: #64748b;
            cursor: pointer;
        }

        /* Sign-in button */
        .btn-signin {
            width: 100%;
            padding: 0.78rem;
            background: linear-gradient(135deg, #1246b5 0%, #1a56db 60%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(26,86,219,0.4);
            transition: all 0.22s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        /* Shimmer effect on button */
        .btn-signin::after {
            content: '';
            position: absolute;
            top: -50%; left: -75%;
            width: 50%; height: 200%;
            background: rgba(255,255,255,0.18);
            transform: skewX(-20deg);
            transition: left 0.5s ease;
        }
        .btn-signin:hover::after { left: 130%; }
        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(26,86,219,0.5);
        }
        .btn-signin:active { transform: translateY(0); }

        /* Divider */
        .hr-line {
            border: none;
            border-top: 1px solid #e8edf5;
            margin: 1.25rem 0 1rem;
        }

        /* Register link */
        .reg-link {
            text-align: center;
            font-size: 0.82rem;
            color: #94a3b8;
        }
        .reg-link a {
            color: #1a56db;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .reg-link a:hover { color: #1246b5; text-decoration: underline; }

        /* Stats strip at the bottom of the card */
        .card-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border-top: 1px solid #e8edf5;
            text-align: center;
        }
        .card-stats .stat {
            padding: 0.75rem 0.5rem;
            border-right: 1px solid #e8edf5;
        }
        .card-stats .stat:last-child { border-right: none; }
        .card-stats .stat-num {
            font-size: 1rem;
            font-weight: 800;
            color: #1a56db;
            display: block;
        }
        .card-stats .stat-lbl {
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 560px) {
            .login-panel { right: 1rem; left: 1rem; width: auto; top: 50%; }
            .page-quote, .page-brand { display: none; }
        }
    </style>
</head>
<body>

    <!-- Full-screen background -->
    <div class="bg-scene"></div>
    <div class="bg-overlay"></div>

    <!-- Floating particles -->
    <div class="particles" id="particles"></div>

    <!-- Top-left branding -->
    <div class="page-brand">
        <div class="brand-icon"><i class="bi bi-book-half"></i></div>
        <div>
            <span class="brand-name">Library Management System</span>
            <span class="brand-sub">Digital Library Portal</span>
        </div>
    </div>

    <!-- Bottom-left quote -->
    <div class="page-quote">
        <blockquote>"A library is not a luxury,<br>but one of the necessities of life."</blockquote>
        <cite>— Henry Ward Beecher</cite>
    </div>

    <!-- ═══ Floating Login Card (Right Side) ═══ -->
    <div class="login-panel">
        <div class="glass-card">
            <div class="card-accent-bar"></div>

            <div class="card-body">
                <!-- Heading -->
                <div class="card-heading">
                    <h2>Welcome back 👋</h2>
                    <p>Sign in to access your library portal</p>
                </div>

                <!-- Error -->
                <?php if($error): ?>
                    <div class="error-box">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="login_process.php" method="post" autocomplete="on">

                    <div class="field">
                        <label for="loginId">Email / CNIC / Reg No</label>
                        <div class="input-box">
                            <input type="text" id="loginId" name="identifier"
                                   placeholder="Enter your email or ID" required autocomplete="username">
                            <i class="bi bi-person ico"></i>
                        </div>
                    </div>

                    <div class="field">
                        <label for="loginPass">Password</label>
                        <div class="input-box">
                            <input type="password" id="loginPass" name="password"
                                   placeholder="••••••••" required autocomplete="current-password">
                            <i class="bi bi-lock ico"></i>
                            <button type="button" class="btn-eye" id="togglePw">
                                <i class="bi bi-eye-slash" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="check-row">
                        <input type="checkbox" id="rememberMe" name="remember">
                        <label for="rememberMe">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn-signin">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In to Portal
                    </button>
                </form>

                <hr class="hr-line">
                <div class="reg-link">
                    New here? <a href="register.php">Create an account →</a>
                </div>
            </div>

            <!-- Mini Stats Strip -->
            <div class="card-stats">
                <div class="stat">
                    <span class="stat-num">10K+</span>
                    <span class="stat-lbl">Books</span>
                </div>
                <div class="stat">
                    <span class="stat-num">500+</span>
                    <span class="stat-lbl">Members</span>
                </div>
                <div class="stat">
                    <span class="stat-num">24/7</span>
                    <span class="stat-lbl">Access</span>
                </div>
            </div>

        </div><!-- /glass-card -->
    </div><!-- /login-panel -->

    <script>
    // ── Floating particles ──
    (function() {
        const container = document.getElementById('particles');
        const count = 28;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 3 + 1.5;
            p.style.cssText = `
                left: ${Math.random() * 100}%;
                width: ${size}px;
                height: ${size}px;
                opacity: ${Math.random() * 0.5 + 0.1};
                animation-duration: ${Math.random() * 18 + 12}s;
                animation-delay: ${Math.random() * -20}s;
            `;
            container.appendChild(p);
        }
    })();

    // ── Password toggle ──
    document.getElementById('togglePw').addEventListener('click', function() {
        const pw = document.getElementById('loginPass');
        const ic = document.getElementById('eyeIcon');
        const isHidden = pw.type === 'password';
        pw.type = isHidden ? 'text' : 'password';
        ic.className = isHidden ? 'bi bi-eye' : 'bi bi-eye-slash';
    });

    // ── Input focus: icon color sync ──
    document.querySelectorAll('.input-box input').forEach(input => {
        const ico = input.parentElement.querySelector('.ico');
        if (!ico) return;
        input.addEventListener('focus',  () => ico.style.color = '#1a56db');
        input.addEventListener('blur',   () => ico.style.color = '#94a3b8');
    });
    </script>
</body>
</html>