<?php
session_start();
include 'db/conexion.php';

// Check if user is logged in
if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}

$userContracts = getUserContracts($_SESSION['cedula']);

// Function to get user contracts
function getUserContracts($cc_contratista) {
    global $conexion;
    $query = "SELECT ci.*, b.nombre as banco_nombre, p.nombre as proceso_nombre 
              FROM contrato_informe ci 
              LEFT JOIN banco b ON ci.banco_id = b.id 
              LEFT JOIN proceso p ON ci.proceso_id = p.id 
              WHERE ci.cc_contratista = '$cc_contratista'";
    $result = mysqli_query($conexion, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get banks for dropdown
$query_bancos = "SELECT * FROM banco WHERE estado = 1 ORDER BY nombre";
$result_bancos = mysqli_query($conexion, $query_bancos);

// Get processes for dropdown
$query_procesos = "SELECT * FROM proceso ORDER BY nombre";
$result_procesos = mysqli_query($conexion, $query_procesos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contratos | Universidad Distrital</title>
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
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
        }
        
        .logo-circular {
            width: 42px;
            height: 42px;
            overflow: hidden;
            margin-right: 0.75rem;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        
        .app-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-right: 2rem;
            letter-spacing: 0.5px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-info {
            margin-right: 1rem;
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 1.5rem;
        }
        
        .user-name {
            font-weight: 500;
            margin-left: 0.5rem;
        }
        
        .header-btn {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            text-decoration: none;
            min-width: 120px;
        }
        
        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .btn-icon {
            margin-right: 0.5rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--medium-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--white);
            display: flex;
            align-items: center;
        }
        
        .card-title-icon {
            margin-right: 0.75rem;
            font-size: 1.125rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .contracts-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .contracts-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            background-color: var(--light-gray);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border-bottom: 2px solid var(--medium-gray);
        }
        
        .contracts-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--medium-gray);
            vertical-align: middle;
        }
        
        .contracts-table tr:hover {
            background-color: var(--light-gray);
        }
        
        .justificado {
            text-align: justify;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            min-width: 40px;
        }
        
        .btn-ver {
            background-color: var(--primary-color);
            color: var(--white);
            min-width: 80px;
        }
        
        .btn-ver:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: var(--white);
        }
        
        .btn-warning:hover {
            background-color: #D97706;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-danger {
            background-color: var(--error);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: #B91C1C;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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
        
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
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
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
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
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
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
        
        .form-textarea-icon {
            position: absolute;
            left: 1rem;
            top: 0.875rem;
            color: var(--dark-gray);
            pointer-events: none;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.75rem;
        }
        
        .form-col {
            flex: 1;
            padding: 0 0.75rem;
            min-width: 250px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            font-family: 'Montserrat', sans-serif;
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: var(--medium-gray);
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-description {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-light);
            display: inline-block;
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
        
        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        
        .table-responsive {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 1200px) {
            .form-col {
                min-width: 300px;
            }
            
            .truncate {
                max-width: 200px;
            }
        }
        
        @media (max-width: 992px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: row;
                width: 100%;
            }
            
            .btn {
                flex: 1;
            }
            
            .truncate {
                max-width: 150px;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: center;
            }
            
            .logo-wrapper {
                margin-bottom: 1rem;
                text-align: center;
            }
            
            .user-menu {
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .user-info {
                margin-right: 0;
                margin-bottom: 0.5rem;
                width: 100%;
                justify-content: center;
            }
            
            .header-btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .btn {
                padding: 0.5rem;
                min-width: 40px;
            }
            
            .truncate {
                max-width: 100px;
            }
            
            .contracts-table th, 
            .contracts-table td {
                padding: 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .contracts-table {
                display: block;
                width: 100%;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .truncate {
                max-width: 80px;
            }
            
            .action-buttons {
                gap: 0.25rem;
            }
            
            .btn {
                padding: 0.4rem;
                font-size: 0.8rem;
            }
            
            .section-title {
                font-size: 1.25rem;
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
                <h1 class="app-title">Gestión de Contratos</h1>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <i class="fas fa-user-circle" style="font-size: 1.25rem;"></i>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombres']); ?></span>
                </div>
                <a href="modificar_usuario.php" class="header-btn" style="margin-right: 0.75rem;">
                    <i class="fas fa-user-edit btn-icon"></i>
                    Mi Perfil
                </a>
                <a href="back/logout.php" class="header-btn">
                    <i class="fas fa-sign-out-alt btn-icon"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="card fadeIn">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-file-contract card-title-icon"></i>
                    Mis Contratos
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($userContracts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open empty-icon"></i>
                        <h3 class="empty-title">No tienes contratos registrados</h3>
                        <p class="empty-description">Comienza agregando un nuevo contrato utilizando el formulario de abajo.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="contracts-table">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Fecha</th>
                                    <th>Objeto</th>
                                    <th>Proceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userContracts as $contract): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contract['numero_contrato']); ?></td>
                                    <td><?php echo htmlspecialchars($contract['fecha_contrato']); ?></td>
                                    <td class="justificado truncate"><?php echo htmlspecialchars($contract['objeto']); ?></td>
                                    <td><?php echo htmlspecialchars($contract['proceso_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="seleccion_informe.php?id=<?php echo $contract['id']; ?>" class="btn btn-ver" title="Ver Informe">
                                                <i class="fas fa-file-alt"></i> Ver
                                            </a>
                                            <a href="modificar_contrato.php?id=<?php echo $contract['id']; ?>" class="btn btn-warning" title="Modificar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="borrar_contrato.php?id=<?php echo $contract['id']; ?>" class="btn btn-danger" 
                                               onclick="return confirm('¿Está seguro de que desea borrar este contrato?');" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card fadeIn delay-1">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle card-title-icon"></i>
                    Agregar Nuevo Contrato
                </h2>
            </div>
            <div class="card-body">
                <form action="back/save_contrato.php" method="post" id="contract-form">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="email_contratista">Correo Institucional del Contratista</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input class="form-input" id="email_contratista" name="email_contratista" type="email" 
                                           placeholder="Ejemplo: WEBIDEXUD@UDISTRITAL.EDU.CO" required
                                           oninput="toUpperCase(this)">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="nombre_supervisor">Nombre del Apoyo a la Supervisión o Supervisor</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-user-tie input-icon"></i>
                                    <input class="form-input" id="nombre_supervisor" name="nombre_supervisor" type="text" 
                                           placeholder="Ejemplo: ROBERTO FERRO ESCOBAR" required
                                           oninput="toUpperCase(this)">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email_supervisor">Correo del Supervisor</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input class="form-input" id="email_supervisor" name="email_supervisor" type="email" 
                                           placeholder="Ejemplo: COORDINACIONTIIDEXUD@UDISTRITAL.EDU.CO" required
                                           oninput="toUpperCase(this)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="numero_contrato">Número de Contrato</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-hashtag input-icon"></i>
                                    <input class="form-input" id="numero_contrato" name="numero_contrato" type="text" 
                                           placeholder="Ejemplo: 255" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="fecha_contrato">Fecha del Contrato</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-calendar-alt input-icon"></i>
                                    <input class="form-input" id="fecha_contrato" name="fecha_contrato" type="date" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="objeto">Objeto del Contrato (Tal como aparece en la minuta)</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-align-left form-textarea-icon"></i>
                                    <textarea class="form-textarea" id="objeto" name="objeto" 
                                              placeholder="Ingrese el objeto del contrato" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title fadeIn delay-2">Datos Financieros</div>
                    
                    <div class="form-row fadeIn delay-2">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="fecha_inicio">Fecha de Inicio del Contrato</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-calendar-plus input-icon"></i>
                                    <input class="form-input" id="fecha_inicio" name="fecha_inicio" type="date" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="fecha_fin">Fecha de Terminación</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-calendar-check input-icon"></i>
                                    <input class="form-input" id="fecha_fin" name="fecha_fin" type="date" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="valor">Valor de la Obligación</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-dollar-sign input-icon"></i>
                                    <input class="form-input" id="valor" name="valor" type="text" 
                                           placeholder="Ingrese el valor del contrato" required
                                           oninput="formatCurrency(this)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="forma_pago">Forma de Pago (Tal como aparece en la minuta)</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-money-check-alt form-textarea-icon"></i>
                                    <textarea class="form-textarea" id="forma_pago" name="forma_pago" maxlength="1000" 
                                              placeholder="Ingrese la forma de pago establecida en el contrato" required></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="rp">Número de RP y Fecha de RP</label>
                                <div class="form-input-wrapper">
                                <i class="fas fa-file-invoice input-icon"></i>
                                    <input class="form-input" id="rp" name="rp" type="text" maxlength="100" 
                                           placeholder="Ejemplo: 11 de 25 de enero del 2025" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="disponibilidad_presupuestal">Número de disponibilidad presupuestal</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-file-invoice-dollar input-icon"></i>
                                    <input class="form-input" id="disponibilidad_presupuestal" name="disponibilidad_presupuestal" 
                                           type="text" maxlength="100" placeholder="Ingrese el número de CDP" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title fadeIn delay-3">Datos Bancarios</div>
                    
                    <div class="form-row fadeIn delay-3">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="cuenta">Número de Cuenta Bancaria</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-credit-card input-icon"></i>
                                    <input class="form-input" id="cuenta" name="cuenta" type="text" 
                                           placeholder="Ingrese el número de cuenta bancaria" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="banco">Banco</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-university input-icon"></i>
                                    <select class="form-select" id="banco" name="banco" required>
                                        <option value="">Seleccione un banco</option>
                                        <?php mysqli_data_seek($result_bancos, 0); ?>
                                        <?php while($banco = mysqli_fetch_assoc($result_bancos)): ?>
                                            <option value="<?php echo $banco['id']; ?>"><?php echo htmlspecialchars($banco['nombre']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="tipo_cuenta">Tipo de Cuenta</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-wallet input-icon"></i>
                                    <select class="form-select" id="tipo_cuenta" name="tipo_cuenta" required>
                                        <option value="">Seleccione tipo de cuenta</option>
                                        <option value="AHORROS">AHORROS</option>
                                        <option value="CORRIENTE">CORRIENTE</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title fadeIn delay-3">Información Institucional</div>
                    
                    <div class="form-row fadeIn delay-3">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="proceso">Proceso</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-tasks input-icon"></i>
                                    <select class="form-select" id="proceso" name="proceso" required>
                                        <option value="">Seleccione un proceso</option>
                                        <?php mysqli_data_seek($result_procesos, 0); ?>
                                        <?php while($proceso = mysqli_fetch_assoc($result_procesos)): ?>
                                            <option value="<?php echo $proceso['id']; ?>"><?php echo htmlspecialchars($proceso['nombre']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="unidad_academica">Unidad Académica y/o Administrativa</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-building input-icon"></i>
                                    <input class="form-input" id="unidad_academica" name="unidad_academica" type="text" 
                                           maxlength="100" placeholder="Ingrese la unidad académica o administrativa" required
                                           oninput="toUpperCase(this)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="sede">Sede</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-map-marker-alt input-icon"></i>
                                    <input class="form-input" id="sede" name="sede" type="text" maxlength="100" 
                                           placeholder="Ingrese la sede" required oninput="toUpperCase(this)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn fadeIn delay-3" name="registro_contrato">
                        <i class="fas fa-plus-circle" style="margin-right: 0.5rem;"></i>
                        Agregar Contrato
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <script>
        // Función para formatear moneda
        function formatCurrency(input) {
            // Eliminar cualquier caracter que no sea un dígito
            let value = input.value.replace(/\D/g, '');
            // Convertir a entero o valor cero si está vacío
            value = (parseInt(value) || 0).toString();
            // Agregar separador de miles
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            // Agregar símbolo de pesos
            input.value = '$' + value;
        }
        
        // Función para convertir a mayúsculas
        function toUpperCase(input) {
            input.value = input.value.toUpperCase();
        }
        
        // Mejorar interacción de los campos
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
            
            inputs.forEach(input => {
                // Cambiar color del icono al enfocar el campo
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-icon') || 
                                this.parentElement.querySelector('.form-textarea-icon');
                    if (icon) {
                        icon.style.color = '#003366';
                    }
                });
                
                // Restaurar color del icono al perder el foco
                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-icon') || 
                                this.parentElement.querySelector('.form-textarea-icon');
                    if (icon) {
                        icon.style.color = '';
                    }
                });
            });
            
            // Validación básica del formulario
            const contractForm = document.getElementById('contract-form');
            if (contractForm) {
                contractForm.addEventListener('submit', function(event) {
                    let hasError = false;
                    const requiredInputs = this.querySelectorAll('input[required], select[required], textarea[required]');
                    
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
            
            // Hacer que las filas de la tabla sean clickeables para mejorar UX en móviles
            const contractRows = document.querySelectorAll('.contracts-table tbody tr');
            contractRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Solo activar si no se hizo clic en un botón o enlace
                    if (!e.target.closest('a') && !e.target.closest('button')) {
                        // Obtener el enlace "Ver" de esta fila
                        const verLink = this.querySelector('.btn-ver');
                        if (verLink) {
                            // En dispositivos móviles, hace que toda la fila sea clickeable
                            if (window.innerWidth <= 768) {
                                window.location.href = verLink.getAttribute('href');
                            }
                        }
                    }
                });
                
                // Efecto hover en dispositivos móviles
                row.addEventListener('touchstart', function() {
                    this.classList.add('hover-effect');
                });
                
                row.addEventListener('touchend', function() {
                    this.classList.remove('hover-effect');
                });
            });
        });
    </script>
</body>
</html>
