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

// Verificar si ya existen actividades para este contrato
$actividades_query = "SELECT * FROM contrato_actividades 
                    WHERE contrato_id = ? AND cc_contratista = ? 
                    ORDER BY orden ASC";
$actividades_stmt = mysqli_prepare($conexion, $actividades_query);
mysqli_stmt_bind_param($actividades_stmt, "is", $id_contrato, $cc_contratista);
mysqli_stmt_execute($actividades_stmt);
$actividades_result = mysqli_stmt_get_result($actividades_stmt);
$actividades = [];
$num_actividades = mysqli_num_rows($actividades_result);
$tiene_actividades = ($num_actividades > 0);

// Cargar actividades existentes
while ($actividad = mysqli_fetch_assoc($actividades_result)) {
    $actividades[] = $actividad;
}

// Verificar si estamos en modo edición
$modo_edicion = isset($_GET['editar']) && $_GET['editar'] === 'true';

// Procesar guardado de actividades
$mensaje = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar si se está enviando el formulario de actividades o si solo se está redirigiendo
    if (isset($_POST['guardar_actividades'])) {
        // Iniciar transacción
        mysqli_begin_transaction($conexion);
        
        try {
            // Primero eliminar todas las actividades existentes para este contrato
            $delete_query = "DELETE FROM contrato_actividades WHERE contrato_id = ? AND cc_contratista = ?";
            $delete_stmt = mysqli_prepare($conexion, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "is", $id_contrato, $cc_contratista);
            mysqli_stmt_execute($delete_stmt);
            
            // Luego insertar las nuevas actividades
            if (isset($_POST['actividades']) && is_array($_POST['actividades'])) {
                $orden = 1;
                foreach ($_POST['actividades'] as $actividad_data) {
                    if (empty($actividad_data['descripcion'])) continue;
                    
                    // Insertar actividad
                    $insert_actividad_query = "INSERT INTO contrato_actividades 
                                            (cc_contratista, contrato_id, numero_contrato, fecha_contrato, 
                                            descripcion_actividad, orden) 
                                            VALUES (?, ?, ?, ?, ?, ?)";
                    $insert_actividad_stmt = mysqli_prepare($conexion, $insert_actividad_query);
                    mysqli_stmt_bind_param(
                        $insert_actividad_stmt, 
                        "sisssi", 
                        $cc_contratista, 
                        $id_contrato, 
                        $contrato['numero_contrato'], 
                        $contrato['fecha_contrato'], 
                        $actividad_data['descripcion'], 
                        $orden
                    );
                    mysqli_stmt_execute($insert_actividad_stmt);
                    
                    $orden++;
                }
            }
            
            // Confirmar la transacción
            mysqli_commit($conexion);
            
            $mensaje = [
                'tipo' => 'success',
                'texto' => 'Las actividades contractuales se han guardado correctamente.'
            ];
            
            // Recargar las actividades
            mysqli_stmt_execute($actividades_stmt);
            $actividades_result = mysqli_stmt_get_result($actividades_stmt);
            $actividades = [];
            $num_actividades = mysqli_num_rows($actividades_result);
            $tiene_actividades = ($num_actividades > 0);
            
            while ($actividad = mysqli_fetch_assoc($actividades_result)) {
                $actividades[] = $actividad;
            }
            
        } catch (Exception $e) {
            // Si hay un error, revertir la transacción
            mysqli_rollback($conexion);
            
            $mensaje = [
                'tipo' => 'error',
                'texto' => 'Error al guardar las actividades: ' . $e->getMessage()
            ];
        }
    } elseif (isset($_POST['continuar_informe'])) {
        // Redireccionar al usuario al informe sin modificar nada
        header("Location: index3.php?id=" . $id_contrato);
        exit();
    }
}

