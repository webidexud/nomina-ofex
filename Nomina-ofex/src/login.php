<?php
session_start();
include 'db/conexion.php';

// Check if user is logged in
if (!isset($_SESSION['cedula'])) {
    echo '';
} else {
    header("Location: contratos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Sistema de Gestión de Honorarios | Universidad Distrital</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plataforma de Gestión de Honorarios de la Universidad Distrital Francisco José de Caldas">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png"/>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #003366;
            --primary-dark: #002244;
            --primary-light: #004488;
            --accent-color: #FF8C00;
            --accent-hover: #FF7000;
            --white: #FFFFFF;
            --light-gray: #F5F7FA;
            --medium-gray: #E0E5EC;
            --dark-gray: #6B7280;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --success: #10B981;
            --error: #EF4444;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 1100px;
            display: flex;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            background-color: var(--white);
            position: relative;
        }
        
        .left-panel {
            width: 45%;
            background-color: var(--primary-color);
            background-image: 
                linear-gradient(135deg, rgba(0, 51, 102, 0.97) 0%, rgba(0, 30, 60, 0.95) 100%),
                url('images/campus-background.jpg');
            background-size: cover;
            background-position: center;
            color: var(--white);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }
        
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('images/pattern.svg') repeat;
            opacity: 0.05;
            z-index: -1;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 3rem;
        }
        
        .logo-circular {
            width: 60px;
            height: 60px;
            overflow: hidden;
            margin-right: 1rem;
        }
        
        .logo-text {
            font-size: 0.8rem;
            line-height: 1.4;
        }
        
        .logo-text strong {
            font-size: 1rem;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .welcome-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .welcome-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
        }
        
        .welcome-description {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        
        .feature-list {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .feature-icon {
            margin-right: 10px;
            width: 24px;
            height: 24px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .right-panel {
            width: 55%;
            padding: 3rem;
            display: flex;
            flex-direction: column;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .login-form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: var(--white);
            color: var(--text-dark);
            font-family: 'Montserrat', sans-serif;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .form-input::placeholder {
            color: var(--dark-gray);
            opacity: 0.7;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            font-size: 1.2rem;
            pointer-events: none;
        }
        
        .login-button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
            font-family: 'Montserrat', sans-serif;
        }
        
        .login-button:hover {
            background-color: var(--primary-dark);
        }
        
        .login-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .login-button:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.5;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        .button-icon {
            margin-right: 0.5rem;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--medium-gray);
        }
        
        .auth-divider::before {
            margin-right: 1rem;
        }
        
        .auth-divider::after {
            margin-left: 1rem;
        }
        
        .register-prompt {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .register-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .register-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .alert-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .footer {
            text-align: center;
            padding: 1.5rem;
            background-color: var(--white);
            color: var(--text-light);
            font-size: 0.8rem;
            border-top: 1px solid var(--medium-gray);
            margin-top: auto;
        }
        
        .footer-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        /* Decorative elements */
        .circle-decoration {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.03);
            z-index: -1;
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            bottom: -150px;
            left: -100px;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            top: -100px;
            right: -50px;
        }
        
        /* Responsiveness */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 600px;
            }
            
            .left-panel, .right-panel {
                width: 100%;
                padding: 2rem;
            }
            
            .left-panel {
                padding-bottom: 3rem;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-container {
                padding: 1rem;
            }
            
            .left-panel, .right-panel {
                padding: 1.5rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .form-input {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
            }
            
            .input-icon {
                font-size: 1rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fadeIn {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .delay-1 {
            animation-delay: 0.1s;
        }
        
        .delay-2 {
            animation-delay: 0.2s;
        }
        
        .delay-3 {
            animation-delay: 0.3s;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="login-container">
            <div class="left-panel">
                <div class="logo-wrapper fadeIn">
                    <div class="logo-circular">
                        <img src="images/LOGO_IDEXUD.png" alt="Logo IDEXUD" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                    <div class="logo-text">
                        <strong>Universidad Distrital</strong>
                        Francisco José de Caldas
                    </div>
                </div>
                
                <div class="welcome-content">
                    <h1 class="welcome-title fadeIn delay-1">Bienvenido al Sistema</h1>
                    <p class="welcome-description fadeIn delay-2">
                        Plataforma de Gestión de Honorarios de la Universidad Distrital Francisco José de Caldas.
                        Acceda a su cuenta para gestionar sus contratos e informes.
                    </p>
                    
                    <ul class="feature-list fadeIn delay-3">
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-check"></i></span>
                            Generación de cumplido
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-check"></i></span>
                            Generación de informes de actividades
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-check"></i></span>
                            Gestión de multiples contratos
                        </li>
                    </ul>
                </div>
                
                <div class="circle-decoration circle-1"></div>
                <div class="circle-decoration circle-2"></div>
            </div>
            
            <div class="right-panel">
                <div class="login-header fadeIn">
                    <h2 class="login-title">Iniciar Sesión</h2>
                    <p class="login-subtitle">Ingrese sus credenciales para acceder</p>
                </div>
                
                <?php
                if(isset($_SESSION['error'])) {
                    echo '<div class="alert alert-error fadeIn">
                            <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
                            <span>' . $_SESSION['error'] . '</span>
                        </div>';
                    unset($_SESSION['error']);
                }
                if(isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success fadeIn">
                            <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
                            <span>' . $_SESSION['success'] . '</span>
                        </div>';
                    unset($_SESSION['success']);
                }
                ?>
                
                <form class="login-form" action="back/login_contratista.php" method="POST">
                    <div class="form-group fadeIn delay-1">
                        <label class="form-label" for="id_person">Número de documento</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-id-card input-icon"></i>
                            <input 
                                type="text" 
                                id="id_person" 
                                name="id_person" 
                                class="form-input" 
                                placeholder="Ingrese su número de documento" 
                                required 
                                autofocus
                            >
                        </div>
                    </div>
                    
                    <div class="form-group fadeIn delay-2">
                        <label class="form-label" for="pass">Contraseña</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="pass" 
                                name="pass" 
                                class="form-input" 
                                placeholder="Ingrese su contraseña" 
                                required
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="login-button fadeIn delay-3" name="login_btn">
                        <i class="fas fa-sign-in-alt button-icon"></i>
                        Ingresar
                    </button>
                </form>
                
                <div class="auth-divider fadeIn delay-3">o</div>
                
                <div class="register-prompt fadeIn delay-3">
                    ¿No tiene una cuenta? <a href="register.php" class="register-link">Registrarse</a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <script>
        // Script para mejorar la interacción del formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto visual al hacer clic en el botón de login
            const loginButton = document.querySelector('.login-button');
            loginButton.addEventListener('click', function() {
                this.classList.add('clicked');
            });
            
            // Mejorar interacción de los campos
            const inputs = document.querySelectorAll('.form-input');
            
            inputs.forEach(input => {
                // Cambiar color del icono al enfocar el campo
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    icon.style.color = '#003366';
                });
                
                // Restaurar color del icono al perder el foco
                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    icon.style.color = '';
                });
            });
        });
    </script>
</body>
</html>
