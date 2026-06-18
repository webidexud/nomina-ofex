<?php
session_start();
include '../db/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['cedula'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar si se recibieron datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y sanitizar los datos básicos del formulario
    $nombres = mysqli_real_escape_string($conexion, $_POST['nombres']);
    $celular = mysqli_real_escape_string($conexion, $_POST['celular']);
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    
    // Datos opcionales que solo se procesan si el campo no existe en el perfil
    $tipo_identificacion_id = isset($_POST['tipo_identificacion_id']) && !empty($_POST['tipo_identificacion_id']) 
        ? intval($_POST['tipo_identificacion_id']) 
        : null;
    
    $lugar_expedicion = isset($_POST['lugar_expedicion']) && !empty($_POST['lugar_expedicion']) 
        ? mysqli_real_escape_string($conexion, $_POST['lugar_expedicion']) 
        : null;
    
    // Contraseña - solo procesar si se proporcionó una nueva
    $pswd = isset($_POST['pswd']) && !empty($_POST['pswd']) 
        ? mysqli_real_escape_string($conexion, $_POST['pswd']) 
        : null;
    $pswd_encoded = $pswd ? base64_encode($pswd) : null;
    
    // Antes de actualizar, verificar si ya tiene valores para tipo de ID y lugar de expedición
    $check_query = "SELECT tipo_identificacion_id, lugar_expedicion FROM contratista WHERE cedula = '" . $_SESSION['cedula'] . "'";
    $check_result = mysqli_query($conexion, $check_query);
    $user_data = mysqli_fetch_assoc($check_result);
    
    // Construir la consulta SQL para actualizar datos básicos
    $query = "UPDATE contratista SET 
              nombres = '$nombres', 
              celular = '$celular', 
              email = '$email'";
    
    // Agregar tipo de ID solo si no existe en el perfil y se proporcionó valor
    if (empty($user_data['tipo_identificacion_id']) && $tipo_identificacion_id) {
        $query .= ", tipo_identificacion_id = $tipo_identificacion_id";
    }
    
    // Agregar lugar de expedición solo si no existe en el perfil y se proporcionó valor
    if (empty($user_data['lugar_expedicion']) && $lugar_expedicion) {
        $query .= ", lugar_expedicion = '$lugar_expedicion'";
    }
    
    // Agregar contraseña si se proporcionó una nueva
    if ($pswd_encoded) {
        $query .= ", pswd = '$pswd_encoded'";
    }
    
    // Completar la consulta con la condición WHERE
    $query .= " WHERE cedula = '" . $_SESSION['cedula'] . "'";
    
    // Ejecutar la consulta
    if (mysqli_query($conexion, $query)) {
        // Actualizar las variables de sesión con los nuevos valores
        $_SESSION['nombres'] = $nombres;
        $_SESSION['celular'] = $celular;
        $_SESSION['email'] = $email;
        
        // Establecer mensaje de éxito
        $_SESSION['success'] = "Datos actualizados correctamente.";
    } else {
        // Establecer mensaje de error
        $_SESSION['error'] = "Error al actualizar los datos: " . mysqli_error($conexion);
    }
}

// Redirigir de vuelta a la página de contratos
header("Location: ../contratos.php");
exit();
?>
