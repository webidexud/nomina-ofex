<?php
require 'vendor/autoload.php';
include 'db/conexion.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Verificar si hay mensajes de éxito o error en la sesión
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']); // Limpiar el mensaje después de mostrarlo
}
if (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    unset($_SESSION['error']); // Limpiar el mensaje después de mostrarlo
}

// Consultar tipos de identificación si es necesario
$query_tipos = "SELECT * FROM tipo_identificacion WHERE estado = 1 ORDER BY nombre";
$result_tipos = mysqli_query($conexion, $query_tipos);

// Obtener datos del usuario con join para obtener el nombre del tipo de identificación
$query = "SELECT c.*, t.nombre as tipo_identificacion_nombre 
          FROM contratista c
          LEFT JOIN tipo_identificacion t ON c.tipo_identificacion_id = t.id
          WHERE c.cedula = '" . $_SESSION['cedula'] . "'";
$result = mysqli_query($conexion, $query);
$user = mysqli_fetch_assoc($result);

// Verificar si existen los campos de tipo de documento y lugar de expedición
$tipo_id_exists = isset($user['tipo_identificacion_id']) && !empty($user['tipo_identificacion_id']);
$lugar_exp_exists = isset($user['lugar_expedicion']) && !empty($user['lugar_expedicion']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Universidad Distrital</title>
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
            --warning: #F59E0B;
            --info: #3B82F6;
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
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        .header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 10;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
        }
        
        .logo-circular {
            width: 40px;
            height: 40px;
            overflow: hidden;
            margin-right: 0.75rem;
        }
        
        .app-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-right: 2rem;
        }
        
        .back-link {
            display: flex;
            align-items: center;
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            margin-left: 1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            background-color: rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }
        
        .back-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .back-icon {
            margin-right: 0.5rem;
        }
        
        .profile-card {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            transition: var(--transition);
        }
        
        .profile-card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-md);
        }
        
        .avatar-icon {
            font-size: 3rem;
            color: var(--primary-light);
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .profile-id {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--medium-gray);
            border-radius: 0.375rem;
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
        
        .form-input.readonly {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            cursor: not-allowed;
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
            pointer-events: none;
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--medium-gray);
            border-radius: 0.375rem;
            font-size: 0.95rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236B7280'%3E%3Cpath d='M7 10l5 5 5-5H7z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5em;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error);
        }
        
        .alert-icon {
            margin-right: 0.75rem;
            font-size: 1.125rem;
        }
        
        .btn-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .btn-icon {
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--medium-gray);
            color: var(--text-dark);
        }
        
        .btn-secondary:hover {
            background-color: var(--dark-gray);
            color: var(--white);
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
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .logo-wrapper {
                margin-bottom: 0.75rem;
            }
            
            .btn-row {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.75rem;
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
    <header class="header">
        <div class="header-content">
            <div class="logo-wrapper">
                <div class="logo-circular">
                    <img src="images/LOGO_IDEXUD.png" alt="Logo IDEXUD" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="app-title">Mi Perfil</h1>
                <a href="contratos.php" class="back-link">
                    <i class="fas fa-arrow-left back-icon"></i>
                    Volver a Contratos
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="profile-card fadeIn">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle avatar-icon"></i>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['nombres']); ?></h2>
                <p class="profile-id">Documento: <?php echo htmlspecialchars($user['cedula']); ?></p>
            </div>
            
            <div class="profile-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle alert-icon"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <form action="back/modify_contractor.php" method="post" id="profile-form">
                    <!-- Número de identificación (no editable) -->
                    <div class="form-group fadeIn delay-1">
                        <label class="form-label" for="cedula">Número de Identificación</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-id-card input-icon"></i>
                            <input class="form-input readonly" id="cedula" type="text" 
                                   value="<?php echo htmlspecialchars($user['cedula']); ?>" readonly>
                        </div>
                    </div>
                    
                    <!-- Tipo de Identificación (solo editable si no existe) -->
                    <div class="form-group fadeIn delay-1">
                        <label class="form-label" for="tipo_identificacion_id">Tipo de Identificación</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-passport input-icon"></i>
                            <?php if ($tipo_id_exists): ?>
                                <input class="form-input readonly" type="text" 
                                       value="<?php echo htmlspecialchars($user['tipo_identificacion_nombre']); ?>" readonly>
                            <?php else: ?>
                                <select class="form-select" id="tipo_identificacion_id" name="tipo_identificacion_id" required>
                                    <option value="">Seleccione un tipo de documento</option>
                                    <?php 
                                    mysqli_data_seek($result_tipos, 0); // Reiniciar el puntero
                                    while($tipo = mysqli_fetch_assoc($result_tipos)): ?>
                                        <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Lugar de Expedición (solo editable si no existe) -->
                    <div class="form-group fadeIn delay-1">
                        <label class="form-label" for="lugar_expedicion">Lugar de Expedición</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <?php if ($lugar_exp_exists): ?>
                                <input class="form-input readonly" type="text" 
                                       value="<?php echo htmlspecialchars($user['lugar_expedicion']); ?>" readonly>
                            <?php else: ?>
                                <input class="form-input" type="text" name="lugar_expedicion" id="lugar_expedicion" 
                                       placeholder="Ingrese el lugar de expedición" maxlength="100" 
                                       oninput="toUpperCase(this)" required>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Nombres (editable) -->
                    <div class="form-group fadeIn delay-2">
                        <label class="form-label" for="nombres">Nombres y Apellidos</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input class="form-input" type="text" name="nombres" id="nombres" 
                                   value="<?php echo htmlspecialchars($user['nombres']); ?>" 
                                   maxlength="200" oninput="toUpperCase(this)" required>
                        </div>
                    </div>
                    
                    <!-- Celular (editable) -->
                    <div class="form-group fadeIn delay-2">
                        <label class="form-label" for="celular">Número de Celular</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-mobile-alt input-icon"></i>
                            <input class="form-input" type="text" name="celular" id="celular" 
                                   value="<?php echo htmlspecialchars($user['celular']); ?>" 
                                   maxlength="50" required>
                        </div>
                    </div>
                    
                    <!-- Email (editable) -->
                    <div class="form-group fadeIn delay-2">
                        <label class="form-label" for="email">Correo Electrónico</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-input" type="email" name="email" id="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   maxlength="150" oninput="toUpperCase(this)" required>
                        </div>
                    </div>
                    
                    <!-- Contraseña (opcional) -->
                    <div class="form-group fadeIn delay-3">
                        <label class="form-label" for="pswd">
                            Nueva Contraseña <span class="text-gray-500 text-sm">(dejar en blanco para no cambiar)</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input class="form-input" type="password" name="pswd" id="pswd" 
                                   placeholder="••••••••" maxlength="50">
                        </div>
                    </div>
                    
                    <div class="btn-row fadeIn delay-3">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-save btn-icon"></i>
                            Guardar Cambios
                        </button>
                        <a href="contratos.php" class="btn btn-secondary">
                            <i class="fas fa-times btn-icon"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <script>
        // Función para convertir a mayúsculas
        function toUpperCase(input) {
            input.value = input.value.toUpperCase();
        }
        
        // Mejorar interacción de los campos
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input:not(.readonly), .form-select');
            
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
            
            // Validación básica del formulario
            const profileForm = document.getElementById('profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(event) {
                    let hasError = false;
                    const requiredInputs = this.querySelectorAll('input[required], select[required]');
                    
                    requiredInputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.style.borderColor = 'var(--error)';
                            hasError = true;
                        } else {
                            input.style.borderColor = '';
                        }
                    });
                    
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
