<?php
session_start();
require_once 'core/auth.php';
$auth = new Auth($pdo);
$roles = $auth->getPublicRoles();

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'            => trim($_POST['name']),
        'email'           => trim($_POST['email']),
        'password'        => $_POST['password'],
        'role'            => $_POST['role'],
        'identity_no'     => trim($_POST['identity_no']),
        'registration_no' => trim($_POST['registration_no'])
    ];

    if ($data['password'] !== $_POST['retype_password']) {
        $msg = "Passwords do not match!";
        $msgType = "danger";
    } else {
        $result = $auth->register($data);
        if ($result === true) {
            $msg = "Registration successful! <a href='login.php' style='color:#065f46; font-weight:700;'>Login now →</a>";
            $msgType = "success";
        } else {
            $msg = $result;
            $msgType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Library Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f2158 0%, #1a56db 50%, #3b82f6 100%);
            position: relative;
            overflow-x: hidden;
            padding: 2rem 1rem;
        }
        body::before {
            content: '';
            position: fixed;
            width: 500px; height: 500px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            top: -150px; right: -100px;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            width: 350px; height: 350px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            bottom: -100px; left: -80px;
            pointer-events: none;
        }

        .register-wrapper {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
        }

        .register-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, #1246b5, #1a56db);
            padding: 1.75rem 2rem;
            text-align: center;
            color: white;
        }
        .register-header .logo-icon {
            width: 55px; height: 55px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
        }
        .register-header h1 { font-size: 1.25rem; font-weight: 800; color: #fff; margin-bottom: 0.15rem; }
        .register-header p  { font-size: 0.82rem; color: rgba(255,255,255,0.75); margin: 0; }

        .register-body { padding: 1.75rem 2rem; }

        .form-label { font-weight: 600; font-size: 0.82rem; color: #334155; margin-bottom: 0.35rem; }
        .form-control, .form-select {
            border: 1.5px solid #dbeafe;
            border-radius: 10px;
            padding: 0.6rem 0.9rem;
            font-size: 0.88rem;
            color: #0f172a;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.12);
            outline: none;
        }
        .input-group-text {
            background: #eff6ff;
            border: 1.5px solid #dbeafe;
            color: #1a56db;
        }
        .input-group > .form-control,
        .input-group > .form-select { border-right: none; border-radius: 10px 0 0 10px !important; }
        .input-group > .input-group-text { border-left: none; border-radius: 0 10px 10px 0 !important; }

        .btn-primary {
            background: linear-gradient(135deg, #1246b5, #1a56db);
            border: none;
            border-radius: 10px;
            padding: 0.72rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            box-shadow: 0 4px 14px rgba(26,86,219,0.35);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0f3d9e, #1246b5);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(26,86,219,0.45);
        }

        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 10px; color: #065f46; font-size: 0.88rem; padding: 0.65rem 1rem; }
        .alert-danger  { background: #fee2e2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 0.88rem; padding: 0.65rem 1rem; }

        .register-footer { text-align: center; padding: 0 2rem 1.5rem; }
        .register-footer a { color: #1a56db; font-weight: 600; font-size: 0.88rem; text-decoration: none; }
        .register-footer a:hover { color: #1246b5; text-decoration: underline; }

        .row-cols-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="logo-icon"><i class="bi bi-person-plus"></i></div>
                <h1>Create an Account</h1>
                <p>Library Management System — New Membership</p>
            </div>

            <!-- Body -->
            <div class="register-body">
                <?php if($msg): ?>
                    <div class="alert-<?= $msgType ?> mb-3">
                        <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-1"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="regName">Full Name</label>
                        <div class="input-group">
                            <input type="text" name="name" class="form-control" id="regName" placeholder="Your full name" required>
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="regEmail">Email Address</label>
                        <div class="input-group">
                            <input type="email" name="email" class="form-control" id="regEmail" placeholder="you@example.com" required>
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;" class="mb-3">
                        <div>
                            <label class="form-label" for="regCnic">CNIC / Identity No</label>
                            <div class="input-group">
                                <input type="text" name="identity_no" class="form-control" id="regCnic" placeholder="00000-0000000-0">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label" for="regNo">Registration No</label>
                            <div class="input-group">
                                <input type="text" name="registration_no" class="form-control" id="regNo" placeholder="e.g. STU-001">
                                <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="regRole">I am a...</label>
                        <div class="input-group">
                            <select name="role" class="form-select" id="regRole" required>
                                <option value="" disabled selected>Select your role</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['role_key'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;" class="mb-4">
                        <div>
                            <label class="form-label" for="regPass">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" id="regPass" placeholder="Min 6 characters" required>
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label" for="regRetype">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" name="retype_password" class="form-control" id="regRetype" placeholder="Repeat password" required>
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-check me-1"></i> Create Account
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="register-footer">
                <span style="font-size:0.85rem; color:#94a3b8;">Already have an account?</span>
                <a href="login.php" class="ms-1">Sign in here</a>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>