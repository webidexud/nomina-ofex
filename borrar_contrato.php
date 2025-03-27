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
    header("Location: index.php");
    exit();
}

$id_contrato = $_GET['id'];
$cc_contratista = $_SESSION['cedula'];

// Verificar si el contrato pertenece al usuario actual
$query = "SELECT * FROM contrato_informe WHERE id = '$id_contrato' AND cc_contratista = '$cc_contratista'";
$result = mysqli_query($conexion, $query);

if (mysqli_num_rows($result) == 0) {
    // El contrato no existe o no pertenece al usuario actual
    header("Location: index.php");
    exit();
}

// Eliminar el contrato
$delete_query = "DELETE FROM contrato_informe WHERE id = '$id_contrato' AND cc_contratista = '$cc_contratista'";

if (mysqli_query($conexion, $delete_query)) {
    // Contrato eliminado exitosamente
    $_SESSION['mensaje'] = "Contrato eliminado exitosamente.";
} else {
    // Error al eliminar el contrato
    $_SESSION['mensaje'] = "Error al eliminar el contrato: " . mysqli_error($conexion);
}

// Redireccionar de vuelta a la página principal
header("Location: contratos.php");
exit();
?> 