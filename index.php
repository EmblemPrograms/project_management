<?php
// index.php - Homepage

require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOS FPE CHAPTER | Project Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #b6ff11;
            --accent: #06d48b;
            --shade: #0f172a;
            --surface: #ffffff;
            --muted: #64748b;
            --bg: #f8fafc;
            --shadow: 0 25px 80px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--shade);
            line-height: 1.7;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .navbar .nav-link {
            color: var(--shade) !important;
            transition: color 0.2s ease;
        }

        .navbar .nav-link:hover,
        .navbar .nav-link:focus {
            color: var(--primary) !important;
        }

        .hero {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.78), rgba(15, 23, 42, 0.48)),
                url('https://images.pexels.com/photos/1181406/pexels-photo-1181406.jpeg?auto=compress&cs=tinysrgb&w=1600') center/cover no-repeat;
            color: #f8fafc;
            min-height: calc(100vh - 72px);
            display: flex;
            align-items: center;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 35%);
        }

        .hero .container {
            position: relative;
            z-index: 1;
        }

        .hero .hero-card {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .hero h1 {
            font-size: clamp(1.75rem, 6vw, 3.75rem);
            letter-spacing: -0.04em;
        }

        .btn-nacos {
            background: linear-gradient(135deg, var(--primary), #0bd83b);
            border: none;
            padding: 0.95rem 2.5rem;
            font-weight: 600;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.18);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-outline-light {
            border-width: 2px;
        }

        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -0.7rem;
            width: 4rem;
            height: 3px;
            border-radius: 999px;
            background: var(--primary);
        }

        .feature-card,
        .step-card,
        .stats-card {
            border: none;
            border-radius: 1.5rem;
            background: var(--surface);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover,
        .step-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 65px rgba(15, 23, 42, 0.12);
        }

        .card-icon {
            width: 72px;
            height: 72px;
            border-radius: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .feature-card p,
        .step-card p {
            color: var(--muted);
        }

        .stats-card {
            padding: 2rem;
            text-align: center;
            color: var(--shade);
        }

        .stats-card strong {
            font-size: 2.5rem;
            display: block;
            color: var(--primary);
        }

        .footer {
            background: var(--shade);
            color: #cbd5e1;
        }

        .footer a {
            color: #f8fafc;
        }

        .footer a:hover {
            color: var(--primary);
        }

        .rounded-glow {
            border-radius: 1.5rem;
            box-shadow: 0 35px 80px rgba(15, 23, 42, 0.16);
        }

        @media (max-width: 991px) {
            .hero {
                min-height: auto;
                padding: 4.5rem 0;
            }
        }

        @media (max-width: 575px) {
            .hero {
                padding: 3rem 0;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">
            <i class="fas fa-graduation-cap"></i> NACOS FPE
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link btn btn-success text-white px-4 ms-3" href="login.php">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center text-white">
            <div class="col-lg-7">
                <span class="badge bg-white text-success rounded-pill mb-3">NACOS FPE | Final Year Projects</span>
                <h1 class="fw-bold mb-4">
                    Manage final year project submissions with clarity, accountability, and speed.
                </h1>
                <p class="lead text-white-75 mb-5">
                    A polished platform for students, supervisors and department admins to upload, review, approve and track project progress in one place.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 mb-4">
                    <a href="login.php" class="btn btn-nacos btn-lg">Get Started</a>
                    <a href="#features" class="btn btn-outline-light btn-lg">Explore features</a>
                </div>
            </div>
            <div class="col-lg-5 mt-5 mt-lg-0">
                <div class="hero-card">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <p class="text-white-75 mb-1 small">Begin your project journey</p>
                            <h4 class="fw-semibold mb-0">Login and upload your work</h4>
                        </div>
                        <span class="badge bg-white text-success py-2 px-3">Fast access</span>
                    </div>
                    <ul class="list-unstyled text-white-75 mb-4">
                        <li class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i> Submit project details and files securely</li>
                        <li class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i> Track approval status in real time</li>
                        <li class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i> Receive email and OTP confirmations</li>
                    </ul>
                    <a href="login.php" class="btn btn-light btn-sm fw-semibold">Open dashboard</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">Powerful Features</h2>
            <p class="text-muted">Everything you need to manage final-year projects with confidence.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4">
                        <i class="fas fa-upload fa-lg"></i>
                    </div>
                    <h5 class="mb-3">Easy Project Upload</h5>
                    <p>Students can upload proposals, abstracts, softcopies and supervision details in one smooth flow.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <h5 class="mb-3">Approval Workflow</h5>
                    <p>Department admins review submissions, add feedback, and approve or reject projects easily.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4">
                        <i class="fas fa-lock fa-lg"></i>
                    </div>
                    <h5 class="mb-3">Secure Access</h5>
                    <p>Only authorized users can access project files, with OTP verification and role-based access control.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fw-bold">How it works</h2>
            <p class="text-muted">A quick process designed for students and administrators alike.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="step-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4 bg-light text-success">
                        <i class="fas fa-user-plus fa-lg"></i>
                    </div>
                    <h5>Create account</h5>
                    <p class="mb-0">Register as a student, supervisor or admin to begin.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4 bg-light text-success">
                        <i class="fas fa-file-upload fa-lg"></i>
                    </div>
                    <h5>Submit project</h5>
                    <p class="mb-0">Upload summaries, files, and supervisor details in one place.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4 bg-light text-success">
                        <i class="fas fa-comments fa-lg"></i>
                    </div>
                    <h5>Review</h5>
                    <p class="mb-0">Admins review submissions and leave comments or approval notes.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="step-card card h-100 p-4 text-center">
                    <div class="card-icon mx-auto mb-4 bg-light text-success">
                        <i class="fas fa-check fa-lg"></i>
                    </div>
                    <h5>Track status</h5>
                    <p class="mb-0">Students monitor approval progress until final acceptance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5">
    <div class="container">
        <div class="row align-items-center gx-5">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">About NACOS FPE CHAPTER</h2>
                <p class="lead text-muted mb-4">
                    The National Association of Computer Science Students (NACOS) Federal Polytechnic Ede Chapter offers a modern, secure platform built to support final year project submissions, reviews, and approvals.
                </p>
                <ul class="list-unstyled mt-4">
                    <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Transparent approval process</li>
                    <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Secure file storage and uploads</li>
                    <li class="mb-3"><i class="fas fa-check text-success me-2"></i> Clear progress tracking for every user</li>
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://images.pexels.com/photos/3184325/pexels-photo-3184325.jpeg?auto=compress&cs=tinysrgb&w=1200" 
                     class="img-fluid rounded-glow" alt="Students collaborating">
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer py-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-6 text-center text-md-start">
                <h5 class="text-white fw-bold">NACOS Federal Polytechnic Ede</h5>
                <p class="mb-0 text-muted">Project Management System for final year project submission and approval.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">&copy; <?= date("Y") ?> NACOS Federal Polytechnic Ede Chapter. All Rights Reserved.</p>
                <p class="small text-muted">Designed for students, supervisors, and admins.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>