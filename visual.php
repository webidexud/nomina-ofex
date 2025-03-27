<?php
// Verificar si se recibieron datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Variables para almacenar los datos del formulario
    // Obtener los datos del formulario
    $nombre = $_POST['nombre'];
    $tipo_id = $_POST['tipo_id'];
    $numero_id = $_POST['numero_id'];
    $lugar_exp_id = $_POST['lugar_exp_id'];
    $email = $_POST['email'];
    $cel = $_POST['cel'];
    $tipo_contratista = $_POST['tipo_contratista'];
    $vincu_estudiante = isset($_POST['vincu_estudiante']) ? $_POST['vincu_estudiante'] : "";
    $facultad_estudiante = isset($_POST['facultad_estudiante']) ? $_POST['facultad_estudiante'] : "";
    $proyecto_curricular_estudiante = isset($_POST['proyecto_curricular_estudiante']) ? $_POST['proyecto_curricular_estudiante'] : "";
    $vincu_egresado = isset($_POST['vincu_egresado']) ? $_POST['vincu_egresado'] : "";
    $facultad_egresado = isset($_POST['facultad_egresado']) ? $_POST['facultad_egresado'] : "";
    $proyecto_curricular_egresado = isset($_POST['proyecto_curricular_egresado']) ? $_POST['proyecto_curricular_egresado'] : "";
    $vincu_contratista = isset($_POST['vincu_contratista']) ? $_POST['vincu_contratista'] : "";
    $tipo_vin_contratista = isset($_POST['tipo_vin_contratista']) ? $_POST['tipo_vin_contratista'] : "";
    $vinculacion_contratista_dependencia_proyecto = isset($_POST['vinculacion_contratista_dependencia_proyecto']) ? $_POST['vinculacion_contratista_dependencia_proyecto'] : "";
    $numero_contrato = isset($_POST['numero_contrato']) ? $_POST['numero_contrato'] : "";
    $ano_contrato = isset($_POST['ano_contrato']) ? $_POST['ano_contrato'] : "";
    $pensionado = isset($_POST['pensionado']) ? $_POST['pensionado'] : "";
    $vincu_docente = isset($_POST['vincu_docente']) ? $_POST['vincu_docente'] : "";
    $tipo_vinculacion_docente = isset($_POST['tipo_vinculacion_docente']) ? $_POST['tipo_vinculacion_docente'] : "";
    $facultad_docente = isset($_POST['facultad_docente']) ? $_POST['facultad_docente'] : "";
    $proyecto_curricular_docente = isset($_POST['proyecto_curricular_docente']) ? $_POST['proyecto_curricular_docente'] : "";
    $ultimo_nivel_educativo = isset($_POST['ultimo_nivel_educativo']) ? $_POST['ultimo_nivel_educativo'] : "";
    $genero = isset($_POST['genero']) ? $_POST['genero'] : "";
    $se_reconoce_como = isset($_POST['se_reconoce_como']) ? $_POST['se_reconoce_como'] : "";
    $orientacion_sexual = isset($_POST['orientacion_sexual']) ? $_POST['orientacion_sexual'] : "";
    $discapacidad = isset($_POST['discapacidad']) ? $_POST['discapacidad'] : "";
    $tipo_discapacidad = isset($_POST['tipo_discapacidad']) ? $_POST['tipo_discapacidad'] : "";
    $etnia = isset($_POST['etnia']) ? $_POST['etnia'] : "";
    $tipo_etnia = isset($_POST['tipo_etnia']) ? $_POST['tipo_etnia'] : "";
    $conflicto_armado = isset($_POST['conflicto_armado']) ? $_POST['conflicto_armado'] : "";
    $migrante = isset($_POST['migrante']) ? $_POST['migrante'] : "";
    $edad = isset($_POST['edad']) ? $_POST['edad'] : "";


   // Mostrar los datos
    echo "<p>Nombre: $nombre</p>";
    echo "<p>Tipo de documento de identificación: $tipo_id</p>";
    echo "<p>Número de documento de identificación: $numero_id</p>";
    echo "<p>Lugar de Expedición del documento de identificación: $lugar_exp_id</p>";
    echo "<p>Correo electrónico de Notificación: $email</p>";
    echo "<p>Teléfono de contacto: $cel</p>";
    echo "<p>¿Es persona jurídica?: $tipo_contratista</p>";
    echo "<p>¿Es estudiante actual de la UDFJC?: $vincu_estudiante</p>";
    echo "<p>Facultad del estudiante: $facultad_estudiante</p>";
    echo "<p>Proyecto curricular del estudiante: $proyecto_curricular_estudiante</p>";
    echo "<p>¿Es usted Egresado de la UDFJC?: $vincu_egresado</p>";
    echo "<p>Facultad del egresado: $facultad_egresado</p>";
    echo "<p>Proyecto curricular del egresado: $proyecto_curricular_egresado</p>";
    echo "<p>¿Es usted contratista actualmente de la UDFJC?: $vincu_contratista</p>";
    echo "<p>Tipo de vinculación del contrato: $tipo_vin_contratista</p>";
    echo "<p>Vinculación con dependencia/proyecto: $vinculacion_contratista_dependencia_proyecto</p>";
    echo "<p>Número de contrato: $numero_contrato</p>";
    echo "<p>Año de suscripción del contrato: $ano_contrato</p>";
    echo "<p>¿Es usted pensionado?: $pensionado</p>";
    echo "<p>¿Es usted docente de la UDFJC?: $vincu_docente</p>";
    echo "<p>Tipo de vinculación del docente: $tipo_vinculacion_docente</p>";
    echo "<p>Facultad del docente: $facultad_docente</p>";
    echo "<p>Proyecto curricular del docente: $proyecto_curricular_docente</p>";
    echo "<p>Último nivel educativo alcanzado (Codificación SNIES): $ultimo_nivel_educativo</p>";
    echo "<p>Género: $genero</p>";
    echo "<p>Se reconoce como: $se_reconoce_como</p>";
    echo "<p>Orientación sexual: $orientacion_sexual</p>";
    echo "<p>¿Tiene alguna discapacidad?: $discapacidad</p>";
    echo "<p>Tipo de discapacidad: $tipo_discapacidad</p>";
    echo "<p>¿Pertenece a algún grupo étnico?: $etnia</p>";
    echo "<p>Tipo de etnia: $tipo_etnia</p>";
    echo "<p>¿Es víctima del conflicto armado?: $conflicto_armado</p>";
    echo "<p>¿Es migrante?: $migrante</p>";
    echo "<p>Edad: $edad</p>";




  

} else {
    // Si no se recibieron datos del formulario, redirigir o mostrar un mensaje de error
    echo "No se recibieron datos del formulario.";
}
?>