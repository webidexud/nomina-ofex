<?php
session_start();
include 'db/conexion.php';

// Consultar tipos de identificación
$query_tipos = "SELECT * FROM tipo_identificacion WHERE estado = 1 ORDER BY nombre";
$result_tipos = mysqli_query($conexion, $query_tipos);

// Check if user is logged in
if (isset($_SESSION['cedula'])) {
    header("Location: contratos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Registro de Contratista | Universidad Distrital</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de contratista en el Sistema de Gestión de Honorarios de la Universidad Distrital Francisco José de Caldas">
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
        
        .register-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            background-color: var(--white);
            position: relative;
            margin: 2rem 0;
        }
        
        .left-panel {
            width: 40%;
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
            width: 60%;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            max-height: 90vh;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .register-form {
            width: 100%;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.75rem;
        }
        
        .form-column {
            flex: 1;
            padding: 0 0.75rem;
            min-width: 250px;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-label.required::after {
            content: '*';
            color: var(--error);
            margin-left: 0.25rem;
        }
        
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input,
        .form-select {
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
        
        .form-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236B7280'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5em;
            padding-right: 3rem;
        }
        
        .form-input:focus,
        .form-select:focus {
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
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            font-size: 1.2rem;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-button {
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
        
        .register-button:hover {
            background-color: var(--primary-dark);
        }
        
        .register-button::after {
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
        
        .register-button:focus:not(:active)::after {
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
        
        .login-prompt {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 2rem;
        }
        
        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .login-link:hover {
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
            .register-container {
                flex-direction: column;
                max-width: 600px;
            }
            
            .left-panel, .right-panel {
                width: 100%;
            }
            
            .left-panel {
                padding: 2rem;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-column {
                width: 100%;
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
            
            .register-title {
                font-size: 1.5rem;
            }
            
            .form-input, .form-select {
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
        <div class="register-container">
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
                    <h1 class="welcome-title fadeIn delay-1">Registro de Contratista</h1>
                    <p class="welcome-description fadeIn delay-2">
                        Complete el formulario para registrarse en la Plataforma de Gestión de Honorarios
                        de la Universidad Distrital Francisco José de Caldas.
                    </p>
                    
                    <ul class="feature-list fadeIn delay-3">
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-check"></i></span>
                            Acceso seguro al sistema
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-check"></i></span>
                            Gestión digital de contratos
                        </li>
                        <li class="feature-item">
                            <span class="feature-icon"><i class="fas fa-check"></i></span>
                            Generación automática de informes
                        </li>
                    </ul>
                </div>
                
                <div class="circle-decoration circle-1"></div>
                <div class="circle-decoration circle-2"></div>
            </div>
            
            <div class="right-panel">
                <div class="register-header fadeIn">
                    <h2 class="register-title">Crear Cuenta</h2>
                    <p class="register-subtitle">Ingrese sus datos personales para el registro</p>
                </div>
                
                <?php
                if(isset($_SESSION['error'])) {
                    echo '<div class="alert alert-error fadeIn">
                            <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
                            <span>' . $_SESSION['error'] . '</span>
                        </div>';
                    unset($_SESSION['error']);
                }
                ?>
                
                <form class="register-form" method="POST" action="back/register_contratista.php" id="register-form">
                    <div class="form-row">
                        <div class="form-column fadeIn delay-1">
                            <div class="form-group">
                                <label class="form-label required" for="nombre">Nombres y Apellidos Completos</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input 
                                        type="text" 
                                        id="nombre" 
                                        name="nombre" 
                                        class="form-input" 
                                        placeholder="Ingrese su nombre completo" 
                                        required 
                                    >
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="tipo_identificacion">Tipo de Identificación</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-id-card input-icon"></i>
                                    <select 
                                        id="tipo_identificacion" 
                                        name="tipo_identificacion" 
                                        class="form-select" 
                                        required
                                    >
                                        <?php mysqli_data_seek($result_tipos, 0); ?>
                                        <?php while($tipo = mysqli_fetch_assoc($result_tipos)): ?>
                                            <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="cc">Número de Identificación</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-fingerprint input-icon"></i>
                                    <input 
                                        type="text" 
                                        id="cc" 
                                        name="cc" 
                                        class="form-input" 
                                        placeholder="Ingrese su número de identificación" 
                                        required 
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-column fadeIn delay-2">
                            <div class="form-group">
                                <label class="form-label required" for="lugar_expedicion">Lugar de Expedición del Documento</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-map-marker-alt input-icon"></i>
                                    <input 
                                        type="text" 
                                        id="lugar_expedicion" 
                                        name="lugar_expedicion" 
                                        class="form-input" 
                                        placeholder="Ciudad de expedición" 
                                        maxlength="100" 
                                        required 
                                    >
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="correo_personal">Correo Electrónico Personal</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input 
                                        type="email" 
                                        id="correo_personal" 
                                        name="correo_personal" 
                                        class="form-input" 
                                        placeholder="Ingrese su correo electrónico" 
                                        required 
                                    >
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="telefono">Número de Teléfono</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input 
                                        type="tel" 
                                        id="telefono" 
                                        name="telefono" 
                                        class="form-input" 
                                        placeholder="Ingrese su número de teléfono" 
                                        required 
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group fadeIn delay-3">
                        <label class="form-label required" for="pswd">Contraseña</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="pswd" 
                                name="pswd" 
                                class="form-input" 
                                placeholder="Ingrese su contraseña" 
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('pswd')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group fadeIn delay-3">
                        <label class="form-label required" for="confirm_pswd">Confirmar Contraseña</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="confirm_pswd" 
                                name="confirm_pswd" 
                                class="form-input" 
                                placeholder="Confirme su contraseña" 
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_pswd')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="register-button fadeIn delay-3" name="register_btn">
                        <i class="fas fa-user-plus button-icon"></i>
                        Crear Cuenta
                    </button>
                    
                    <div class="login-prompt fadeIn delay-3">
                        ¿Ya tiene una cuenta? <a href="login.php" class="login-link">Iniciar Sesión</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <script>
        // Script para mejorar la interacción del formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Función para alternar la visibilidad de la contraseña
            window.togglePassword = function(fieldId) {
                const passwordField = document.getElementById(fieldId);
                const toggleIcon = passwordField.parentElement.querySelector('.password-toggle i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            };
            
            // Convertir a mayúsculas ciertos campos
            const uppercaseFields = ['nombre', 'lugar_expedicion', 'correo_personal'];
            
            uppercaseFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
                }
            });
            
            // Mejorar interacción de los campos
            const inputs = document.querySelectorAll('.form-input, .form-select');
            
            inputs.forEach(input => {
                // Cambiar color del icono al enfocar el campo
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) {
                        icon.style.color = '#003366';
                    }
                });
                
                // Restaurar color del icono al perder el foco
                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-icon');
                    if (icon) {
                        icon.style.color = '';
                    }
                });
            });
            
            // Efecto visual al hacer clic en el botón de registro
            const registerButton = document.querySelector('.register-button');
            if (registerButton) {
                registerButton.addEventListener('click', function() {
                    this.classList.add('clicked');
                });
            }
            
            // Validación básica del formulario
            const registerForm = document.getElementById('register-form');
            if (registerForm) {
                registerForm.addEventListener('submit', function(event) {
                    let hasError = false;
                    const inputs = this.querySelectorAll('input[required], select[required]');
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.style.borderColor = 'var(--error)';
                            hasError = true;
                        } else {
                            input.style.borderColor = '';
                        }
                    });
                    
                    // Validar formato de email
                    const emailField = document.getElementById('correo_personal');
                    if (emailField && emailField.value) {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailPattern.test(emailField.value)) {
                            emailField.style.borderColor = 'var(--error)';
                            hasError = true;
                            alert('Por favor, ingrese un correo electrónico válido.');
                            event.preventDefault();
                            return;
                        }
                    }
                    
                    // Validar que las contraseñas coincidan
                    const password = document.getElementById('pswd').value;
                    const confirmPassword = document.getElementById('confirm_pswd').value;
                    
                    if (password !== confirmPassword) {
                        document.getElementById('pswd').style.borderColor = 'var(--error)';
                        document.getElementById('confirm_pswd').style.borderColor = 'var(--error)';
                        hasError = true;
                        alert('Las contraseñas no coinciden. Por favor, verifique.');
                        event.preventDefault();
                        return;
                    }
                    
                    if (hasError) {
                        event.preventDefault();
                        alert('Por favor, complete todos los campos requeridos correctamente.');
                    }
                });
            }
        });
    </script>
</body>
</html>
