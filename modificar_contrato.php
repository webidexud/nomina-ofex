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
$query = "SELECT c.*, b.nombre as banco_nombre FROM contrato_informe c 
          LEFT JOIN banco b ON c.banco_id = b.id 
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Procesamiento del formulario cuando se envía
    $nombre_supervisor = mysqli_real_escape_string($conexion, $_POST['nombre_supervisor'] ?? '');
    $email_supervisor = mysqli_real_escape_string($conexion, $_POST['email_supervisor'] ?? '');
    $numero_contrato = mysqli_real_escape_string($conexion, $_POST['numero_contrato'] ?? '');
    $anio = mysqli_real_escape_string($conexion, $_POST['anio'] ?? '');
    $objeto = mysqli_real_escape_string($conexion, $_POST['objeto'] ?? '');
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio'] ?? '');
    $fecha_fin = mysqli_real_escape_string($conexion, $_POST['fecha_fin'] ?? '');
    $valor = str_replace(['$', "'"], '', $_POST['valor'] ?? '0');
    $forma_pago = mysqli_real_escape_string($conexion, $_POST['forma_pago'] ?? '');
    $rp = mysqli_real_escape_string($conexion, $_POST['rp'] ?? '');
    $cuenta_bancaria = mysqli_real_escape_string($conexion, $_POST['cuenta_bancaria'] ?? '');
    $banco_id = mysqli_real_escape_string($conexion, $_POST['banco'] ?? '');
    $tipo_cuenta = mysqli_real_escape_string($conexion, $_POST['tipo_cuenta'] ?? '');

    $update_query = "UPDATE contrato_informe SET 
                     nombre_supervisor = ?,
                     email_supervisor = ?,
                     numero_contrato = ?,
                     anio = ?,
                     objeto = ?,
                     fecha_inicio = ?,
                     fecha_fin = ?,
                     valor = ?,
                     forma_pago = ?,
                     rp = ?,
                     cuenta_bancaria = ?,
                     banco_id = ?,
                     tipo_cuenta = ?
                     WHERE id = ? AND cc_contratista = ?";

    $stmt = mysqli_prepare($conexion, $update_query);
    if (!$stmt) {
        die("Error en la preparación de la actualización: " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, "sssssssdsssssis", 
        $nombre_supervisor,
        $email_supervisor,
        $numero_contrato,
        $anio,
        $objeto,
        $fecha_inicio,
        $fecha_fin,
        $valor,
        $forma_pago,
        $rp,
        $cuenta_bancaria,
        $banco_id,
        $tipo_cuenta,
        $contract_id,
        $_SESSION['cedula']
    );

    if (mysqli_stmt_execute($stmt)) {
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
    <title>Modificar Contrato</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function formatCurrency(input) {
            let value = input.value.replace(/\D/g, '');
            value = (parseInt(value) || 0).toString();
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            input.value = '$' + value;
        }
        
        function toUpperCase(input) {
            input.value = input.value.toUpperCase();
        }
    </script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">Modificar Contrato</h1>
        </div>
    </header>

    <main class="container mx-auto mt-8">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <form action="<?php echo htmlspecialchars("https://idexud.udistrital.edu.co/nomina_ofex/modificar_contrato.php?id=" . $contract_id); ?>" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nombre_supervisor">
                        Nombre del Supervisor / Apoyo a la Supervisión
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="nombre_supervisor" name="nombre_supervisor" type="text" 
                        value="<?php echo htmlspecialchars($contract['nombre_supervisor'] ?? ''); ?>" 
                        oninput="toUpperCase(this)" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email_supervisor">
                        Correo del Supervisor / Apoyo a la supervisión
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="email_supervisor" name="email_supervisor" type="email" 
                        value="<?php echo htmlspecialchars($contract['email_supervisor'] ?? ''); ?>" 
                        oninput="toUpperCase(this)" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="numero_contrato">
                        Número de Contrato
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="numero_contrato" name="numero_contrato" type="text" 
                        value="<?php echo htmlspecialchars($contract['numero_contrato'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="anio">
                        Año del Contrato
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="anio" name="anio" type="text" 
                        value="<?php echo htmlspecialchars($contract['anio'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="objeto">
                        Objeto del Contrato
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="objeto" name="objeto" required><?php echo htmlspecialchars($contract['objeto'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha_inicio">
                        Fecha de Inicio
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="fecha_inicio" name="fecha_inicio" type="date" 
                        value="<?php echo htmlspecialchars($contract['fecha_inicio'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha_fin">
                        Fecha de Finalización
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="fecha_fin" name="fecha_fin" type="date" 
                        value="<?php echo htmlspecialchars($contract['fecha_fin'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="valor">
                        Valor del Contrato
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="valor" name="valor" type="text" 
                        value="<?php echo '$' . number_format($contract['valor'] ?? 0, 0, '', "'"); ?>" 
                        oninput="formatCurrency(this)" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="forma_pago">
                        Forma de Pago
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="forma_pago" name="forma_pago" maxlength="1000" required><?php echo htmlspecialchars($contract['forma_pago'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="rp">
                        Número de RP y Fecha de RP por ejemplo (11 de 25 de enero del 2025)
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="rp" name="rp" type="text" maxlength="100" 
                        value="<?php echo htmlspecialchars($contract['rp'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="cuenta_bancaria">
                        Número de Cuenta Bancaria
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="cuenta_bancaria" name="cuenta_bancaria" type="text" 
                        value="<?php echo htmlspecialchars($contract['cuenta_bancaria'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="banco">
                        Banco
                    </label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                        id="banco" name="banco" required>
                        <option value="">Seleccione un banco</option>
                        <?php while($banco = mysqli_fetch_assoc($bancos_result)): ?>
                            <option value="<?php echo $banco['id']; ?>" 
                                <?php echo ($banco['id'] == ($contract['banco_id'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($banco['nombre']); ?>
                            </option>
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
                        <option value="AHORROS" <?php echo (($contract['tipo_cuenta'] ?? '') == 'AHORROS') ? 'selected' : ''; ?>>AHORROS</option>
                        <option value="CORRIENTE" <?php echo (($contract['tipo_cuenta'] ?? '') == 'CORRIENTE') ? 'selected' : ''; ?>>CORRIENTE</option>
                    </select>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                        type="submit">
                        Actualizar Contrato
                    </button>
                    <a href="contratos.php" 
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
