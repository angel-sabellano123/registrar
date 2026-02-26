
<?php
session_start();

// Redirect logged in users to dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Ticketing System</title>
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
            --card-bg: rgba(255,255,255,0.04);
            --border: rgba(255,255,255,0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--primary);
            color: var(--white);
            overflow-x: hidden;
        }

        /* NAVBAR */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.2rem 5%;
            background: rgba(26,26,46,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .nav-logo {
            display: flex; align-items: center; gap: 12px;
            font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800;
            color: var(--white); text-decoration: none;
        }

        .nav-logo .logo-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: white;
        }

        .nav-links { display: flex; align-items: center; gap: 2rem; }
        .nav-links a { color: var(--gray); text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: var(--white); }

        .nav-cta { display: flex; gap: 0.8rem; }
        .btn-outline {
            padding: 0.6rem 1.4rem; border: 1.5px solid rgba(255,255,255,0.2);
            border-radius: 8px; color: var(--white); text-decoration: none;
            font-size: 0.88rem; font-weight: 500; transition: all 0.3s;
            background: transparent;
        }
        .btn-outline:hover { border-color: var(--gold); color: var(--gold); }

        .btn-primary {
            padding: 0.6rem 1.4rem;
            background: linear-gradient(135deg, var(--gold), #c73652);
            border-radius: 8px; color: var(--white); text-decoration: none;
            font-size: 0.88rem; font-weight: 600; transition: all 0.3s;
            border: none; cursor: pointer;
            box-shadow: 0 4px 20px rgba(233,69,96,0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(233,69,96,0.45); }

        /* HERO */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center;
            padding: 8rem 5% 4rem;
            position: relative; overflow: hidden;
        }

        .hero-bg {
            position: absolute; inset: 0; z-index: 0;
            background: radial-gradient(ellipse at 70% 50%, rgba(15,52,96,0.6) 0%, transparent 60%),
                        radial-gradient(ellipse at 20% 80%, rgba(233,69,96,0.12) 0%, transparent 50%);
        }

        .hero-grid {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .hero-content { position: relative; z-index: 1; max-width: 700px; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(233,69,96,0.12); border: 1px solid rgba(233,69,96,0.3);
            padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.8rem;
            color: var(--gold); font-weight: 600; letter-spacing: 0.5px;
            margin-bottom: 1.5rem; text-transform: uppercase;
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800; line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        h1 span { color: var(--gold); }

        .hero p {
            font-size: 1.1rem; color: var(--gray); line-height: 1.7;
            max-width: 560px; margin-bottom: 2.5rem;
        }

        .hero-btns { display: flex; gap: 1rem; flex-wrap: wrap; }

        .btn-lg {
            padding: 0.9rem 2rem; font-size: 1rem; border-radius: 10px;
        }

        /* STATS */
        .stats {
            display: flex; gap: 2rem; margin-top: 4rem;
            padding-top: 2rem; border-top: 1px solid var(--border);
        }
        .stat-item { }
        .stat-num {
            font-family: 'Syne', sans-serif; font-size: 1.8rem;
            font-weight: 800; color: var(--white);
        }
        .stat-num span { color: var(--gold); }
        .stat-label { font-size: 0.8rem; color: var(--gray); font-weight: 500; }

        /* FLOATING CARDS */
        .hero-visual {
            position: absolute; right: 5%; top: 50%; transform: translateY(-50%);
            width: 420px; display: none;
        }
        @media (min-width: 1100px) { .hero-visual { display: block; } }

        .float-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 16px; padding: 1.2rem 1.5rem;
            backdrop-filter: blur(10px);
            margin-bottom: 1rem;
            animation: float 4s ease-in-out infinite;
        }
        .float-card:nth-child(2) { animation-delay: -2s; margin-left: 40px; }
        .float-card:nth-child(3) { animation-delay: -1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .float-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem; }
        .card-type { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold); }
        .card-status {
            font-size: 0.7rem; padding: 2px 10px; border-radius: 50px; font-weight: 600;
        }
        .status-pending { background: rgba(245,166,35,0.15); color: var(--gold2); }
        .status-done { background: rgba(52,199,89,0.15); color: #34c759; }
        .status-process { background: rgba(0,122,255,0.15); color: #007aff; }

        .card-title { font-size: 0.9rem; font-weight: 600; margin-bottom: 0.3rem; }
        .card-meta { font-size: 0.75rem; color: var(--gray); }

        /* FEATURES */
        .features {
            padding: 6rem 5%;
            background: var(--secondary);
        }

        .section-header { text-align: center; margin-bottom: 4rem; }
        .section-label {
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;
            color: var(--gold); font-weight: 700; margin-bottom: 0.8rem;
        }
        .section-header h2 {
            font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800;
        }
        .section-header p { color: var(--gray); margin-top: 0.8rem; max-width: 500px; margin-inline: auto; }

        .features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 2rem;
            transition: all 0.3s; cursor: default;
        }
        .feature-card:hover {
            border-color: rgba(233,69,96,0.3);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .feature-icon {
            width: 50px; height: 50px; border-radius: 12px;
            background: linear-gradient(135deg, rgba(233,69,96,0.2), rgba(245,166,35,0.1));
            border: 1px solid rgba(233,69,96,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: var(--gold); margin-bottom: 1.2rem;
        }
        .feature-card h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 0.6rem; }
        .feature-card p { font-size: 0.88rem; color: var(--gray); line-height: 1.6; }

        /* REQUEST TYPES */
        .requests-section { padding: 6rem 5%; }
        .requests-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;
            margin-top: 3rem;
        }
        .req-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 14px; padding: 1.5rem 1.2rem; text-align: center;
            transition: all 0.3s;
        }
        .req-card:hover {
            border-color: var(--gold); background: rgba(233,69,96,0.06);
            transform: translateY(-4px);
        }
        .req-icon {
            font-size: 1.8rem; margin-bottom: 0.8rem;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .req-card h4 { font-size: 0.88rem; font-weight: 600; color: var(--white); }

        /* CTA */
        .cta-section {
            padding: 6rem 5%; text-align: center;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            position: relative; overflow: hidden;
        }
        .cta-section::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at center, rgba(233,69,96,0.15) 0%, transparent 60%);
        }
        .cta-section h2 {
            font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800;
            position: relative; margin-bottom: 1rem;
        }
        .cta-section p { color: var(--gray); position: relative; margin-bottom: 2rem; }

        /* FOOTER */
        footer {
            padding: 2rem 5%; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 1rem;
        }
        footer p { color: var(--gray); font-size: 0.85rem; }
        .footer-links { display: flex; gap: 1.5rem; }
        .footer-links a { color: var(--gray); text-decoration: none; font-size: 0.85rem; transition: color 0.3s; }
        .footer-links a:hover { color: var(--white); }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        RTS Portal
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#requests">Services</a>
        <a href="#about">About</a>
    </div>
    <div class="nav-cta">
        <a href="login.php" class="btn-outline">Log In</a>
        <a href="register.php" class="btn-primary">Get Started</a>
    </div>
