<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Chitgbd AI</title>
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

        .register-container {
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

        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0;
            height: 20px;
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .password-toggle:focus {
            outline: none;
        }

        .btn-register {
            background-color: var(--btn-primary);
            border: none;
            border-radius: var(--radius);
            color: white;
            font-weight: 500;
            padding: 0.75rem;
            transition: background-color 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-register:hover {
            background-color: var(--btn-primary-hover);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }

        .login-link a {
            color: var(--btn-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert-danger {
            border-radius: var(--radius);
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
        }

        @media (max-width: 576px) {
            .register-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo-container">
            <img src="{{ asset('images/lugo.png') }}" alt="Logo" class="logo">
            <h1 class="platform-name">Chitgbd AI</h1>
            <p class="platform-subtitle">Create your account</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter your full name" required autofocus>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <div class="password-input-group">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle" id="togglePasswordConfirmation">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-register w-100">Create Account</button>

            <div class="login-link">
                Already registered? <a href="{{ route('login.show') }}">Login</a>
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input');
            const togglePassword = document.getElementById('togglePassword');
            const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
            const passwordField = document.getElementById('password');
            const passwordConfirmationField = document.getElementById('password_confirmation');
            
            // Password visibility toggle functionality
            function togglePasswordVisibility(field, button) {
                const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
                field.setAttribute('type', type);
                
                // Toggle icon
                if (type === 'text') {
                    button.innerHTML = `
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.94 17.94C16.2306 19.243 14.1491 19.9649 12 20C5 20 1 12 1 12C2.24389 9.68192 3.96914 7.65663 6.06 6.06006M9.9 4.24002C10.5883 4.0789 11.2931 3.99836 12 4.00003C19 4.00003 23 12 23 12C22.393 13.1356 21.6691 14.2048 20.84 15.19M14.12 14.12C13.8454 14.4148 13.5141 14.6512 13.1462 14.8151C12.7782 14.9791 12.3809 15.0673 11.9781 15.0744C11.5753 15.0815 11.1752 15.0074 10.8016 14.8565C10.4281 14.7056 10.0887 14.4811 9.80385 14.1962C9.51897 13.9113 9.29439 13.572 9.14351 13.1984C8.99262 12.8249 8.91853 12.4247 8.92563 12.0219C8.93274 11.6191 9.02091 11.2219 9.18488 10.8539C9.34884 10.4859 9.58525 10.1547 9.88 9.88003" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M1 1L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    `;
                } else {
                    button.innerHTML = `
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    `;
                }
            }
            
            // Add event listeners for password toggle buttons
            togglePassword.addEventListener('click', function() {
                togglePasswordVisibility(passwordField, togglePassword);
            });
            
            togglePasswordConfirmation.addEventListener('click', function() {
                togglePasswordVisibility(passwordConfirmationField, togglePasswordConfirmation);
            });
            
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
            
            // Password confirmation validation
            passwordConfirmationField.addEventListener('input', function() {
                if (passwordField.value !== passwordConfirmationField.value) {
                    passwordConfirmationField.style.borderColor = '#dc3545';
                } else {
                    passwordConfirmationField.style.borderColor = '';
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Check if all fields are filled
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#dc3545';
                    } else {
                        input.style.borderColor = '';
                    }
                });
                
                // Check if passwords match
                if (passwordField.value !== passwordConfirmationField.value) {
                    valid = false;
                    passwordConfirmationField.style.borderColor = '#dc3545';
                    alert('Passwords do not match. Please make sure both password fields are identical.');
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>