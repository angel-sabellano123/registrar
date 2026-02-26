<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $student_id  = trim($_POST['student_id'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $course      = trim($_POST['course'] ?? '');
    $year_level  = trim($_POST['year_level'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm_pw  = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($first_name))   $errors['first_name'] = 'First name is required.';
    if (empty($last_name))    $errors['last_name'] = 'Last name is required.';
    if (empty($student_id))   $errors['student_id'] = 'Student ID is required.';
    if (empty($email))        $errors['email'] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (empty($course))       $errors['course'] = 'Course is required.';
    if (empty($year_level))   $errors['year_level'] = 'Year level is required.';
    if (empty($password))     $errors['password'] = 'Password is required.';
    elseif (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password))
        $errors['password'] = 'Password must contain letters and numbers.';
    if ($password !== $confirm_pw) $errors['confirm_password'] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();

        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR student_id = ?");
        $stmt->bind_param("ss", $email, $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $errors['email'] = 'An account with this email or student ID already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (student_id, first_name, last_name, email, password, course, year_level) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param("sssssss", $student_id, $first_name, $last_name, $email, $hashed, $course, $year_level);
            if ($ins->execute()) {
                $_SESSION['register_success'] = 'Account created successfully! Please sign in.';
                header('Location: login.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $ins->close();
        }
        $stmt->close();
        $db->close();
    }
}

$old = $_POST ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Registrar Ticketing System</title>
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
        @media (min-width: 1000px) { .auth-left { display: flex; } }

        .auth-left::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 70% 40%, rgba(245,166,35,0.15) 0%, transparent 55%);
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
            font-family: 'Syne', sans-serif; font-size: 2.3rem; font-weight: 800;
            line-height: 1.15; margin-bottom: 1rem;
        }
        .auth-left-content h2 span { color: var(--gold2); }
        .auth-left-content p { color: rgba(255,255,255,0.6); line-height: 1.6; max-width: 380px; }

        .step-list {
            position: relative; z-index: 1;
            display: flex; flex-direction: column; gap: 1.2rem;
        }
        .step {
            display: flex; align-items: flex-start; gap: 14px;
        }
        .step-num {
            width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: 0.85rem;
        }
        .step-info h4 { font-size: 0.9rem; font-weight: 600; margin-bottom: 2px; }
        .step-info p { font-size: 0.8rem; color: rgba(255,255,255,0.5); }

        .auth-right {
            width: 100%; max-width: 560px;
            display: flex; align-items: flex-start; justify-content: center;
            padding: 2.5rem 2rem;
            overflow-y: auto;
            min-height: 100vh;
        }

        .auth-form-container { width: 100%; max-width: 460px; padding: 1rem 0 3rem; }

        .auth-header { margin-bottom: 2rem; }
        .auth-header h1 {
            font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800;
            margin-bottom: 0.4rem;
        }
        .auth-header p { color: var(--gray); font-size: 0.9rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: rgba(255,255,255,0.7); margin-bottom: 0.5rem; letter-spacing: 0.3px;
        }

        .input-wrap { position: relative; }
        .input-wrap i.icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--gray); font-size: 0.9rem; pointer-events: none;
        }
        .input-wrap input, .input-wrap select {
            width: 100%; padding: 0.8rem 1rem 0.8rem 2.8rem;
            background: var(--input-bg); border: 1.5px solid var(--border);
            border-radius: 10px; color: var(--white); font-size: 0.88rem;
            font-family: 'DM Sans', sans-serif; transition: all 0.3s; outline: none;
        }
        .input-wrap select { cursor: pointer; }
        .input-wrap select option { background: var(--secondary); color: var(--white); }
        .input-wrap input:focus, .input-wrap select:focus {
            border-color: var(--gold); background: rgba(233,69,96,0.05);
            box-shadow: 0 0 0 3px rgba(233,69,96,0.1);
        }
        .input-wrap input::placeholder { color: rgba(255,255,255,0.25); }
        .input-wrap input.error, .input-wrap select.error { border-color: #e94560; }

        .field-error { color: #ff6b87; font-size: 0.78rem; margin-top: 4px; display: block; }

        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: var(--gray); cursor: pointer;
            background: none; border: none; font-size: 0.9rem; transition: color 0.3s;
        }
        .toggle-pw:hover { color: var(--white); }

        .password-strength { margin-top: 6px; }
        .strength-bar {
            height: 4px; border-radius: 4px; background: rgba(255,255,255,0.08);
            overflow: hidden; margin-bottom: 4px;
        }
        .strength-fill { height: 100%; border-radius: 4px; transition: all 0.3s; width: 0; }
        .strength-text { font-size: 0.75rem; color: var(--gray); }

        .terms-check {
            display: flex; align-items: flex-start; gap: 10px; margin-bottom: 1.5rem;
        }
        .terms-check input { accent-color: var(--gold); margin-top: 3px; cursor: pointer; flex-shrink: 0; }
        .terms-check label { font-size: 0.83rem; color: var(--gray); line-height: 1.5; cursor: pointer; }
        .terms-check a { color: var(--gold); text-decoration: none; }

        .btn-submit {
            width: 100%; padding: 0.9rem;
            background: linear-gradient(135deg, var(--gold), #c73652);
            border: none; border-radius: 10px;
            color: var(--white); font-size: 1rem; font-weight: 700;
            font-family: 'Syne', sans-serif; cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(233,69,96,0.3);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(233,69,96,0.45); }

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
        <h2>Start Your<br><span>Digital</span><br>Journey</h2>
        <p>Create your student account and gain instant access to all registrar services online — fast, easy, and paperless.</p>
    </div>
    <div class="step-list">
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-info">
                <h4>Create Your Account</h4>
                <p>Fill in your student details to get started</p>
            </div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-info">
                <h4>Submit Your Request</h4>
                <p>Choose the document type and fill out the form</p>
            </div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-info">
                <h4>Track & Receive</h4>
                <p>Get notified when your document is ready</p>
            </div>
        </div>
    </div>
</div>

<div class="auth-right">
    <div class="auth-form-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Register to access registrar services online.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="regForm" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <div class="input-wrap">
                        <i class="fas fa-user icon"></i>
                        <input type="text" name="first_name" placeholder="Juan"
                               value="<?= htmlspecialchars($old['first_name'] ?? '') ?>"
                               class="<?= isset($errors['first_name']) ? 'error' : '' ?>">
                    </div>
                    <?php if (isset($errors['first_name'])): ?>
                    <span class="field-error"><?= $errors['first_name'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <div class="input-wrap">
                        <i class="fas fa-user icon"></i>
                        <input type="text" name="last_name" placeholder="dela Cruz"
                               value="<?= htmlspecialchars($old['last_name'] ?? '') ?>"
                               class="<?= isset($errors['last_name']) ? 'error' : '' ?>">
                    </div>
                    <?php if (isset($errors['last_name'])): ?>
                    <span class="field-error"><?= $errors['last_name'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Student ID *</label>
                <div class="input-wrap">
                    <i class="fas fa-id-card icon"></i>
                    <input type="text" name="student_id" placeholder="e.g. 2024-00001"
                           value="<?= htmlspecialchars($old['student_id'] ?? '') ?>"
                           class="<?= isset($errors['student_id']) ? 'error' : '' ?>">
                </div>
                <?php if (isset($errors['student_id'])): ?>
                <span class="field-error"><?= $errors['student_id'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" name="email" placeholder="your@school.edu"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           class="<?= isset($errors['email']) ? 'error' : '' ?>">
                </div>
                <?php if (isset($errors['email'])): ?>
                <span class="field-error"><?= $errors['email'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Course / Program *</label>
                    <div class="input-wrap">
                        <i class="fas fa-book icon"></i>
                        <select name="course" class="<?= isset($errors['course']) ? 'error' : '' ?>">
                            <option value="">Select Course</option>
                            <?php
                            $courses = ['BS Computer Science','BS Information Technology','BS Engineering','BS Education',
                                        'BS Nursing','BS Accountancy','BS Business Administration','AB Communication',
                                        'BS Psychology','BS Architecture','BS Criminal Justice','Other'];
                            foreach ($courses as $c) {
                                $sel = (($old['course'] ?? '') === $c) ? 'selected' : '';
                                echo "<option value=\"$c\" $sel>$c</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php if (isset($errors['course'])): ?>
                    <span class="field-error"><?= $errors['course'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Year Level *</label>
                    <div class="input-wrap">
                        <i class="fas fa-layer-group icon"></i>
                        <select name="year_level" class="<?= isset($errors['year_level']) ? 'error' : '' ?>">
                            <option value="">Select Year</option>
                            <?php
                            $years = ['1st Year','2nd Year','3rd Year','4th Year','5th Year','Graduate','Alumni'];
                            foreach ($years as $y) {
                                $sel = (($old['year_level'] ?? '') === $y) ? 'selected' : '';
                                echo "<option value=\"$y\" $sel>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <?php if (isset($errors['year_level'])): ?>
                    <span class="field-error"><?= $errors['year_level'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="password" name="password" placeholder="Min. 8 chars with letters & numbers"
                           class="<?= isset($errors['password']) ? 'error' : '' ?>" oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('password','pwIcon')">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                <span class="field-error"><?= $errors['password'] ?></span>
                <?php endif; ?>
                <div class="password-strength">
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-text" id="strengthText">Enter a password</div>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password"
                           class="<?= isset($errors['confirm_password']) ? 'error' : '' ?>">
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','cpwIcon')">
                        <i class="fas fa-eye" id="cpwIcon"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                <span class="field-error"><?= $errors['confirm_password'] ?></span>
                <?php endif; ?>
            </div>

            <div class="terms-check">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> of the RTS Portal.</label>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-user-plus" style="margin-right:8px"></i> Create Account
            </button>
        </form>

        <div class="auth-divider">or</div>

        <div class="auth-switch">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</div>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

function checkStrength(pw) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    if (pw.length >= 12) score++;

    const levels = [
        {pct:'0%', color:'transparent', label:''},
        {pct:'25%', color:'#e94560', label:'Weak'},
        {pct:'50%', color:'#f5a623', label:'Fair'},
        {pct:'75%', color:'#007aff', label:'Good'},
        {pct:'100%', color:'#34c759', label:'Strong'},
    ];
    const l = levels[Math.min(score, 4)];
    fill.style.width = l.pct;
    fill.style.background = l.color;
    text.textContent = l.label ? `Password strength: ${l.label}` : 'Enter a password';
    text.style.color = l.color || '#8892a4';
}

document.getElementById('regForm').addEventListener('submit', function(e) {
    const terms = document.getElementById('terms');
    if (!terms.checked) {
        e.preventDefault();
        alert('Please agree to the Terms of Service to continue.');
        return;
    }
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px"></i> Creating Account...';
    document.getElementById('submitBtn').disabled = true;
});
</script>
</body>
</html>