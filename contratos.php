<?php
session_start();
include 'db/conexion.php';

// Check if user is logged in
if (!isset($_SESSION['cedula'])) {
    header("Location: login.php");
    exit();
}




$userContracts = getUserContracts($_SESSION['cedula']);


// Function to get user contracts
function getUserContracts($cc_contratista) {
    global $conexion;
    $query = "SELECT * FROM contrato_informe WHERE cc_contratista = '$cc_contratista'";
    $result = mysqli_query($conexion, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Handle form submission for adding a new contract
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cc_contratista = $_SESSION['cedula'];
    $email_contratista = $_SESSION['email'];
    $nombre_supervisor = $_POST['nombre_supervisor'];
    $email_supervisor = $_POST['email_supervisor'];
    $numero_contrato = $_POST['numero_contrato'];
    $anio = $_POST['anio'];
    $objeto = $_POST['objeto'];

    $query = "INSERT INTO contrato_informe (cc_contratista, email_contratista, nombre_supervisor, email_supervisor, numero_contrato, anio, objeto) 
              VALUES ('$cc_contratista', '$email_contratista', '$nombre_supervisor', '$email_supervisor', '$numero_contrato', '$anio', '$objeto')";
    
    if (mysqli_query($conexion, $query)) {
        // Refresh the contracts list
        $userContracts = getUserContracts($_SESSION['cedula']);
    } else {
        echo "Error: " . $query . "<br>" . mysqli_error($conexion);
    }
}

$userContracts = getUserContracts($_SESSION['cedula']);

$query_bancos = "SELECT * FROM banco ORDER BY nombre";
$result_bancos = mysqli_query($conexion, $query_bancos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contratos de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<script>
        function toUpperCase(input) {
            input.value = input.value.toUpperCase();
        }
    </script>
<style>
    .justificado{
         text-align: justify;
    }
</style>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Gestión de Contratos</h1>

            <div class="absolute top-4 right-4 text-white">
                <a href="modificar_usuario.php" class="hover:underline">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombres']); ?></a>
                <a href="back/logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto mt-8">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-xl font-bold mb-4">Mis Contratos</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Número de Contrato</th>
                            <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Año</th>
                            <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light justificado">Objeto</th>
                            <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Ver</th>
                            <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Modificar</th>
                            <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">Borrar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userContracts as $contract): ?>
                        <tr class="hover:bg-grey-lighter">
                            <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($contract['numero_contrato']); ?></td>
                            <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($contract['anio']); ?></td>
                            <td class="py-4 px-6 border-b border-grey-light justificado"><?php echo htmlspecialchars($contract['objeto']); ?></td>
                            <td class="py-4 px-6 border-b border-grey-light">
                                <a href="index.php?id=<?php echo $contract['id']; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Ver</a>
                            </td>
                            <td class="py-4 px-6 border-b border-grey-light">
                                <a href="modificar_contrato.php?id=<?php echo $contract['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">Modificar</a>
                            </td>
                            <td class="py-4 px-6 border-b border-grey-light">
                                <a href="borrar_contrato.php?id=<?php echo $contract['id']; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('¿Está seguro de que desea borrar este contrato?');">Borrar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-xl font-bold mb-4">Agregar Nuevo Contrato</h2>
            <form action="back/save_contrato.php" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email_contratista">
                        Correo Institucional del Contratista (Ejemplo: WEBIDEXUD@UDISTRITAL.EDU.CO)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                        id="email_contratista" name="email_contratista" type="email" required
                        oninput="toUpperCase(this)">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nombre_supervisor">
                        Nombre del Apoyo a la Supervición o Supervisor (Ejemplo: ROBERTO FERRO ESCOBAR)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                        id="nombre_supervisor" name="nombre_supervisor" type="text" required
                        oninput="toUpperCase(this)">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email_supervisor">
                        Correo del Supervisor (Ejemplo: COORDINACIONTIIDEXUD@UDISTRITAL.EDU.CO)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                        id="email_supervisor" name="email_supervisor" type="email" required
                        oninput="toUpperCase(this)">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="numero_contrato">
                        Número de Contrato (Ejemplo: 255)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="numero_contrato" name="numero_contrato" type="text" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="anio">
                        Año del Contrato (Ejemplo: 2025)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="anio" name="anio" type="text" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="objeto">
                        Objeto del Contrato (Tal como aparece en la minuta)
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="objeto" name="objeto" required></textarea>
                </div>
                <div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha_inicio">
        Fecha de Inicio del Contrato (Tal como aparece en el acta de inicio)
    </label>
    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
        id="fecha_inicio" name="fecha_inicio" type="date" required>
</div>

<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha_fin">
        Fecha de Terminación (Tal como aparece en el acta de inicio)
    </label>
    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
        id="fecha_fin" name="fecha_fin" type="date" required>
</div>

<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="valor">
        Valor de la Obligación (Tal como aparece en el acta de inicio)
    </label>
    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
        id="valor" name="valor" type="text" required
        oninput="formatCurrency(this)">
</div>

<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="forma_pago">
        Forma de Pago (Tal como aparece en la minuta)
    </label>
    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
        id="forma_pago" name="forma_pago" maxlength="1000" required></textarea>
</div>

<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="rp">
        Número de RP y Fecha de RP por ejemplo (11 de 25 de enero del 2025)
    </label>
    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
        id="rp" name="rp" type="text" maxlength="100" required>
</div>



<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="cuenta">
        Número de Cuenta Bancaria 
    </label>
    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
        id="cuenta" name="cuenta" type="text" required>
</div>

<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="banco">
        Banco
    </label>
    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
        id="banco" name="banco" required>
        <option value="">Seleccione un banco</option>
        <?php while($banco = mysqli_fetch_assoc($result_bancos)): ?>
            <option value="<?php echo $banco['id']; ?>"><?php echo $banco['nombre']; ?></option>
        <?php endwhile; ?>
    </select>
</div>

<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="tipo_cuenta">
        Tipo de Cuenta
    </label>
    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
        id="tipo_cuenta" name="tipo_cuenta" required>
        <option value="">Seleccione tipo de cuenta</option>
        <option value="AHORROS">AHORROS</option>
        <option value="CORRIENTE">CORRIENTE</option>
    </select>
</div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" name="registro_contrato" type="submit">
                        Agregar Contrato
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
<script>
    function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    value = (parseInt(value) || 0).toString();
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
    input.value = '$' + value;
}
</script>
</html>
