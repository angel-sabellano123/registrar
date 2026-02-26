<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $db->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'student_id' => $user['student_id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'course' => $user['course'],
                'year_level' => $user['year_level'],
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Registrar Ticketing System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --accent: #0f3460;
            --gold: #e94560;
            --gold2: #f5a623;
            --white: #ffffff;
            --light: #f8f9ff;
            --gray: #8892a4;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--primary);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .auth-left {
            flex: 1;
            display: none;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            padding: 3rem;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 900px) { .auth-left { display: flex; } }

        .auth-left::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 30% 60%, rgba(233,69,96,0.2) 0%, transparent 55%);
        }

        .auth-grid {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .auth-brand {
            position: relative; z-index: 1;
            display: flex; align-items: center; gap: 12px;
            font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800;
            text-decoration: none; color: var(--white);
        }

        .brand-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }

        .auth-left-content { position: relative; z-index: 1; }
        .auth-left-content h2 {
            font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800;
            line-height: 1.15; margin-bottom: 1rem;
        }
        .auth-left-content h2 span { color: var(--gold); }
        .auth-left-content p { color: rgba(255,255,255,0.6); line-height: 1.6; max-width: 380px; }

        .auth-features {
            position: relative; z-index: 1;
            display: flex; flex-direction: column; gap: 1rem;
        }
        .auth-feature {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            padding: 0.9rem 1.2rem; border-radius: 12px;
        }
        .auth-feature i { color: var(--gold); font-size: 1rem; width: 20px; text-align: center; }
        .auth-feature span { font-size: 0.88rem; color: rgba(255,255,255,0.8); font-weight: 500; }

        .auth-right {
            width: 100%; max-width: 500px;
            display: flex; align-items: center; justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }
        @media (min-width: 900px) { .auth-right { min-height: 100vh; } }

        .auth-form-container {
            width: 100%; max-width: 420px;
        }

        .auth-header { margin-bottom: 2.5rem; }
        .auth-header h1 {
            font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800;
            margin-bottom: 0.4rem;
        }
        .auth-header p { color: var(--gray); font-size: 0.9rem; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: rgba(255,255,255,0.7); margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--gray); font-size: 0.9rem; pointer-events: none;
        }
        .input-wrap input {
            width: 100%; padding: 0.85rem 1rem 0.85rem 2.8rem;
            background: var(--input-bg); border: 1.5px solid var(--border);
            border-radius: 10px; color: var(--white); font-size: 0.9rem;
            font-family: 'DM Sans', sans-serif; transition: all 0.3s;
            outline: none;
        }
        .input-wrap input:focus {
            border-color: var(--gold); background: rgba(233,69,96,0.05);
            box-shadow: 0 0 0 3px rgba(233,69,96,0.1);
        }
        .input-wrap input::placeholder { color: rgba(255,255,255,0.25); }

        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: var(--gray); cursor: pointer; pointer-events: all;
            background: none; border: none; font-size: 0.9rem; transition: color 0.3s;
        }
        .toggle-pw:hover { color: var(--white); }

        .form-options {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem;
        }
        .checkbox-wrap {
            display: flex; align-items: center; gap: 8px;
            cursor: pointer; font-size: 0.85rem; color: var(--gray);
        }
        .checkbox-wrap input[type="checkbox"] { accent-color: var(--gold); cursor: pointer; }
        .form-link {
            font-size: 0.85rem; color: var(--gold); text-decoration: none;
            font-weight: 500; transition: opacity 0.3s;
        }
        .form-link:hover { opacity: 0.8; }

        .btn-submit {
            width: 100%; padding: 0.9rem;
            background: linear-gradient(135deg, var(--gold), #c73652);
            border: none; border-radius: 10px;
            color: var(--white); font-size: 1rem; font-weight: 700;
            font-family: 'Syne', sans-serif; cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(233,69,96,0.3);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(233,69,96,0.45);
        }
        .btn-submit:active { transform: translateY(0); }

        .auth-divider {
            display: flex; align-items: center; gap: 1rem;
            margin: 1.5rem 0; color: var(--gray); font-size: 0.8rem;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        .auth-switch {
            text-align: center; font-size: 0.88rem; color: var(--gray);
        }
        .auth-switch a { color: var(--gold); text-decoration: none; font-weight: 600; }
        .auth-switch a:hover { text-decoration: underline; }

        .alert {
            padding: 0.85rem 1rem; border-radius: 10px; font-size: 0.88rem;
            margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px;
        }
        .alert-error { background: rgba(233,69,96,0.12); border: 1px solid rgba(233,69,96,0.25); color: #ff6b87; }
        .alert-success { background: rgba(52,199,89,0.1); border: 1px solid rgba(52,199,89,0.25); color: #34c759; }

        .demo-hint {
            background: rgba(245,166,35,0.08); border: 1px solid rgba(245,166,35,0.2);
            border-radius: 10px; padding: 0.8rem 1rem; margin-bottom: 1.2rem;
            font-size: 0.8rem; color: rgba(255,255,255,0.5);
        }
        .demo-hint strong { color: var(--gold2); }

        /* Loading state */
        .btn-submit.loading { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body>

<div class="auth-left">
    <div class="auth-grid"></div>
    <a href="index.php" class="auth-brand">
        <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
        RTS Portal
    </a>
    <div class="auth-left-content">
        <h2>Welcome<br>Back to <span>RTS</span><br>Portal</h2>
        <p>Sign in to access your student dashboard, submit document requests, and track all your registrar transactions.</p>
    </div>
    <div class="auth-features">
        <div class="auth-feature">
            <i class="fas fa-file-alt"></i>
            <span>Submit document requests online</span>
        </div>
        <div class="auth-feature">
            <i class="fas fa-bell"></i>
            <span>Get real-time status notifications</span>
        </div>
        <div class="auth-feature">
            <i class="fas fa-history"></i>
            <span>View complete request history</span>
        </div>
    </div>
</div>

<div class="auth-right">
    <div class="auth-form-container">
        <div class="auth-header">
            <h1>Sign In</h1>
            <p>Enter your credentials to access your account.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <div class="demo-hint">
            <strong>Admin Demo:</strong> admin@rts.edu / admin123
        </div>

        <form method="POST" id="loginForm" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="your@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <span class="field-error" id="emailError" style="color:#ff6b87;font-size:0.78rem;margin-top:4px;display:none"></span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password','toggleIcon')">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <span class="field-error" id="pwError" style="color:#ff6b87;font-size:0.78rem;margin-top:4px;display:none"></span>
            </div>

            <div class="form-options">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="#" class="form-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-sign-in-alt" style="margin-right:8px"></i> Sign In
            </button>
        </form>

        <div class="auth-divider">or</div>

        <div class="auth-switch">
            Don't have an account? <a href="register.php">Create one here</a>
        </div>
    </div>
</div>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    let valid = true;
    const email = document.getElementById('email');
    const pw = document.getElementById('password');
    const emailErr = document.getElementById('emailError');
    const pwErr = document.getElementById('pwError');

    emailErr.style.display = 'none';
    pwErr.style.display = 'none';

    if (!email.value.trim()) {
        emailErr.textContent = 'Email is required.';
        emailErr.style.display = 'block';
        email.style.borderColor = '#e94560';
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        emailErr.textContent = 'Enter a valid email address.';
        emailErr.style.display = 'block';
        email.style.borderColor = '#e94560';
        valid = false;
    }

    if (!pw.value) {
        pwErr.textContent = 'Password is required.';
        pwErr.style.display = 'block';
        pw.style.borderColor = '#e94560';
        valid = false;
    }

    if (!valid) { e.preventDefault(); return; }

    document.getElementById('submitBtn').classList.add('loading');
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px"></i> Signing in...';
});
</script>
</body>
</html>