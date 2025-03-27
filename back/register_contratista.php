<?php
session_start();
include '../db/conexion.php';

if (isset($_POST['register_btn'])) {
    $cedula = mysqli_real_escape_string($conexion, $_POST['cc']);
    $nombres_contratista = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $email_contratista = mysqli_real_escape_string($conexion, $_POST['correo_personal']);
    $celular_contratista = mysqli_real_escape_string($conexion, $_POST['telefono']);
    $pswd = $_POST['pswd'];
    $pswd_encode = base64_encode($pswd);

    // Verificar si la cédula ya está registrada
    $check_query = "SELECT * FROM contratista WHERE cedula = '$cedula'";
    $check_result = mysqli_query($conexion, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "Esta cédula ya está registrada en el sistema.";
        header('location: ../register.php');
        exit();
    }

    $sql = "INSERT INTO contratista (cedula, nombres, email, celular, pswd) 
            VALUES ('$cedula', '$nombres_contratista', '$email_contratista', '$celular_contratista', '$pswd_encode')";

    if (mysqli_query($conexion, $sql)) {
        header('location: ../login.php');
    } else {
        $_SESSION['error'] = "Error al registrar: " . mysqli_error($conexion);
        header('location: ../register.php');
    }
} else {
    header('location: ../register.php');
}
?>