</nav>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>
    <div class="hero-content">
        <div class="hero-badge"><i class="fas fa-circle-check"></i> Online Request System Active</div>
        <h1>Registrar <span>Ticketing</span> System</h1>
        <p>Submit, track, and receive your academic documents online. No more long queues — just fast, seamless, and secure requests directly from your browser.</p>
        <div class="hero-btns">
            <a href="register.php" class="btn-primary btn-lg">
                <i class="fas fa-rocket" style="margin-right:8px"></i> Create Account
            </a>
            <a href="login.php" class="btn-outline btn-lg">
                <i class="fas fa-sign-in-alt" style="margin-right:8px"></i> Sign In
            </a>
        </div>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-num">2.4<span>K+</span></div>
                <div class="stat-label">Students Served</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">98<span>%</span></div>
                <div class="stat-label">Satisfaction Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">24<span>h</span></div>
                <div class="stat-label">Avg. Processing</div>
            </div>
        </div>
    </div>

    <div class="hero-visual">
        <div class="float-card">
            <div class="float-card-header">
                <span class="card-type"><i class="fas fa-file-alt" style="margin-right:5px"></i>Transcript of Records</span>
                <span class="card-status status-done">Completed</span>
            </div>
            <div class="card-title">Maria Santos — TOR Request</div>
            <div class="card-meta"><i class="fas fa-calendar" style="margin-right:5px"></i>Processing complete — Ready for pickup</div>
        </div>
        <div class="float-card">
            <div class="float-card-header">
                <span class="card-type"><i class="fas fa-id-card" style="margin-right:5px"></i>Enrollment Permit</span>
                <span class="card-status status-process">In Progress</span>
            </div>
            <div class="card-title">Juan dela Cruz — Enrollment Permit</div>
            <div class="card-meta"><i class="fas fa-clock" style="margin-right:5px"></i>Est. 1–2 business days</div>
        </div>
        <div class="float-card">
            <div class="float-card-header">
                <span class="card-type"><i class="fas fa-money-bill" style="margin-right:5px"></i>Statement of Account</span>
                <span class="card-status status-pending">Pending</span>
            </div>
            <div class="card-title">Ana Reyes — SOA AY 2024–2025</div>
            <div class="card-meta"><i class="fas fa-hourglass-half" style="margin-right:5px"></i>Awaiting registrar review</div>
        </div>
    </div>
