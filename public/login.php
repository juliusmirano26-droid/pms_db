<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Login</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for visual accents -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for ultra-clean typography -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #090d16;
            --card-bg: rgba(15, 23, 42, 0.85); /* Slightly darker for better field contrast */
            --input-bg: #1e293b; /* High-contrast solid dark background for inputs */
            --accent-blue: #3b82f6;
            --accent-purple: #6366f1;
            --text-label: #f1f5f9; /* Brightened up for crisp visibility */
            --text-muted: #94a3b8;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: #ffffff;
        }

        /* --- Aesthetic Animated Mesh Background --- */
        .mesh-gradient {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(99, 102, 241, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 50% 10%, rgba(168, 85, 247, 0.1) 0%, transparent 35%);
            filter: blur(60px);
            animation: meshMove 20s ease infinite alternate;
        }

        @keyframes meshMove {
            0% { transform: scale(1) translate(0px, 0px); }
            50% { transform: scale(1.1) translate(20px, -30px); }
            100% { transform: scale(1) translate(-10px, 20px); }
        }

        /* Architectural Blueprint Grid Pattern */
        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
            pointer-events: none;
        }

        /* --- Premium Glassmorphic Login Card --- */
        .pms-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px) saturate(120%);
            -webkit-backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 3rem 2.5rem !important;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            z-index: 10;
            width: 100%;
            max-width: 440px;
        }

        /* Dynamic Branding Icon Glow */
        .brand-icon-wrapper {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
            margin-bottom: 1.25rem;
        }

        /* Modernized Form Styling - Visually Enhanced */
        .form-label {
            color: var(--text-label);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.25); /* More defined border outline */
            transition: all 0.25s ease;
            background: var(--input-bg);
        }

        .input-group:focus-within {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
            background: #1e293b;
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #cbd5e1; /* Made the icons punchier and clearer */
            padding-left: 1.25rem;
        }

        .form-control {
            background: transparent !important;
            border: none;
            color: #ffffff !important;
            padding: 0.8rem 1rem 0.8rem 0.5rem;
            font-size: 1rem; /* Slightly larger text for instant reading */
            font-weight: 500;
        }

        .form-control:focus {
            box-shadow: none;
            background: transparent;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.45); /* Highly readable placeholder text */
        }

        /* High-End Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border: none;
            color: white;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
            opacity: 0.95;
        }

        /* Alert Refinements */
        .alert {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            border-radius: 12px;
            font-weight: 500;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
        }
    </style>
</head>
<body>

<!-- Dynamic Aesthetic Background Components -->
<div class="mesh-gradient"></div>
<div class="bg-grid"></div>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card pms-card">
        <div class="text-center mb-4">
            <!-- Professional Hub Identity Header -->
            <div class="brand-icon-wrapper">
                <i class="fa-solid fa-folder-tree fs-4 text-white"></i>
            </div>
            <h3 class="fw-bold text-white m-0" style="letter-spacing: -0.5px;">Project Management Portal</h3>
            <p class="text-muted small mt-2 m-0" style="color: var(--text-muted) !important;">Enter credentials to open your dashboard workspace</p>
        </div>
        
        <!-- ALERT CONTAINER: Activated dynamically via jQuery response loops -->
        <div id="alert-msg" class="alert d-none p-3 mb-3 small"></div>

        <form id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="julius@pms.com">
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="••••••">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-submit">
                Sign In To Workspace
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault(); 

        var email = $('#email').val();
        var password = $('#password').val();
        var alertBox = $('#alert-msg');

        $.ajax({
            url: 'auth.php', 
            type: 'POST',
            data: { email: email, password: password },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertBox.removeClass('alert-danger d-none').addClass('alert-success d-block').text(response.message);
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1000);
                } else {
                    alertBox.removeClass('alert-success d-none').addClass('alert-danger d-block').text(response.message);
                }
            },
            error: function() {
                alertBox.removeClass('alert-success d-none').addClass('alert-danger d-block').text('An unexpected system error occurred.');
            }
        });
    });
});
</script>
</body>
</html>