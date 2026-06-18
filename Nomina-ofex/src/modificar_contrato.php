<?php
session_start();
include 'db/conexion.php';

if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$contract_id = $_GET['id'];

// Agregamos manejo de errores en la consulta principal
$query = "SELECT c.*, b.nombre as banco_nombre, p.nombre as proceso_nombre 
          FROM contrato_informe c 
          LEFT JOIN banco b ON c.banco_id = b.id 
          LEFT JOIN proceso p ON c.proceso_id = p.id 
          WHERE c.id = ? AND c.cc_contratista = ?";

$stmt = mysqli_prepare($conexion, $query);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, "is", $contract_id, $_SESSION['cedula']);
if (!mysqli_stmt_execute($stmt)) {
    die("Error al ejecutar la consulta: " . mysqli_error($conexion));
}

$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $contract = mysqli_fetch_assoc($result);
    // Verificamos que tenemos datos
    if (!$contract) {
        die("Error al obtener los datos del contrato");
    }
} else {
    die("Contrato no encontrado");
}

// Consulta de bancos con manejo de errores
$bancos_query = "SELECT * FROM banco WHERE estado = 1 ORDER BY nombre";
$bancos_result = mysqli_query($conexion, $bancos_query);
if (!$bancos_result) {
    die("Error al obtener la lista de bancos: " . mysqli_error($conexion));
}

// Consulta de procesos
$procesos_query = "SELECT * FROM proceso ORDER BY nombre";
$procesos_result = mysqli_query($conexion, $procesos_query);
if (!$procesos_result) {
    die("Error al obtener la lista de procesos: " . mysqli_error($conexion));
}

// Verificar si existe un otrosí para este contrato
$otrosi_query = "SELECT * FROM contrato_otrosi WHERE contrato_id = ? ORDER BY id DESC LIMIT 1";
$otrosi_stmt = mysqli_prepare($conexion, $otrosi_query);
mysqli_stmt_bind_param($otrosi_stmt, "i", $contract_id);
mysqli_stmt_execute($otrosi_stmt);
$otrosi_result = mysqli_stmt_get_result($otrosi_stmt);
$tiene_otrosi = mysqli_num_rows($otrosi_result) > 0;
$otrosi_data = $tiene_otrosi ? mysqli_fetch_assoc($otrosi_result) : null;

