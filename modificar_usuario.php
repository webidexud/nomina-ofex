<?php
require 'vendor/autoload.php';
include 'db/conexion.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres = mysqli_real_escape_string($conexion, $_POST['nombres']);
    $celular = mysqli_real_escape_string($conexion, $_POST['celular']);
    $email = mysqli_real_escape_string($conexion, $_POST['email']);
    $pswd = $_POST['pswd'] ? mysqli_real_escape_string($conexion, $_POST['pswd']) : null;
    $pswd = base64_encode($pswd);

    $query = "UPDATE contratista SET nombres = '$nombres', celular = '$celular', email = '$email'";
    if ($pswd) {
        $query .= ", pswd = '$pswd'";
    }
    $query .= " WHERE cedula = '" . $_SESSION['cedula'] . "'";

    if (mysqli_query($conexion, $query)) {
        $message = "Datos actualizados correctamente.";
        $_SESSION['nombres'] = $nombres;
        $_SESSION['celular'] = $celular;
        $_SESSION['email'] = $email;
    } else {
        $message = "Error al actualizar los datos: " . mysqli_error($conexion);
    }
}

$query = "SELECT * FROM contratista WHERE cedula = '" . $_SESSION['cedula'] . "'";
$result = mysqli_query($conexion, $query);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Datos del Contratista</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<script>
        function toUpperCase(input) {
            input.value = input.value.toUpperCase();
        }
    </script>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
        <div class="md:flex">
            <div class="p-8">
                <h2 class="block mt-1 text-lg leading-tight font-medium text-black">Modificar Datos del Contratista</h2>
                <?php if ($message): ?>
                    <p class="mt-2 text-green-600"><?php echo $message; ?></p>
                <?php endif; ?>
                <form action="https://idexud.udistrital.edu.co/nomina_ofex/modify_contractor.php" method="post" class="mt-6">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nombres">
                            Nombres:
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               type="text" name="nombres" id="nombres" 
                               value="<?php echo htmlspecialchars($user['nombres']); ?>" 
                               required maxlength="200" oninput="toUpperCase(this)">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="celular">
                            Celular:
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" name="celular" id="celular" value="<?php echo htmlspecialchars($user['celular']); ?>" required maxlength="50">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email:
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               type="email" name="email" id="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               required maxlength="150" oninput="toUpperCase(this)">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="pswd">
                            Nueva Contraseña (dejar en blanco para no cambiar):
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="password" name="pswd" id="pswd" maxlength="50">
                    </div>
                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                            Actualizar Datos
                        </button>
                        <a href="contratos.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                            Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
