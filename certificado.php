<?php
require('fpdf/fpdf.php');
include 'db/conexion.php';

date_default_timezone_set('America/Bogota');
// Datos del certificado
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

$vincu_est_definitivo = "";
$vincu_egre_definitivo = "";
$vincu_contra_definitivo = "";
$vincu_docen_definitivo = "";


if ($vincu_estudiante == "si") {
    $vincu_est_definitivo = "- Estudiante \n";
}
if ($vincu_egresado == "si") {
    $vincu_egre_definitivo = "- Egresado \n";
}
if ($vincu_contratista == "si") {
    $vincu_contra_definitivo = "- Contratista"." número de contrato ".$numero_contrato." de ".$vinculacion_contratista_dependencia_proyecto. " del año ". $ano_contrato."\n";
}
if ($vincu_docente == "si") {
    $vincu_docen_definitivo = "- Docente \n";
}

$vincu_definitivo = $vincu_est_definitivo. "" .$vincu_egre_definitivo. "" .$vincu_contra_definitivo. "" .$vincu_docen_definitivo;

if ($vincu_est_definitivo == "" && $vincu_egre_definitivo == "" && $vincu_contra_definitivo == "" && $vincu_docen_definitivo == "") {
    $vincu_definitivo = "Ninguno";
}


$tipoVinculacion = "-";
$fechaExpedicion = date('Y - m - d');
$horaExpedicion = date('h:i:s A');

    $sql_query = "INSERT INTO vinculacion (nombre, tipo_id, numero_id, lugar_exp_id, email, cel, tipo_contratista, vincu_estudiante, facultad_estudiante, proyecto_curricular_estudiante, vincu_egresado, facultad_egresado, proyecto_curricular_egresado, vincu_contratista, tipo_vin_contratista, vinculacion_contratista_dependencia_proyecto, numero_contrato, ano_contrato, pensionado, vincu_docente, tipo_vinculacion_docente, facultad_docente, proyecto_curricular_docente, ultimo_nivel_educativo, genero, se_reconoce_como, orientacion_sexual, discapacidad, tipo_discapacidad, etnia, tipo_etnia, conflicto_armado, migrante, edad) 
    VALUES ('$nombre', '$tipo_id', '$numero_id', '$lugar_exp_id', '$email', '$cel', '$tipo_contratista', '$vincu_estudiante', '$facultad_estudiante', '$proyecto_curricular_estudiante', '$vincu_egresado', '$facultad_egresado', '$proyecto_curricular_egresado', '$vincu_contratista', '$tipo_vin_contratista', '$vinculacion_contratista_dependencia_proyecto', '$numero_contrato', '$ano_contrato', '$pensionado', '$vincu_docente', '$tipo_vinculacion_docente', '$facultad_docente', '$proyecto_curricular_docente', '$ultimo_nivel_educativo', '$genero', '$se_reconoce_como', '$orientacion_sexual', '$discapacidad', '$tipo_discapacidad', '$etnia', '$tipo_etnia', '$conflicto_armado', '$migrante', '$edad')";


  
  $sql = mysqli_query($conexion, $sql_query);
  
  
// Llamar a la función para generar el certificado PDF
$nombre_archivo = $numero_id ."_certificado.pdf";
//generarCertificadoPDF($nombre, $numero_id, $vincu_contratista, $vincu_definitivo, $fechaExpedicion, $horaExpedicion, $tipo_id, $nombre_archivo);

// Función para generar el certificado en formato PDF
function generarCertificadoPDF($nombre, $numero_id, $vincu_contratista, $vincu_definitivo, $fechaExpedicion, $horaExpedicion, $tipo_id, $nombre_archivo)
{
    // Crear instancia de PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetMargins(20, 40, 20); // Establecer márgenes (izquierda, arriba, derecha)
    $pdf->SetFont('Times', '', 12); // Usar fuente Times New Roman

    // Cabecera con imagen centrada
    $pdf->Image('image.png', 20, 12, 80, 30); // Tamaño de la imagen: 95px de ancho x 33px de alto, centrada horizontalmente

    // Encabezado
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Ln(5); // Salto de línea
    $pdf->Cell(0, 30, '', 0, 1); // Margen superior
    $pdf->Cell(0, 10, utf8_decode('CERTIFICADO DE REGISTRO DE VINCULACIÓN Y CARACTERIZACIÓN PERSONAL'), 0, 1, 'C');
    $pdf->Ln(10); // Salto de línea

    // Contenido
    $pdf->SetFont('Times', '', 12);
    $pdf->MultiCell(0, 6, utf8_decode("La Oficina de Extensión - IDEXUD certifica que $nombre identificado con $tipo_id, número $numero_id completó el registro de vinculación y caracterización personal, del FORMATO DE VINCULACIÓN Y CARACTERIZACIÓN Código: GC-FR-043, declarando la siguiente información:"), 0, 'J', false); // Alineación justificada
    $pdf->Ln(8); // Salto de línea

    // Es contratista
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 6, utf8_decode("Es usted contratista actualmente de la Universidad Distrital Francisco José de Caldas:"));
    $pdf->Ln(8); // Salto de línea
    $pdf->SetFont('Times', '', 12);
    $pdf->Write(5, utf8_decode($vincu_contratista));
    $pdf->Ln(8); // Salto de línea

    // Tipo de vinculación
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 6, utf8_decode("Tipo de vinculación actual:"));
    $pdf->Ln(8); // Salto de línea
    $pdf->SetFont('Times', '', 12);
    $pdf->Write(5, utf8_decode($vincu_definitivo));
    $pdf->Ln(10); // Salto de línea

    // Manifiesto
    $pdf->SetFont('Times', '', 12);
    $pdf->MultiCell(0, 6, utf8_decode("Yo, $nombre manifiesto bajo la gravedad de juramento, que la información registrada es verídica y que SI ___ NO ___ me encuentro dentro de las causales de inhabilidad e incompatibilidad bajo los lineamientos establecidos del estatuto de contratación y demás normas establecidas por la Universidad Distrital Francisco José de Caldas que lo desarrollen."), 0, 'J', false); // Alineación justificada
    $pdf->Ln(8); // Salto de línea

    // Fecha y hora de expedición
    $pdf->SetFont('Times', 'B', 12);
    $pdf->MultiCell(0, 5, utf8_decode("Este documento se expide el $fechaExpedicion a las $horaExpedicion y tendrá validez por un período de 30 días posterior a la fecha de expedición."), 0, 'J', false); // Alineación justificada
    $pdf->Ln(12); // Salto de línea

    // Firma
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Ln(18);
    $textoFirma = utf8_decode("Firme aquí");
    $anchoFirma = $pdf->GetStringWidth($textoFirma);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 6, $textoFirma, 0, 0, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('Times', '', 12);
    $pdf->Cell(0, 6, utf8_decode("__________________________"), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 6, utf8_decode($nombre), 0, 1, 'C');
    $pdf->Cell(0, 6, utf8_decode("$tipo_id : $numero_id"), 0, 1, 'C');

    // Salida del PDF
    $pdf->Output('F', $nombre_archivo);
}

// Llamar a la función para generar el certificado PDF
generarCertificadoPDF($nombre, $numero_id, $vincu_contratista, $vincu_definitivo, $fechaExpedicion, $horaExpedicion, $tipo_id, $nombre_archivo);

    // Descargar el archivo
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    readfile($nombre_archivo);


?>