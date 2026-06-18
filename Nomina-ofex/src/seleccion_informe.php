<?php
session_start();
include 'db/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se proporcionó un ID de contrato
if (!isset($_GET['id'])) {
    header("Location: contratos.php");
    exit();
}

$id_contrato = $_GET['id'];
$cc_contratista = $_SESSION['cedula'];

// Verificar si el contrato pertenece al usuario actual
$query = "SELECT ci.*, b.nombre as banco_nombre, p.nombre as proceso_nombre 
          FROM contrato_informe ci 
          LEFT JOIN banco b ON ci.banco_id = b.id 
          LEFT JOIN proceso p ON ci.proceso_id = p.id 
          WHERE ci.id = ? AND ci.cc_contratista = ?";

$stmt = mysqli_prepare($conexion, $query);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, "is", $id_contrato, $cc_contratista);
if (!mysqli_stmt_execute($stmt)) {
    die("Error al ejecutar la consulta: " . mysqli_error($conexion));
}

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // El contrato no existe o no pertenece al usuario actual
    header("Location: contratos.php");
    exit();
}

$contrato = mysqli_fetch_assoc($result);


function tieneActividadesRegistradas($id_contrato, $cc_contratista, $conexion) {
    $query = "SELECT COUNT(*) as total FROM contrato_actividades 
              WHERE contrato_id = ? AND cc_contratista = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "is", $id_contrato, $cc_contratista);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return ($row['total'] > 0);
}

