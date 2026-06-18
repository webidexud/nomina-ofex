<?php
    /*Archivo de conexión a base de datos*/

    $server = "nomina_mariadb";
    $user = "nomina_automatica_user";
    $pass = "0uXUVmYXY5V5VJyBEzCqIDVCVoeEqSrjslH0xK";
    $db = "nomina_automatica";
    $port = "3306";
    
    $conexion = new mysqli($server, $user, $pass, $db, $port);

    if ($conexion->connect_errno) {
        echo "error de conexion";
        exit();
    }

?>
