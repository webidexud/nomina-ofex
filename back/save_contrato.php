<?php
session_start();
include '../db/conexion.php';

if (isset($_POST['registro_contrato'])) {
    $cc_contratista = $_SESSION['cedula'];
    $email_contratista = $_POST['email_contratista'];
    $nombre_supervisor = $_POST['nombre_supervisor'];
    $email_supervisor = $_POST['email_supervisor'];
    $numero_contrato = $_POST['numero_contrato'];
    $anio = $_POST['anio'];
    $objeto = $_POST['objeto'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $valor = str_replace(['$', "'"], '', $_POST['valor']); // Limpia formato moneda
    $forma_pago = $_POST['forma_pago'];
    $rp = $_POST['rp'];
    $cuenta_bancaria = $_POST['cuenta'];
    $banco_id = $_POST['banco'];
    $tipo_cuenta = $_POST['tipo_cuenta'];

    $sql = mysqli_query($conexion, "INSERT INTO contrato_informe 
    (cc_contratista, email_contratista, nombre_supervisor, email_supervisor, 
    numero_contrato, anio, objeto, fecha_inicio, fecha_fin, valor, forma_pago, 
    rp, cuenta_bancaria, banco_id, tipo_cuenta) VALUES
    ('$cc_contratista', '$email_contratista', '$nombre_supervisor', '$email_supervisor', 
    '$numero_contrato', '$anio', '$objeto', '$fecha_inicio', '$fecha_fin', '$valor', 
    '$forma_pago', '$rp', '$cuenta_bancaria', '$banco_id', '$tipo_cuenta')");

    header('location: ../contratos.php');
}
?>