$tieneActividades = tieneActividadesRegistradas($id_contrato, $_SESSION['cedula'], $conexion);
$rutaDestino = $tieneActividades ? "index3.php" : "actividades_manuales.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Informe | Universidad Distrital</title>
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
            padding: 3rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1.25rem 2rem;
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
            width: 45px;
            height: 45px;
            overflow: hidden;
            margin-right: 1rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            border-radius: 50%;
        }
        
        .app-title {
            font-size: 1.35rem;
            font-weight: 600;
            margin-right: 2rem;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .back-link {
            display: flex;
            align-items: center;
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            margin-left: 1.5rem;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            background-color: rgba(255, 255, 255, 0.15);
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .back-link:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .back-icon {
            margin-right: 0.6rem;
        }
        
        .page-title {
            text-align: center;
            margin: 0 0 2.5rem 0;
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 1rem;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }
        
        .contract-info {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 3rem;
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contract-info:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .contract-header {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: var(--white);
            padding: 1.2rem 1.8rem;
            display: flex;
            align-items: center;
        }
        
        .contract-header-icon {
            margin-right: 1rem;
            font-size: 1.4rem;
            background-color: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .contract-header-title {
            font-size: 1.35rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .contract-body {
            padding: 2rem;
        }
        
        .contract-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .detail-item {
            margin-bottom: 1.2rem;
            position: relative;
            padding-left: 0.5rem;
        }
        
        .detail-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.4rem;
            bottom: 0.4rem;
            width: 3px;
            background-color: var(--primary-light);
            opacity: 0.3;
            border-radius: 3px;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .options-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin: 0 auto;
            max-width: 1100px;
        }
        
        .option-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid var(--medium-gray);
        }
        
        .option-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }
        
        .option-header {
            background-color: var(--white);
            padding: 2rem 1.5rem;
            position: relative;
            text-align: center;
            border-bottom: 1px solid var(--medium-gray);
        }
        
        .option-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(0, 51, 102, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .option-card:hover .option-icon-wrapper {
            transform: scale(1.1);
        }
        
        .option-icon {
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .option-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .option-subtitle {
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .option-body {
            padding: 2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--white);
        }
        
        .option-description {
            margin-bottom: 1.8rem;
            color: var(--text-dark);
            flex-grow: 1;
            line-height: 1.7;
            font-size: 1rem;
        }
        
        .option-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .option-feature {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .feature-icon {
            color: var(--success);
            margin-right: 0.75rem;
            font-size: 1.2rem;
            margin-top: 0.1rem;
        }
        
        .option-cta {
            margin-top: auto;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.9rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }
        
        .btn:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 100%;
            transition: all 0.3s;
            z-index: -1;
        }
        
        .btn:hover:before {
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.3);
        }
        
        .btn-primary:after {
            background-color: var(--primary-color);
        }
        
        .btn-primary:before {
            background-color: var(--primary-dark);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 51, 102, 0.4);
        }
        
        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--text-dark);
            border: 1px solid var(--medium-gray);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary:after {
            background-color: var(--light-gray);
        }
        
        .btn-secondary:before {
            background-color: var(--medium-gray);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-accent {
            background-color: var(--accent-color);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }
        
        .btn-accent:after {
            background-color: var(--accent-color);
        }
        
        .btn-accent:before {
            background-color: var(--accent-hover);
        }
        
        .btn-accent:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 140, 0, 0.4);
        }
        
        .btn-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .footer {
            text-align: center;
            padding: 1.8rem;
            background-color: var(--white);
            color: var(--text-light);
            font-size: 0.85rem;
            border-top: 1px solid var(--medium-gray);
            margin-top: auto;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        @media (max-width: 1100px) {
            .options-container {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 2rem 1.5rem;
            }
            
            .header-content {
                flex-wrap: wrap;
            }
            
            .logo-wrapper {
                margin-bottom: 0.5rem;
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            
            .app-title {
                margin-right: 0;
                margin-bottom: 0.5rem;
                width: 100%;
                text-align: center;
            }
            
            .back-link {
                margin: 0.5rem auto 0;
            }
            
            .options-container {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .contract-details {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .contract-header-title {
                font-size: 1.2rem;
            }
            
            .contract-body {
                padding: 1.5rem;
            }
            
            .option-card {
                max-width: 100%;
            }
            
            .option-header {
                padding: 1.5rem 1rem;
            }
            
            .option-icon-wrapper {
                width: 70px;
                height: 70px;
            }
            
            .option-title {
                font-size: 1.2rem;
            }
            
            .option-body {
                padding: 1.5rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fadeIn {
            animation: fadeIn 0.7s ease-out forwards;
            opacity: 0;
        }
        
        .delay-1 {
            animation-delay: 0.1s;
        }
        
        .delay-2 {
            animation-delay: 0.2s;
        }
        
        .delay-3 {
            animation-delay: 0.35s;
        }
        
        .delay-4 {
            animation-delay: 0.5s;
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
                <h1 class="app-title">Selección de Informe</h1>
                <a href="contratos.php" class="back-link">
                    <i class="fas fa-arrow-left back-icon"></i>
                    Volver a Contratos
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <h1 class="page-title fadeIn">Generación de Informes</h1>
        
        <div class="contract-info fadeIn delay-1">
            <div class="contract-header">
                <i class="fas fa-file-contract contract-header-icon"></i>
                <h2 class="contract-header-title">Información del Contrato</h2>
            </div>
            <div class="contract-body">
                <div class="contract-details">
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Número de contrato</div>
                            <div class="detail-value"><?php echo htmlspecialchars($contrato['numero_contrato']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Fecha de contrato</div>
                            <div class="detail-value">
                                <?php 
                                    $fecha = new DateTime($contrato['fecha_contrato']);
                                    echo $fecha->format('d/m/Y'); 
                                ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Supervisor</div>
                            <div class="detail-value"><?php echo htmlspecialchars($contrato['nombre_supervisor']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Proceso</div>
                            <div class="detail-value"><?php echo htmlspecialchars($contrato['proceso_nombre'] ?? 'No especificado'); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Valor</div>
                            <div class="detail-value"><?php echo '$' . number_format((float)$contrato['valor'], 0, '', '.'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Unidad Académica</div>
                            <div class="detail-value"><?php echo htmlspecialchars($contrato['unidad_academica'] ?? 'No especificada'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="page-title fadeIn delay-2" style="font-size: 1.5rem; margin-top: 1rem;">
            Seleccione el tipo de informe que desea generar
        </h2>

        <div class="options-container">
            <!-- Opción 1: Informe Planner (Antiguo) -->
            <div class="option-card fadeIn delay-2">
                <div class="option-header">
                    <div class="option-icon-wrapper">
                        <i class="fas fa-tasks option-icon"></i>
                    </div>
                    <h3 class="option-title">Informe con Planner</h3>
                    <p class="option-subtitle">Formato Clásico</p>
                </div>
                <div class="option-body">
                    <p class="option-description">
                        Genera un informe de actividades importando datos desde Microsoft Planner
                        en el formato tradicional (Word y Excel).
                    </p>
                    <ul class="option-features">
                        <li class="option-feature">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <span>Importación desde archivo de Planner</span>
                        </li>
                        <li class="option-feature">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <span>Generación de Word y Excel</span>
                        </li>
                        <li class="option-feature">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <span>Formato clásico de informe</span>
                        </li>
                    </ul>
                    <div class="option-cta">
                        <a href="index.php?id=<?php echo $id_contrato; ?>" class="btn btn-primary">
                            <i class="fas fa-file-word btn-icon"></i>
                            Seleccionar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 2: Informe Planner (Nuevo) -->
            <div class="option-card fadeIn delay-3">
                <div class="option-header">
                    <div class="option-icon-wrapper" style="background-color: rgba(255, 140, 0, 0.1);">
                        <i class="fas fa-chart-line option-icon" style="color: var(--accent-color);"></i>
                    </div>
                    <h3 class="option-title" style="color: var(--accent-color);">Informe con Planner</h3>
                    <p class="option-subtitle">Formato Nuevo</p>
                </div>
                <div class="option-body">
                    <p class="option-description">
                        Genera un informe de actividades importando datos desde Microsoft Planner
                        en el nuevo formato mejorado (Excel).
                    </p>
                    <ul class="option-features">
                        <li class="option-feature">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <span>Importación desde archivo de Planner</span>
                        </li>
                        <li class="option-feature">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <span>Generación en formato Excel único</span>
                        </li>
                        <li class="option-feature">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <span>Diseño mejorado y optimizado</span>
                        </li>
                    </ul>
                    <div class="option-cta">
                        <a href="index2.php?id=<?php echo $id_contrato; ?>" class="btn btn-accent">
                            <i class="fas fa-file-excel btn-icon"></i>
                            Seleccionar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Opción 3: Informe Manual -->
            <div class="option-card fadeIn delay-4">
    <div class="option-header">
        <div class="option-icon-wrapper" style="background-color: rgba(59, 130, 246, 0.1);">
                <i class="fas fa-edit option-icon" style="color: var(--info);"></i>
                </div>
                <h3 class="option-title" style="color: var(--info);">Informe Manual</h3>
                <p class="option-subtitle">Sin Planner</p>
            </div>
            <div class="option-body">
                <p class="option-description">
                    Genera un informe de actividades sin importar datos externos.
                    Ideal para informes sencillos o cuando no se usa Planner.
                </p>
                <ul class="option-features">
                    <li class="option-feature">
                        <i class="fas fa-check-circle feature-icon"></i>
                        <span>No requiere archivo externo</span>
                    </li>
                    <li class="option-feature">
                        <i class="fas fa-check-circle feature-icon"></i>
                        <span>Proceso más rápido y directo</span>
                    </li>
                    <li class="option-feature">
                        <i class="fas fa-check-circle feature-icon"></i>
                        <span>Ideal para informes sencillos</span>
                    </li>
                </ul>
                <div class="option-cta">
                    <!-- Siempre dirigimos a actividades_manuales.php, que decidirá si mostrar las actividades o permitir su edición -->
                    <a href="actividades_manuales.php?id=<?php echo $id_contrato; ?>" class="btn btn-secondary">
                        <i class="fas fa-pencil-alt btn-icon"></i>
                        Seleccionar
                    </a>
                </div>
            </div>
        </div>
        </div>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
</body>
</html>