</section>

<section class="features" id="features">
    <div class="section-header">
        <div class="section-label">Why Choose RTS</div>
        <h2>Everything You Need,<br>All In One Place</h2>
        <p>A streamlined system built for students and registrar staff alike.</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bolt"></i></div>
            <h3>Instant Submission</h3>
            <p>Submit document requests anytime, anywhere — no need to visit the registrar's office in person.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bell"></i></div>
            <h3>Real-Time Updates</h3>
            <p>Get instant notifications when your ticket status changes from pending to completed.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
            <h3>Secure & Private</h3>
            <p>Your academic records and personal data are protected with enterprise-grade security.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-clock-rotate-left"></i></div>
            <h3>Track History</h3>
            <p>View all your past and current requests with full audit trail and status history.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-headset"></i></div>
            <h3>Direct Messaging</h3>
            <p>Communicate directly with the registrar staff through the built-in messaging system.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-file-export"></i></div>
            <h3>Document Ready Alerts</h3>
            <p>Receive alerts when your documents are printed, signed, and ready for release.</p>
        </div>
    </div>
</section>

<section class="requests-section" id="requests">
    <div class="section-header">
        <div class="section-label">Available Services</div>
        <h2>Documents You Can Request</h2>
        <p>A wide range of registrar documents available for online request submission.</p>
    </div>
    <div class="requests-grid">
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-star-of-life"></i></div>
            <h4>Grade Report</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-id-badge"></i></div>
            <h4>Enrollment Permit</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-scroll"></i></div>
            <h4>TOR (Alumni)</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-receipt"></i></div>
            <h4>Statement of Account</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-calendar-days"></i></div>
            <h4>Study Load</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-file-lines"></i></div>
            <h4>Form 137</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-file-certificate"></i></div>
            <h4>Form 138</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-certificate"></i></div>
            <h4>Certificate of Enrollment</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-award"></i></div>
            <h4>Good Moral Certificate</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-magnifying-glass-chart"></i></div>
            <h4>Honorable Dismissal</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-user-graduate"></i></div>
            <h4>Diploma Replacement</h4>
        </div>
        <div class="req-card">
            <div class="req-icon"><i class="fas fa-ellipsis"></i></div>
            <h4>Other Requests</h4>
        </div>
    </div>
</section>

<section class="cta-section">
    <h2>Ready to Get Started?</h2>
    <p>Join thousands of students already using the RTS Portal for their academic needs.</p>
    <a href="register.php" class="btn-primary btn-lg" style="display:inline-flex;align-items:center;gap:8px">
        <i class="fas fa-user-plus"></i> Create Your Free Account
    </a>
</section>

<footer id="about">
    <p>&copy; <?= date('Y') ?> Registrar Ticketing System. All rights reserved.</p>
    <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Use</a>
        <a href="#">Contact</a>
    </div>
</footer>

</body>
</html>