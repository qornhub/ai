<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Chitgbd AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #ffffff;
            --bg-sidebar: #f7f7f8;
            --sidebar-border: #e4e4e7;
            --input-bg: #e9e9ea;
            --input-border: #d1d1d1;
            --item-hover: #f3f3f4;

            --btn-primary: #111111;
            --btn-primary-hover: #000000;

            --text-primary: #111;
            --text-secondary: #666;

            --radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3fef6;
            margin: 0;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background-color: var(--bg-main);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            height: 60px;
            margin-bottom: 1rem;
        }

        .platform-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .platform-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background-color: var(--bg-main);
            border-color: var(--btn-primary);
            box-shadow: 0 0 0 2px rgba(17, 17, 17, 0.1);
        }

        .btn-login {
            background-color: var(--btn-primary);
            border: none;
            border-radius: var(--radius);
            color: white;
            font-weight: 500;
            padding: 0.75rem;
            transition: background-color 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background-color: var(--btn-primary);
            color: white;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }

        .register-link a {
            color: var(--btn-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert-danger {
            border-radius: var(--radius);
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="{{ asset('images/lugo.png') }}" alt="Logo" class="logo">
            <h1 class="platform-name">Chitgbd AI</h1>
            <p class="platform-subtitle">Sign in to your account</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-login w-100">Login</button>

            <div class="register-link">
                Don't have an account? <a href="{{ route('register.show') }}">Register</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some subtle interactive enhancements
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input');
            
            // Add focus states for better UX
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
            
            // Simple form validation
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#dc3545';
                    } else {
                        input.style.borderColor = '';
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    // In a real app, you would show a more specific error message
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>