// Función para formatear fechas
function formatearFechaSimple($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades Contractuales | Universidad Distrital</title>
    <link rel="icon" type="image/png" href="images/favicon.png"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
    <style>
        :root {
            --primary-color: #003366;
            --primary-dark: #002244;
            --primary-light: #004488;
            --primary-ultra-light: #E6F0FF;
            --accent-color: #FF8C00;
            --accent-dark: #E67E00;
            --accent-light: #FFD700;
            --white: #FFFFFF;
            --light-gray: #F5F7FA;
            --medium-gray: #E0E5EC;
            --dark-gray: #6B7280;
            --text-dark: #1F2937;
            --success: #10B981;
            --success-light: #ECFDF5;
            --error: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --border-color: #D1D5DB;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 15px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 1;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo-wrapper {
            display: flex;
            align-items: center;
        }
        
        .logo-img {
            height: 45px;
            margin-right: 15px;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
        }
        
        .app-title {
            font-size: 1.3rem;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            background-color: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            backdrop-filter: blur(5px);
        }
        
        .user-name {
            margin-right: 15px;
            font-weight: 500;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
        }
        
        .btn-secondary {
            background: linear-gradient(to right, var(--medium-gray), #C5CAD3);
            color: var(--text-dark);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(to right, #C5CAD3, var(--medium-gray));
            color: var(--text-dark);
        }
        
        .btn-success {
            background: linear-gradient(to right, var(--success), #0D9668);
            color: var(--white);
        }
        
        .btn-success:hover {
            background: linear-gradient(to right, #0D9668, var(--success));
        }
        
        .btn-danger {
            background: linear-gradient(to right, var(--error), #DC2626);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background: linear-gradient(to right, #DC2626, var(--error));
        }
        
        .btn-warning {
            background: linear-gradient(to right, var(--warning), #D97706);
            color: var(--white);
        }
        
        .btn-warning:hover {
            background: linear-gradient(to right, #D97706, var(--warning));
        }
        
        .btn-info {
            background: linear-gradient(to right, var(--info), #1D4ED8);
            color: var(--white);
        }
        
        .btn-info:hover {
            background: linear-gradient(to right, #1D4ED8, var(--info));
        }
        
        .btn-icon {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(209, 213, 219, 0.3);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--accent-color), var(--accent-light));
        }
        
        .card-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
        }
        
        .card-icon {
            margin-right: 12px;
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 24px;
        }
        
        .contratos-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .contratos-table th, .contratos-table td {
            padding: 14px 18px;
            text-align: left;
        }
        
        .contratos-table th {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: var(--white);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }
        
        .contratos-table th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 25%;
            height: 50%;
            width: 1px;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .contratos-table tr {
            transition: background-color 0.2s ease;
        }
        
        .contratos-table tr:nth-child(even) {
            background-color: var(--light-gray);
        }
        
        .contratos-table tr:hover {
            background-color: var(--primary-ultra-light);
        }
        
        .contratos-table td {
            border-bottom: 1px solid var(--medium-gray);
        }
        
        .contratos-table tr:last-child td {
            border-bottom: none;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background-color: var(--success-light);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
        }
        
        .alert-icon {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        /* Estilos para las actividades */
        .activity-container {
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background-color: var(--white);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .activity-container:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .activity-container.ui-sortable-helper {
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            transform: scale(1.02);
            z-index: 1000;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: var(--white);
            cursor: move;
        }
        
        .activity-title {
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }
        
        .activity-title .number {
            background-color: var(--accent-color);
            color: var(--white);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 12px;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .activity-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .action-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .action-btn.remove {
            background-color: rgba(239, 68, 68, 0.8);
        }
        
        .action-btn.remove:hover {
            background-color: var(--error);
        }
        
        .action-btn.edit {
            background-color: rgba(245, 158, 11, 0.8);
        }
        
        .action-btn.edit:hover {
            background-color: var(--warning);
        }
        
        .activity-body {
            padding: 20px;
        }
        
        .activity-description {
            margin-bottom: 20px;
        }
        
        .activity-description-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .activity-description-text {
            padding: 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            background-color: var(--light-gray);
            min-height: 60px;
        }
        
        .text-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s;
            resize: vertical;
            min-height: 60px;
        }
        
        .text-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
        
        .text-input.readonly {
            background-color: var(--light-gray);
            cursor: not-allowed;
        }
        
        #add-activity-btn {
            margin: 20px 0;
            font-size: 1rem;
            padding: 12px 20px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            font-weight: 600;
        }
        
        #add-activity-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
        }
        
        #add-activity-btn i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        #add-activity-btn.disabled {
            background: linear-gradient(to right, var(--dark-gray), var(--medium-gray));
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        #add-activity-btn.disabled:hover {
            transform: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .placeholder {
            border: 2px dashed var(--medium-gray);
            border-radius: 12px;
            background-color: rgba(224, 229, 236, 0.5);
            height: 80px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-gray);
            font-style: italic;
        }
        
        .actions-container {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--medium-gray);
        }
        
        .action-buttons-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding: 20px;
            background-color: var(--light-gray);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .action-buttons-container .btn {
            min-width: 180px;
        }
        
        /* Estilos para los modales */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--medium-gray);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-dark);
        }
        
        #no-activities {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--primary-ultra-light);
            border-radius: 12px;
            margin: 30px 0;
        }

        .action-buttons-container {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(to right, var(--primary-ultra-light), var(--light-gray));
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 51, 102, 0.1);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .action-prompt {
            text-align: center;
        }

        .action-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .action-description {
            color: var(--text-dark);
            max-width: 600px;
            margin: 0 auto;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-action {
            min-width: 220px;
            padding: 14px 20px;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .btn-action::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: linear-gradient(rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: 1px solid var(--primary-dark);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), var(--accent-dark));
            border: 1px solid var(--accent-dark);
        }

        /* Media queries para responsividad */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-action {
                width: 100%;
            }
        }
        
        #no-activities-icon {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 20px;
        }
        
        #no-activities-text {
            font-size: 1.2rem;
            color: var(--primary-dark);
            margin-bottom: 20px;
        }
        
        .loading-indicator {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--medium-gray);
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        .loading-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .footer {
            text-align: center;
            padding: 24px;
            margin-top: 40px;
            border-top: 1px solid var(--medium-gray);
            color: var(--dark-gray);
            font-size: 0.85rem;
            background-color: var(--white);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                padding: 15px;
            }
            
            .logo-wrapper {
                margin-bottom: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-title {
                margin-bottom: 10px;
            }
            
            .activity-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .activity-title {
                margin-bottom: 10px;
            }
            
            .activity-actions {
                align-self: flex-end;
            }
            
            .modal {
                width: 95%;
                max-height: 80vh;
            }
            
            .action-buttons-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons-container .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .card {
                margin-bottom: 20px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .activity-body {
                padding: 15px;
            }
            
            .btn {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
            
            .modal-body {
                padding: 15px;
            }
        }
        </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-wrapper">
                <img src="images/LOGO_IDEXUD.png" alt="Logo IDEXUD" class="logo-img">
                <h1 class="app-title">Actividades Contractuales</h1>
            </div>
            <div class="user-info">
                <span class="user-name"><i class="fas fa-user-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($_SESSION['nombres']); ?></span>
                <a href="contratos.php" class="btn btn-secondary" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left btn-icon"></i>
                    Volver
                </a>
                <a href="back/logout.php" class="btn btn-danger" style="margin-left: 10px;">
                    <i class="fas fa-sign-out-alt btn-icon"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-file-contract card-icon"></i>
                    Información del Contrato
                </h2>
            </div>
            <div class="card-body">
                <table class="contratos-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Objeto</th>
                            <th>Proceso</th>
                            <th>Unidad Académica</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($contrato['numero_contrato']); ?></td>
                            <td><?php echo htmlspecialchars(formatearFechaSimple($contrato['fecha_contrato'])); ?></td>
                            <td><?php echo htmlspecialchars($contrato['objeto']); ?></td>
                            <td><?php echo htmlspecialchars($contrato['proceso_nombre'] ?? 'No especificado'); ?></td>
                            <td><?php echo htmlspecialchars($contrato['unidad_academica'] ?? 'No especificado'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $mensaje['tipo'] === 'success' ? 'success' : 'error'; ?>">
            <div class="alert-icon">
                <i class="fas fa-<?php echo $mensaje['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            </div>
            <div>
                <?php echo $mensaje['texto']; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tiene_actividades && !$modo_edicion): ?>
        <!-- Si tiene actividades pero no está en modo edición, mostrar opciones -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-tasks card-icon"></i>
                    Actividades Contractuales Registradas
                </h2>
            </div>
            <div class="card-body">
                <!-- Mostrar actividades existentes en modo solo lectura -->
                <div id="activities-container" class="readonly-activities">
                    <?php foreach ($actividades as $index => $actividad): ?>
                        <div class="activity-container" data-activity-id="<?php echo $index + 1; ?>">
                            <div class="activity-header">
                                <div class="activity-title">
                                    <div class="number"><?php echo $index + 1; ?></div>
                                    <span>Actividad Contractual</span>
                                </div>
                            </div>
                            <div class="activity-body">
                                <div class="activity-description">
                                    <div class="activity-description-label">Descripción de la actividad:</div>
                                    <div class="activity-description-text"><?php echo htmlspecialchars($actividad['descripcion_actividad']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="action-buttons-container">
                    <div class="action-prompt">
                        <h3 class="action-title">¿Qué desea hacer?</h3>
                        <p class="action-description">
                            Puede continuar para generar su informe o modificar sus actividades contractuales.
                        </p>
                    </div>
                    <div class="action-buttons">
                        <form action="<?php echo htmlspecialchars('actividades_manuales.php?id=' . $id_contrato); ?>" method="post" style="display: inline-block;">
                            <button type="submit" name="continuar_informe" class="btn btn-action btn-primary">
                                <i class="fas fa-file-alt btn-icon"></i>
                                Continuar al Informe
                            </button>
                        </form>
                        <a href="<?php echo htmlspecialchars('actividades_manuales.php?id=' . $id_contrato . '&editar=true'); ?>" class="btn btn-action btn-warning">
                            <i class="fas fa-edit btn-icon"></i>
                            Modificar Actividades
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Si no tiene actividades o está en modo edición, mostrar formulario de edición -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-tasks card-icon"></i>
                    <?php echo $modo_edicion ? 'Modificar Actividades Contractuales' : 'Registrar Actividades Contractuales'; ?>
                </h2>
            </div>
            <div class="card-body">
                <form id="activities-form" action="actividades_manuales.php?id=<?php echo $id_contrato; ?>" method="post">
                    <input type="hidden" name="id" value="<?php echo $id_contrato; ?>">
                    
                    <div id="activities-container">
                        <?php if (empty($actividades) && !$modo_edicion): ?>
                            <div id="no-activities">
                                <div id="no-activities-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div id="no-activities-text">
                                    No hay actividades contractuales registradas
                                </div>
                                <p>
                                    Comience agregando las actividades de su contrato.
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($actividades as $index => $actividad): ?>
                                <div class="activity-container" data-activity-id="<?php echo $index + 1; ?>">
                                    <div class="activity-header">
                                        <div class="activity-title">
                                            <div class="number"><?php echo $index + 1; ?></div>
                                            <span>Actividad Contractual</span>
                                        </div>
                                        <div class="activity-actions">
                                            <button type="button" class="action-btn edit edit-activity-btn" title="Editar actividad">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="action-btn remove remove-activity-btn" title="Eliminar actividad">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="activity-body">
                                        <div class="activity-description">
                                            <div class="activity-description-label">Descripción de la actividad:</div>
                                            <div class="activity-description-text"><?php echo htmlspecialchars($actividad['descripcion_actividad']); ?></div>
                                            <input type="hidden" name="actividades[<?php echo $index + 1; ?>][descripcion]" value="<?php echo htmlspecialchars($actividad['descripcion_actividad']); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" id="add-activity-btn">
                        <i class="fas fa-plus-circle"></i> Agregar nueva actividad
                    </button>
                    
                    <div class="actions-container">
                        <a href="contratos.php" class="btn btn-secondary">
                            <i class="fas fa-times btn-icon"></i>
                            Cancelar
                        </a>
                        <button type="submit" name="guardar_actividades" class="btn btn-success">
                            <i class="fas fa-save btn-icon"></i>
                            Guardar actividades
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modales -->
    <div class="modal-overlay" id="activity-modal-overlay">
        <div class="modal" id="activity-modal">
            <div class="modal-header">
                <h3 class="modal-title" id="activity-modal-title">Agregar actividad</h3>
                <button type="button" class="modal-close" id="close-activity-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="activity-description" class="form-label">Descripción de la actividad contractual:</label>
                    <textarea id="activity-description" class="text-input" rows="4" placeholder="Describa la actividad según aparece en su contrato"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-activity-btn">Cancelar</button>
                <button type="button" class="btn btn-primary" id="save-activity-btn">Guardar</button>
            </div>
        </div>
    </div>
    
    <div class="loading-indicator" id="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Guardando cambios...</div>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Universidad Distrital Francisco José de Caldas - Todos los derechos reservados
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            // Variables globales
            let nextActivityId = <?php echo empty($actividades) ? 1 : count($actividades) + 1; ?>;
            let currentEditingActivity = null;
            
            // Funciones para los modales
            function openActivityModal(isEdit = false, activityId = null) {
                currentEditingActivity = activityId;
                
                // Actualizar título del modal
                $('#activity-modal-title').text(isEdit ? 'Editar actividad' : 'Agregar actividad');
                
                // Si es edición, cargar datos existentes
                if (isEdit && activityId) {
                    const descriptionInput = $(`input[name="actividades[${activityId}][descripcion]"]`).val();
                    $('#activity-description').val(descriptionInput);
                } else {
                    $('#activity-description').val('');
                }
                
                // Mostrar modal
                $('#activity-modal-overlay').addClass('active');
            }
            
            function closeActivityModal() {
                $('#activity-modal-overlay').removeClass('active');
                currentEditingActivity = null;
            }
            
            // Función para generar nueva actividad
            function addActivity(description = '') {
                const activityId = nextActivityId++;
                
                // Crear estructura HTML para la nueva actividad
                const activityHtml = `
                    <div class="activity-container" data-activity-id="${activityId}">
                        <div class="activity-header">
                            <div class="activity-title">
                                <div class="number">${activityId}</div>
                                <span>Actividad Contractual</span>
                            </div>
                            <div class="activity-actions">
                                <button type="button" class="action-btn edit edit-activity-btn" title="Editar actividad">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="action-btn remove remove-activity-btn" title="Eliminar actividad">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="activity-body">
                            <div class="activity-description">
                                <div class="activity-description-label">Descripción de la actividad:</div>
                                <div class="activity-description-text">${description}</div>
                                <input type="hidden" name="actividades[${activityId}][descripcion]" value="${description}">
                            </div>
                        </div>
                    </div>
                `;
                
                // Eliminar mensaje de "No hay actividades" si existe
                $('#no-activities').remove();
                
                // Agregar la nueva actividad al contenedor
                $('#activities-container').append(activityHtml);
                
                // Hacer las actividades ordenables
                makeActivitiesSortable();
                
                // Actualizar la numeración de las actividades
                updateActivitiesNumbering();
                
                return activityId;
            }
            
            // Función para actualizar la numeración de las actividades después de reordenar
            function updateActivitiesNumbering() {
                $('.activity-container').each(function(index) {
                    const activityId = index + 1;
                    const oldActivityId = $(this).data('activity-id');
                    
                    // Actualizar el número visible
                    $(this).find('.activity-title .number').text(activityId);
                    
                    // Actualizar data-activity-id
                    $(this).attr('data-activity-id', activityId);
                    
                    // Actualizar los nombres de los campos hidden para mantener la correspondencia
                    $(this).find(`input[name^="actividades[${oldActivityId}]"]`).each(function() {
                        const oldName = $(this).attr('name');
                        const newName = oldName.replace(`actividades[${oldActivityId}]`, `actividades[${activityId}]`);
                        $(this).attr('name', newName);
                    });
                });
            }
            
            // Hacer que las actividades sean ordenables con drag & drop
            function makeActivitiesSortable() {
                $('#activities-container').sortable({
                    handle: '.activity-header',
                    placeholder: 'placeholder',
                    opacity: 0.8,
                    update: function(event, ui) {
                        updateActivitiesNumbering();
                    }
                });
            }
            
            // Event Listeners
            
            // Abrir modal para agregar actividad
            $('#add-activity-btn').on('click', function() {
                openActivityModal(false);
            });
            
            // Cerrar modales
            $('#close-activity-modal, #cancel-activity-btn').on('click', closeActivityModal);
            
            // Guardar nueva actividad o editar existente
            $('#save-activity-btn').on('click', function() {
                const description = $('#activity-description').val().trim();
                
                if (!description) {
                    alert('Por favor, ingrese una descripción para la actividad.');
                    return;
                }
                
                if (currentEditingActivity) {
                    // Editar actividad existente
                    const activityContainer = $(`.activity-container[data-activity-id="${currentEditingActivity}"]`);
                    activityContainer.find('.activity-description-text').text(description);
                    activityContainer.find(`input[name="actividades[${currentEditingActivity}][descripcion]"]`).val(description);
                } else {
                    // Agregar nueva actividad
                    addActivity(description);
                }
                
                closeActivityModal();
            });
            
            // Editar actividad
            $(document).on('click', '.edit-activity-btn', function() {
                const activityId = $(this).closest('.activity-container').data('activity-id');
                openActivityModal(true, activityId);
            });
            
            // Eliminar actividad
            $(document).on('click', '.remove-activity-btn', function() {
                if (confirm('¿Está seguro de que desea eliminar esta actividad?')) {
                    $(this).closest('.activity-container').remove();
                    updateActivitiesNumbering();
                    
                    // Si no quedan actividades, mostrar mensaje
                    if ($('.activity-container').length === 0) {
                        $('#activities-container').html(`
                            <div id="no-activities">
                                <div id="no-activities-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div id="no-activities-text">
                                    No hay actividades contractuales registradas
                                </div>
                                <p>
                                    Comience agregando las actividades de su contrato.
                                </p>
                            </div>
                        `);
                    }
                }
            });
            
            // Mostrar indicador de carga al enviar el formulario
            $('#activities-form').on('submit', function() {
                $('#loading-overlay').show();
            });
            
            // Inicializar sortables solo si no estamos en modo solo lectura
            <?php if ($modo_edicion || !$tiene_actividades): ?>
            makeActivitiesSortable();
            <?php endif; ?>
        });
    </script>
</body>
</html>