// Verificar si existe una cesión para este contrato
$cesion_query = "SELECT * FROM contrato_cesion WHERE contrato_id = ? ORDER BY id DESC LIMIT 1";
$cesion_stmt = mysqli_prepare($conexion, $cesion_query);
mysqli_stmt_bind_param($cesion_stmt, "i", $contract_id);
mysqli_stmt_execute($cesion_stmt);
$cesion_result = mysqli_stmt_get_result($cesion_stmt);
$tiene_cesion = mysqli_num_rows($cesion_result) > 0;
$cesion_data = $tiene_cesion ? mysqli_fetch_assoc($cesion_result) : null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Procesamiento del formulario cuando se envía
    $nombre_supervisor = mysqli_real_escape_string($conexion, $_POST['nombre_supervisor'] ?? '');
    $email_supervisor = mysqli_real_escape_string($conexion, $_POST['email_supervisor'] ?? '');
    $numero_contrato = mysqli_real_escape_string($conexion, $_POST['numero_contrato'] ?? '');
    $fecha_contrato = mysqli_real_escape_string($conexion, $_POST['fecha_contrato'] ?? '');
    $objeto = mysqli_real_escape_string($conexion, $_POST['objeto'] ?? '');
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio'] ?? '');
    $fecha_fin = mysqli_real_escape_string($conexion, $_POST['fecha_fin'] ?? '');
    $valor = str_replace(['$', "'"], '', $_POST['valor'] ?? '0');
    $forma_pago = mysqli_real_escape_string($conexion, $_POST['forma_pago'] ?? '');
    $rp = mysqli_real_escape_string($conexion, $_POST['rp'] ?? '');
    $disponibilidad_presupuestal = mysqli_real_escape_string($conexion, $_POST['disponibilidad_presupuestal'] ?? '');
    $cuenta_bancaria = mysqli_real_escape_string($conexion, $_POST['cuenta_bancaria'] ?? '');
    $banco_id = intval($_POST['banco'] ?? 0);
    
    // Validar que tipo_cuenta sea exactamente uno de los valores permitidos
    $tipo_cuenta_input = trim($_POST['tipo_cuenta'] ?? '');
    if ($tipo_cuenta_input == 'AHORROS') {
        $tipo_cuenta = 'AHORROS';
    } elseif ($tipo_cuenta_input == 'CORRIENTE') {
        $tipo_cuenta = 'CORRIENTE';
    } else {
        $tipo_cuenta = null; // Valor NULL si no coincide con los permitidos
    }
    
    $proceso_id = intval($_POST['proceso'] ?? 0);
    $unidad_academica = mysqli_real_escape_string($conexion, $_POST['unidad_academica'] ?? '');
    $sede = mysqli_real_escape_string($conexion, $_POST['sede'] ?? '');
    $cc_contratista = $_SESSION['cedula'];

    // Verificar la cantidad de parámetros en la consulta
    $update_query = "UPDATE contrato_informe SET 
                     nombre_supervisor = ?,
                     email_supervisor = ?,
                     numero_contrato = ?,
                     fecha_contrato = ?,
                     objeto = ?,
                     fecha_inicio = ?,
                     fecha_fin = ?,
                     valor = ?,
                     forma_pago = ?,
                     rp = ?,
                     disponibilidad_presupuestal = ?,
                     cuenta_bancaria = ?,
                     banco_id = ?,
                     tipo_cuenta = ?,
                     proceso_id = ?,
                     unidad_academica = ?,
                     sede = ?
                     WHERE id = ?";

    $stmt = mysqli_prepare($conexion, $update_query);
    if (!$stmt) {
        die("Error en la preparación de la actualización: " . mysqli_error($conexion));
    }

    // Contar cuántos ? hay en la consulta para asegurarnos que coinciden con los parámetros
    $param_count = substr_count($update_query, '?');
    
    // Aquí hay exactamente 18 parámetros, igual que los ? en la consulta
    mysqli_stmt_bind_param($stmt, "sssssssdssssissssi", 
        $nombre_supervisor,
        $email_supervisor,
        $numero_contrato,
        $fecha_contrato,
        $objeto,
        $fecha_inicio,
        $fecha_fin,
        $valor,
        $forma_pago,
        $rp,
        $disponibilidad_presupuestal,
        $cuenta_bancaria,
        $banco_id,
        $tipo_cuenta,
        $proceso_id,
        $unidad_academica,
        $sede,
        $contract_id
    );

    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        die("Error al actualizar el contrato: " . mysqli_error($conexion) . " - " . mysqli_stmt_error($stmt));
    }

    // Procesar otrosí
    if (isset($_POST['tiene_otrosi']) && $_POST['tiene_otrosi'] == 1) {
        $fecha_inicio_otrosi = mysqli_real_escape_string($conexion, $_POST['fecha_inicio_otrosi'] ?? '');
        $fecha_fin_otrosi = mysqli_real_escape_string($conexion, $_POST['fecha_fin_otrosi'] ?? '');
        
        // Verificar si ya existe un otrosí para este contrato
        if ($tiene_otrosi) {
            // Actualizar otrosí existente
            $otrosi_update = "UPDATE contrato_otrosi SET 
                              fecha_inicio_otrosi = ?,
                              fecha_fin_otrosi = ?
                              WHERE contrato_id = ?";
            $otrosi_stmt = mysqli_prepare($conexion, $otrosi_update);
            mysqli_stmt_bind_param($otrosi_stmt, "ssi", $fecha_inicio_otrosi, $fecha_fin_otrosi, $contract_id);
            mysqli_stmt_execute($otrosi_stmt);
        } else {
            // Insertar nuevo otrosí
            $otrosi_insert = "INSERT INTO contrato_otrosi 
                             (contrato_id, cc_contratista, numero_contrato, fecha_contrato, fecha_inicio_otrosi, fecha_fin_otrosi)
                             VALUES (?, ?, ?, ?, ?, ?)";
            $otrosi_stmt = mysqli_prepare($conexion, $otrosi_insert);
            mysqli_stmt_bind_param($otrosi_stmt, "iissss", 
                $contract_id, 
                $cc_contratista, 
                $numero_contrato, 
                $fecha_contrato, 
                $fecha_inicio_otrosi, 
                $fecha_fin_otrosi
            );
            mysqli_stmt_execute($otrosi_stmt);
        }
    } else if ($tiene_otrosi) {
        // El checkbox de otrosí no está marcado pero existía un otrosí antes
        // Eliminar el otrosí asociado al contrato
        $delete_otrosi_query = "DELETE FROM contrato_otrosi WHERE contrato_id = ?";
        $delete_otrosi_stmt = mysqli_prepare($conexion, $delete_otrosi_query);
        mysqli_stmt_bind_param($delete_otrosi_stmt, "i", $contract_id);
        mysqli_stmt_execute($delete_otrosi_stmt);
    }

    // Procesar cesión
    if (isset($_POST['tiene_cesion']) && $_POST['tiene_cesion'] == 1) {
        $fecha_cesion = mysqli_real_escape_string($conexion, $_POST['fecha_cesion'] ?? '');
        $cc_cesionario = mysqli_real_escape_string($conexion, $_POST['cc_cesionario'] ?? '');
        $nombre_cesionario = mysqli_real_escape_string($conexion, $_POST['nombre_cesionario'] ?? '');
        
        // Verificar si ya existe una cesión para este contrato
        if ($tiene_cesion) {
            // Actualizar cesión existente
            $cesion_update = "UPDATE contrato_cesion SET 
                             fecha_cesion = ?,
                             cc_cesionario = ?,
                             nombre_cesionario = ?
                             WHERE contrato_id = ?";
            $cesion_stmt = mysqli_prepare($conexion, $cesion_update);
            mysqli_stmt_bind_param($cesion_stmt, "sisi", $fecha_cesion, $cc_cesionario, $nombre_cesionario, $contract_id);
            mysqli_stmt_execute($cesion_stmt);
        } else {
            // Insertar nueva cesión
            $cesion_insert = "INSERT INTO contrato_cesion 
                            (contrato_id, cc_contratista, numero_contrato, fecha_contrato, fecha_cesion, cc_cesionario, nombre_cesionario)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $cesion_stmt = mysqli_prepare($conexion, $cesion_insert);
            mysqli_stmt_bind_param($cesion_stmt, "iisssis", 
                $contract_id, 
                $cc_contratista, 
                $numero_contrato, 
                $fecha_contrato, 
                $fecha_cesion, 
                $cc_cesionario, 
                $nombre_cesionario
            );
            mysqli_stmt_execute($cesion_stmt);
        }
    } else if ($tiene_cesion) {
        // El checkbox de cesión no está marcado pero existía una cesión antes
        // Eliminar la cesión asociada al contrato
        $delete_cesion_query = "DELETE FROM contrato_cesion WHERE contrato_id = ?";
        $delete_cesion_stmt = mysqli_prepare($conexion, $delete_cesion_query);
        mysqli_stmt_bind_param($delete_cesion_stmt, "i", $contract_id);
        mysqli_stmt_execute($delete_cesion_stmt);
    }

    if ($success) {
        header("Location: contratos.php");
        exit();
    } else {
        die("Error al actualizar el contrato: " . mysqli_error($conexion));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Contrato | Universidad Distrital</title>
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
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
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
            max-width: 1400px;
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
        
        .card {
            background-color: var(--white);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
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
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-light);
            display: inline-block;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .form-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            cursor: pointer;
        }
        
        .form-checkbox-label {
            font-weight: 600;
            cursor: pointer;
        }
        
        .conditional-fields {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            border-left: 4px solid;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 0 0.375rem 0.375rem 0;
            transition: var(--transition);
        }
        
        .conditional-fields.blue {
            border-color: var(--info);
        }
        
        .conditional-fields.green {
            border-color: var(--success);
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
        
        @media (max-width: 992px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                width: 100%;
            }
            
            .btn-row {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.75rem;
            }
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
                <h1 class="app-title">Modificar Contrato</h1>
                <a href="contratos.php" class="back-link">
                    <i class="fas fa-arrow-left back-icon"></i>
                    Volver a Contratos
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="card fadeIn">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-edit card-title-icon"></i>
                    Editar Contrato #<?php echo htmlspecialchars($contract['numero_contrato'] ?? ''); ?>
                </h2>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars("modificar_contrato.php?id=" . $contract_id); ?>" method="post" id="update-contract-form">
                    
                    <div class="section-title fadeIn delay-1">Información General</div>
                    
                    <div class="form-row fadeIn delay-1">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="nombre_supervisor">Nombre del Supervisor / Apoyo a la Supervisión</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-user-tie input-icon"></i>
                                    <input class="form-input" id="nombre_supervisor" name="nombre_supervisor" type="text" 
                                           value="<?php echo htmlspecialchars($contract['nombre_supervisor'] ?? ''); ?>" 
                                           oninput="toUpperCase(this)" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email_supervisor">Correo del Supervisor / Apoyo a la supervisión</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input class="form-input" id="email_supervisor" name="email_supervisor" type="email" 
                                           value="<?php echo htmlspecialchars($contract['email_supervisor'] ?? ''); ?>" 
                                           oninput="toUpperCase(this)" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="numero_contrato">Número de Contrato</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-hashtag input-icon"></i>
                                    <input class="form-input" id="numero_contrato" name="numero_contrato" type="text" 
                                           value="<?php echo htmlspecialchars($contract['numero_contrato'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="fecha_contrato">Fecha del Contrato</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-calendar-alt input-icon"></i>
                                    <input class="form-input" id="fecha_contrato" name="fecha_contrato" type="date" 
                                           value="<?php echo htmlspecialchars($contract['fecha_contrato'] ?? $contract['anio'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group fadeIn delay-1">
                        <label class="form-label" for="objeto">Objeto del Contrato</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-align-left form-textarea-icon"></i>
                            <textarea class="form-textarea" id="objeto" name="objeto" required><?php echo htmlspecialchars($contract['objeto'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="section-title fadeIn delay-2">Fechas y Valores</div>
                    
                    <div class="form-row fadeIn delay-2">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="fecha_inicio">Fecha de Inicio</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-calendar-plus input-icon"></i>
                                    <input class="form-input" id="fecha_inicio" name="fecha_inicio" type="date" 
                                           value="<?php echo htmlspecialchars($contract['fecha_inicio'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="fecha_fin">Fecha de Finalización</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-calendar-check input-icon"></i>
                                    <input class="form-input" id="fecha_fin" name="fecha_fin" type="date" 
                                           value="<?php echo htmlspecialchars($contract['fecha_fin'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="valor">Valor del Contrato</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-dollar-sign input-icon"></i>
                                    <input class="form-input" id="valor" name="valor" type="text" 
                                        value="<?php echo '$' . number_format($contract['valor'] ?? 0, 0, '', "'"); ?>" 
                                        oninput="formatCurrency(this)" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row fadeIn delay-2">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="forma_pago">Forma de Pago</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-money-check-alt form-textarea-icon"></i>
                                    <textarea class="form-textarea" id="forma_pago" name="forma_pago" maxlength="1000" required><?php echo htmlspecialchars($contract['forma_pago'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="rp">Número de RP y Fecha de RP</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-file-invoice input-icon"></i>
                                    <input class="form-input" id="rp" name="rp" type="text" maxlength="100" 
                                           value="<?php echo htmlspecialchars($contract['rp'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="disponibilidad_presupuestal">Número de disponibilidad presupuestal</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-file-invoice-dollar input-icon"></i>
                                    <input class="form-input" id="disponibilidad_presupuestal" name="disponibilidad_presupuestal" type="text" maxlength="100" 
                                           value="<?php echo htmlspecialchars($contract['disponibilidad_presupuestal'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title fadeIn delay-2">Datos Bancarios</div>
                    
                    <div class="form-row fadeIn delay-2">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="cuenta_bancaria">Número de Cuenta Bancaria</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-credit-card input-icon"></i>
                                    <input class="form-input" id="cuenta_bancaria" name="cuenta_bancaria" type="text" 
                                           value="<?php echo htmlspecialchars($contract['cuenta_bancaria'] ?? ''); ?>" required>
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
                                        <?php 
                                        mysqli_data_seek($bancos_result, 0); // Reiniciar el puntero del resultado
                                        while($banco = mysqli_fetch_assoc($bancos_result)): ?>
                                            <option value="<?php echo $banco['id']; ?>" 
                                                <?php echo ($banco['id'] == ($contract['banco_id'] ?? '')) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($banco['nombre']); ?>
                                            </option>
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
                                        <option value="AHORROS" <?php echo (($contract['tipo_cuenta'] ?? '') === 'AHORROS') ? 'selected' : ''; ?>>AHORROS</option>
                                        <option value="CORRIENTE" <?php echo (($contract['tipo_cuenta'] ?? '') === 'CORRIENTE') ? 'selected' : ''; ?>>CORRIENTE</option>
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
                                        <?php 
                                        mysqli_data_seek($procesos_result, 0); // Reiniciar el puntero del resultado
                                        while($proceso = mysqli_fetch_assoc($procesos_result)): ?>
                                            <option value="<?php echo $proceso['id']; ?>" 
                                                <?php echo ($proceso['id'] == ($contract['proceso_id'] ?? '')) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($proceso['nombre']); ?>
                                            </option>
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
                                    <input class="form-input" id="unidad_academica" name="unidad_academica" type="text" maxlength="100" 
                                           value="<?php echo htmlspecialchars($contract['unidad_academica'] ?? ''); ?>" 
                                           oninput="toUpperCase(this)" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="sede">Sede</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-map-marker-alt input-icon"></i>
                                    <input class="form-input" id="sede" name="sede" type="text" maxlength="100" 
                                           value="<?php echo htmlspecialchars($contract['sede'] ?? ''); ?>" 
                                           oninput="toUpperCase(this)" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECCIÓN OTROSÍ -->
                    <div class="section-title fadeIn delay-3">Información Adicional</div>
                    
                    <div class="fadeIn delay-3">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="tiene_otrosi" name="tiene_otrosi" value="1" 
                                   onclick="toggleOtrosi()" 
                                   <?php echo $tiene_otrosi ? 'checked' : ''; ?> 
                                   class="form-checkbox">
                            <label class="form-checkbox-label" for="tiene_otrosi">
                                <i class="fas fa-file-contract" style="margin-right: 0.5rem; color: var(--info);"></i>
                                El contrato tiene OTROSÍ
                            </label>
                        </div>
                        
                        <div id="otrosi_fields" class="conditional-fields blue">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label" for="fecha_inicio_otrosi">Fecha de Inicio del OTROSÍ</label>
                                        <div class="form-input-wrapper">
                                            <i class="fas fa-calendar input-icon"></i>
                                            <input class="form-input" id="fecha_inicio_otrosi" name="fecha_inicio_otrosi" type="date" 
                                                   value="<?php echo htmlspecialchars($otrosi_data['fecha_inicio_otrosi'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label" for="fecha_fin_otrosi">Fecha de Finalización del OTROSÍ</label>
                                        <div class="form-input-wrapper">
                                            <i class="fas fa-calendar-check input-icon"></i>
                                            <input class="form-input" id="fecha_fin_otrosi" name="fecha_fin_otrosi" type="date" 
                                                   value="<?php echo htmlspecialchars($otrosi_data['fecha_fin_otrosi'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SECCIÓN CESIÓN -->
                        <div class="checkbox-wrapper mt-4">
                            <input type="checkbox" id="tiene_cesion" name="tiene_cesion" value="1" 
                                   onclick="toggleCesion()" 
                                   <?php echo $tiene_cesion ? 'checked' : ''; ?> 
                                   class="form-checkbox">
                            <label class="form-checkbox-label" for="tiene_cesion">
                                <i class="fas fa-handshake" style="margin-right: 0.5rem; color: var(--success);"></i>
                                El contrato tiene CESIÓN
                            </label>
                        </div>
                        
                        <div id="cesion_fields" class="conditional-fields green">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label" for="fecha_cesion">Fecha de CESIÓN</label>
                                        <div class="form-input-wrapper">
                                            <i class="fas fa-calendar input-icon"></i>
                                            <input class="form-input" id="fecha_cesion" name="fecha_cesion" type="date" 
                                                   value="<?php echo htmlspecialchars($cesion_data['fecha_cesion'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label" for="cc_cesionario">Cédula del Cesionario</label>
                                        <div class="form-input-wrapper">
                                            <i class="fas fa-id-card input-icon"></i>
                                            <input class="form-input" id="cc_cesionario" name="cc_cesionario" type="text" 
                                                   value="<?php echo htmlspecialchars($cesion_data['cc_cesionario'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-col">
                                    <div class="form-group">
                                        <label class="form-label" for="nombre_cesionario">Nombre del Cesionario</label>
                                        <div class="form-input-wrapper">
                                            <i class="fas fa-user input-icon"></i>
                                            <input class="form-input" id="nombre_cesionario" name="nombre_cesionario" type="text" 
                                                   value="<?php echo htmlspecialchars($cesion_data['nombre_cesionario'] ?? ''); ?>"
                                                   oninput="toUpperCase(this)">
                                        </div>
                                    </div>
                                </div>
                            </div>
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
        // Función para formatear moneda
        function formatCurrency(input) {
            let value = input.value.replace(/\D/g, '');
            value = (parseInt(value) || 0).toString();
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            input.value = ' + value;
        }
        
        // Función para convertir a mayúsculas
        function toUpperCase(input) {
            input.value = input.value.toUpperCase();
        }

        // Función para mostrar/ocultar campos de OTROSÍ
        function toggleOtrosi() {
            const checkbox = document.getElementById('tiene_otrosi');
            const otrosi_fields = document.getElementById('otrosi_fields');
            
            if (checkbox.checked) {
                otrosi_fields.style.display = 'block';
            } else {
                otrosi_fields.style.display = 'none';
            }
        }

        // Función para mostrar/ocultar campos de CESIÓN
        function toggleCesion() {
            const checkbox = document.getElementById('tiene_cesion');
            const cesion_fields = document.getElementById('cesion_fields');
            
            if (checkbox.checked) {
                cesion_fields.style.display = 'block';
            } else {
                cesion_fields.style.display = 'none';
            }
        }

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            toggleOtrosi();
            toggleCesion();
            
            // Mejorar interacción de los campos
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
            const updateForm = document.getElementById('update-contract-form');
            if (updateForm) {
                updateForm.addEventListener('submit', function(event) {
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
        });
    </script>
</body>
</html>
