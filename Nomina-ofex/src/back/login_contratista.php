<?php
session_start();
include '../db/conexion.php';

if (isset($_POST['login_btn'])) {
    $id = mysqli_real_escape_string($conexion, $_POST['id_person']);
    $pass = $_POST['pass'];
    $pswd_encode = base64_encode($pass);

    $consulta = mysqli_query($conexion, "SELECT * FROM contratista 
                            WHERE cedula = '$id'");
    $exist = mysqli_num_rows($consulta);

    if ($exist == 1) {
        $datos = mysqli_fetch_array($consulta);
        if ($datos['pswd'] == $pswd_encode) {
            $_SESSION['cedula'] = $datos['cedula'];
            $_SESSION['nombres'] = $datos['nombres'];
            $_SESSION['email'] = $datos['email'];
            $_SESSION['celular'] = $datos['celular'];
            header('location:../contratos.php');
        } else {
            $_SESSION['error'] = "Contraseña incorrecta";
            header('location:../login.php');
        }
    } else {
        $_SESSION['error'] = "El usuario no existe";
        header('location:../login.php');
    }
} else {
    header('location:../login.php');
}
?>